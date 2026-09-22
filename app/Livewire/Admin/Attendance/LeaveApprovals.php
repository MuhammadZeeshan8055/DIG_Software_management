<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\LeaveRequest;
use App\Support\LeaveBalance;
use App\Support\UserNotifier;
use Livewire\Component;

/**
 * Admin / super_admin approve or reject leave.
 * Over-balance leave can still be approved as paid (emergency).
 */
class LeaveApprovals extends Component
{
    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function render()
    {
        // Soft deny — only admin / super_admin
        if (! auth()->user()?->isAdmin()) {
            return view('livewire.admin.attendance.leave-approvals', [
                'denied' => true,
                'pending' => collect(),
                'recent' => collect(),
            ]);
        }

        $pending = LeaveRequest::query()
            ->with('user')
            ->where('status', 'pending')
            ->orderBy('from_date')
            ->orderBy('id')
            ->get();

        // Recent decisions so we can see who approved/rejected
        $recent = LeaveRequest::query()
            ->with(['user', 'approver'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        return view('livewire.admin.attendance.leave-approvals', [
            'denied' => false,
            'pending' => $pending,
            'recent' => $recent,
        ]);
    }

    public function approve(int $id): void
    {
        $this->decide($id, 'approved');
    }

    public function reject(int $id): void
    {
        $this->decide($id, 'rejected');
    }

    /**
     * Shared approve / reject logic.
     */
    protected function decide(int $id, string $status): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin or super admin can approve leave.';

            return;
        }

        $request = LeaveRequest::query()->with('user')->find($id);

        if (! $request) {
            $this->errorMessage = 'Leave request not found.';

            return;
        }

        if ($request->status !== 'pending') {
            $this->errorMessage = 'This request was already decided.';

            return;
        }

        $overBalance = $status === 'approved' && ! LeaveBalance::hasEnough($request);

        // Always keep as paid — admin/super admin may approve emergency leave over balance
        $request->update([
            'status' => $status,
            'approved_by' => auth()->id(),
            'is_paid' => true,
        ]);

        if ($request->user) {
            $from = optional($request->from_date)->format('Y-m-d');
            $to = optional($request->to_date)->format('Y-m-d');
            $dateLabel = $from === $to ? $from : $from.' → '.$to;
            $outcome = $status === 'approved' ? 'approved' : 'rejected';

            UserNotifier::send(
                $request->user,
                'leave_decision',
                'Leave request '.$outcome,
                'Your leave for '.$dateLabel.' was '.$outcome.'.',
                'attendance',
                'apply-leave'
            );
        }

        if ($status === 'approved') {
            $this->successMessage = $overBalance
                ? 'Leave approved as paid (emergency — over monthly balance).'
                : 'Leave request approved.';
        } else {
            $this->successMessage = 'Leave request rejected.';
        }
    }
}
