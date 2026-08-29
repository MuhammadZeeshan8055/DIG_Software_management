<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\ReceivingAccount;
use Livewire\Component;

class ManageReceivingAccounts extends Component
{
    public string $method = 'BANK';

    public string $name = '';

    public ?string $successMessage = null;

    public function addAccount(): void
    {
        $this->validate([
            'method' => ['required', 'in:'.implode(',', array_keys(config('payment_accounts.options', [])))],
            'name' => ['required', 'string', 'max:120'],
        ]);

        ReceivingAccount::create([
            'method' => $this->method,
            'name' => trim($this->name),
            'is_active' => true,
        ]);

        $this->name = '';
        $this->successMessage = 'Account added.';
    }

    public function deleteAccount(int $id): void
    {
        ReceivingAccount::where('id', $id)->update(['is_active' => false]);
        $this->successMessage = 'Account removed.';
    }

    public function render()
    {
        return view('livewire.admin.accounts.manage-receiving-accounts', [
            'methods' => config('payment_accounts.options', []),
            'accounts' => ReceivingAccount::query()->active()->orderBy('name')->get(),
        ]);
    }
}
