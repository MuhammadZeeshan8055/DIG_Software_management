<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\DailyVisitor;
use App\Models\User;
use App\Models\VisitorReminder;
use App\Support\UserNotifier;
use Livewire\Component;

/** Desk staff: see my visitors — wait / send now / complete / remind. CRO can send to a manager. */
class MyQueue extends Component
{
    public string $remarks = '';

    public ?int $completingId = null;

    /** Which visitor we are setting a reminder for. */
    public ?int $remindingId = null;

    public string $remind_on = '';

    public string $remind_note = '';

    /** CRO only: which visitor we are sending to a manager. */
    public ?int $sendingToManagerId = null;

    /** Selected manager user id (string for the select). */
    public string $manager_id = '';

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

    /** Visitor currently assigned to me (manager / desk actions). */
    protected function findMine(int $id): ?DailyVisitor
    {
        $user = auth()->user();
        $visitor = DailyVisitor::with(['creator', 'croUser', 'assignedUser'])->find($id);

        if (! $visitor) {
            return null;
        }

        if ((int) $visitor->assigned_to !== (int) $user->id) {
            return null;
        }

        return $visitor;
    }

    /** Visitor I own as assignee OR as CRO who forwarded it. */
    protected function findVisible(int $id): ?DailyVisitor
    {
        $user = auth()->user();
        $visitor = DailyVisitor::with(['creator', 'croUser', 'assignedUser'])->find($id);

        if (! $visitor) {
            return null;
        }

        $uid = (int) $user->id;

        if ((int) $visitor->assigned_to === $uid) {
            return $visitor;
        }

        if ($visitor->cro_user_id && (int) $visitor->cro_user_id === $uid) {
            return $visitor;
        }

        return null;
    }

    public function pleaseWait(int $id): void
    {
        $this->setStatus($id, 'please_wait', 'Marked as Please wait. CRO / reception was notified.');
    }

    public function sendNow(int $id): void
    {
        $this->setStatus($id, 'send_now', 'Marked as Send now. CRO / reception was notified.');
    }

    public function startComplete(int $id): void
    {
        $this->sendingToManagerId = null;
        $this->manager_id = '';
        $this->remindingId = null;
        $this->completingId = $id;
        $this->remarks = '';
        $this->errorMessage = null;
    }

    public function cancelComplete(): void
    {
        $this->completingId = null;
        $this->remarks = '';
    }

    /** CRO only: open Send to manager form. */
    public function startSendToManager(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
        $this->completingId = null;
        $this->remindingId = null;
        $this->manager_id = '';
        $this->remarks = '';

        if (! auth()->user()?->isCro()) {
            $this->errorMessage = 'Only CRO can send a visitor to a manager.';

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

        if ($visitor->case_status === 'closed') {
            $this->errorMessage = 'This case is closed.';

            return;
        }

        if ($visitor->cro_user_id) {
            $this->errorMessage = 'Already sent to a manager. Wait for Please wait / Send now.';

            return;
        }

        // Prefill if CRO already typed remarks earlier
        $this->remarks = (string) ($visitor->remarks ?? '');
        $this->sendingToManagerId = $id;
    }

    public function cancelSendToManager(): void
    {
        $this->sendingToManagerId = null;
        $this->manager_id = '';
        $this->remarks = '';
    }

    /** CRO sends visitor to a Manager — manager is notified (bell + My Visitors). */
    public function sendToManager(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canUse()) {
            $this->errorMessage = 'You do not have access to My Visitors.';

            return;
        }

        if (! auth()->user()?->isCro()) {
            $this->errorMessage = 'Only CRO can send a visitor to a manager.';

            return;
        }

        if (! $this->sendingToManagerId) {
            $this->errorMessage = 'No visitor selected.';

            return;
        }

        $this->validate([
            'manager_id' => ['required', 'integer', 'exists:users,id'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $visitor = $this->findMine($this->sendingToManagerId);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found or not assigned to you.';

            return;
        }

        if ($visitor->status === 'completed') {
            $this->errorMessage = 'This visitor is already completed.';

            return;
        }

        if ($visitor->case_status === 'closed') {
            $this->errorMessage = 'This case is closed.';

            return;
        }

        $managerId = (int) $this->manager_id;

        if ($managerId === (int) auth()->id()) {
            $this->errorMessage = 'Pick a different manager.';

            return;
        }

        $manager = User::query()
            ->where('id', $managerId)
            ->where('role', 'staff')
            ->where('is_manager', true)
            ->first();

        if (! $manager) {
            $this->errorMessage = 'Selected user is not a manager.';

            return;
        }

        $note = trim($this->remarks);

        // Keep CRO on the case; manager becomes the assignee
        $visitor->remarks = $note;
        $visitor->cro_user_id = auth()->id();
        $visitor->assigned_to = $manager->id;
        $visitor->status = 'waiting';

        // If manager has a desk, keep visitor desk in sync (first desk)
        $managerDesk = $manager->desks()->orderBy('name')->first();
        if ($managerDesk) {
            $visitor->desk_id = $managerDesk->id;
        }

        $visitor->save();

        // Notify manager (bell + My Visitors poll)
        UserNotifier::send(
            $manager,
            'visitor_waiting',
            'Visitor sent to you by CRO',
            $visitor->name.' ('.$visitor->contact_no.') — '.$visitor->purpose.'. CRO remarks: '.$note,
            'my-visitors',
            'my-queue'
        );

        // Tell receptionist what happened
        if ($visitor->creator && (int) $visitor->creator->id !== (int) auth()->id()) {
            UserNotifier::send(
                $visitor->creator,
                'visitor_response',
                'CRO sent visitor to manager',
                auth()->user()->name.' sent '.$visitor->name.' to '.$manager->name.'.',
                'daily-visitors',
                'register'
            );
        }

        $this->sendingToManagerId = null;
        $this->manager_id = '';
        $this->remarks = '';
        $this->successMessage = 'Visitor sent to '.$manager->name.'. You will still see this visitor — wait for Please wait / Send now.';
    }

    /**
     * CRO marks visitor as sent after manager says Send now (same idea as reception Mark sent).
     */
    public function markSentByCro(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canUse()) {
            $this->errorMessage = 'You do not have access to My Visitors.';

            return;
        }

        $visitor = $this->findVisible($id);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found.';

            return;
        }

        // Only the CRO who forwarded can mark sent
        if ((int) $visitor->cro_user_id !== (int) auth()->id()) {
            $this->errorMessage = 'Only the CRO on this case can mark sent.';

            return;
        }

        if ($visitor->status === 'completed') {
            $this->errorMessage = 'This visitor is already completed.';

            return;
        }

        if ($visitor->status === 'in_meeting') {
            $this->errorMessage = 'Already marked as sent.';

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

        $this->successMessage = 'Marked as In meeting (sent). Manager was notified.';
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

        // Also tell CRO if this was a forwarded case
        if ($visitor->cro_user_id && (int) $visitor->cro_user_id !== (int) auth()->id()) {
            $cro = $visitor->croUser ?: User::find($visitor->cro_user_id);
            if ($cro) {
                UserNotifier::send(
                    $cro,
                    'visitor_completed',
                    'Visitor meeting completed',
                    $visitor->name.' meeting is done. Remarks: '.$visitor->remarks,
                    'my-visitors',
                    'my-queue'
                );
            }
        }

        $this->completingId = null;
        $this->remarks = '';
        $this->successMessage = 'Visitor marked as completed.';
    }

    /** Open the reminder form only when staff chooses to set one (optional). */
    public function startReminder(int $id): void
    {
        $this->errorMessage = null;
        $this->completingId = null;
        $this->sendingToManagerId = null;
        $this->manager_id = '';

        $visitor = $this->findMine($id);

        if ($visitor && $visitor->case_status === 'closed') {
            $this->errorMessage = 'This case is closed. Ask an admin to reopen it before setting a reminder.';
            $this->remindingId = null;

            return;
        }

        $this->remindingId = $id;
        $this->remind_on = '';
        $this->remind_note = '';

        $existing = VisitorReminder::query()
            ->where('daily_visitor_id', $id)
            ->where('user_id', auth()->id())
            ->where('is_done', false)
            ->orderByDesc('id')
            ->first();

        // Only fill if they already saved one — no automatic date
        if ($existing) {
            $this->remind_on = $existing->remind_on->toDateString();
            $this->remind_note = (string) ($existing->note ?? '');
        }
    }

    public function cancelReminder(): void
    {
        $this->remindingId = null;
        $this->remind_on = '';
        $this->remind_note = '';
    }

    public function saveReminder(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canUse()) {
            $this->errorMessage = 'You do not have access to My Visitors.';

            return;
        }

        if (! $this->remindingId) {
            $this->errorMessage = 'No visitor selected.';

            return;
        }

        $this->validate([
            'remind_on' => ['required', 'date', 'after_or_equal:today'],
            'remind_note' => ['nullable', 'string', 'max:255'],
        ]);

        $visitor = $this->findMine($this->remindingId);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found or not assigned to you.';

            return;
        }

        if ($visitor->status !== 'completed') {
            $this->errorMessage = 'Complete the visit before setting a reminder.';

            return;
        }

        if ($visitor->case_status === 'closed') {
            $this->errorMessage = 'This case is closed. Ask an admin to reopen it before setting a reminder.';

            return;
        }

        $userId = (int) auth()->id();

        // One open reminder per visitor for this user — update or create
        $reminder = VisitorReminder::query()
            ->where('daily_visitor_id', $visitor->id)
            ->where('user_id', $userId)
            ->where('is_done', false)
            ->orderByDesc('id')
            ->first();

        if (! $reminder) {
            $reminder = new VisitorReminder;
            $reminder->daily_visitor_id = $visitor->id;
            $reminder->user_id = $userId;
        }

        $reminder->remind_on = $this->remind_on;
        $reminder->note = trim($this->remind_note) !== '' ? trim($this->remind_note) : null;
        $reminder->notified_at = null;
        $reminder->is_done = false;
        $reminder->save();

        // New follow-up work — keep out of "awaiting review" until this reminder is done
        if ($visitor->case_status === 'awaiting_review') {
            $visitor->case_status = 'pending';
            $visitor->save();
        }

        $this->remindingId = null;
        $this->remind_on = '';
        $this->remind_note = '';
        $this->successMessage = 'Reminder saved for '.$visitor->name.'.';
    }

    /** Remove open reminder if not needed. */
    public function removeReminder(int $id): void
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

        VisitorReminder::query()
            ->where('daily_visitor_id', $visitor->id)
            ->where('user_id', auth()->id())
            ->where('is_done', false)
            ->delete();

        if ($this->remindingId === $id) {
            $this->cancelReminder();
        }

        $this->successMessage = 'Reminder removed for '.$visitor->name.'.';
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

        $actionText = $status === 'send_now'
            ? 'Please SEND the visitor in now.'
            : 'Please ask the visitor to WAIT.';

        // CRO path: notify CRO so they can Mark sent
        if ($visitor->cro_user_id && (int) $visitor->cro_user_id !== (int) auth()->id()) {
            $cro = $visitor->croUser ?: User::find($visitor->cro_user_id);
            if ($cro) {
                UserNotifier::send(
                    $cro,
                    'visitor_response',
                    'Update for visitor '.$visitor->name,
                    auth()->user()->name.': '.$actionText,
                    'my-visitors',
                    'my-queue'
                );
            }
        } elseif ($visitor->creator && (int) $visitor->creator->id !== (int) auth()->id()) {
            // Normal desk path: notify receptionist
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
                'isCro' => false,
                'myUserId' => 0,
                'managers' => collect(),
                'myVisitors' => collect(),
                'countInQueue' => 0,
                'countMeeting' => 0,
                'countDone' => 0,
                'countReminders' => 0,
            ]);
        }

        $user = auth()->user();
        $isCro = $user->isCro();

        if ($this->filter_date === '') {
            $this->filter_date = now(app_timezone())->toDateString();
        }

        $myId = $user->id;

        // Show visitors assigned to me, OR ones I forwarded as CRO
        $countQuery = DailyVisitor::whereDate('created_at', $this->filter_date)
            ->where(function ($query) use ($myId) {
                $query->where('assigned_to', $myId)
                    ->orWhere('cro_user_id', $myId);
            });

        $dayRows = $countQuery->get(['status']);

        $listQuery = DailyVisitor::with([
            'desk',
            'creator',
            'assignedUser',
            'reminders' => function ($query) use ($myId) {
                $query->where('user_id', $myId);
                $query->where('is_done', false);
            },
        ])
            ->whereDate('created_at', $this->filter_date)
            ->where(function ($query) use ($myId) {
                $query->where('assigned_to', $myId);
                $query->orWhere('cro_user_id', $myId);
            })
            ->orderByDesc('id');

        if ($this->filter_status !== '') {
            $listQuery->where('status', $this->filter_status);
        }

        // Open reminders for this staff (all dates)
        $countReminders = VisitorReminder::where('user_id', $myId)
            ->where('is_done', false)
            ->count();

        // Managers list for CRO "Send to manager"
        $managers = collect();
        if ($isCro) {
            $managers = User::where('role', 'staff')
                ->where('is_manager', true)
                ->where('id', '!=', $myId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('livewire.admin.daily-visitors.my-queue', [
            'denied' => false,
            'isCro' => $isCro,
            'myUserId' => $myId,
            'managers' => $managers,
            'myVisitors' => $listQuery->limit(100)->get(),
            'countInQueue' => $dayRows->whereNotIn('status', ['in_meeting', 'completed'])->count(),
            'countMeeting' => $dayRows->where('status', 'in_meeting')->count(),
            'countDone' => $dayRows->where('status', 'completed')->count(),
            'countReminders' => $countReminders,
        ]);
    }
}
