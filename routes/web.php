<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Permissions\PermissionController;
use App\Http\Controllers\Settings\SettingController;

Route::middleware('web')->group(function () {

    // Guest: login
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    // Authed routes
    Route::middleware(['auth','team.scope'])->group(function () {

        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

        // === Core Example Protected ===
        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:users.view')->name('users.index');

        Route::get('/reports/export', fn() => 'export')
            ->middleware(['role:Superadmin|SDM Unit','permission:reports.export'])
            ->name('reports.export');

        // === Management Modules ===
        Route::resource('users', UserController::class)
            ->names('users')
            ->middleware('permission:users.view|users.manage');

        Route::resource('roles', RoleController::class)
            ->except(['show'])
            ->names('roles')
            ->middleware('permission:roles.view|roles.manage');

        Route::resource('permissions', PermissionController::class)
            ->only(['index','edit','update'])
            ->names('permissions')
            ->middleware('permission:permissions.view|permissions.manage');

        Route::resource('settings', SettingController::class)
            ->only(['index','store'])
            ->names('settings')
            ->middleware('permission:settings.manage');

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
