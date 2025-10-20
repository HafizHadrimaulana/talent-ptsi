<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Access\UserController;
use App\Http\Controllers\Admin\Access\RoleController;
use App\Http\Controllers\Admin\Access\PermissionController;
use App\Http\Controllers\Recruitment\{
    MonitoringController,
    PrincipalApprovalController,
    ContractController
};
// optional: kalau ada controller khusus training
use App\Http\Controllers\Training\{
    MonitoringController as TrainingMonitoringController,
    PrincipalApprovalController as TrainingApprovalController
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
        Route::prefix('admin/settings/access')->name('admin.')->group(function () {
            Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
            Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
            Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

            Route::get('roles/options', [UserController::class, 'roleOptions'])->middleware('permission:users.view')->name('roles.options');

            Route::get('roles', [RoleController::class, 'index'])->middleware('permission:rbac.view')->name('roles.index');
            Route::post('roles', [RoleController::class, 'store'])->middleware('permission:rbac.assign')->name('roles.store');
            Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:rbac.assign')->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:rbac.assign')->name('roles.destroy');

            Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:rbac.view')->name('permissions.index');
            Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:rbac.assign')->name('permissions.update');
        });

        // ====== RECRUITMENT ======
        Route::prefix('recruitment')->name('recruitment.')->group(function () {
            Route::get('monitoring', [MonitoringController::class,'index'])
                ->middleware('permission:recruitment.view')->name('monitoring');

            Route::get('principal-approval', [PrincipalApprovalController::class,'index'])
                ->middleware('permission:recruitment.view')->name('principal-approval.index');
            Route::post('principal-approval', [PrincipalApprovalController::class,'store'])
                ->middleware('permission:recruitment.view')->name('principal-approval.store');
            Route::post('principal-approval/{req}/submit', [PrincipalApprovalController::class,'submit'])
                ->middleware('permission:recruitment.view')->name('principal-approval.submit');
            Route::post('principal-approval/{req}/approve', [PrincipalApprovalController::class,'approve'])
                ->middleware('permission:recruitment.view|contract.approve')->name('principal-approval.approve');
            Route::post('principal-approval/{req}/reject', [PrincipalApprovalController::class,'reject'])
                ->middleware('permission:recruitment.view|contract.approve')->name('principal-approval.reject');

            Route::get('contracts', [ContractController::class,'index'])
                ->middleware('permission:contract.view')->name('contracts.index');
            Route::post('contracts', [ContractController::class,'store'])
                ->middleware('permission:contract.create')->name('contracts.store');
            Route::post('contracts/{contract}/submit', [ContractController::class,'submit'])
                ->middleware('permission:contract.update')->name('contracts.submit');
            Route::post('contracts/{contract}/approve', [ContractController::class,'approve'])
                ->middleware('permission:contract.approve')->name('contracts.approve');
            Route::post('contracts/{contract}/sign', [ContractController::class,'sign'])
                ->middleware('permission:contract.sign')->name('contracts.sign');
        });

        // ====== TRAINING ======
        Route::prefix('training')->name('training.')->group(function () {
            // Monitoring
            Route::get('monitoring', fn () => view('training.monitoring'))
                ->middleware('permission:training.view')
                ->name('monitoring');

            // Principal Approval
            Route::get('principal-approval', fn () => view('training.principal-approval'))
                ->middleware('permission:training.view')
                ->name('principal-approval');
        });

        // ====== LOGOUT ======
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
