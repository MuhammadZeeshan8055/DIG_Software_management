<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'modules' => $this->modulesForUser(),
            'quickActions' => config('admin.quick_actions'),
            'workspace' => config('admin_workspace'),
            'pageTitle' => 'Operations Overview',
            'breadcrumb' => ['Employee Portal', 'Workspace'],
        ]);
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

                // Settings → only admins / super_admins
                if ($moduleKey === 'settings') {
                    return $user->canManageUsers() ? $module : null;
                }

                // Admin / super_admin see everything
                if ($user->isAdmin()) {
                    return $module;
                }

                // Staff: keep only features they can view
                $children = collect($module['children'] ?? [])
                    ->filter(fn (array $child) => $user->canView($moduleKey, $child['key'] ?? ''))
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
