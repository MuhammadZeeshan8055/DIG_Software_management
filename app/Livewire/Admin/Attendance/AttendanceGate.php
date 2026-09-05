<?php

namespace App\Livewire\Admin\Attendance;

use App\Support\AttendancePunch;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Staff gate after login.
 * Check-in here = AttendancePunch::start() (same as portal Start Shift).
 */
#[Layout('layouts.attendance-gate')]
class AttendanceGate extends Component
{
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $this->redirect(route('dashboard'), navigate: false);

            return;
        }

        if (AttendancePunch::hasStartedToday($user)) {
            $this->redirect(route('dashboard'), navigate: false);
        }
    }

    public function startShift(): void
    {
        $this->errorMessage = null;

        $result = AttendancePunch::start(auth()->user(), (string) request()->ip());

        if (! $result['ok']) {
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
