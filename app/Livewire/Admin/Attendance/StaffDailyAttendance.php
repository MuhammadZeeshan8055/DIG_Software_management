<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\User;
use App\Support\DailyAttendanceRows;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Admin / super_admin: pick a staff user and view their daily attendance.
 */
class StaffDailyAttendance extends Component
{
    /** e.g. "2026-09" */
    public string $month = '';

    /** Selected staff user id */
    public ?int $userId = null;

    public function mount(): void
    {
        $this->month = DailyAttendanceRows::normalizeMonth(null);
        $this->userId = $this->firstStaffId();
    }

    public function updatedMonth(string $value): void
    {
        $this->month = DailyAttendanceRows::normalizeMonth($value);
    }

    public function updatedUserId(mixed $value): void
    {
        // Select sends "" when "Select staff…" is chosen
        $this->userId = ($value === null || $value === '') ? null : (int) $value;
    }

    public function render()
    {
        // Not admin → soft deny (same pattern as Holidays / Leave Approvals)
        if (! auth()->user()?->isAdmin()) {
            return view('livewire.admin.attendance.staff-daily-attendance', [
                'denied' => true,
                'staffUsers' => collect(),
                'selectedUser' => null,
                'rows' => [],
                'monthLabel' => '',
            ]);
        }

        $staffUsers = $this->staffUsers();
        $selectedUser = $this->selectedStaff($staffUsers);

        $rows = [];
        $monthLabel = '';

        if ($selectedUser) {
            $built = DailyAttendanceRows::build($selectedUser, $this->month);
            $rows = $built['rows'];
            $monthLabel = $built['monthLabel'];
        }

        return view('livewire.admin.attendance.staff-daily-attendance', [
            'denied' => false,
            'staffUsers' => $staffUsers,
            'selectedUser' => $selectedUser,
            'rows' => $rows,
            'monthLabel' => $monthLabel,
        ]);
    }

    /** Staff dropdown list (name A→Z). */
    protected function staffUsers(): Collection
    {
        return User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /** First staff id for default dropdown value. */
    protected function firstStaffId(): ?int
    {
        $id = User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /** Currently selected staff, or null. */
    protected function selectedStaff(Collection $staffUsers): ?User
    {
        if (! $this->userId) {
            return null;
        }

        return $staffUsers->firstWhere('id', $this->userId);
    }
}
