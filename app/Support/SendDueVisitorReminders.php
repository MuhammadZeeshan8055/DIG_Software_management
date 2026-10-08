<?php

namespace App\Support;

use App\Models\VisitorReminder;

/**
 * Find open reminders due today (or overdue) and notify the staff user once.
 */
class SendDueVisitorReminders
{
    /**
     * @return int How many reminders were notified
     */
    public static function run(): int
    {
        $today = now(app_timezone())->toDateString();

        $reminders = VisitorReminder::with(['visitor', 'user'])
            ->where('is_done', false)
            ->whereNull('notified_at')
            ->whereDate('remind_on', '<=', $today)
            ->orderBy('id')
            ->get();

        $sent = 0;

        foreach ($reminders as $reminder) {
            $user = $reminder->user;

            if (! $user) {
                continue;
            }

            $visitorName = $reminder->visitor?->name ?? 'Visitor';
            $note = $reminder->note ? ' — '.$reminder->note : '';

            UserNotifier::send(
                $user,
                'visitor_reminder_due',
                'Reminder: '.$visitorName,
                'Follow up with '.$visitorName.$note.'. Open My Visitors → Reminders.',
                'my-visitors',
                'reminders-list'
            );

            $reminder->notified_at = now();
            $reminder->save();
            $sent++;
        }

        return $sent;
    }
}
