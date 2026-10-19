<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\DailyVisitor;
use App\Models\Desk;
use App\Models\User;
use App\Support\UserNotifier;
use Livewire\Component;

/** Reception: register visitor and mark sent. */
class RegisterVisitor extends Component
{
    public string $name = '';

    public string $contact_no = '';

    public string $purpose = '';

    public string $desk_id = '';

    public string $assigned_to = '';

    public string $filter_date = '';

    public string $filter_status = '';

    public string $filter_desk = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?int $editingVisitorId = null;

    public bool $showUpdateConfirm = false;

    public function mount(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
    }

    public function updatedDeskId(): void
    {
        $this->assigned_to = '';
    }

    public function resetFilters(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
        $this->filter_status = '';
        $this->filter_desk = '';
    }

    protected function canRegister(): bool
    {
        $user = auth()->user();

        return $user && $user->canView('daily-visitors', 'register');
    }

    protected function clearForm(): void
    {
        $this->name = '';
        $this->contact_no = '';
        $this->purpose = '';
        $this->desk_id = '';
        $this->assigned_to = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canRegister()) {
            $this->errorMessage = 'You do not have access to register visitors.';

            return;
        }

        if ($this->editingVisitorId) {
            $this->requestUpdate();

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_no' => ['required', 'string', 'max:40'],
            'purpose' => ['required', 'string', 'max:255'],
            'desk_id' => ['required', 'integer', 'exists:desks,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = auth()->user();

        $visitor = DailyVisitor::create([
            'name' => trim($this->name),
            'contact_no' => trim($this->contact_no),
            'purpose' => trim($this->purpose),
            'desk_id' => (int) $this->desk_id,
            'assigned_to' => (int) $this->assigned_to,
            'created_by' => $user->id,
            'status' => 'waiting',
            'remarks' => null,
        ]);

        $meetPerson = User::find((int) $this->assigned_to);

        if ($meetPerson) {
            UserNotifier::send(
                $meetPerson,
                'visitor_waiting',
                'Visitor waiting for you',
                $visitor->name.' ('.$visitor->contact_no.') — '.$visitor->purpose.'. Please reply: Send now or Please wait.',
                'my-visitors',
                'my-queue'
            );
        }

        $this->clearForm();
        $this->successMessage = 'Visitor registered. The assigned person was notified.';
    }

    public function markSent(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canRegister()) {
            $this->errorMessage = 'You do not have access to register visitors.';

            return;
        }

        $visitor = DailyVisitor::with('assignedUser')->find($id);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found.';

            return;
        }

        if ($visitor->status === 'completed') {
            $this->errorMessage = 'This visitor is already completed.';

            return;
        }

        $visitor->status = 'in_meeting';
        $visitor->save();

        if ($visitor->assignedUser && (int) $visitor->assignedUser->id !== (int) auth()->id()) {
            UserNotifier::send(
                $visitor->assignedUser,
                'visitor_sent',
                'Visitor sent to you',
                $visitor->name.' has been sent / is coming for the meeting.',
                'my-visitors',
                'my-queue'
            );
        }

        $this->successMessage = 'Marked as In meeting (sent).';
    }

    public function startEdit(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canRegister()) {
            $this->errorMessage = 'You do not have access to edit visitors.';

            return;
        }

        $visitor = DailyVisitor::find($id);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found.';

            return;
        }

        $this->editingVisitorId = $visitor->id;
        $this->name = $visitor->name;
        $this->contact_no = $visitor->contact_no;
        $this->purpose = $visitor->purpose;
        $this->desk_id = (string) $visitor->desk_id;
        $this->assigned_to = (string) $visitor->assigned_to;
        $this->resetValidation();

        $this->js(<<<'JS'
            requestAnimationFrame(() => {
                const el = document.getElementById('visitor-form-panel');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        JS);
    }

    public function cancelEdit(): void
    {
        $this->showUpdateConfirm = false;
        $this->editingVisitorId = null;
        $this->clearForm();
        $this->js('document.body.classList.remove("mu-modal-open")');
    }

    public function requestUpdate(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canRegister()) {
            $this->errorMessage = 'You do not have access to edit visitors.';

            return;
        }

        if (! $this->editingVisitorId) {
            $this->errorMessage = 'No visitor selected.';

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_no' => ['required', 'string', 'max:40'],
            'purpose' => ['required', 'string', 'max:255'],
            'desk_id' => ['required', 'integer', 'exists:desks,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $this->showUpdateConfirm = true;
        $this->js('document.body.classList.add("mu-modal-open")');
    }

    public function cancelUpdateConfirm(): void
    {
        $this->showUpdateConfirm = false;
        $this->js('document.body.classList.remove("mu-modal-open")');
    }

    public function updateVisitor(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
        $this->showUpdateConfirm = false;
        $this->js('document.body.classList.remove("mu-modal-open")');

        if (! $this->canRegister()) {
            $this->errorMessage = 'You do not have access to edit visitors.';

            return;
        }

        if (! $this->editingVisitorId) {
            $this->errorMessage = 'No visitor selected.';

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_no' => ['required', 'string', 'max:40'],
            'purpose' => ['required', 'string', 'max:255'],
            'desk_id' => ['required', 'integer', 'exists:desks,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $visitor = DailyVisitor::find($this->editingVisitorId);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found.';
            $this->cancelEdit();

            return;
        }

        $previousAssignedTo = (int) $visitor->assigned_to;
        $newAssignedTo = (int) $this->assigned_to;

        $visitor->name = trim($this->name);
        $visitor->contact_no = trim($this->contact_no);
        $visitor->purpose = trim($this->purpose);
        $visitor->desk_id = (int) $this->desk_id;
        $visitor->assigned_to = $newAssignedTo;
        $visitor->save();

        if ($previousAssignedTo !== $newAssignedTo && $visitor->status !== 'completed') {
            $meetPerson = User::find($newAssignedTo);

            if ($meetPerson) {
                UserNotifier::send(
                    $meetPerson,
                    'visitor_waiting',
                    'Visitor assigned to you',
                    $visitor->name.' ('.$visitor->contact_no.') — '.$visitor->purpose.'. Please reply: Send now or Please wait.',
                    'my-visitors',
                    'my-queue'
                );
            }
        }

        $this->successMessage = 'Visitor details updated for '.$visitor->name.'.';
        $this->editingVisitorId = null;
        $this->clearForm();
    }

    public function render()
    {
        if (! $this->canRegister()) {
            return view('livewire.admin.daily-visitors.register-visitor', [
                'denied' => true,
                'desks' => collect(),
                'staffList' => collect(),
                'visitors' => collect(),
                'countInQueue' => 0,
                'countMeeting' => 0,
                'countDone' => 0,
            ]);
        }

        if ($this->filter_date === '') {
            $this->filter_date = now(app_timezone())->toDateString();
        }

        $desks = Desk::where('is_active', true)->orderBy('name')->get();

        $staffList = collect();

        if ($this->desk_id !== '') {
            $staffList = User::where('role', 'staff')
                ->where('desk_id', (int) $this->desk_id)
                ->orderBy('name')
                ->get(['id', 'name']);

            if ($this->assigned_to !== '' && ! $staffList->contains('id', (int) $this->assigned_to)) {
                $this->assigned_to = '';
            }
        }

        $query = DailyVisitor::with(['desk', 'assignedUser'])
            ->whereDate('created_at', $this->filter_date)
            ->orderByDesc('id');

        if ($this->filter_status !== '') {
            $query->where('status', $this->filter_status);
        }

        if ($this->filter_desk !== '') {
            $query->where('desk_id', (int) $this->filter_desk);
        }

        $dayRows = DailyVisitor::whereDate('created_at', $this->filter_date)->get(['status']);

        return view('livewire.admin.daily-visitors.register-visitor', [
            'denied' => false,
            'desks' => $desks,
            'staffList' => $staffList,
            'visitors' => $query->limit(100)->get(),
            'countInQueue' => $dayRows->whereNotIn('status', ['in_meeting', 'completed'])->count(),
            'countMeeting' => $dayRows->where('status', 'in_meeting')->count(),
            'countDone' => $dayRows->where('status', 'completed')->count(),
        ]);
    }
}
