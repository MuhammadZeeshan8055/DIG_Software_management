<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Support\AttendancePunch;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Logged-in user's monthly attendance (header profile → My Daily Attendance).
 */
class MyDailyAttendance extends Component
{
    /** Month to show, e.g. 2026-09 */
    public string $month;

    public function mount(): void
    {
        // Always on dashboard HTML — do not abort here.
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

        // Month range (e.g. 1 Sep → 30 Sep)
        $start = Carbon::createFromFormat('Y-m', $this->month, app_timezone())->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $settings = AttendanceSetting::query()->first();
        $requiredHours = (int) ($settings?->required_hours ?? 8);

        // This user's rows for that month
        $records = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('work_date')
            ->get();

        // Build simple rows for the table
        $rows = [];
        foreach ($records as $record) {
            $rows[] = $this->rowFromRecord($record);
        }

        return view('livewire.admin.attendance.my-daily-attendance', [
            'rows' => $rows,
            'requiredHours' => $requiredHours,
            'monthLabel' => $start->format('F Y'),
        ]);
    }

    /**
     * Turn one DB record into one table row.
     */
    protected function rowFromRecord(AttendanceRecord $record): array
    {
        $finished = $record->check_out_at !== null;
        $started = $record->check_in_at !== null;

        // Worked minutes
        $minutes = (int) $record->worked_minutes;
        if ($minutes <= 0 && $started && $finished) {
            $seconds = (int) $record->check_in_at->diffInSeconds($record->check_out_at);
            $minutes = intdiv(max(0, $seconds), 60);
        }

        // Green / red (only after check-out)
        $color = null;
        if ($finished) {
            $color = $record->status_color;
            if ($color === null) {
                $color = AttendancePunch::colorForWorkedMinutes($minutes);
            }
        }

        // Labels for the UI
        $worked = '—';
        $status = '—';
        if ($finished) {
            $worked = sprintf('%dh %dm', intdiv($minutes, 60), $minutes % 60);
            $status = $color === 'green' ? 'Met hours' : 'Short hours';
        } elseif ($started) {
            $worked = 'In progress';
            $status = 'Running';
        }

        return [
            'date' => format_date($record->work_date, 'd M Y'),
            'check_in' => $started ? format_datetime($record->check_in_at, 'h:i A') : '—',
            'check_out' => $finished ? format_datetime($record->check_out_at, 'h:i A') : '—',
            'worked' => $worked,
            'status' => $status,
            'color' => $color,
        ];
    }
}
