<?php

namespace App\Http\Middleware;

use App\Support\AttendancePunch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff must Start Shift today before opening the portal.
 * Admin / super_admin skip this gate.
 */
class EnsureStaffAttendanceStarted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Admins manage the system — no attendance gate
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Staff already checked in today → allow portal
        if (AttendancePunch::hasStartedToday($user)) {
            return $next($request);
        }

        return redirect()->route('attendance.gate');
    }
}
