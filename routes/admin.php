<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Access\UserController;
use App\Http\Controllers\Admin\Access\RoleController;
use App\Http\Controllers\Admin\Access\PermissionController;

use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Self\ProfileController;

use App\Http\Controllers\Recruitment\MonitoringController as RecruitmentMonitoringController;
use App\Http\Controllers\Recruitment\PrincipalApprovalController as RecruitmentApprovalController;
use App\Http\Controllers\Recruitment\ContractController;
use App\Http\Controllers\Recruitment\PublishingController;

use App\Http\Controllers\Training\MonitoringController as TrainingMonitoringController;
use App\Http\Controllers\Training\PrincipalApprovalController as TrainingApprovalController;

// NEW: Back Office Org (Directorates & Units)
use App\Http\Controllers\Admin\Org\OrgController;

Route::middleware(['web', 'auth', 'team.scope'])->group(function () {

    // =========================
    // SETTINGS · ACCESS
    // =========================
    Route::prefix('admin/settings/access')->name('admin.')->group(function () {
        // Users
        Route::get('users',           [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::post('users',          [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::put('users/{user}',    [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

        // Role options (JSON) untuk modal
        Route::get('roles/options',   [UserController::class, 'roleOptions'])->middleware('permission:users.view')->name('roles.options');

        // Roles (RBAC)
        Route::get('roles',           [RoleController::class, 'index'])->middleware('permission:rbac.view')->name('roles.index');
        Route::post('roles',          [RoleController::class, 'store'])->middleware('permission:rbac.assign')->name('roles.store');
        Route::put('roles/{role}',    [RoleController::class, 'update'])->middleware('permission:rbac.assign')->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:rbac.assign')->name('roles.destroy');

        // Permissions (RBAC)
        Route::get('permissions',              [PermissionController::class, 'index'])->middleware('permission:rbac.view')->name('permissions.index');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:rbac.assign')->name('permissions.update');
    });

    // =========================
    // EMPLOYEES
    // =========================
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('employees',      [EmployeeController::class, 'index'])->middleware('permission:employees.view')->name('employees.index');
        Route::get('employees/{id}', [EmployeeController::class, 'show'])->middleware('permission:employees.view')->name('employees.show');
    });

    // =========================
    // BACK OFFICE · MASTER DATA GROUP (Directorates & Units)
    // =========================
    Route::prefix('admin/org')->name('admin.org.')->middleware('permission:org.view')->group(function () {
        // Single page
        Route::get('/', [OrgController::class, 'index'])->name('index');

        // Tree JSON
        Route::get('tree', [OrgController::class, 'tree'])->name('tree');

        // Directorates CRUD (AJAX)
        Route::get('directorates',            [OrgController::class, 'directorates'])->name('directorates.list');
        Route::get('directorates/options',    [OrgController::class, 'directorateOptions'])->name('directorates.options');
        Route::post('directorates',           [OrgController::class, 'directorateStore'])->middleware('permission:org.create')->name('directorates.store');
        Route::put('directorates/{id}',       [OrgController::class, 'directorateUpdate'])->middleware('permission:org.update')->name('directorates.update');
        Route::delete('directorates/{id}',    [OrgController::class, 'directorateDestroy'])->middleware('permission:org.delete')->name('directorates.destroy');

        // Units CRUD (AJAX)
        Route::get('units',                   [OrgController::class, 'units'])->name('units.list');
        Route::post('units',                  [OrgController::class, 'unitStore'])->middleware('permission:org.create')->name('units.store');
        Route::put('units/{id}',              [OrgController::class, 'unitUpdate'])->middleware('permission:org.update')->name('units.update');
        Route::put('units/{id}/reassign',     [OrgController::class, 'unitReassign'])->middleware('permission:org.update')->name('units.reassign');
        Route::delete('units/{id}',           [OrgController::class, 'unitDestroy'])->middleware('permission:org.delete')->name('units.destroy');
    });

    // =========================
    // RECRUITMENT
    // =========================
    Route::prefix('recruitment')->name('recruitment.')->group(function () {
        // Monitoring Rekrutmen (Flow dari Izin Prinsip s/d Kontrak)
        Route::get('monitoring', [RecruitmentMonitoringController::class, 'index'])
            ->middleware('permission:recruitment.view')->name('monitoring');

        // Izin Prinsip (principal approval)
        Route::get('principal-approval', [RecruitmentApprovalController::class, 'index'])
            ->middleware('permission:recruitment.view')->name('principal-approval.index');

        Route::post('principal-approval', [RecruitmentApprovalController::class, 'store'])
            ->middleware('permission:recruitment.update')->name('principal-approval.store');

        // NEW: update draft Izin Prinsip (edit)
        Route::put('principal-approval/{req}', [RecruitmentApprovalController::class, 'update'])
            ->middleware('permission:recruitment.update')->name('principal-approval.update');

        Route::post('principal-approval/{req}/submit',  [RecruitmentApprovalController::class, 'submit'])
            ->middleware('permission:recruitment.submit')->name('principal-approval.submit');

        Route::post('principal-approval/{req}/approve', [RecruitmentApprovalController::class, 'approve'])
            ->middleware('permission:recruitment.approve')->name('principal-approval.approve');

        Route::post('principal-approval/{req}/reject',  [RecruitmentApprovalController::class, 'reject'])
            ->middleware('permission:recruitment.reject')->name('principal-approval.reject');

        // Penerbitan Kontrak (SPK / PKWT) + e-sign
        Route::get('contracts', [ContractController::class, 'index'])
            ->middleware('permission:contract.view')->name('contracts.index');

        // detail kontrak (JSON untuk modal / inline)
        Route::get('contracts/{contract}', [ContractController::class, 'show'])
            ->middleware('permission:contract.view')->name('contracts.show');

        Route::post('contracts', [ContractController::class, 'store'])
            ->middleware('permission:contract.create')->name('contracts.store');

        // submit draft (SDM Unit → kepala unit / approver)
        Route::post('contracts/{contract}/submit',  [ContractController::class, 'submit'])
            ->middleware('permission:contract.update')->name('contracts.submit');

        // approve kontrak (kepala unit / DHC / Dir SDM sesuai flowmap)
        Route::post('contracts/{contract}/approve', [ContractController::class, 'approve'])
            ->middleware('permission:contract.approve')->name('contracts.approve');

        // e-sign (draw + cam + geo loc)
        Route::post('contracts/{contract}/sign',    [ContractController::class, 'sign'])
            ->middleware('permission:contract.sign')->name('contracts.sign');

        // Publishing lowongan dari Izin Prinsip
        Route::middleware('permission:recruitment.update')->group(function () {
            Route::get('requests/{req}/publish',  [PublishingController::class, 'edit'])->name('publish.edit');
            Route::put('requests/{req}/publish',  [PublishingController::class, 'update'])->name('publish.update');
            Route::post('requests/{req}/toggle',  [PublishingController::class, 'toggle'])->name('publish.toggle');
        });
    });

    // =========================
    // TRAINING
    // =========================
    Route::prefix('training')->name('training.')->group(function () {
        Route::get('monitoring', fn () => view('training.monitoring'))
            ->middleware('permission:training.view')->name('monitoring');

        Route::get('principal-approval', fn () => view('training.principal-approval'))
            ->middleware('permission:training.view')->name('principal-approval');
    });
});
