<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\SalarySlip;
use App\Models\User;
use App\Support\SalarySlipCalculator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Admin: generate salary slips from attendance + unpaid leave.
 */
class SalarySlips extends Component
{
    public ?int $userId = null;

    public string $month = '';

    public bool $showFormModal = false;

    public ?int $previewId = null;

    public bool $showPreview = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->month = SalarySlipCalculator::normalizeMonth(null);
        $this->userId = $this->firstStaffId();
    }

    public function openFormModal(): void
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $this->month = SalarySlipCalculator::normalizeMonth(null);
        $this->userId = $this->firstStaffId();
        $this->showFormModal = true;
        $this->showPreview = false;
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->resetValidation();
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetValidation();
    }

    public function generate(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin can generate salary slips.';

            return;
        }

        $this->validate([
            'userId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'staff')),
            ],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $user = User::findOrFail($this->userId);

        if ($user->monthly_salary === null || (float) $user->monthly_salary <= 0) {
            $this->errorMessage = 'Set monthly salary for this user in Manage Users first.';

            return;
        }

        $month = SalarySlipCalculator::normalizeMonth($this->month);
        $todayMonth = now(app_timezone())->format('Y-m');

        if ($month > $todayMonth) {
            $this->errorMessage = 'Cannot generate a salary slip for a future month.';

            return;
        }

        $calc = SalarySlipCalculator::forUser($user, $month);

        $slip = SalarySlip::updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => $month,
            ],
            [
                ...$calc,
                'generated_by' => auth()->id(),
            ]
        );

        $this->showFormModal = false;
        $this->previewId = $slip->id;
        $this->showPreview = true;
        $this->successMessage = 'Salary slip generated for '.$user->name.' ('.$slip->monthLabel().').';
    }

    public function openPreview(int $id): void
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $this->previewId = $id;
        $this->showPreview = true;
        $this->showFormModal = false;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewId = null;
    }

    public function render()
    {
        if (! auth()->user()?->isAdmin()) {
            return view('livewire.admin.attendance.salary-slips', [
                'denied' => true,
                'staffUsers' => collect(),
                'slips' => collect(),
                'previewSlip' => null,
            ]);
        }

        $previewSlip = null;

        if ($this->showPreview && $this->previewId) {
            $previewSlip = SalarySlip::with('user')->find($this->previewId);
        }

        return view('livewire.admin.attendance.salary-slips', [
            'denied' => false,
            'staffUsers' => $this->staffUsers(),
            'slips' => SalarySlip::query()
                ->with('user')
                ->orderByDesc('month')
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            'previewSlip' => $previewSlip,
        ]);
    }

    protected function staffUsers(): Collection
    {
        return User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'monthly_salary']);
    }

    protected function firstStaffId(): ?int
    {
        $id = User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->value('id');

        return $id ? (int) $id : null;
    }
}
