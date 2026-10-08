<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Desk;
use Illuminate\Support\Str;
use Livewire\Component;

/** Admin: add / turn on / turn off desks. */
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

        if ($key === '' || Desk::where('key', $key)->exists()) {
            $this->errorMessage = $key === ''
                ? 'Please enter a valid desk name.'
                : 'A desk with this name already exists.';

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
        $this->setActive($id, false, 'Desk turned off.');
    }

    public function turnOn(int $id): void
    {
        $this->setActive($id, true, 'Desk turned on again.');
    }

    protected function setActive(int $id, bool $active, string $okMessage): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin or super admin can manage desks.';

            return;
        }

        $desk = Desk::find($id);

        if (! $desk) {
            $this->errorMessage = 'Desk not found.';

            return;
        }

        $desk->is_active = $active;
        $desk->save();

        $this->successMessage = $okMessage;
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
            'desks' => Desk::orderBy('name')->get(),
        ]);
    }
}
