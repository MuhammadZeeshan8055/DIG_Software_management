<?php

namespace App\Support;

use App\Models\AttendanceSetting;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;

/**
 * Leave balance for one employee in one month.
 *
 * Inside this file we count half-days:
 *   full day = 2, half day = 1
 *   e.g. allow 2 full + 2 half → 6 half-days total
 *
 * Balance only changes when leave is APPROVED.
 */
class LeaveBalance
{
    /** How many half-days the company allows each month (Office Settings). */
    public static function allowed(): int
    {
        $settings = AttendanceSetting::query()->first();

        $full = (int) ($settings?->monthly_full_days ?? 0);
        $half = (int) ($settings?->monthly_half_days ?? 0);

        return ($full * 2) + $half;
    }

    /** How many half-days this one leave request costs. */
    public static function cost(LeaveRequest $leave): int
    {
        return ((int) $leave->full_days * 2) + (int) $leave->half_days;
    }

    /** Half-days already used (approved leaves only) in that month. */
    public static function used(User $user, ?Carbon $month = null): int
    {
        [$start, $end] = self::monthRange($month);

        $leaves = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('from_date', [$start, $end])
            ->get(['full_days', 'half_days']);

        $total = 0;
        foreach ($leaves as $leave) {
            $total += self::cost($leave);
        }

        return $total;
    }

    /** Half-days still left (never negative). */
    public static function remaining(User $user, ?Carbon $month = null): int
    {
        return max(0, self::allowed() - self::used($user, $month));
    }

    /** How many pending requests this user has in that month. */
    public static function pendingCount(User $user, ?Carbon $month = null): int
    {
        [$start, $end] = self::monthRange($month);

        return LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereBetween('from_date', [$start, $end])
            ->count();
    }

    /**
     * Split half-days into full + half for the UI.
     *
     * @return array{full: int, half: int, label: string}
     */
    public static function parts(int $halfDays): array
    {
        $full = intdiv(max(0, $halfDays), 2);
        $half = max(0, $halfDays) % 2;

        if ($full > 0 && $half > 0) {
            $label = "{$full} full + {$half} half";
        } elseif ($full > 0) {
            $label = $full === 1 ? '1 full' : "{$full} full";
        } elseif ($half > 0) {
            $label = '1 half';
        } else {
            $label = '0';
        }

        return [
            'full' => $full,
            'half' => $half,
            'label' => $label,
        ];
    }

    /**
     * Full summary for Apply Leave / dashboard cards.
     *
     * @return array{
     *   month_label: string,
     *   allowed_label: string,
     *   used: array{full: int, half: int, label: string},
     *   left: array{full: int, half: int, label: string},
     *   pending_count: int,
     *   full: int,
     *   half: int
     * }
     */
    public static function summary(User $user, ?Carbon $month = null): array
    {
        $month = $month?->copy() ?? Carbon::now(app_timezone());
        $used = self::parts(self::used($user, $month));
        $left = self::parts(self::remaining($user, $month));

        return [
            'month_label' => $month->format('F Y'),
            // Show Office Settings as saved (do not merge half→full)
            'allowed_label' => self::settingsLabel(),
            'used' => $used,
            'left' => $left,
            'pending_count' => self::pendingCount($user, $month),
            'full' => $left['full'],
            'half' => $left['half'],
        ];
    }

    /**
     * Allowance text exactly as set in Office Settings
     * e.g. "1 full + 2 half" — not converted into "2 full".
     */
    public static function settingsLabel(): string
    {
        $settings = AttendanceSetting::query()->first();

        $full = (int) ($settings?->monthly_full_days ?? 0);
        $half = (int) ($settings?->monthly_half_days ?? 0);

        return self::labelFromCounts($full, $half);
    }

    /** Build label from separate full/half counts (as admin typed them). */
    protected static function labelFromCounts(int $full, int $half): string
    {
        if ($full > 0 && $half > 0) {
            return "{$full} full + {$half} half";
        }

        if ($full > 0) {
            return $full === 1 ? '1 full' : "{$full} full";
        }

        if ($half > 0) {
            return $half === 1 ? '1 half' : "{$half} half";
        }

        return '0';
    }

    /** True if approving this leave still fits in remaining balance. */
    public static function hasEnough(LeaveRequest $leave): bool
    {
        if (! $leave->user) {
            return false;
        }

        $month = Carbon::parse($leave->from_date, app_timezone());

        return self::cost($leave) <= self::remaining($leave->user, $month);
    }

    /** Human text for one request, e.g. "2 full". */
    public static function costLabel(LeaveRequest $leave): string
    {
        return self::parts(self::cost($leave))['label'];
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
