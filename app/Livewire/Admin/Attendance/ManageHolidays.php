<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\Holiday;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Admin: add / remove public holidays.
 * Sundays are off in code — no need to store them here.
 */
class ManageHolidays extends Component
{
    public string $date = '';

    public string $title = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        // Always on dashboard — do not abort(403) here.
    }

    public function addHoliday(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin or super admin can manage holidays.';

            return;
        }

        $this->validate([
            'date' => ['required', 'date', 'unique:holidays,date'],
            'title' => ['required', 'string', 'max:120'],
        ], [
            'date.unique' => 'A holiday is already saved for this date.',
        ]);

        // Sundays are already off in My Daily Attendance — no need to save them
        if (Carbon::parse($this->date, app_timezone())->isSunday()) {
            $this->errorMessage = 'Sundays are already off. No need to add them as holidays.';

            return;
        }

        Holiday::create([
            'date' => $this->date,
            'title' => trim($this->title),
        ]);

        $this->date = '';
        $this->title = '';
        $this->successMessage = 'Holiday added.';
    }

    public function deleteHoliday(int $id): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! auth()->user()?->isAdmin()) {
            $this->errorMessage = 'Only admin or super admin can manage holidays.';

            return;
        }

        Holiday::query()->where('id', $id)->delete();
        $this->successMessage = 'Holiday removed.';
    }

    public function render()
    {
        if (! auth()->user()?->isAdmin()) {
            return view('livewire.admin.attendance.manage-holidays', [
                'denied' => true,
                'holidays' => collect(),
            ]);
        }

        $year = (int) Carbon::now(app_timezone())->year;

        return view('livewire.admin.attendance.manage-holidays', [
            'denied' => false,
            'holidays' => Holiday::query()
                ->whereYear('date', '>=', $year - 1)
                ->orderByDesc('date')
                ->get(),
        ]);
    }
}
