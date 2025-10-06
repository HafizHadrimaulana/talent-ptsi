<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\PermissionController;

Route::middleware('web')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth','team.scope'])->group(function () {
        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

        // SETTINGS (modal-first)
        Route::prefix('settings')->name('settings.')->group(function () {
            // Users: index + store + update + destroy (NO create/edit pages)
            Route::get('users', [UserController::class,'index'])
                ->middleware('permission:users.view')->name('users.index');
            Route::post('users', [UserController::class,'store'])
                ->middleware('permission:users.create')->name('users.store');
            Route::put('users/{user}', [UserController::class,'update'])
                ->middleware('permission:users.update')->name('users.update');
            Route::delete('users/{user}', [UserController::class,'destroy'])
                ->middleware('permission:users.delete')->name('users.destroy');

            // Roles
            Route::get('roles', [RoleController::class,'index'])
                ->middleware('permission:rbac.view')->name('roles.index');
            Route::post('roles', [RoleController::class,'store'])
                ->middleware('permission:rbac.assign')->name('roles.store');
            Route::put('roles/{role}', [RoleController::class,'update'])
                ->middleware('permission:rbac.assign')->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class,'destroy'])
                ->middleware('permission:rbac.assign')->name('roles.destroy');

            // Permissions (rename only)
            Route::get('permissions', [PermissionController::class,'index'])
                ->middleware('permission:rbac.view')->name('permissions.index');
            Route::put('permissions/{permission}', [PermissionController::class,'update'])
                ->middleware('permission:rbac.assign')->name('permissions.update');
        });

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
