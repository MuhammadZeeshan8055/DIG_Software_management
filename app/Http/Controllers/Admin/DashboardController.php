<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'navigation' => config('admin.navigation'),
            'modules' => config('admin.modules'),
            'quickActions' => config('admin.quick_actions'),
            'pageTitle' => 'Operations Overview',
            'breadcrumb' => ['Employee Portal', 'Workspace'],
        ]);
    }
}
