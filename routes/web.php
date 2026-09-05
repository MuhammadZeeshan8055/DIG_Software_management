<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Admin\Attendance\AttendanceGate;
use App\Models\TicketImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

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
});

require __DIR__.'/auth.php';
