<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\LeaveBalance;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'modules' => $this->modulesForUser(),
            'quickActions' => config('admin.quick_actions'),
            'workspace' => $this->workspaceForUser(),
            'pageTitle' => 'Operations Overview',
            'breadcrumb' => ['Employee Portal', 'Workspace'],
        ]);
    }

    /**
     * Workspace stats — Settings + Attendance use real counts.
     */
    protected function workspaceForUser(): array
    {
        $workspace = config('admin_workspace', []);
        $user = auth()->user();

        $totalUsers = User::count();
        $staffCount = User::where('role', 'staff')->count();
        $adminCount = User::whereIn('role', ['admin', 'super_admin'])->count();

        $workspace['settings']['stats'] = [
            [
                'label' => 'Total Users',
                'value' => (string) $totalUsers,
                'hint' => 'All accounts',
                'tone' => 'blue',
            ],
            [
                'label' => 'Staff',
                'value' => (string) $staffCount,
                'hint' => 'Limited access',
                'tone' => 'amber',
            ],
            [
                'label' => 'Admins',
                'value' => (string) $adminCount,
                'hint' => 'Full access',
                'tone' => 'navy',
            ],
        ];

        $workspace['attendance']['stats'] = $this->attendanceStatsFor($user);

        return $workspace;
    }

    /**
     * Four cards on Attendance module home.
     */
    protected function attendanceStatsFor(User $user): array
    {
        $today = Carbon::now(app_timezone())->toDateString();
        $month = Carbon::now(app_timezone());
        $summary = LeaveBalance::summary($user, $month);

        $presentToday = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->whereNotNull('check_in_at')
            ->exists();

        $onLeaveToday = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->exists();

        $holidaysThisMonth = Holiday::query()
            ->whereBetween('date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->count();

        return [
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

    /**
     * Show only modules / features this user is allowed to see.
     */
    protected function modulesForUser(): array
    {
        $user = auth()->user();
        $modules = config('admin.modules', []);

        return collect($modules)
            ->map(function (array $module) use ($user) {
                $moduleKey = $module['key'] ?? '';

                if ($moduleKey === 'settings') {
                    return $user->canManageUsers() ? $module : null;
                }

                if ($user->isAdmin()) {
                    return $module;
                }

                $children = collect($module['children'] ?? [])
                    ->reject(fn (array $child) => ! empty($child['admin_only']))
                    ->filter(function (array $child) use ($user, $moduleKey) {
                        $feature = $child['key'] ?? '';

                        if ($moduleKey === 'attendance' && $feature === 'my-daily-attendance') {
                            return $user->permissions()
                                ->where('module_key', 'attendance')
                                ->exists();
                        }

                        return $user->canView($moduleKey, $feature);
                    })
                    ->values()
                    ->all();

                if ($children === []) {
                    return null;
                }

                $module['children'] = $children;

                return $module;
            })
            ->filter()
            ->values()
            ->all();
    }
}
