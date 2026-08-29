<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\PaymentEntry;
use App\Models\ReceivingAccount;
use App\Support\PaymentLedgerRules;
use Livewire\Component;

class PaymentEntries extends Component
{
    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?int $editingId = null;

    public string $edit_amount_agreed = '';

    public string $edit_amount_paid = '';

    public string $edit_payment_status = 'PENDING';

    public string $edit_payment_method = 'BANK';

    public ?int $edit_receiving_account_id = null;

    public function startEdit(int $id): void
    {
        $entry = PaymentEntry::with('receivingAccount')->find($id);

        if (! $entry) {
            return;
        }

        $this->editingId = $id;
        $this->edit_amount_agreed = (string) $entry->amount_agreed;
        $this->edit_amount_paid = (string) $entry->amount_paid;
        $this->edit_payment_status = $entry->payment_status;
        $this->edit_payment_method = $entry->receivingAccount?->method ?? $entry->received_in ?? config('payment_accounts.default', 'BANK');
        $this->edit_receiving_account_id = $entry->receiving_account_id;
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->edit_amount_agreed = '';
        $this->edit_amount_paid = '';
        $this->edit_payment_status = config('payment_status.default', 'PENDING');
        $this->edit_payment_method = config('payment_accounts.default', 'BANK');
        $this->edit_receiving_account_id = null;
        $this->errorMessage = null;
    }

    public function saveEditWithLedger(
        string $amountAgreed,
        string $amountPaid,
        string $paymentStatus,
        string $paymentMethod,
        int|string|null $receivingAccountId = null,
    ): void {
        $this->edit_amount_agreed = $amountAgreed;
        $this->edit_amount_paid = $amountPaid === '' ? '0' : $amountPaid;
        $this->edit_payment_status = $paymentStatus;
        $this->edit_payment_method = $paymentMethod;
        $this->edit_receiving_account_id = $receivingAccountId !== null && $receivingAccountId !== ''
            ? (int) $receivingAccountId
            : null;

        $this->saveEdit();
    }

    public function saveEdit(): void
    {
        if (! $this->editingId) {
            return;
        }

        $this->errorMessage = null;

        $this->validate(
            PaymentLedgerRules::rules('edit_'),
            PaymentLedgerRules::messages('edit_')
        );

        try {
            $entry = PaymentEntry::find($this->editingId);

            if (! $entry) {
                return;
            }

            $entry->update([
                'amount_agreed' => (float) $this->edit_amount_agreed,
                'amount_paid' => (float) $this->edit_amount_paid,
                'payment_status' => $this->edit_payment_status,
                'receiving_account_id' => $this->edit_receiving_account_id,
            ]);

            $this->cancelEdit();
            $this->successMessage = 'Payment updated.';
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Could not save payment. Please check all fields and try again.';
        }
    }

    public function deleteEntry(int $id): void
    {
        if ($this->editingId === $id) {
            $this->cancelEdit();
        }

        $entry = PaymentEntry::find($id);

        if ($entry && $entry->delete()) {
            $this->successMessage = 'Payment entry deleted.';
        }
    }

    public function render()
    {
        return view('livewire.admin.accounts.payment-entries', [
            'entries' => PaymentEntry::query()->with('receivingAccount')->latest()->limit(50)->get(),
            'ledgerTotals' => PaymentEntry::totals(),
            'paymentStatuses' => config('payment_status.options', []),
            'paymentMethods' => config('payment_accounts.options', []),
            'allReceivingAccounts' => ReceivingAccount::pickerOptions(),
            'currency' => config('payment_status.currency', 'PKR'),
        ]);
    }
}
