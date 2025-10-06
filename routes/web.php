<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::middleware('web')->group(function () {
    // Guest: login
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    // Authed
    Route::middleware(['auth','team.scope'])->group(function () {
        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

        // contoh rute protected (sudah ada di jawaban sebelumnya)
        Route::get('/users', fn() => 'users index')
            ->middleware('permission:users.view')->name('users.index');

        Route::get('/reports/export', fn() => 'export')
            ->middleware(['role:Superadmin|SDM Unit','permission:reports.export'])->name('reports.export');

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
