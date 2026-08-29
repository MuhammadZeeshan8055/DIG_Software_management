<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\PaymentEntry;
use Livewire\Component;

class PaymentEntries extends Component
{
    public ?string $successMessage = null;

    public ?int $editingId = null;

    public string $edit_amount_agreed = '';

    public string $edit_amount_paid = '';

    public string $edit_payment_status = 'PENDING';

    public function startEdit(int $id): void
    {
        $entry = PaymentEntry::find($id);

        if (! $entry) {
            return;
        }

        $this->editingId = $id;
        $this->edit_amount_agreed = (string) $entry->amount_agreed;
        $this->edit_amount_paid = (string) $entry->amount_paid;
        $this->edit_payment_status = $entry->payment_status;
        $this->successMessage = null;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->edit_amount_agreed = '';
        $this->edit_amount_paid = '';
        $this->edit_payment_status = config('payment_status.default', 'PENDING');
    }

    public function updatedEditAmountAgreed(): void
    {
        $this->syncEditPaymentStatus();
    }

    public function updatedEditAmountPaid(): void
    {
        $this->syncEditPaymentStatus();
    }

    public function getEditBalanceProperty(): float
    {
        return max(0, (float) $this->edit_amount_agreed - (float) $this->edit_amount_paid);
    }

    public function saveEdit(): void
    {
        if (! $this->editingId) {
            return;
        }

        $this->validate([
            'edit_amount_agreed' => ['required', 'numeric', 'min:0'],
            'edit_amount_paid' => ['required', 'numeric', 'min:0', 'lte:edit_amount_agreed'],
            'edit_payment_status' => ['required', 'in:'.implode(',', array_keys(config('payment_status.options', [])))],
        ], [
            'edit_amount_paid.lte' => 'Amount paid cannot be more than amount agreed.',
        ]);

        $agreed = (float) $this->edit_amount_agreed;
        $paid = (float) $this->edit_amount_paid;

        PaymentEntry::where('id', $this->editingId)->update([
            'amount_agreed' => $agreed,
            'amount_paid' => $paid,
            'balance' => max(0, $agreed - $paid),
            'payment_status' => $this->edit_payment_status,
        ]);

        $this->cancelEdit();
        $this->successMessage = 'Payment updated.';
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
            'entries' => PaymentEntry::query()->latest()->limit(50)->get(),
            'ledgerTotals' => PaymentEntry::totals(),
            'paymentStatuses' => config('payment_status.options', []),
            'currency' => config('payment_status.currency', 'PKR'),
        ]);
    }

    private function syncEditPaymentStatus(): void
    {
        $agreed = (float) $this->edit_amount_agreed;

        if ($agreed <= 0) {
            return;
        }

        $paid = (float) $this->edit_amount_paid;

        if ($paid >= $agreed) {
            $this->edit_payment_status = 'PAID';
        } elseif ($paid <= 0) {
            $this->edit_payment_status = 'PENDING';
        } else {
            $this->edit_payment_status = 'HALF_RECEIVE';
        }
    }
}
