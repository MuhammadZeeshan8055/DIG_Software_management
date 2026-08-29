<?php

namespace App\Livewire\Admin\Ticketing;

use App\Http\Requests\ConfirmTicketImportRequest;
use App\Models\PaymentEntry;
use App\Models\ReceivingAccount;
use App\Models\TicketImport;
use App\Services\TicketPdfParser;
use App\Support\PaymentLedgerRules;
use Illuminate\Support\Facades\DB;
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

    public ?string $errorMessage = null;

    public ?string $parseError = null;

    public string $payment_status = 'PENDING';

    public string $amount_agreed = '';

    public string $amount_paid = '0';

    public string $payment_method = 'BANK';

    public ?int $receiving_account_id = null;

    public ?int $viewingImportId = null;

    protected TicketPdfParser $parser;

    public function boot(TicketPdfParser $parser): void
    {
        $this->parser = $parser;
    }

    public function mount(): void
    {
        $this->payment_status = config('payment_status.default', 'PENDING');
        $this->payment_method = config('payment_accounts.default', 'BANK');
        $this->selectFirstAccount();
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
            $path = $this->pdfFile->getRealPath();
            $text = $this->parser->extractText($path);
            $parsed = $this->parser->parse($text);

            $this->raw_pdf_text = $text;
            $this->form = array_merge($this->emptyFormFields(), $parsed['fields']);
            $this->flightSegments = $parsed['flight_segments'] ?: [$this->parser->emptySegment()];

            $this->parseError = $text === ''
                ? 'No readable text found in this PDF. Fill the fields manually below.'
                : null;
            $this->parsed = true;
        } catch (\Throwable $exception) {
            $this->parseError = 'Could not read this PDF. Fill the fields manually below.';
            $this->flightSegments = [$this->parser->emptySegment()];
            $this->parsed = true;
            report($exception);
        }
    }

    public function addFlightSegment(): void
    {
        $this->flightSegments[] = $this->parser->emptySegment();
    }

    public function removeFlightSegment(int $index): void
    {
        if (count($this->flightSegments) <= 1) {
            return;
        }

        unset($this->flightSegments[$index]);
        $this->flightSegments = array_values($this->flightSegments);
    }

    public function viewImport(int $id): void
    {
        $this->viewingImportId = $id;
    }

    public function closeView(): void
    {
        $this->viewingImportId = null;
    }

    public function confirmWithLedger(
        string $amountAgreed,
        string $amountPaid,
        string $paymentStatus,
        string $paymentMethod,
        int|string|null $receivingAccountId = null,
    ): void {
        $this->amount_agreed = $amountAgreed;
        $this->amount_paid = $amountPaid === '' ? '0' : $amountPaid;
        $this->payment_status = $paymentStatus;
        $this->payment_method = $paymentMethod;
        $this->receiving_account_id = $receivingAccountId !== null && $receivingAccountId !== ''
            ? (int) $receivingAccountId
            : null;

        $this->confirm();
    }

    public function confirm(): void
    {
        $this->errorMessage = null;

        if (! $this->pdfFile) {
            $this->addError('pdfFile', 'Please upload a PDF first.');

            return;
        }

        $validated = validator(
            [...$this->form, 'flight_segments' => $this->flightSegments],
            (new ConfirmTicketImportRequest)->rules(),
            [],
            $this->fieldLabels()
        )->validate();

        $this->validate(
            PaymentLedgerRules::rules(),
            PaymentLedgerRules::messages()
        );

        try {
            $agreed = (float) $this->amount_agreed;
            $paid = (float) $this->amount_paid;
            $balance = PaymentEntry::computeBalance($agreed, $paid);

            DB::transaction(function () use ($validated, $agreed, $paid) {
                $storedPath = $this->pdfFile->store('ticket-imports', 'local');

                $ticket = TicketImport::create([
                    'user_id' => auth()->id(),
                    'original_filename' => $this->pdfFile->getClientOriginalName(),
                    'pdf_path' => $storedPath,
                    'raw_pdf_text' => $this->raw_pdf_text,
                    ...$validated,
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                PaymentEntry::create([
                    'ticket_import_id' => $ticket->id,
                    'user_id' => auth()->id(),
                    'passenger_name' => $validated['passenger_name'],
                    'booking_reference' => $validated['booking_reference'] ?? null,
                    'amount_agreed' => $agreed,
                    'amount_paid' => $paid,
                    'payment_status' => $this->payment_status,
                    'receiving_account_id' => $this->receiving_account_id,
                ]);
            });

            $statusLabel = config('payment_status.options.'.$this->payment_status, $this->payment_status);
            $currency = config('payment_status.currency', 'PKR');

            $this->resetForm();
            $this->successMessage = "Ticket saved. {$statusLabel} — Balance: {$currency} ".number_format($balance, 0);
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Could not save ticket. Please check all fields and try again.';
        }
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
            'receiving_account_id',
        ]);

        $this->payment_status = config('payment_status.default', 'PENDING');
        $this->payment_method = config('payment_accounts.default', 'BANK');
        $this->amount_paid = '0';
        $this->selectFirstAccount();
        $this->resetFormFields();
    }

    public function render()
    {
        return view('livewire.admin.ticketing.import-ticket-details', [
            'formFields' => config('ticket_import.form_fields', []),
            'segmentFields' => config('ticket_import.flight_segment_fields', []),
            'paymentStatuses' => config('payment_status.options', []),
            'paymentMethods' => config('payment_accounts.options', []),
            'allReceivingAccounts' => ReceivingAccount::pickerOptions(),
            'currency' => config('payment_status.currency', 'PKR'),
            'paymentEntries' => PaymentEntry::query()->with('receivingAccount')->latest()->limit(15)->get(),
            'ledgerTotals' => PaymentEntry::totals(),
            'recentImports' => TicketImport::query()
                ->with('user')
                ->latest()
                ->limit(15)
                ->get(),
            'viewingImport' => $this->viewingImportId
                ? TicketImport::query()->find($this->viewingImportId)
                : null,
        ]);
    }

    /** @return array<string, string> */
    private function emptyFormFields(): array
    {
        return array_fill_keys(array_keys(config('ticket_import.form_fields', [])), '');
    }

    private function resetFormFields(): void
    {
        $this->form = $this->emptyFormFields();
        $this->flightSegments = [];
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

    private function selectFirstAccount(): void
    {
        $this->receiving_account_id = ReceivingAccount::query()
            ->active()
            ->where('method', $this->payment_method)
            ->orderBy('name')
            ->value('id');
    }
}
