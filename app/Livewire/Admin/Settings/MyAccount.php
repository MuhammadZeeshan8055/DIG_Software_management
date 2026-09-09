<?php

namespace App\Livewire\Admin\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Logged-in user updates their own email / password
 * (after admin created a dummy login for them).
 */
class MyAccount extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $successMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
    }

    public function save(): void
    {
        $this->successMessage = null;
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];

        $this->validate($rules);

        $user->name = trim($this->name);
        $user->email = strtolower(trim($this->email));

        if ($this->password !== '') {
            $user->password = $this->password; // cast hashes it
        }

        $user->save();

        // Clear password fields after save
        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';

        $this->successMessage = 'Account updated. Use your new email/password next time you sign in.';
    }

    public function render()
    {
        return view('livewire.admin.settings.my-account');
    }
}
