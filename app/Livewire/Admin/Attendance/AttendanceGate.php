<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\LeaveRequest;
use App\Support\AttendancePunch;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Page after login for staff: Start Shift.
 * If already started OR on leave → send to dashboard.
 */
#[Layout('layouts.attendance-gate')]
class AttendanceGate extends Component
{
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = auth()->user();

        // Admin → dashboard
        if ($user->isAdmin()) {
            $this->redirect(route('dashboard'), navigate: false);

            return;
        }

        // On leave → dashboard (no shift today)
        $onLeave = LeaveRequest::isOnApprovedLeave($user->id);
        if ($onLeave === true) {
            $this->redirect(route('dashboard'), navigate: false);

            return;
        }

        // Already punched in → dashboard
        $started = AttendancePunch::hasStartedToday($user);
        if ($started === true) {
            $this->redirect(route('dashboard'), navigate: false);
        }
    }

    public function startShift(): void
    {
        $this->errorMessage = null;

        $result = AttendancePunch::start(auth()->user(), (string) request()->ip());

        if ($result['ok'] === false) {
            $this->errorMessage = $result['message'];

            return;
        }

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        $user = auth()->user();
        $now = Carbon::now(app_timezone());

        return view('livewire.admin.attendance.attendance-gate', [
            'userName' => $user->name,
            'roleLabel' => strtoupper(str_replace('_', ' ', $user->role ?? 'staff')),
            'shortDate' => $now->format('d-M-Y'),
        ]);
    }
}
