<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Admin\Attendance\AttendanceGate;
use App\Models\Invoice;
use App\Models\TicketImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'head', 'post'], '/', function () {
    return redirect()->route('login');
});

Route::get('/verify/invoice/{token}', function (string $token) {
    $invoice = Invoice::query()
        ->where('verification_token', $token)
        ->with(['items', 'payments.receivingAccount'])
        ->firstOrFail();

    return view('invoices.verify', [
        'invoice' => $invoice,
    ]);
})->middleware('throttle:60,1')->name('invoices.verify');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'attendance.started'])
    ->name('dashboard');

Route::get('/attendance/start', AttendanceGate::class)
    ->middleware(['auth', 'verified'])
    ->name('attendance.gate');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/ticket-imports/{ticketImport}/document', function (Request $request, TicketImport $ticketImport) {
        $ticketImport->load(['user', 'paymentEntry']);

        return view('ticket-imports.document', [
            'import' => $ticketImport,
            'autoPrint' => $request->boolean('print'),
        ]);
    })->name('ticket-imports.document');

    Route::get('/invoices/{invoice}/document', function (Request $request, Invoice $invoice) {
        abort_unless($request->user()->canView('accounts', 'invoices'), 403);

        $invoice->load(['items', 'payments.receivingAccount', 'user']);

        return view('invoices.document', [
            'invoice' => $invoice,
            'autoPrint' => $request->boolean('print'),
        ]);
    })->name('invoices.document');
});

require __DIR__.'/auth.php';
