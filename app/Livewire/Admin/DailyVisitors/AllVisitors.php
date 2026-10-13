<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\DailyVisitor;
use App\Models\Desk;
use Livewire\Component;

/** Admin: view all visitors and close / keep pending the case. */
class AllVisitors extends Component
{
    public string $filter_date = '';

    public string $filter_status = '';

    public string $filter_desk = '';

    public string $filter_case = '';

    // Which visitor we are updating (case form)
    public ?int $caseVisitorId = null;

    // close, pending, or reopen
    public string $caseAction = '';

    public string $case_admin_note = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
    }

    public function resetFilters(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
        $this->filter_status = '';
        $this->filter_desk = '';
        $this->filter_case = '';
    }

    protected function canViewList(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin();
    }

    public function startClose(int $id): void
    {
        $this->errorMessage = null;
        $this->caseVisitorId = $id;
        $this->caseAction = 'close';
        $this->case_admin_note = '';
    }

    public function startPending(int $id): void
    {
        $this->errorMessage = null;
        $this->caseVisitorId = $id;
        $this->caseAction = 'pending';
        $this->case_admin_note = '';
    }

    public function startReopen(int $id): void
    {
        $this->errorMessage = null;
        $this->caseVisitorId = $id;
        $this->caseAction = 'reopen';
        $this->case_admin_note = '';
    }

    public function cancelCaseForm(): void
    {
        $this->caseVisitorId = null;
        $this->caseAction = '';
        $this->case_admin_note = '';
    }

    public function saveCase(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->canViewList()) {
            $this->errorMessage = 'Only admin or super admin can update case status.';

            return;
        }

        if (! $this->caseVisitorId) {
            $this->errorMessage = 'No visitor selected.';

            return;
        }

        $this->validate([
            'case_admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $visitor = DailyVisitor::find($this->caseVisitorId);

        if (! $visitor) {
            $this->errorMessage = 'Visitor not found.';

            return;
        }

        $note = trim($this->case_admin_note);
        if ($note === '') {
            $note = null;
        }

        // Close case
        if ($this->caseAction === 'close') {
            if ($visitor->status !== 'completed') {
                $this->errorMessage = 'Visit must be completed before closing the case.';

                return;
            }

            $visitor->case_status = 'closed';
            $visitor->case_admin_note = $note;
            $visitor->case_closed_by = auth()->id();
            $visitor->case_closed_at = now();
            $visitor->save();

            $this->successMessage = 'Case closed for '.$visitor->name.'.';
            $this->cancelCaseForm();

            return;
        }

        // Keep pending
        if ($this->caseAction === 'pending') {
            if ($visitor->case_status === 'closed') {
                $this->errorMessage = 'Use Reopen for a closed case.';

                return;
            }

            if ($visitor->status !== 'completed') {
                $this->errorMessage = 'Visit must be completed before setting case pending.';

                return;
            }

            $visitor->case_status = 'pending';
            $visitor->case_admin_note = $note;
            $visitor->case_closed_by = null;
            $visitor->case_closed_at = null;
            $visitor->save();

            $this->successMessage = 'Case kept pending for '.$visitor->name.'.';
            $this->cancelCaseForm();

            return;
        }

        // Reopen case
        if ($this->caseAction === 'reopen') {
            if ($visitor->case_status !== 'closed') {
                $this->errorMessage = 'Only a closed case can be reopened.';

                return;
            }

            $visitor->case_status = 'pending';
            $visitor->case_admin_note = $note;
            $visitor->case_closed_by = null;
            $visitor->case_closed_at = null;
            $visitor->save();

            $this->successMessage = 'Case reopened (pending) for '.$visitor->name.'.';
            $this->cancelCaseForm();

            return;
        }

        $this->errorMessage = 'Unknown action.';
    }

    public function render()
    {
        if (! $this->canViewList()) {
            return view('livewire.admin.daily-visitors.all-visitors', [
                'denied' => true,
                'desks' => collect(),
                'visitors' => collect(),
            ]);
        }

        if ($this->filter_date === '') {
            $this->filter_date = now(app_timezone())->toDateString();
        }

        $desks = Desk::where('is_active', true)->orderBy('name')->get();

        $query = DailyVisitor::with([
            'desk',
            'assignedUser',
            'creator',
            'caseClosedBy',
            'reminders' => function ($q) {
                $q->with('user')->orderBy('remind_on')->orderBy('id');
            },
        ])
            ->whereDate('created_at', $this->filter_date)
            ->orderByDesc('id');

        if ($this->filter_status !== '') {
            $query->where('status', $this->filter_status);
        }

        if ($this->filter_desk !== '') {
            $query->where('desk_id', (int) $this->filter_desk);
        }

        if ($this->filter_case === 'none') {
            $query->whereNull('case_status');
        } elseif ($this->filter_case !== '') {
            $query->where('case_status', $this->filter_case);
        }

        return view('livewire.admin.daily-visitors.all-visitors', [
            'denied' => false,
            'desks' => $desks,
            'visitors' => $query->limit(200)->get(),
        ]);
    }
}
