<?php

namespace App\Livewire\Admin\Accounts;

use App\Models\ReceivingAccount;
use Livewire\Component;

class ManageReceivingAccounts extends Component
{
    public string $method = 'BANK';

    public string $name = '';

    public ?string $successMessage = null;

    public function mount(): void
    {
        // No abort here — this component is always on the dashboard HTML.
    }

    public function addAccount(): void
    {
        abort_unless(auth()->user()->canManage('accounts', 'bank-accounts'), 403);

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
        abort_unless(auth()->user()->canManage('accounts', 'bank-accounts'), 403);

        ReceivingAccount::where('id', $id)->update(['is_active' => false]);
        $this->successMessage = 'Account removed.';
    }

    public function render()
    {
        if (! auth()->user()->canView('accounts', 'bank-accounts')) {
            return view('livewire.admin.accounts.manage-receiving-accounts', [
                'methods' => [],
                'accounts' => collect(),
            ]);
        }

        return view('livewire.admin.accounts.manage-receiving-accounts', [
            'methods' => config('payment_accounts.options', []),
            'accounts' => ReceivingAccount::query()->active()->orderBy('name')->get(),
        ]);
    }
}
