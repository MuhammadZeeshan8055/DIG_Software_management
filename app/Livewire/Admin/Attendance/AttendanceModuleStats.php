<?php

namespace App\Livewire\Admin\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Support\LeaveBalance;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Attendance home cards. wire:poll on the blade keeps them live.
 */
class AttendanceModuleStats extends Component
{
    public function render()
    {
        $user = auth()->user();
        $stats = [];

        if ($user) {
            $today = Carbon::now(app_timezone())->toDateString();
            $month = Carbon::now(app_timezone());
            $summary = LeaveBalance::summary($user, $month);

            $presentToday = AttendanceRecord::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $today)
                ->whereNotNull('check_in_at')
                ->exists();

            $onLeaveToday = LeaveRequest::isOnApprovedLeave($user->id, $today);

            $holidaysThisMonth = Holiday::query()
                ->whereBetween('date', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ])
                ->count();

            $stats = [
                [
                    'label' => 'Leaves Used',
                    'value' => $summary['used']['label'],
                    'hint' => $summary['month_label'],
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Leaves Left',
                    'value' => $summary['left']['label'],
                    'hint' => 'Allowance '.$summary['allowed_label'],
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Pending Leave',
                    'value' => (string) $summary['pending_count'],
                    'hint' => 'Awaiting approval',
                    'tone' => 'navy',
                ],
                [
                    'label' => 'Today',
                    'value' => $onLeaveToday ? 'On leave' : ($presentToday ? 'Present' : '—'),
                    'hint' => $holidaysThisMonth.' holiday(s) this month',
                    'tone' => $onLeaveToday ? 'amber' : ($presentToday ? 'green' : 'red'),
                ],
            ];
        }

        return view('livewire.admin.attendance.attendance-module-stats', [
            'stats' => $stats,
        ]);
    }
}
