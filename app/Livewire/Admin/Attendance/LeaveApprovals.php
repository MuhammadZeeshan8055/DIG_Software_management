<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\LeaveRequest;
use Livewire\Component;

/**
 * Step 4: Admin / super_admin approve or reject leave requests.
 * Balance math comes in Step 5 — here we only change status + approved_by.
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
     * Shared approve / reject logic (clear and simple).
     */
    protected function decide(int $id, string $status): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin or super admin can approve leave.';

            return;
        }

        $request = LeaveRequest::query()->find($id);

        if (! $request) {
            $this->errorMessage = 'Leave request not found.';

            return;
        }

        if ($request->status !== 'pending') {
            $this->errorMessage = 'This request was already decided.';

            return;
        }

        $request->update([
            'status' => $status,
            'approved_by' => auth()->id(),
        ]);

        $label = $status === 'approved' ? 'approved' : 'rejected';
        $this->successMessage = 'Leave request '.$label.'.';
    }
}
