<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Support\AttendancePunch;
use Carbon\Carbon;
use Livewire\Component;

/**
 * My Daily Attendance
 *
 * Shows one row per day:
 * - current month → day 1 until today
 * - past month    → full month
 * - future month  → empty
 */
class MyDailyAttendance extends Component
{
    /** Selected month, e.g. "2026-09" */
    public string $month;

    public function mount(): void
    {
        $this->month = Carbon::now(app_timezone())->format('Y-m');
    }

    public function updatedMonth(string $value): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $value)) {
            $this->month = Carbon::now(app_timezone())->format('Y-m');
        }
    }

    public function render()
    {
        $user = auth()->user();

        // Today: "2026-09-09"
        $today = Carbon::now(app_timezone())->toDateString();

        // First and last day of selected month as text
        $firstDate = $this->month.'-01';
        $daysInMonth = (int) Carbon::parse($firstDate, app_timezone())->daysInMonth;
        $lastDateInMonth = $this->month.'-'.str_pad((string) $daysInMonth, 2, '0', STR_PAD_LEFT);

        // Which day number should we stop at?
        $todayMonth = substr($today, 0, 7); // "2026-09"

        if ($this->month === $todayMonth) {
            // This month → stop at today's day number (e.g. 9)
            $stopDay = (int) substr($today, 8, 2);
        } elseif ($this->month > $todayMonth) {
            // Future month → show nothing
            $stopDay = 0;
        } else {
            // Past month → show all days
            $stopDay = $daysInMonth;
        }

        // Attendance rows for this month (key = "2026-09-08")
        $records = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$firstDate, $lastDateInMonth])
            ->get()
            ->keyBy(function ($record) {
                return $record->work_date->toDateString();
            });

        // Leaves that touch this month
        $leaves = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('from_date', '<=', $lastDateInMonth)
            ->whereDate('to_date', '>=', $firstDate)
            ->get();

        // Build rows: day 1, day 2, ... stopDay
        $rows = [];

        for ($day = 1; $day <= $stopDay; $day++) {
            $date = $this->month.'-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            $record = $records->get($date);
            $leave = $this->findLeaveOnDate($leaves, $date);

            $rows[] = $this->makeRow($date, $record, $leave);
        }
        
        $rows = $rows;

        $monthLabel = Carbon::parse($firstDate, app_timezone())->format('F Y');

        return view('livewire.admin.attendance.my-daily-attendance', [
            'rows' => $rows,
            'monthLabel' => $monthLabel,
        ]);
    }

    /**
     * Find leave for one date.
     * Approved wins over pending.
     */
    protected function findLeaveOnDate($leaves, string $date): ?LeaveRequest
    {
        $pending = null;

        foreach ($leaves as $leave) {
            // Late penalty is not a day off — keep showing attendance
            if ($leave->isLatePenalty()) {
                continue;
            }

            $from = $leave->from_date->toDateString();
            $to = $leave->to_date->toDateString();

            // date not inside leave range → skip
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
     * One table row for one date.
     */
    protected function makeRow(string $date, ?AttendanceRecord $record, ?LeaveRequest $leave): array
    {
        $dateLabel = format_date($date, 'd M Y');

        // --- On leave ---
        if ($leave !== null) {
            $isApproved = $leave->status === 'approved';

            return [
                'date' => $dateLabel,
                'check_in' => 'On leave',
                'check_out' => '—',
                'worked' => '—',
                'status' => $isApproved ? 'On leave' : 'Leave pending',
                'row' => $isApproved ? 'leave-ok' : 'leave-pending',
                'is_late' => false,
                'is_early' => false,
            ];
        }

        // --- No check-in ---
        if ($record === null || $record->check_in_at === null) {
            return [
                'date' => $dateLabel,
                'check_in' => '—',
                'check_out' => '—',
                'worked' => '—',
                'status' => '—',
                'row' => 'empty',
                'is_late' => false,
                'is_early' => false,
            ];
        }

        // --- Started but not finished ---
        if ($record->check_out_at === null) {
            return [
                'date' => $dateLabel,
                'check_in' => format_datetime($record->check_in_at, 'h:i A'),
                'check_out' => '—',
                'worked' => 'In progress',
                'status' => 'Running',
                'row' => 'incomplete',
                'is_late' => (bool) $record->is_late,
                'is_early' => false,
            ];
        }

        // --- Finished shift ---
        $minutes = (int) $record->worked_minutes;

        if ($minutes <= 0) {
            $seconds = (int) $record->check_in_at->diffInSeconds($record->check_out_at);
            $minutes = intdiv(max(0, $seconds), 60);
        }

        $color = $record->status_color;
        if ($color === null) {
            $color = AttendancePunch::colorForWorkedMinutes($minutes);
        }

        return [
            'date' => $dateLabel,
            'check_in' => format_datetime($record->check_in_at, 'h:i A'),
            'check_out' => format_datetime($record->check_out_at, 'h:i A'),
            'worked' => sprintf('%dh %dm', intdiv($minutes, 60), $minutes % 60),
            'status' => $color === 'green' ? 'Met hours' : 'Short hours',
            'row' => $color === 'green' ? 'ok' : 'incomplete',
            'is_late' => (bool) $record->is_late,
            'is_early' => (bool) $record->is_early,
        ];
    }
}
