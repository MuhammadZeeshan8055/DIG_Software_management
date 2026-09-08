<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Step 3: Staff applies for leave (saved as pending).
 * Balance is NOT changed here — only after admin approves (Step 4–5).
 */
class ApplyLeave extends Component
{
    /** full | half */
    public string $leave_type = 'full';

    public string $from_date = '';

    public string $to_date = '';

    public string $reason = '';

    public ?string $successMessage = null;

    public function mount(): void
    {
        // Default dates = today (app timezone)
        $today = Carbon::now(app_timezone())->toDateString();
        $this->from_date = $today;
        $this->to_date = $today;
    }

    /**
     * When user switches to half day, force one date only.
     */
    public function updatedLeaveType(string $value): void
    {
        if ($value === 'half') {
            $this->to_date = $this->from_date;
        }
    }

    public function updatedFromDate(string $value): void
    {
        if ($this->leave_type === 'half') {
            $this->to_date = $value;
        }
    }

    public function render()
    {
        $user = auth()->user();

        // Soft deny (component stays on dashboard HTML)
        if (! $user->canView('attendance', 'apply-leave')) {
            return view('livewire.admin.attendance.apply-leave', [
                'denied' => true,
                'requests' => collect(),
            ]);
        }

        // This user's leave list (newest first)
        $requests = LeaveRequest::query()
            ->with('approver')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('livewire.admin.attendance.apply-leave', [
            'denied' => false,
            'requests' => $requests,
        ]);
    }

    public function submit(): void
    {
        if (! auth()->user()->canView('attendance', 'apply-leave')) {
            return;
        }

        $this->successMessage = null;

        $this->validate([
            'leave_type' => ['required', 'in:full,half'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Half day = always one calendar day
        if ($this->leave_type === 'half') {
            $this->to_date = $this->from_date;
            $fullDays = 0;
            $halfDays = 1;
        } else {
            // Inclusive day count: 10 Sep → 12 Sep = 3 full days
            $from = Carbon::parse($this->from_date, app_timezone())->startOfDay();
            $to = Carbon::parse($this->to_date, app_timezone())->startOfDay();
            $fullDays = (int) $from->diffInDays($to) + 1;
            $halfDays = 0;
        }

        LeaveRequest::create([
            'user_id' => auth()->id(),
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'leave_type' => $this->leave_type,
            'full_days' => $fullDays,
            'half_days' => $halfDays,
            'is_paid' => true,
            'reason' => $this->reason !== '' ? $this->reason : null,
            'status' => 'pending',
            'approved_by' => null,
        ]);

        $this->reason = '';
        $this->successMessage = 'Leave request submitted. Waiting for admin approval.';
    }
}
