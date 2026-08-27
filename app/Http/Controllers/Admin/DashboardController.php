<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'modules' => config('admin.modules'),
            'quickActions' => config('admin.quick_actions'),
            'workspace' => config('admin_workspace'),
            'pageTitle' => 'Operations Overview',
            'breadcrumb' => ['Employee Portal', 'Workspace'],
        ]);
    }
}
