<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\PaymentEntry;
use Livewire\Component;

class PaymentEntries extends Component
{
    public ?string $successMessage = null;

    public function deleteEntry(int $id): void
    {
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
        ]);
    }
}
