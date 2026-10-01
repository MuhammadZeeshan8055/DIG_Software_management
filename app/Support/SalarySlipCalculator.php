<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Simple monthly salary: daily = salary / working days.
 * Charge absent (1×) and unpaid leave (full 1×, short/half 0.25×).
 */
class SalarySlipCalculator
{
    /** Unpaid short / half-day leave fraction of one day. */
    public const SHORT_DAY_FRACTION = 0.25;

    /**
     * @return array{
     *   monthly_salary: float,
     *   working_days: int,
     *   absent_days: float,
     *   unpaid_leave_days: float,
     *   deduct_days: float,
     *   daily_rate: float,
     *   deduction: float,
     *   net_pay: float
     * }
     */
    public static function forUser(User $user, string $month): array
    {
        $month = self::normalizeMonth($month);
        $salary = (float) ($user->monthly_salary ?? 0);

        [$first, $last, $stopDay] = self::dayRange($month);

        $holidays = Holiday::query()
            ->whereBetween('date', [$first, $last])
            ->get()
            ->keyBy(fn (Holiday $h) => $h->date->toDateString());

        $records = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$first, $last])
            ->get()
            ->keyBy(fn (AttendanceRecord $r) => $r->work_date->toDateString());

        $leaves = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $last)
            ->whereDate('to_date', '>=', $first)
            ->get();

        $workingDays = 0;
        $absentDays = 0.0;
        $unpaidLeaveDays = 0.0;

        $daysInMonth = (int) Carbon::parse($first, app_timezone())->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month.'-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            $carbon = Carbon::parse($date, app_timezone());

            if ($carbon->isSunday() || $holidays->has($date)) {
                continue;
            }

            $workingDays++;

            // Future days in current month: not absent yet
            if ($day > $stopDay) {
                continue;
            }

            $leave = self::leaveOnDate($leaves, $date);

            if ($leave !== null && ! $leave->is_paid) {
                $unpaidLeaveDays += $leave->leave_type === 'half'
                    ? self::SHORT_DAY_FRACTION
                    : 1.0;

                continue;
            }

            if ($leave !== null && $leave->is_paid && ! $leave->isLatePenalty()) {
                continue;
            }

            $record = $records->get($date);

            if ($record === null || $record->check_in_at === null) {
                $absentDays += 1.0;
            }
        }

        $daily = $workingDays > 0 ? round($salary / $workingDays, 2) : 0.0;
        $deductDays = round($absentDays + $unpaidLeaveDays, 2);
        $deduction = round($deductDays * $daily, 2);
        $net = round(max(0, $salary - $deduction), 2);

        return [
            'monthly_salary' => round($salary, 2),
            'working_days' => $workingDays,
            'absent_days' => round($absentDays, 2),
            'unpaid_leave_days' => round($unpaidLeaveDays, 2),
            'deduct_days' => $deductDays,
            'daily_rate' => $daily,
            'deduction' => $deduction,
            'net_pay' => $net,
        ];
    }

    public static function normalizeMonth(?string $month): string
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }

        return Carbon::now(app_timezone())->format('Y-m');
    }

    /**
     * @return array{0: string, 1: string, 2: int}
     */
    protected static function dayRange(string $month): array
    {
        $today = Carbon::now(app_timezone())->toDateString();
        $todayMonth = substr($today, 0, 7);

        $first = $month.'-01';
        $daysInMonth = (int) Carbon::parse($first, app_timezone())->daysInMonth;
        $last = $month.'-'.str_pad((string) $daysInMonth, 2, '0', STR_PAD_LEFT);

        if ($month === $todayMonth) {
            $stopDay = (int) substr($today, 8, 2);
        } elseif ($month > $todayMonth) {
            $stopDay = 0;
        } else {
            $stopDay = $daysInMonth;
        }

        return [$first, $last, $stopDay];
    }

    /** Prefer unpaid approved leave on that date; otherwise any approved leave. */
    protected static function leaveOnDate(Collection $leaves, string $date): ?LeaveRequest
    {
        $paid = null;

        foreach ($leaves as $leave) {
            $from = $leave->from_date->toDateString();
            $to = $leave->to_date->toDateString();

            if ($date < $from || $date > $to) {
                continue;
            }

            if (! $leave->is_paid) {
                return $leave;
            }

            $paid = $leave;
        }

        return $paid;
    }
}
