<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\PaymentEntry;
use App\Models\ReceivingAccount;
use Illuminate\Validation\Rule;
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
        $this->ensureEditReceivingAccount();
        $this->successMessage = null;
        $this->errorMessage = null;
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
        $this->errorMessage = null;
    }

    public function getEditBalanceProperty(): float
    {
        return max(0, (float) $this->edit_amount_agreed - (float) $this->edit_amount_paid);
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
            'edit_amount_agreed.required' => 'Please enter the amount agreed.',
            'edit_amount_agreed.min' => 'Amount agreed cannot be negative.',
            'edit_amount_paid.required' => 'Please enter the amount paid.',
            'edit_amount_paid.min' => 'Amount paid cannot be negative.',
            'edit_amount_paid.lte' => 'Amount paid cannot be more than amount agreed.',
            'edit_receiving_account_id.required' => 'Please select a payment account.',
            'edit_receiving_account_id.exists' => 'The selected payment account is not valid.',
        ]);

        try {
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
