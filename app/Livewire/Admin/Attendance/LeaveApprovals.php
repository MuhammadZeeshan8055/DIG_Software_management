<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\LeaveRequest;
use App\Support\LeaveBalance;
use Livewire\Component;

/**
 * Admin / super_admin approve or reject leave.
 * Approve is blocked if remaining balance is not enough.
 */
class LeaveApprovals extends Component
{
    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    /** Used by poll to notice new pending requests */
    public ?int $seenPendingCount = null;

    /**
     * Quiet refresh while this panel is visible.
     * If pending count goes up → toast (admin sees new leave without F5).
     */
    public function poll(): void
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $count = LeaveRequest::query()->where('status', 'pending')->count();

        if ($this->seenPendingCount !== null && $count > $this->seenPendingCount) {
            $this->successMessage = 'New leave request waiting for approval.';
        }

        $this->seenPendingCount = $count;
    }

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

        // Not enough leave left → do not approve (paid/unpaid split later)
        if ($status === 'approved' && ! LeaveBalance::hasEnough($request)) {
            $this->errorMessage = 'Not enough leave balance for this request ('.LeaveBalance::costLabel($request).'). Reject it, or ask for a shorter leave.';

            return;
        }

        $request->update([
            'status' => $status,
            'approved_by' => auth()->id(),
        ]);

        $this->successMessage = $status === 'approved'
            ? 'Leave request approved.'
            : 'Leave request rejected.';
    }
}
