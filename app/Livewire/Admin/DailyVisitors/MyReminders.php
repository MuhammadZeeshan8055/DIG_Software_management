<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\VisitorReminder;
use App\Support\UserNotifier;
use Livewire\Component;

/**
 * Staff: own reminders.
 * Admin / super_admin: all reminders.
 */
class MyReminders extends Component
{
    /** open | done | all */
    public string $filter = 'open';

    /** Which reminder we are completing. */
    public ?int $completingId = null;

    public string $done_note = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    protected function canUse(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isStaff());
    }

    protected function isAdminView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    /** Find reminder — staff: own only; admin: any. */
    protected function findReminder(int $id): ?VisitorReminder
    {
        $query = VisitorReminder::with(['visitor', 'user'])->where('id', $id);

        if (! $this->isAdminView()) {
            $query->where('user_id', auth()->id());
        }

        return $query->first();
    }

    public function startMarkDone(int $id): void
    {
        $this->errorMessage = null;
        $this->completingId = $id;
        $this->done_note = '';
    }

    public function cancelMarkDone(): void
    {
        $this->completingId = null;
        $this->done_note = '';
    }

    public function saveMarkDone(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canUse()) {
            $this->errorMessage = 'You do not have access to Reminders.';

            return;
        }

        if (! $this->completingId) {
            $this->errorMessage = 'No reminder selected.';

            return;
        }

        $this->validate([
            'done_note' => ['required', 'string', 'max:500'],
        ]);

        $reminder = $this->findReminder($this->completingId);

        if (! $reminder) {
            $this->errorMessage = 'Reminder not found.';

            return;
        }

        if ($reminder->is_done) {
            $this->errorMessage = 'This reminder is already done.';

            return;
        }

        $reminder->is_done = true;
        $reminder->done_note = trim($this->done_note);
        $reminder->done_at = now();
        $reminder->save();

        // Tell other admins (skip if current user is already an admin doing it)
        if (! $this->isAdminView()) {
            $visitorName = $reminder->visitor?->name ?? 'Visitor';
            UserNotifier::send(
                UserNotifier::admins(),
                'visitor_reminder_done',
                'Reminder follow-up: '.$visitorName,
                auth()->user()->name.' — '.$reminder->done_note,
                'daily-visitors',
                'reminders-list'
            );
        }

        $this->completingId = null;
        $this->done_note = '';
        $this->successMessage = 'Reminder marked as done.';
    }

    public function removeReminder(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canUse()) {
            $this->errorMessage = 'You do not have access to Reminders.';

            return;
        }

        $reminder = $this->findReminder($id);

        if (! $reminder || $reminder->is_done) {
            $this->errorMessage = 'Reminder not found or already done.';

            return;
        }

        $reminder->delete();

        if ($this->completingId === $id) {
            $this->cancelMarkDone();
        }

        $this->successMessage = 'Reminder removed.';
    }

    public function render()
    {
        if (! $this->canUse()) {
            return view('livewire.admin.daily-visitors.my-reminders', [
                'denied' => true,
                'isAdminView' => false,
                'reminders' => collect(),
                'countOpen' => 0,
                'countDue' => 0,
                'countDone' => 0,
            ]);
        }

        $adminView = $this->isAdminView();
        $today = now(app_timezone())->toDateString();
        $userId = auth()->id();

        $countOpenQ = VisitorReminder::query()->where('is_done', false);
        $countDueQ = VisitorReminder::query()->where('is_done', false)->whereDate('remind_on', '<=', $today);
        $countDoneQ = VisitorReminder::query()->where('is_done', true);
        $list = VisitorReminder::with(['visitor', 'user']);

        if (! $adminView) {
            $countOpenQ->where('user_id', $userId);
            $countDueQ->where('user_id', $userId);
            $countDoneQ->where('user_id', $userId);
            $list->where('user_id', $userId);
        }

        $countOpen = $countOpenQ->count();
        $countDue = $countDueQ->count();
        $countDone = $countDoneQ->count();

        if ($this->filter === 'open') {
            $list->where('is_done', false)->orderBy('remind_on')->orderBy('id');
        } elseif ($this->filter === 'done') {
            $list->where('is_done', true)->orderByDesc('done_at')->orderByDesc('id');
        } else {
            $list->orderBy('is_done')->orderBy('remind_on')->orderBy('id');
        }

        return view('livewire.admin.daily-visitors.my-reminders', [
            'denied' => false,
            'isAdminView' => $adminView,
            'reminders' => $list->limit(200)->get(),
            'countOpen' => $countOpen,
            'countDue' => $countDue,
            'countDone' => $countDone,
        ]);
    }
}
