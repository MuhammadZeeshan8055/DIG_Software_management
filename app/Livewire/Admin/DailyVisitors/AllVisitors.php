<?php

namespace App\Livewire\Admin\DailyVisitors;

use App\Models\DailyVisitor;
use App\Models\Desk;
use Livewire\Component;

/** Admin: view all visitor records (filters + list). */
class AllVisitors extends Component
{
    public string $filter_date = '';

    public string $filter_status = '';

    public string $filter_desk = '';

    public function mount(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
    }

    public function resetFilters(): void
    {
        $this->filter_date = now(app_timezone())->toDateString();
        $this->filter_status = '';
        $this->filter_desk = '';
    }

    protected function canViewList(): bool
    {
        $user = auth()->user();

        return $user && $user->isAdmin();
    }

    public function render()
    {
        if (! $this->canViewList()) {
            return view('livewire.admin.daily-visitors.all-visitors', [
                'denied' => true,
                'desks' => collect(),
                'visitors' => collect(),
                'countInQueue' => 0,
                'countMeeting' => 0,
                'countDone' => 0,
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

        $dayRows = DailyVisitor::whereDate('created_at', $this->filter_date)->get(['status']);

        return view('livewire.admin.daily-visitors.all-visitors', [
            'denied' => false,
            'desks' => $desks,
            'visitors' => $query->limit(200)->get(),
            'countInQueue' => $dayRows->whereNotIn('status', ['in_meeting', 'completed'])->count(),
            'countMeeting' => $dayRows->where('status', 'in_meeting')->count(),
            'countDone' => $dayRows->where('status', 'completed')->count(),
        ]);
    }
}
