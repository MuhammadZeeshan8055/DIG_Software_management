<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\DailyVisitor;
use App\Support\UserNotifier;
use Livewire\Component;

/** Desk staff: see my visitors — wait / send now / complete. */
class MyQueue extends Component
{
    public string $remarks = '';

    public ?int $completingId = null;

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

    protected function canUse(): bool
    {
        $user = auth()->user();

        // Desk staff only — admins use Daily Visitors
        return $user && $user->isStaff();
    }

    /** Find a visitor assigned to me. */
    protected function findMine(int $id): ?DailyVisitor
    {
        $user = auth()->user();
        $visitor = DailyVisitor::with('creator')->find($id);

        if (! $visitor) {
            return null;
        }

        if ((int) $visitor->assigned_to !== (int) $user->id) {
            return null;
        }

        return $visitor;
    }

    public function pleaseWait(int $id): void
    {
        $this->setStatus($id, 'please_wait', 'Marked as Please wait. Reception was notified.');
    }

    public function sendNow(int $id): void
    {
        $this->setStatus($id, 'send_now', 'Marked as Send now. Reception was notified.');
    }

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

        if (! $this->canUse()) {
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

        $visitor = $this->findMine($this->completingId);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found or not assigned to you.';

            return;
        }

        $visitor->status = 'completed';
        $visitor->remarks = trim($this->remarks);
        $visitor->save();

        if ($visitor->creator && (int) $visitor->creator->id !== (int) auth()->id()) {
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

    protected function setStatus(int $id, string $status, string $okMessage): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canUse()) {
            $this->errorMessage = 'You do not have access to My Visitors.';

            return;
        }

        $visitor = $this->findMine($id);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found or not assigned to you.';

            return;
        }

        if ($visitor->status === 'completed') {
            $this->errorMessage = 'This visitor is already completed.';

            return;
        }

        $visitor->status = $status;
        $visitor->save();

        if ($visitor->creator && (int) $visitor->creator->id !== (int) auth()->id()) {
            $actionText = $status === 'send_now'
                ? 'Please SEND the visitor in now.'
                : 'Please ask the visitor to WAIT.';

            UserNotifier::send(
                $visitor->creator,
                'visitor_response',
                'Update for visitor '.$visitor->name,
                auth()->user()->name.': '.$actionText,
                'daily-visitors',
                'register'
            );
        }

        $this->successMessage = $okMessage;
    }

    public function render()
    {
        if (! $this->canUse()) {
            return view('livewire.admin.daily-visitors.my-queue', [
                'denied' => true,
                'myVisitors' => collect(),
                'countInQueue' => 0,
                'countMeeting' => 0,
                'countDone' => 0,
            ]);
        }

        $user = auth()->user();

        if ($this->filter_date === '') {
            $this->filter_date = now(app_timezone())->toDateString();
        }

        // Only visitors assigned to this staff user
        $countQuery = DailyVisitor::whereDate('created_at', $this->filter_date)
            ->where('assigned_to', $user->id);

        $dayRows = $countQuery->get(['status']);

        $listQuery = DailyVisitor::with(['desk', 'creator'])
            ->whereDate('created_at', $this->filter_date)
            ->where('assigned_to', $user->id)
            ->orderByDesc('id');

        if ($this->filter_status !== '') {
            $listQuery->where('status', $this->filter_status);
        }

        return view('livewire.admin.daily-visitors.my-queue', [
            'denied' => false,
            'myVisitors' => $listQuery->limit(100)->get(),
            'countInQueue' => $dayRows->whereNotIn('status', ['in_meeting', 'completed'])->count(),
            'countMeeting' => $dayRows->where('status', 'in_meeting')->count(),
            'countDone' => $dayRows->where('status', 'completed')->count(),
        ]);
    }
}
