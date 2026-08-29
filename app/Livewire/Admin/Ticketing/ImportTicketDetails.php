<?php

namespace App\Livewire\Admin\Ticketing;

use App\Http\Requests\ConfirmTicketImportRequest;
use App\Models\PaymentEntry;
use App\Models\ReceivingAccount;
use App\Models\TicketImport;
use App\Services\TicketPdfParser;
use Illuminate\Validation\Rule;
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

    public function mount(): void
    {
        $this->payment_status = config('payment_status.default', 'PENDING');
        $this->payment_method = config('payment_accounts.default', 'BANK');
        $this->selectFirstAccount();
        $this->resetFormFields();
    }

    public function updatedPaymentMethod(): void
    {
        $account = ReceivingAccount::find($this->receiving_account_id);

        if (! $account || $account->method !== $this->payment_method || ! $account->is_active) {
            $this->selectFirstAccount();
        }
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
            'payment_method' => ['required', 'in:'.implode(',', array_keys(config('payment_accounts.options', [])))],
            'receiving_account_id' => [
                'required',
                Rule::exists('receiving_accounts', 'id')->where(function ($query) {
                    $query->where('method', $this->payment_method)->where('is_active', true);
                }),
            ],
        ], [
            'amount_agreed.required' => 'Please enter the amount agreed.',
            'amount_agreed.min' => 'Amount agreed cannot be negative.',
            'amount_paid.required' => 'Please enter the amount paid.',
            'amount_paid.min' => 'Amount paid cannot be negative.',
            'amount_paid.lte' => 'Amount paid cannot be more than amount agreed.',
            'receiving_account_id.required' => 'Please select a payment account.',
            'receiving_account_id.exists' => 'The selected payment account is not valid.',
        ]);

        try {
            $account = ReceivingAccount::find($this->receiving_account_id);
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

            $agreed = $this->amountAgreedValue();
            $paid = $this->amountPaidValue();

            PaymentEntry::create([
                'ticket_import_id' => $ticket->id,
                'user_id' => auth()->id(),
                'passenger_name' => $validated['passenger_name'],
                'booking_reference' => $validated['booking_reference'] ?? null,
                'amount_agreed' => $agreed,
                'amount_paid' => $paid,
                'balance' => max(0, $agreed - $paid),
                'payment_status' => $this->payment_status,
                'receiving_account_id' => $account?->id,
                'received_in' => $account?->method,
                'received_account' => $account?->name,
            ]);

            $statusLabel = config('payment_status.options.'.$this->payment_status, $this->payment_status);
            $currency = config('payment_status.currency', 'PKR');
            $balance = $this->balance;

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
            'allReceivingAccounts' => ReceivingAccount::query()->active()->orderBy('name')->get()->map(fn ($account) => [
                'id' => $account->id,
                'method' => $account->method,
                'name' => $account->name,
                'type' => $account->methodLabel(),
            ])->values(),
            'currency' => config('payment_status.currency', 'PKR'),
            'paymentEntries' => PaymentEntry::query()->with('receivingAccount')->latest()->limit(15)->get(),
            'ledgerTotals' => PaymentEntry::totals(),
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

    private function selectFirstAccount(): void
    {
        $account = ReceivingAccount::query()
            ->active()
            ->where('method', $this->payment_method)
            ->orderBy('name')
            ->first();

        $this->receiving_account_id = $account?->id;
    }
}
