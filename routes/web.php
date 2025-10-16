<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\PermissionController;

use App\Http\Controllers\Rekrutmen\{
    MonitoringController,
    IzinPrinsipController,
    KontrakController
};

Route::middleware('web')->group(function () {

    // ====== GUEST (AUTH) ======
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    // ====== AUTHENTICATED ======
    Route::middleware(['auth', 'team.scope'])->group(function () {
        Route::get('/', fn () => redirect()->route('dashboard'));
        Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

        // ====== SETTINGS ======
        Route::prefix('settings')->name('settings.')->group(function () {

            // Users
            Route::get('users', [UserController::class, 'index'])
                ->middleware('permission:users.view')->name('users.index');
            Route::post('users', [UserController::class, 'store'])
                ->middleware('permission:users.create')->name('users.store');
            Route::put('users/{user}', [UserController::class, 'update'])
                ->middleware('permission:users.update')->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])
                ->middleware('permission:users.delete')->name('users.destroy');

            // JSON role options
            Route::get('roles/options', [UserController::class, 'roleOptions'])
                ->middleware('permission:users.view')->name('roles.options');

            // Roles
            Route::get('roles', [RoleController::class, 'index'])
                ->middleware('permission:rbac.view')->name('roles.index');
            Route::post('roles', [RoleController::class, 'store'])
                ->middleware('permission:rbac.assign')->name('roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])
                ->middleware('permission:rbac.assign')->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])
                ->middleware('permission:rbac.assign')->name('roles.destroy');

            // Permissions
            Route::get('permissions', [PermissionController::class, 'index'])
                ->middleware('permission:rbac.view')->name('permissions.index');
            Route::put('permissions/{permission}', [PermissionController::class, 'update'])
                ->middleware('permission:rbac.assign')->name('permissions.update');
        });

        // ====== REKRUTMEN (pakai views & controller yang sudah ada) ======
        Route::prefix('rekrutmen')->name('rekrutmen.')->group(function () {

            // Monitoring (views/rekrutmen/monitoring.blade.php)
            Route::get('monitoring', [MonitoringController::class,'index'])
                ->middleware('permission:recruitment.view')
                ->name('monitoring');

            // Izin Prinsip (views/rekrutmen/izin-prinsip/index.blade.php) — create via modal di index
            Route::get('izin-prinsip', [IzinPrinsipController::class,'index'])
                ->middleware('permission:recruitment.view')
                ->name('izin-prinsip.index');
            Route::post('izin-prinsip', [IzinPrinsipController::class,'store'])
                ->middleware('permission:recruitment.view')
                ->name('izin-prinsip.store');
            Route::post('izin-prinsip/{req}/submit', [IzinPrinsipController::class,'submit'])
                ->middleware('permission:recruitment.view')
                ->name('izin-prinsip.submit');
            Route::post('izin-prinsip/{req}/approve', [IzinPrinsipController::class,'approve'])
                ->middleware('permission:recruitment.view|contract.approve')
                ->name('izin-prinsip.approve');
            Route::post('izin-prinsip/{req}/reject', [IzinPrinsipController::class,'reject'])
                ->middleware('permission:recruitment.view|contract.approve')
                ->name('izin-prinsip.reject');

            // Penerbitan Kontrak (views/rekrutmen/kontrak/index.blade.php) — create via modal di index
            Route::get('kontrak', [KontrakController::class,'index'])
                ->middleware('permission:contract.view')->name('kontrak.index');
            Route::post('kontrak', [KontrakController::class,'store'])
                ->middleware('permission:contract.create')->name('kontrak.store');
            Route::post('kontrak/{contract}/submit', [KontrakController::class,'submit'])
                ->middleware('permission:contract.update')->name('kontrak.submit');
            Route::post('kontrak/{contract}/approve', [KontrakController::class,'approve'])
                ->middleware('permission:contract.approve')->name('kontrak.approve');
            Route::post('kontrak/{contract}/sign', [KontrakController::class,'sign'])
                ->middleware('permission:contract.sign')->name('kontrak.sign');
        });

        // ====== PELATIHAN (langsung nembak views yang ada) ======
        Route::prefix('pelatihan')->name('pelatihan.')->group(function () {
            // views/pelatihan/monitoring.blade.php
            Route::get('monitoring', fn () => view('pelatihan.monitoring'))
                ->middleware('permission:training.view')
                ->name('monitoring');
            // views/pelatihan/izin-prinsip.blade.php
            Route::get('izin-prinsip', fn () => view('pelatihan.izin-prinsip'))
                ->middleware('permission:training.view')
                ->name('izin-prinsip');
        });

        // Logout
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
