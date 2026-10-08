<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Desk;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Admin: add office desks any time (Umrah, Ticketing, etc.).
 * Keep this file simple — add / list / turn off.
 */
class ManageDesks extends Component
{
    public string $name = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function addDesk(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin or super admin can manage desks.';

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $name = trim($this->name);
        $key = Str::slug($name);

        if ($key === '') {
            $this->errorMessage = 'Please enter a valid desk name.';

            return;
        }

        // Same key already exists?
        $exists = Desk::query()->where('key', $key)->exists();

        if ($exists) {
            $this->errorMessage = 'A desk with this name already exists.';

            return;
        }

        Desk::create([
            'name' => $name,
            'key' => $key,
            'is_active' => true,
        ]);

        $this->name = '';
        $this->successMessage = 'Desk added.';
        $this->js('window.dispatchEvent(new CustomEvent("staff-list-changed"))');
    }

    public function turnOff(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin or super admin can manage desks.';

            return;
        }

        $desk = Desk::query()->find($id);

        if (! $desk) {
            $this->errorMessage = 'Desk not found.';

            return;
        }

        $desk->is_active = false;
        $desk->save();

        $this->successMessage = 'Desk turned off. It will not show in new forms.';
        $this->js('window.dispatchEvent(new CustomEvent("staff-list-changed"))');
    }

    public function turnOn(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin or super admin can manage desks.';

            return;
        }

        $desk = Desk::query()->find($id);

        if (! $desk) {
            $this->errorMessage = 'Desk not found.';

            return;
        }

        $desk->is_active = true;
        $desk->save();

        $this->successMessage = 'Desk turned on again.';
        $this->js('window.dispatchEvent(new CustomEvent("staff-list-changed"))');
    }

    public function render()
    {
        if (! auth()->user()?->isAdmin()) {
            return view('livewire.admin.settings.manage-desks', [
                'denied' => true,
                'desks' => collect(),
            ]);
        }

        return view('livewire.admin.settings.manage-desks', [
            'denied' => false,
            'desks' => Desk::query()->orderBy('name')->get(),
        ]);
    }
}
