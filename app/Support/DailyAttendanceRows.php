<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Build the day-by-day attendance table for one user + one month.
 *
 * Used by:
 *   - My Daily Attendance (own rows)
 *   - Staff Attendance (admin viewing a staff member)
 *
 * Row types (the "row" key in each array):
 *   holiday | leave-ok | leave-pending | empty | incomplete | ok
 */
class DailyAttendanceRows
{
    /**
     * Fix bad month values → "YYYY-MM".
     */
    public static function normalizeMonth(?string $month): string
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }

        return Carbon::now(app_timezone())->format('Y-m');
    }

    /**
     * @return array{rows: list<array<string, mixed>>, monthLabel: string}
     */
    public static function build(User $user, string $month): array
    {
        $month = self::normalizeMonth($month);

        // Step 1 — which calendar days to show
        [$firstDate, $lastDate, $stopDay] = self::dayRange($month);

        // Step 2 — load data once for the whole month
        $records = self::attendanceByDate($user->id, $firstDate, $lastDate);
        $leaves = self::leavesTouchingMonth($user->id, $firstDate, $lastDate);
        $holidays = self::holidaysByDate($firstDate, $lastDate);

        // Step 3 — one table row per day
        $rows = [];
        for ($day = 1; $day <= $stopDay; $day++) {
            $date = $month.'-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            $rows[] = self::rowForDay(
                $date,
                $records->get($date),
                self::leaveOnDate($leaves, $date),
                self::holidayLabel($date, $holidays->get($date))
            );
        }

        return [
            'rows' => $rows,
            'monthLabel' => Carbon::parse($firstDate, app_timezone())->format('F Y'),
        ];
    }

    /**
     * First date, last date, and last day-number to draw.
     *
     * current month → 1 … today
     * past month    → full month
     * future month  → nothing (stopDay = 0)
     *
     * @return array{0: string, 1: string, 2: int}
     */
    protected static function dayRange(string $month): array
    {
        $today = Carbon::now(app_timezone())->toDateString();
        $todayMonth = substr($today, 0, 7);

        $firstDate = $month.'-01';
        $daysInMonth = (int) Carbon::parse($firstDate, app_timezone())->daysInMonth;
        $lastDate = $month.'-'.str_pad((string) $daysInMonth, 2, '0', STR_PAD_LEFT);

        if ($month === $todayMonth) {
            $stopDay = (int) substr($today, 8, 2);
        } elseif ($month > $todayMonth) {
            $stopDay = 0;
        } else {
            $stopDay = $daysInMonth;
        }

        return [$firstDate, $lastDate, $stopDay];
    }

    /** @return Collection<string, AttendanceRecord> */
    protected static function attendanceByDate(int $userId, string $firstDate, string $lastDate): Collection
    {
        return AttendanceRecord::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$firstDate, $lastDate])
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->work_date->toDateString());
    }

    /** @return Collection<int, LeaveRequest> */
    protected static function leavesTouchingMonth(int $userId, string $firstDate, string $lastDate): Collection
    {
        return LeaveRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('from_date', '<=', $lastDate)
            ->whereDate('to_date', '>=', $firstDate)
            ->get();
    }

    /** @return Collection<string, Holiday> */
    protected static function holidaysByDate(string $firstDate, string $lastDate): Collection
    {
        return Holiday::query()
            ->whereBetween('date', [$firstDate, $lastDate])
            ->get()
            ->keyBy(fn (Holiday $holiday) => $holiday->date->toDateString());
    }

    /**
     * Sunday and/or public holiday label, or null if a normal work day.
     */
    protected static function holidayLabel(string $date, ?Holiday $holiday): ?string
    {
        $isSunday = Carbon::parse($date, app_timezone())->isSunday();

        if ($holiday && $isSunday) {
            return $holiday->title.' · Sunday';
        }

        if ($holiday) {
            return $holiday->title;
        }

        if ($isSunday) {
            return 'Sunday (Off)';
        }

        return null;
    }

    /**
     * Approved leave wins; otherwise pending; late-penalty rows are ignored.
     */
    protected static function leaveOnDate(Collection $leaves, string $date): ?LeaveRequest
    {
        $pending = null;

        foreach ($leaves as $leave) {
            if ($leave->isLatePenalty()) {
                continue;
            }

            $from = $leave->from_date->toDateString();
            $to = $leave->to_date->toDateString();

            if ($date < $from || $date > $to) {
                continue;
            }

            if ($leave->status === 'approved') {
                return $leave;
            }

            if ($leave->status === 'pending') {
                $pending = $leave;
            }
        }

        return $pending;
    }

    /**
     * Priority for one day:
     * 1) Holiday / Sunday
     * 2) Leave
     * 3) Attendance punches
     * 4) Empty
     *
     * @return array<string, mixed>
     */
    protected static function rowForDay(
        string $date,
        ?AttendanceRecord $record,
        ?LeaveRequest $leave,
        ?string $holidayLabel
    ): array {
        $dateLabel = format_date($date, 'd M Y');

        // 1) Off day
        if ($holidayLabel !== null) {
            return self::baseRow($dateLabel, [
                'check_in' => 'Holiday',
                'worked' => 'Off',
                'status' => $holidayLabel,
                'row' => 'holiday',
            ]);
        }

        // 2) Leave
        if ($leave !== null) {
            $approved = $leave->status === 'approved';

            return self::baseRow($dateLabel, [
                'check_in' => 'On leave',
                'status' => $approved ? 'On leave' : 'Leave pending',
                'row' => $approved ? 'leave-ok' : 'leave-pending',
            ]);
        }

        // 3) No punch
        if ($record === null || $record->check_in_at === null) {
            return self::baseRow($dateLabel, [
                'row' => 'empty',
            ]);
        }

        // 4) Still working
        if ($record->check_out_at === null) {
            return self::baseRow($dateLabel, [
                'check_in' => format_datetime($record->check_in_at, 'h:i A'),
                'worked' => 'In progress',
                'status' => 'Running',
                'row' => 'incomplete',
                'is_late' => (bool) $record->is_late,
            ]);
        }

        // 5) Finished shift
        $minutes = self::workedMinutes($record);
        $color = $record->status_color ?? AttendancePunch::colorForWorkedMinutes($minutes);
        $metHours = $color === 'green';

        return self::baseRow($dateLabel, [
            'check_in' => format_datetime($record->check_in_at, 'h:i A'),
            'check_out' => format_datetime($record->check_out_at, 'h:i A'),
            'worked' => sprintf('%dh %dm', intdiv($minutes, 60), $minutes % 60),
            'status' => $metHours ? 'Met hours' : 'Short hours',
            'row' => $metHours ? 'ok' : 'incomplete',
            'is_late' => (bool) $record->is_late,
            'is_early' => (bool) $record->is_early,
        ]);
    }

    /**
     * Default empty cells, then override what this day needs.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected static function baseRow(string $dateLabel, array $extra): array
    {
        return array_merge([
            'date' => $dateLabel,
            'check_in' => '—',
            'check_out' => '—',
            'worked' => '—',
            'status' => '—',
            'row' => 'empty',
            'is_late' => false,
            'is_early' => false,
        ], $extra);
    }

    protected static function workedMinutes(AttendanceRecord $record): int
    {
        $minutes = (int) $record->worked_minutes;

        if ($minutes > 0) {
            return $minutes;
        }

        $seconds = (int) $record->check_in_at->diffInSeconds($record->check_out_at);

        return intdiv(max(0, $seconds), 60);
    }
}
