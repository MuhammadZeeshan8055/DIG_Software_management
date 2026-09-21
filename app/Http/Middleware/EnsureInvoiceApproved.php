<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInvoiceApproved
{
    /**
     * Allow access if the invoice is approved, or if the user is admin/super_admin.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $invoice = $request->route('invoice');

        if (! $invoice || $invoice->isApproved()) {
            return $next($request);
        }

        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403, 'This invoice is awaiting admin approval.');

        return $next($request);
    }
}
