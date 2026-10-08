<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\VisitorReminder;
use App\Support\UserNotifier;
use Livewire\Component;

/** Desk staff: list of my visitor reminders. */
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

        return $user && $user->isStaff();
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

        $reminder = VisitorReminder::with('visitor')
            ->where('id', $this->completingId)
            ->where('user_id', auth()->id())
            ->first();

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

        // Tell admins what happened after the reminder
        $visitorName = $reminder->visitor?->name ?? 'Visitor';
        UserNotifier::send(
            UserNotifier::admins(),
            'visitor_reminder_done',
            'Reminder follow-up: '.$visitorName,
            auth()->user()->name.' — '.$reminder->done_note,
            'daily-visitors',
            'list'
        );

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

        $reminder = VisitorReminder::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->where('is_done', false)
            ->first();

        if (! $reminder) {
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
                'reminders' => collect(),
                'countOpen' => 0,
                'countDue' => 0,
                'countDone' => 0,
            ]);
        }

        $userId = auth()->id();
        $today = now(app_timezone())->toDateString();

        $countOpen = VisitorReminder::where('user_id', $userId)->where('is_done', false)->count();
        $countDue = VisitorReminder::where('user_id', $userId)
            ->where('is_done', false)
            ->whereDate('remind_on', '<=', $today)
            ->count();
        $countDone = VisitorReminder::where('user_id', $userId)->where('is_done', true)->count();

        $list = VisitorReminder::with('visitor')->where('user_id', $userId);

        if ($this->filter === 'open') {
            $list->where('is_done', false)->orderBy('remind_on')->orderBy('id');
        } elseif ($this->filter === 'done') {
            $list->where('is_done', true)->orderByDesc('done_at')->orderByDesc('id');
        } else {
            $list->orderBy('is_done')->orderBy('remind_on')->orderBy('id');
        }

        return view('livewire.admin.daily-visitors.my-reminders', [
            'denied' => false,
            'reminders' => $list->limit(100)->get(),
            'countOpen' => $countOpen,
            'countDue' => $countDue,
            'countDone' => $countDone,
        ]);
    }
}
