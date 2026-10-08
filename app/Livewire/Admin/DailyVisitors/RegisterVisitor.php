<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\DailyVisitor;
use App\Models\Desk;
use App\Models\User;
use Livewire\Component;

/**
 * Reception: register a walk-in visitor and pick who they should meet.
 * Keep code plain and easy to read.
 */
class RegisterVisitor extends Component
{
    public string $name = '';

    public string $contact_no = '';

    public string $purpose = '';

    public string $desk_id = '';

    public string $assigned_to = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    /** When desk changes, clear the selected person. */
    public function updatedDeskId(): void
    {
        $this->assigned_to = '';
    }

    public function save(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $user = auth()->user();

        if (! $user || ! $user->canView('daily-visitors', 'register')) {
            $this->errorMessage = 'You do not have access to register visitors.';

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_no' => ['required', 'string', 'max:40'],
            'purpose' => ['required', 'string', 'max:255'],
            'desk_id' => ['required', 'integer', 'exists:desks,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        DailyVisitor::create([
            'name' => trim($this->name),
            'contact_no' => trim($this->contact_no),
            'purpose' => trim($this->purpose),
            'desk_id' => (int) $this->desk_id,
            'assigned_to' => (int) $this->assigned_to,
            'created_by' => $user->id,
            'status' => 'waiting',
            'remarks' => null,
        ]);

        // Clear form for next visitor
        $this->name = '';
        $this->contact_no = '';
        $this->purpose = '';
        $this->desk_id = '';
        $this->assigned_to = '';

        $this->successMessage = 'Visitor registered. They are waiting for the assigned person.';
    }

    public function render()
    {
        $user = auth()->user();

        if (! $user || ! $user->canView('daily-visitors', 'register')) {
            return view('livewire.admin.daily-visitors.register-visitor', [
                'denied' => true,
                'desks' => collect(),
                'staffList' => collect(),
                'todayVisitors' => collect(),
            ]);
        }

        // Active desks for the dropdown
        $desks = Desk::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // People to meet — staff on the selected desk (or empty until desk picked)
        $staffList = collect();

        if ($this->desk_id !== '') {
            $staffList = User::query()
                ->where('role', 'staff')
                ->where('desk_id', (int) $this->desk_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        // Today's visitors (newest first)
        $today = now(app_timezone())->toDateString();

        $todayVisitors = DailyVisitor::query()
            ->with(['desk', 'assignedUser'])
            ->whereDate('created_at', $today)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('livewire.admin.daily-visitors.register-visitor', [
            'denied' => false,
            'desks' => $desks,
            'staffList' => $staffList,
            'todayVisitors' => $todayVisitors,
        ]);
    }
}
