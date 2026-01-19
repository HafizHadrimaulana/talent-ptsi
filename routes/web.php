<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Public\CareersController;
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Recruitment\SalaryController;

Route::middleware('web')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/',  [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/', [AuthenticatedSessionController::class, 'store'])->name('login.store');
        Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
        Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
    });

    Route::middleware(['auth', 'team.scope'])->group(function () {        
        // Route Akun & Utilitas Umum
        Route::post('/account/profile',  [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        
        // API Helpers (Tetap disini atau pindah ke api.php/admin.php tergantung kebutuhan JS)
        Route::post('/ajax/calculate-salary', [SalaryController::class, 'calculate'])->name('api.calculate.salary');
        Route::get('/api/project-codes', [\App\Http\Controllers\ProjectCodeController::class, 'index'])
            ->name('api.project_codes.index');
    });

    require __DIR__ . '/admin.php';
});