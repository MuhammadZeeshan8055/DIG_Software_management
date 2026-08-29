<?php

namespace App\Livewire\Admin\Ticketing;

use App\Http\Requests\ConfirmTicketImportRequest;
use App\Models\TicketImport;
use App\Services\TicketPdfParser;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportTicketDetails extends Component
{
    use WithFileUploads;

    public $pdfFile;

    /** @var array<string, string> */
    public array $form = [];

    /** @var array<int, array<string, string>> */
    public array $flightSegments = [];

    public string $raw_pdf_text = '';

    public bool $parsed = false;

    public ?string $successMessage = null;

    public ?string $parseError = null;

    public string $payment_status = 'PENDING';

    public string $amount_agreed = '';

    public string $amount_paid = '';

    public function mount(): void
    {
        $this->payment_status = config('payment_status.default', 'PENDING');
        $this->resetFormFields();
    }

    public function updatedPdfFile(): void
    {
        $this->resetValidation();
        $this->successMessage = null;
        $this->parseError = null;
        $this->parsed = false;

        $this->validate([
            'pdfFile' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('ticket_import.max_pdf_size_kb', 5120)],
        ]);

        $this->parseUploadedPdf();
    }

    public function parseUploadedPdf(): void
    {
        if (! $this->pdfFile) {
            return;
        }

        try {
            $parser = app(TicketPdfParser::class);
            $path = $this->pdfFile->getRealPath();
            $text = $parser->extractText($path);
            $parsed = $parser->parse($text);

            $this->raw_pdf_text = $text;
            $this->form = array_merge($this->emptyFormFields(), $parsed['fields']);
            $this->flightSegments = $parsed['flight_segments'] ?: [app(TicketPdfParser::class)->emptySegment()];

            $this->parseError = $text === ''
                ? 'No readable text found in this PDF. Fill the fields manually below.'
                : null;
            $this->parsed = true;
        } catch (\Throwable $exception) {
            $this->parseError = 'Could not read this PDF. Fill the fields manually below.';
            $this->flightSegments = [app(TicketPdfParser::class)->emptySegment()];
            $this->parsed = true;
            report($exception);
        }
    }

    public function addFlightSegment(): void
    {
        $this->flightSegments[] = app(TicketPdfParser::class)->emptySegment();
    }

    public function removeFlightSegment(int $index): void
    {
        if (count($this->flightSegments) <= 1) {
            return;
        }

        unset($this->flightSegments[$index]);
        $this->flightSegments = array_values($this->flightSegments);
    }

    public function updatedAmountAgreed(): void
    {
        $this->syncPaymentStatusFromAmounts();
    }

    public function updatedAmountPaid(): void
    {
        $this->syncPaymentStatusFromAmounts();
    }

    public function getBalanceProperty(): float
    {
        return max(0, $this->amountAgreedValue() - $this->amountPaidValue());
    }

    public function confirm(): void
    {
        $payload = [
            ...$this->form,
            'flight_segments' => $this->flightSegments,
        ];

        $validated = validator(
            $payload,
            (new ConfirmTicketImportRequest)->rules(),
            [],
            $this->fieldLabels()
        )->validate();

        $this->validate([
            'amount_agreed' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0', 'lte:amount_agreed'],
            'payment_status' => ['required', 'in:'.implode(',', array_keys(config('payment_status.options', [])))],
        ], [
            'amount_paid.lte' => 'Amount paid cannot be more than amount agreed.',
        ]);

        if (! $this->pdfFile) {
            $this->addError('pdfFile', 'Please upload a PDF first.');

            return;
        }

        $storedPath = $this->pdfFile->store('ticket-imports', 'local');

        TicketImport::create([
            'user_id' => auth()->id(),
            'original_filename' => $this->pdfFile->getClientOriginalName(),
            'pdf_path' => $storedPath,
            'raw_pdf_text' => $this->raw_pdf_text,
            ...$validated,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->saveDemoPaymentRecord($validated);

        $statusLabel = config('payment_status.options.'.$this->payment_status, $this->payment_status);
        $currency = config('payment_status.currency', 'PKR');
        $balance = $this->balance;

        $this->resetForm();
        $this->successMessage = "Ticket saved. {$statusLabel} — Balance: {$currency} ".number_format($balance, 0);
    }

    public function resetForm(): void
    {
        $this->reset([
            'pdfFile',
            'raw_pdf_text',
            'parsed',
            'parseError',
            'flightSegments',
            'amount_agreed',
            'amount_paid',
        ]);

        $this->payment_status = config('payment_status.default', 'PENDING');
        $this->resetFormFields();
    }

    public function render()
    {
        return view('livewire.admin.ticketing.import-ticket-details', [
            'formFields' => config('ticket_import.form_fields', []),
            'segmentFields' => config('ticket_import.flight_segment_fields', []),
            'paymentStatuses' => config('payment_status.options', []),
            'currency' => config('payment_status.currency', 'PKR'),
            'demoPayments' => session('demo_ticket_payments', []),
            'ledgerTotals' => $this->ledgerTotals(session('demo_ticket_payments', [])),
            'recentImports' => TicketImport::query()
                ->with('user')
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }

    /** @return array<string, string> */
    private function emptyFormFields(): array
    {
        $fields = [];

        foreach (array_keys(config('ticket_import.form_fields', [])) as $key) {
            $fields[$key] = '';
        }

        return $fields;
    }

    private function resetFormFields(): void
    {
        $this->form = $this->emptyFormFields();
        $this->flightSegments = [];
    }

    /** @param array<string, mixed> $validated */
    private function saveDemoPaymentRecord(array $validated): void
    {
        $agreed = $this->amountAgreedValue();
        $paid = $this->amountPaidValue();

        session()->push('demo_ticket_payments', [
            'passenger' => $validated['passenger_name'] ?? '—',
            'booking_reference' => $validated['booking_reference'] ?? '—',
            'amount_agreed' => $agreed,
            'amount_paid' => $paid,
            'balance' => max(0, $agreed - $paid),
            'payment_status' => $this->payment_status,
            'saved_at' => now()->toDateTimeString(),
        ]);
    }

    private function syncPaymentStatusFromAmounts(): void
    {
        $agreed = $this->amountAgreedValue();

        if ($agreed <= 0) {
            return;
        }

        $paid = $this->amountPaidValue();

        if ($paid >= $agreed) {
            $this->payment_status = 'PAID';
        } elseif ($paid <= 0) {
            $this->payment_status = 'PENDING';
        } else {
            $this->payment_status = 'HALF_RECEIVE';
        }
    }

    private function amountAgreedValue(): float
    {
        return (float) $this->amount_agreed;
    }

    private function amountPaidValue(): float
    {
        return (float) $this->amount_paid;
    }

    /** @param array<int, array<string, mixed>> $records */
    private function ledgerTotals(array $records): array
    {
        $agreed = 0.0;
        $paid = 0.0;
        $balance = 0.0;

        foreach ($records as $record) {
            $agreed += (float) ($record['amount_agreed'] ?? 0);
            $paid += (float) ($record['amount_paid'] ?? 0);
            $balance += (float) ($record['balance'] ?? 0);
        }

        return [
            'agreed' => $agreed,
            'paid' => $paid,
            'balance' => $balance,
        ];
    }

    /** @return array<string, string> */
    private function fieldLabels(): array
    {
        $labels = [];

        foreach (config('ticket_import.form_fields', []) as $key => $field) {
            $labels[$key] = $field['label'] ?? $key;
        }

        $labels['flight_segments'] = 'Flight segments';

        return $labels;
    }
}
