<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\AttendanceRecord;
use App\Support\AttendancePunch;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Current Workday panel — buttons only; real rules are in AttendancePunch.
 *
 * startShift() → AttendancePunch::start()
 * endShift()   → AttendancePunch::end()
 */
class AttendanceTodayPanel extends Component
{
    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    /** Ask before ending — stops accidental End Shift after a double-click on Start. */
    public bool $showEndConfirm = false;

    public function mount(): void
    {
        // Always on the dashboard — do not abort(403) here.
    }

    public function askEndShift(): void
    {
        $this->clearMessages();
        $this->showEndConfirm = true;
    }

    public function cancelEndShift(): void
    {
        $this->showEndConfirm = false;
    }

    public function startShift(): void
    {
        $this->clearMessages();
        $this->showEndConfirm = false;

        $result = AttendancePunch::start(auth()->user(), (string) request()->ip());

        if ($result['ok']) {
            $this->successMessage = $result['message'];
        } else {
            $this->errorMessage = $result['message'];
        }
    }

    public function endShift(): void
    {
        $this->clearMessages();
        $this->showEndConfirm = false;

        $result = AttendancePunch::end(auth()->user(), (string) request()->ip());

        if ($result['ok']) {
            $this->successMessage = $result['message'];
        } else {
            $this->errorMessage = $result['message'];
        }
    }

    protected function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%dh %dm', $hours, $mins);
    }

    protected function formatRunning(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
    }

    protected function workedLabel(?AttendanceRecord $record): string
    {
        if (! $record || $record->check_in_at === null) {
            return '—';
        }

        if ($record->check_out_at !== null) {
            $minutes = (int) $record->worked_minutes;

            if ($minutes <= 0) {
                $minutes = intdiv(
                    max(0, (int) $record->check_in_at->diffInSeconds($record->check_out_at)),
                    60
                );
            }

            return $this->formatMinutes($minutes);
        }

        $elapsed = max(0, (int) $record->check_in_at->diffInSeconds(Carbon::now(app_timezone())));

        return $this->formatRunning($elapsed).' (running)';
    }

    protected function clearMessages(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        $user = auth()->user();
        $record = AttendancePunch::todayRecord($user);

        return view('livewire.admin.attendance.attendance-today-panel', [
            'todayLabel' => format_date(Carbon::now(app_timezone())),
            'checkInLabel' => $record?->check_in_at
                ? format_datetime($record->check_in_at, 'h:i A')
                : '—',
            'checkOutLabel' => $record?->check_out_at
                ? format_datetime($record->check_out_at, 'h:i A')
                : '—',
            'workedLabel' => $this->workedLabel($record),
            'isRunning' => $record !== null
                && $record->check_in_at !== null
                && $record->check_out_at === null,
            'checkInAtMs' => $record?->check_in_at
                ? ((int) $record->check_in_at->timestamp) * 1000
                : null,
            'canStart' => $record === null || $record->check_in_at === null,
            'canEnd' => $record !== null
                && $record->check_in_at !== null
                && $record->check_out_at === null,
            'clientIp' => request()->ip(),
        ]);
    }
}
