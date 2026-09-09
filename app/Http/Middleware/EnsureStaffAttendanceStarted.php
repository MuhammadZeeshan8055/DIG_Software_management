<?php

namespace App\Http\Middleware;

use App\Models\LeaveRequest;
use App\Support\AttendancePunch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff must start shift before dashboard.
 * Exception: admin, or staff who is on leave today.
 */
class EnsureStaffAttendanceStarted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not logged in → continue
        if (! $user) {
            return $next($request);
        }

        // Admin → no gate
        if ($user->isAdmin()) {
            return $next($request);
        }

        // On leave today → open dashboard (no punch needed)
        $onLeave = LeaveRequest::isOnApprovedLeave($user->id);
        if ($onLeave === true) {
            return $next($request);
        }

        // Already started shift today → open dashboard
        $started = AttendancePunch::hasStartedToday($user);
        if ($started === true) {
            return $next($request);
        }

        // Otherwise → go to Start Shift page
        return redirect()->route('attendance.gate');
    }
}
