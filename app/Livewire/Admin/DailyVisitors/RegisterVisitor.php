<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\DailyVisitor;
use App\Models\Desk;
use App\Models\User;
use App\Support\UserNotifier;
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

    /** Filters for the visitors list (past days too). */
    public string $filter_date = '';

    public string $filter_status = '';

    public string $filter_desk = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
    }

    /** When desk changes, clear the selected person. */
    public function updatedDeskId(): void
    {
        $this->assigned_to = '';
    }

    /** Reset list filters to today / all. */
    public function resetFilters(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
        $this->filter_status = '';
        $this->filter_desk = '';
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

        // Notify the person they should meet
        $meetPerson = User::query()->find((int) $this->assigned_to);

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

        // Clear form for next visitor
        $this->name = '';
        $this->contact_no = '';
        $this->purpose = '';
        $this->desk_id = '';
        $this->assigned_to = '';

        $this->successMessage = 'Visitor registered. The assigned person was notified.';
    }

    /**
     * Reception: visitor has been sent to the desk / is in the meeting.
     */
    public function markSent(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $user = auth()->user();

        if (! $user || ! $user->canView('daily-visitors', 'register')) {
            $this->errorMessage = 'You do not have access to register visitors.';

            return;
        }

        $visitor = DailyVisitor::query()->with('assignedUser')->find($id);

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

        // Tell the desk person the visitor is on the way / in meeting
        if ($visitor->assignedUser && (int) $visitor->assignedUser->id !== (int) $user->id) {
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

    public function render()
    {
        $user = auth()->user();

        if (! $user || ! $user->canView('daily-visitors', 'register')) {
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

            // If selected person is no longer on this desk, clear the choice
            $stillThere = false;
            foreach ($staffList as $staff) {
                if ((int) $staff->id === (int) $this->assigned_to) {
                    $stillThere = true;
                    break;
                }
            }

            if ($this->assigned_to !== '' && ! $stillThere) {
                $this->assigned_to = '';
            }
        }

        // Visitors for selected date (+ optional status / desk)
        $query = DailyVisitor::query()
            ->with(['desk', 'assignedUser'])
            ->whereDate('created_at', $this->filter_date)
            ->orderByDesc('id');

        if ($this->filter_status !== '') {
            $query->where('status', $this->filter_status);
        }

        if ($this->filter_desk !== '') {
            $query->where('desk_id', (int) $this->filter_desk);
        }

        $visitors = $query->limit(100)->get();

        // Counts for the selected date (ignore status/desk filters so cards stay clear)
        $dayRows = DailyVisitor::query()
            ->whereDate('created_at', $this->filter_date)
            ->get(['id', 'status']);

        $countInQueue = 0;
        $countMeeting = 0;
        $countDone = 0;

        foreach ($dayRows as $visitor) {
            if ($visitor->status === 'completed') {
                $countDone++;
            } elseif ($visitor->status === 'in_meeting') {
                $countMeeting++;
            } else {
                $countInQueue++;
            }
        }

        return view('livewire.admin.daily-visitors.register-visitor', [
            'denied' => false,
            'desks' => $desks,
            'staffList' => $staffList,
            'visitors' => $visitors,
            'countInQueue' => $countInQueue,
            'countMeeting' => $countMeeting,
            'countDone' => $countDone,
        ]);
    }
}
