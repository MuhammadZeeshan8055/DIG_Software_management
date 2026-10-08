<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\DailyVisitor;
use App\Support\UserNotifier;
use Livewire\Component;

/**
 * Desk person: see visitors sent to me.
 * Actions: Please wait / Send now / Complete with remarks.
 */
class MyQueue extends Component
{
    /** Remarks typed before completing a visitor. */
    public string $remarks = '';

    /** Which visitor we are completing (id). */
    public ?int $completingId = null;

    /** List filters (see previous days too). */
    public string $filter_date = '';

    public string $filter_status = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
    }

    public function resetFilters(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
        $this->filter_status = '';
    }

    /**
     * Can this user open My Visitors?
     * Admins / permission OR any logged-in staff (they only see their own queue).
     */
    protected function canUseMyQueue(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Own queue — always for staff (separate My Visitors block)
        return $user->isStaff();
    }

    public function pleaseWait(int $id): void
    {
        $this->setStatus($id, 'please_wait', 'Marked as Please wait. Reception was notified.');
    }

    public function sendNow(int $id): void
    {
        $this->setStatus($id, 'send_now', 'Marked as Send now. Reception was notified.');
    }

    /** Open the complete box for one visitor. */
    public function startComplete(int $id): void
    {
        $this->completingId = $id;
        $this->remarks = '';
        $this->errorMessage = null;
    }

    public function cancelComplete(): void
    {
        $this->completingId = null;
        $this->remarks = '';
    }

    public function complete(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $user = auth()->user();

        if (! $this->canUseMyQueue()) {
            $this->errorMessage = 'You do not have access to My Visitors.';

            return;
        }

        if (! $this->completingId) {
            $this->errorMessage = 'No visitor selected.';

            return;
        }

        $this->validate([
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $visitor = DailyVisitor::query()->with('creator')->find($this->completingId);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found.';

            return;
        }

        // Only the assigned person (or admin) can complete
        if ((int) $visitor->assigned_to !== (int) $user->id && ! $user->isAdmin()) {
            $this->errorMessage = 'This visitor is not assigned to you.';

            return;
        }

        $visitor->status = 'completed';
        $visitor->remarks = trim($this->remarks);
        $visitor->save();

        // Tell reception the meeting is done
        if ($visitor->creator && (int) $visitor->creator->id !== (int) $user->id) {
            UserNotifier::send(
                $visitor->creator,
                'visitor_completed',
                'Visitor meeting completed',
                $visitor->name.' meeting is done. Remarks: '.$visitor->remarks,
                'daily-visitors',
                'register'
            );
        }

        $this->completingId = null;
        $this->remarks = '';
        $this->successMessage = 'Visitor marked as completed.';
    }

    /**
     * Shared helper for Please wait / Send now.
     */
    protected function setStatus(int $id, string $status, string $okMessage): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $user = auth()->user();

        if (! $this->canUseMyQueue()) {
            $this->errorMessage = 'You do not have access to My Visitors.';

            return;
        }

        $visitor = DailyVisitor::query()->with('creator')->find($id);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found.';

            return;
        }

        if ((int) $visitor->assigned_to !== (int) $user->id && ! $user->isAdmin()) {
            $this->errorMessage = 'This visitor is not assigned to you.';

            return;
        }

        if ($visitor->status === 'completed') {
            $this->errorMessage = 'This visitor is already completed.';

            return;
        }

        $visitor->status = $status;
        $visitor->save();

        // Tell reception what to do with the waiting visitor
        if ($visitor->creator && (int) $visitor->creator->id !== (int) $user->id) {
            $actionText = $status === 'send_now'
                ? 'Please SEND the visitor in now.'
                : 'Please ask the visitor to WAIT.';

            UserNotifier::send(
                $visitor->creator,
                'visitor_response',
                'Update for visitor '.$visitor->name,
                $user->name.': '.$actionText,
                'daily-visitors',
                'register'
            );
        }

        $this->successMessage = $okMessage;
    }

    public function render()
    {
        $user = auth()->user();

        if (! $this->canUseMyQueue()) {
            return view('livewire.admin.daily-visitors.my-queue', [
                'denied' => true,
                'myVisitors' => collect(),
                'countInQueue' => 0,
                'countMeeting' => 0,
                'countDone' => 0,
            ]);
        }

        $today = now(app_timezone())->toDateString();
        $user = auth()->user();

        if ($this->filter_date === '') {
            $this->filter_date = $today;
        }

        // Counts for selected date (staff = only mine)
        $allDayQuery = DailyVisitor::query()
            ->whereDate('created_at', $this->filter_date);

        if (! $user->isAdmin()) {
            $allDayQuery->where('assigned_to', $user->id);
        }

        $allDay = $allDayQuery->get(['id', 'status']);

        $countInQueue = 0;
        $countMeeting = 0;
        $countDone = 0;

        foreach ($allDay as $row) {
            if ($row->status === 'completed') {
                $countDone++;
            } elseif ($row->status === 'in_meeting') {
                $countMeeting++;
            } else {
                $countInQueue++;
            }
        }

        // List for selected date (includes completed so staff can read remarks)
        $query = DailyVisitor::query()
            ->with(['desk', 'creator'])
            ->whereDate('created_at', $this->filter_date)
            // Open visits first, then done; newest within each group
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderByDesc('id');

        if (! $user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        }

        if ($this->filter_status !== '') {
            $query->where('status', $this->filter_status);
        }

        return view('livewire.admin.daily-visitors.my-queue', [
            'denied' => false,
            'myVisitors' => $query->limit(100)->get(),
            'countInQueue' => $countInQueue,
            'countMeeting' => $countMeeting,
            'countDone' => $countDone,
        ]);
    }
}
