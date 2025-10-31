<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Access\UserController;
use App\Http\Controllers\Admin\Access\RoleController;
use App\Http\Controllers\Admin\Access\PermissionController;

use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Self\ProfileController;

// Recruitment (internal)
use App\Http\Controllers\Recruitment\{
    MonitoringController,
    PrincipalApprovalController,
    ContractController,
    PublishingController
};

// Training (internal, optional)
use App\Http\Controllers\Training\{
    MonitoringController as TrainingMonitoringController,
    PrincipalApprovalController as TrainingApprovalController,
    DashboardController as TrainingDashboardController
};

Route::middleware(['web', 'auth', 'team.scope'])->group(function () {
    Route::prefix('admin/settings/access')->name('admin.')->group(function () {
        Route::get('users',           [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::post('users',          [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::put('users/{user}',    [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

        Route::get('roles/options',   [UserController::class, 'roleOptions'])->middleware('permission:users.view')->name('roles.options');

        Route::get('roles',           [RoleController::class, 'index'])->middleware('permission:rbac.view')->name('roles.index');
        Route::post('roles',          [RoleController::class, 'store'])->middleware('permission:rbac.assign')->name('roles.store');
        Route::put('roles/{role}',    [RoleController::class, 'update'])->middleware('permission:rbac.assign')->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:rbac.assign')->name('roles.destroy');

        Route::get('permissions',              [PermissionController::class, 'index'])->middleware('permission:rbac.view')->name('permissions.index');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:rbac.assign')->name('permissions.update');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('employees',      [EmployeeController::class, 'index'])->middleware('permission:employees.view')->name('employees.index');
        Route::get('employees/{id}', [EmployeeController::class, 'show'])->middleware('permission:employees.view')->name('employees.show');
    });

    Route::prefix('recruitment')->name('recruitment.')->group(function () {
        Route::get('monitoring', [RecruitmentMonitoringController::class, 'index'])
            ->middleware('permission:recruitment.view')->name('monitoring');

        Route::get('principal-approval', [RecruitmentApprovalController::class, 'index'])
            ->middleware('permission:recruitment.view')->name('principal-approval.index');

        Route::post('principal-approval', [RecruitmentApprovalController::class, 'store'])
            ->middleware('permission:recruitment.update')->name('principal-approval.store');

        Route::post('principal-approval/{req}/submit',  [RecruitmentApprovalController::class, 'submit'])
            ->middleware('permission:recruitment.submit')->name('principal-approval.submit');

        Route::post('principal-approval/{req}/approve', [RecruitmentApprovalController::class, 'approve'])
            ->middleware('permission:recruitment.approve')->name('principal-approval.approve');

        Route::post('principal-approval/{req}/reject',  [RecruitmentApprovalController::class, 'reject'])
            ->middleware('permission:recruitment.reject')->name('principal-approval.reject');

        Route::get('contracts', [ContractController::class, 'index'])
            ->middleware('permission:contract.view')->name('contracts.index');

        Route::post('contracts', [ContractController::class, 'store'])
            ->middleware('permission:contract.create')->name('contracts.store');

        Route::post('contracts/{contract}/submit',  [ContractController::class, 'submit'])
            ->middleware('permission:contract.update')->name('contracts.submit');

        Route::post('contracts/{contract}/approve', [ContractController::class, 'approve'])
            ->middleware('permission:contract.approve')->name('contracts.approve');

        Route::post('contracts/{contract}/sign',    [ContractController::class, 'sign'])
            ->middleware('permission:contract.sign')->name('contracts.sign');

        Route::middleware('permission:recruitment.update')->group(function () {
            Route::get('requests/{req}/publish', [PublishingController::class, 'edit'])->name('publish.edit');
            Route::put('requests/{req}/publish', [PublishingController::class, 'update'])->name('publish.update');
            Route::post('requests/{req}/toggle', [PublishingController::class, 'toggle'])->name('publish.toggle');
        });
    });

    Route::prefix('training')->name('training.')->group(function () {

        // Dashboard
        Route::get('dashboard', [TrainingDashboardController::class,'dataDashboard'])
            ->middleware('permission:training.view')->name('dashboard');

        // Monitoring
        Route::get('monitoring', fn () => view('training.monitoring.monitoring'))
            ->middleware('permission:training.view')->name('monitoring');
        Route::post('import', [TrainingMonitoringController::class,'import'])
            ->name('import');
        
        Route::get('list', [TrainingMonitoringController::class,'list'])
            ->name('list');
        Route::post('input', [TrainingMonitoringController::class,'input'])
            ->name('input');
        Route::get('edit/{id}/get-data', [TrainingMonitoringController::class,'getEditData'])
            ->name('get-data');
        Route::post('edit/{id}', [TrainingMonitoringController::class,'update'])
            ->name('update');
        Route::delete('delete/{id}', [TrainingMonitoringController::class,'destroy'])
            ->name('delete');

        Route::post('monitoring/{id}/approve', [TrainingMonitoringController::class,'approveStatus'])
            ->name('monitoring.approveStatus');
        Route::post('monitoring/{id}/reject', [TrainingMonitoringController::class,'rejectStatus'])
            ->name('monitoring.rejectStatus');
        Route::post('all-approve', [TrainingMonitoringController::class,'updateAllStatus'])
            ->name('allApprove');
        Route::post('bulk-approve', [TrainingMonitoringController::class,'bulkApprove'])
            ->name('bulkApprove');

        Route::get('download-template', [TrainingMonitoringController::class,'downloadTemplate'])
            ->name('download-template');
        
        // Principal Approval
        Route::get('principal-approval', fn () => view('training.principal-approval.principal-approval'))
            ->middleware('permission:training.view')->name('principal-approval');
    });
});
