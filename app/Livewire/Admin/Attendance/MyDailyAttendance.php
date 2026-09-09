<?php

namespace App\Livewire\Admin\Attendance;

use App\Support\DailyAttendanceRows;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Staff: own daily attendance table.
 */
class MyDailyAttendance extends Component
{
    /** e.g. "2026-09" */
    public string $month = '';

    public function mount(): void
    {
        $this->month = DailyAttendanceRows::normalizeMonth(null);
    }

    public function updatedMonth(string $value): void
    {
        $this->month = DailyAttendanceRows::normalizeMonth($value);
    }

    public function render()
    {
        $built = DailyAttendanceRows::build(auth()->user(), $this->month);

        return view('livewire.admin.attendance.my-daily-attendance', [
            'rows' => $built['rows'],
            'monthLabel' => $built['monthLabel'],
        ]);
    }
}
