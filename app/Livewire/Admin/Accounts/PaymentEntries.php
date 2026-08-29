<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\PaymentEntry;
use App\Models\ReceivingAccount;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PaymentEntries extends Component
{
    public ?string $successMessage = null;

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
        $this->ensureEditReceivingAccount();
        $this->successMessage = null;
    }

    public function updatedEditPaymentMethod(): void
    {
        $this->ensureEditReceivingAccount();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->edit_amount_agreed = '';
        $this->edit_amount_paid = '';
        $this->edit_payment_status = config('payment_status.default', 'PENDING');
        $this->edit_payment_method = config('payment_accounts.default', 'BANK');
        $this->edit_receiving_account_id = null;
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
            'edit_payment_method' => ['required', 'in:'.implode(',', array_keys(config('payment_accounts.options', [])))],
            'edit_receiving_account_id' => [
                'required',
                Rule::exists('receiving_accounts', 'id')->where(function ($query) {
                    $query->where('method', $this->edit_payment_method)->where('is_active', true);
                }),
            ],
        ], [
            'edit_amount_paid.lte' => 'Amount paid cannot be more than amount agreed.',
        ]);

        $account = ReceivingAccount::find($this->edit_receiving_account_id);
        $agreed = (float) $this->edit_amount_agreed;
        $paid = (float) $this->edit_amount_paid;

        PaymentEntry::where('id', $this->editingId)->update([
            'amount_agreed' => $agreed,
            'amount_paid' => $paid,
            'balance' => max(0, $agreed - $paid),
            'payment_status' => $this->edit_payment_status,
            'receiving_account_id' => $account?->id,
            'received_in' => $account?->method,
            'received_account' => $account?->name,
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
            'entries' => PaymentEntry::query()->with('receivingAccount')->latest()->limit(50)->get(),
            'ledgerTotals' => PaymentEntry::totals(),
            'paymentStatuses' => config('payment_status.options', []),
            'paymentMethods' => config('payment_accounts.options', []),
            'allReceivingAccounts' => ReceivingAccount::query()->active()->orderBy('name')->get()->map(fn ($account) => [
                'id' => $account->id,
                'method' => $account->method,
                'name' => $account->name,
                'type' => $account->methodLabel(),
            ])->values(),
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

    private function ensureEditReceivingAccount(): void
    {
        $validIds = ReceivingAccount::query()
            ->active()
            ->where('method', $this->edit_payment_method)
            ->pluck('id')
            ->all();

        if (! in_array((int) $this->edit_receiving_account_id, array_map('intval', $validIds), true)) {
            $this->edit_receiving_account_id = $validIds[0] ?? null;
        }
    }
}
