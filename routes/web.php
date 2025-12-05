<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Public careers (one-page)
use App\Http\Controllers\Public\CareersController;
use App\Http\Controllers\Public\ApplicationController as PublicApplicationController;

// Account (modal one-page actions)
use App\Http\Controllers\Account\AccountController;

use App\Http\Controllers\Recruitment\PrincipalApprovalController;

use App\Http\Controllers\Recruitment\SalaryController;

Route::middleware('web')->group(function () {

    // ====== PUBLIC (NO AUTH) ======
    Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
    Route::post('/careers/apply', [PublicApplicationController::class, 'store'])->name('careers.apply');

    // ====== GUEST (AUTH) ======
    Route::middleware('guest')->group(function () {
        Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    // ====== AUTHENTICATED SHELL ======
    Route::middleware(['auth', 'team.scope'])->group(function () {
        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

        // Account (modal actions; project-local only)
        Route::post('/account/profile',  [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');

        // Logout
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::post('/recruitment/uraian-jabatan/preview-pdf', [PrincipalApprovalController::class, 'previewUraianPdf'])
        ->name('recruitment.uraian-jabatan.preview-pdf');
    });

    Route::post('/ajax/calculate-salary', [SalaryController::class, 'calculate'])
    ->name('api.calculate.salary');

    // ====== LOAD INTERNAL / ADMIN ROUTES ======
    require __DIR__ . '/admin.php';
});
