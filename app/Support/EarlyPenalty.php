<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;

/**
 * Early checkout → half-day leave after every 4th early this month.
 * Same rules as LatePenalty (paid if leave balance left).
 */
class EarlyPenalty
{
    /** How many early days this user has in the month. */
    public static function earlyCount(User $user, ?Carbon $month = null): int
    {
        [$start, $end] = self::monthRange($month);

        return AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->where('is_early', true)
            ->whereBetween('work_date', [$start, $end])
            ->count();
    }

    /** True when this early should cost a half day (4th, 8th, …). */
    public static function shouldDeduct(User $user, ?Carbon $month = null): bool
    {
        $count = self::earlyCount($user, $month);

        return $count > 0 && $count % 4 === 0;
    }

    /**
     * If this early is the 4th / 8th / …, create one approved half-day leave.
     * Paid when leave balance has at least 1 half day left; otherwise unpaid.
     */
    public static function applyIfNeeded(User $user, string $workDate): void
    {
        $month = Carbon::parse($workDate, app_timezone());

        if (! self::shouldDeduct($user, $month)) {
            return;
        }

        $alreadyExists = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('from_date', $workDate)
            ->where('reason', 'like', 'Early penalty%')
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $count = self::earlyCount($user, $month);
        $isPaid = LeaveBalance::remaining($user, $month) >= 1;

        LeaveRequest::create([
            'user_id' => $user->id,
            'from_date' => $workDate,
            'to_date' => $workDate,
            'leave_type' => 'half',
            'full_days' => 0,
            'half_days' => 1,
            'is_paid' => $isPaid,
            'reason' => "Early penalty ({$count}th early this month)",
            'status' => 'approved',
            'approved_by' => null,
        ]);
    }

    /** First and last date of the month. */
    protected static function monthRange(?Carbon $month = null): array
    {
        $month = $month?->copy() ?? Carbon::now(app_timezone());

        return [
            $month->copy()->startOfMonth()->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
        ];
    }
}
