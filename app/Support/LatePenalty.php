<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;

/**
 * Late → half-day leave after every 4th late this month.
 *
 * 7.1 lateCount — done
 * 7.2 shouldDeduct — done
 * 7.3 applyIfNeeded — create approved half-day leave (paid if balance left)
 */
class LatePenalty
{
    /** How many late days this user has in the month. */
    public static function lateCount(User $user, ?Carbon $month = null): int
    {
        [$start, $end] = self::monthRange($month);

        return AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->where('is_late', true)
            ->whereBetween('work_date', [$start, $end])
            ->count();
    }

    /** True when this late should cost a half day (4th, 8th, …). */
    public static function shouldDeduct(User $user, ?Carbon $month = null): bool
    {
        $count = self::lateCount($user, $month);

        return $count > 0 && $count % 4 === 0;
    }

    /**
     * If this late is the 4th / 8th / …, create one approved half-day leave.
     * Paid when leave balance has at least 1 half day left; otherwise unpaid.
     */
    public static function applyIfNeeded(User $user, string $workDate): void
    {
        $month = Carbon::parse($workDate, app_timezone());

        // Not the 4th / 8th / … late → nothing to do
        if (! self::shouldDeduct($user, $month)) {
            return;
        }

        // Already created a penalty leave for this day → don't create twice
        $alreadyExists = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('from_date', $workDate)
            ->where('reason', 'like', 'Late penalty%')
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $count = self::lateCount($user, $month);
        $isPaid = LeaveBalance::remaining($user, $month) >= 1;

        LeaveRequest::create([
            'user_id' => $user->id,
            'from_date' => $workDate,
            'to_date' => $workDate,
            'leave_type' => 'half',
            'full_days' => 0,
            'half_days' => 1,
            'is_paid' => $isPaid,
            'reason' => "Late penalty ({$count}th late this month)",
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
