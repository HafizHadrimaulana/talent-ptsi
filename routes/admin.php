<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Access\UserController;
use App\Http\Controllers\Admin\Access\RoleController;
use App\Http\Controllers\Admin\Access\PermissionController;

use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Self\ProfileController;

// Recruitment (internal)
use App\Http\Controllers\Recruitment\MonitoringController as RecruitmentMonitoringController;
use App\Http\Controllers\Recruitment\PrincipalApprovalController as RecruitmentApprovalController;
use App\Http\Controllers\Recruitment\ContractController;
use App\Http\Controllers\Recruitment\PublishingController;

// Training (internal, optional)
use App\Http\Controllers\Training\{
    MonitoringController as TrainingMonitoringController,
    PrincipalApprovalController as TrainingApprovalController,
    DashboardController as TrainingDashboardController,
    TrainingRequestController
};

// Back Office Org (Directorates & Units)
use App\Http\Controllers\Admin\Org\OrgController;

Route::middleware(['web', 'auth', 'team.scope'])->group(function () {

    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::prefix('admin/settings/access')->name('admin.')->group(function () {
        Route::get('users',           [UserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');

        Route::post('users',          [UserController::class, 'store'])
            ->middleware('permission:users.create')
            ->name('users.store');

        Route::put('users/{user}',    [UserController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('users.update');

        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete')
            ->name('users.destroy');

        Route::get('roles/options',   [UserController::class, 'roleOptions'])
            ->middleware('permission:users.view')
            ->name('roles.options');

        Route::get('roles',           [RoleController::class, 'index'])
            ->middleware('permission:rbac.view')
            ->name('roles.index');

        Route::post('roles',          [RoleController::class, 'store'])
            ->middleware('permission:rbac.assign')
            ->name('roles.store');

        Route::put('roles/{role}',    [RoleController::class, 'update'])
            ->middleware('permission:rbac.assign')
            ->name('roles.update');

        Route::delete('roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:rbac.assign')
            ->name('roles.destroy');

        Route::get('permissions',              [PermissionController::class, 'index'])
            ->middleware('permission:rbac.view')
            ->name('permissions.index');

        Route::put('permissions/{permission}', [PermissionController::class, 'update'])
            ->middleware('permission:rbac.assign')
            ->name('permissions.update');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('employees',      [EmployeeController::class, 'index'])
            ->middleware('permission:employees.view')
            ->name('employees.index');

        Route::get('employees/{id}', [EmployeeController::class, 'show'])
            ->middleware('permission:employees.view')
            ->name('employees.show');

        Route::get('employees/positions/options', [EmployeeController::class, 'positionOptions'])
            ->middleware('permission:employees.view')
            ->name('employees.positions.options');
    });

    Route::prefix('admin/org')
        ->name('admin.org.')
        ->middleware('permission:org.view')
        ->group(function () {
            Route::get('/', [OrgController::class, 'index'])->name('index');
            Route::get('tree', [OrgController::class, 'tree'])->name('tree');
            Route::get('directorates',            [OrgController::class, 'directorates'])->name('directorates.list');
            Route::get('directorates/options',    [OrgController::class, 'directorateOptions'])->name('directorates.options');
            Route::post('directorates',           [OrgController::class, 'directorateStore'])
                ->middleware('permission:org.create')
                ->name('directorates.store');
            Route::put('directorates/{id}',       [OrgController::class, 'directorateUpdate'])
                ->middleware('permission:org.update')
                ->name('directorates.update');
            Route::delete('directorates/{id}',    [OrgController::class, 'directorateDestroy'])
                ->middleware('permission:org.delete')
                ->name('directorates.destroy');

            Route::get('units',                   [OrgController::class, 'units'])->name('units.list');
            Route::post('units',                  [OrgController::class, 'unitStore'])
                ->middleware('permission:org.create')
                ->name('units.store');
            Route::put('units/{id}',              [OrgController::class, 'unitUpdate'])
                ->middleware('permission:org.update')
                ->name('units.update');
            Route::put('units/{id}/reassign',     [OrgController::class, 'unitReassign'])
                ->middleware('permission:org.update')
                ->name('units.reassign');
            Route::delete('units/{id}',           [OrgController::class, 'unitDestroy'])
                ->middleware('permission:org.delete')
                ->name('units.destroy');
        });

    Route::prefix('recruitment')->name('recruitment.')->group(function () {
        Route::get('monitoring', [RecruitmentMonitoringController::class, 'index'])
            ->middleware('permission:recruitment.view')
            ->name('monitoring');

        Route::get('principal-approval', [RecruitmentApprovalController::class, 'index'])
            ->middleware('permission:recruitment.view')
            ->name('principal-approval.index');

        Route::post('principal-approval', [RecruitmentApprovalController::class, 'store'])
            ->middleware('permission:recruitment.update')
            ->name('principal-approval.store');

        Route::put('principal-approval/{req}', [RecruitmentApprovalController::class, 'update'])
            ->middleware('permission:recruitment.update')
            ->name('principal-approval.update');

        Route::post('principal-approval/{req}/submit',  [RecruitmentApprovalController::class, 'submit'])
            ->middleware('permission:recruitment.submit')
            ->name('principal-approval.submit');

        Route::post('principal-approval/{req}/approve', [RecruitmentApprovalController::class, 'approve'])
            ->middleware('permission:recruitment.approve')
            ->name('principal-approval.approve');

        Route::post('principal-approval/{req}/reject',  [RecruitmentApprovalController::class, 'reject'])
            ->middleware('permission:recruitment.reject')
            ->name('principal-approval.reject');

        Route::get('contracts', [ContractController::class, 'index'])
            ->middleware('permission:contract.view')
            ->name('contracts.index');

        Route::get('contracts/base-options', [ContractController::class, 'baseOptions'])
            ->middleware('permission:contract.view')
            ->name('contracts.base-options');

        Route::get('contracts/{contract}', [ContractController::class, 'show'])
            ->middleware('permission:contract.view')
            ->name('contracts.show');

        Route::post('contracts', [ContractController::class, 'store'])
            ->middleware('permission:contract.create')
            ->name('contracts.store');

        Route::put('contracts/{contract}', [ContractController::class, 'update'])
            ->middleware('permission:contract.update')
            ->name('contracts.update');

        Route::post('contracts/{contract}/submit',  [ContractController::class, 'submit'])
            ->middleware('permission:contract.update')
            ->name('contracts.submit');

        Route::post('contracts/{contract}/approve', [ContractController::class, 'approve'])
            ->middleware('permission:contract.approve')
            ->name('contracts.approve');

        Route::post('contracts/{contract}/reject',  [ContractController::class, 'reject'])
            ->middleware('permission:contract.approve')
            ->name('contracts.reject');

        Route::post('contracts/{contract}/sign',    [ContractController::class, 'sign'])
            ->middleware('permission:contract.sign')
            ->name('contracts.sign');

        Route::middleware('permission:recruitment.update')->group(function () {
            Route::get('requests/{req}/publish',  [PublishingController::class, 'edit'])
                ->name('publish.edit');

            Route::put('requests/{req}/publish',  [PublishingController::class, 'update'])
                ->name('publish.update');

            Route::post('requests/{req}/toggle',  [PublishingController::class, 'toggle'])
                ->name('publish.toggle');
        });
    });

    Route::prefix('training')->name('training.')->group(function () {

        // Dashboard
        Route::get('dashboard', [TrainingDashboardController::class,'dataDashboard'])
            ->middleware('permission:training.view')->name('dashboard');
        Route::get('dashboard/data-evaluation', [TrainingDashboardController::class,'getDataEvaluation'])
            ->name('dashboard.data-evaluation');

        Route::get('dashboard/{id}/detail-evaluation', [TrainingDashboardController::class,'getDataEvaluationByID'])
            ->name('dashboard.detail-evaluation');

        Route::post('dashboard/input-evaluation', [TrainingDashboardController::class,'inputEvaluation'])
            ->name('dashboard.input-evaluation');
        Route::post('dashboard/{id}/update-jenis-pelatihan', [TrainingDashboardController::class,'updateJenisPelatihan'])
             ->name('dashboard.update-jenis-pelatihan');

        Route::get('dashboard/{id}/get-detail-evaluation', [TrainingDashboardController::class,'getDetailEvaluation'])
            ->name('dashboard.get-detail-evaluation');

        Route::post('dashboard/upload-certif-evaluation', [TrainingDashboardController::class,'uploadCertifEvaluation'])
            ->name('dashboard.upload-certif-evaluation');
        Route::post('dashboard/{id}/update-realisasi-date', [TrainingDashboardController::class,'updateRealisasiDate'])
            ->name('dashboard.update-realisasi-date');

        // Training Request
        //// dhc unit
        Route::get('training-request', [TrainingRequestController::class, 'index'])
            ->name('training-request');
        Route::post('training-request/import-lna', [TrainingRequestController::class, 'importLna'])
            ->name('training-request.import-lna');
        Route::get('training-request/get-data-lna', [TrainingRequestController::class, 'getDataLna'])
            ->name('training-request.get-data-lna');

        Route::get('training-request/{id}/get-lna-by-id', [TrainingRequestController::class, 'getLnaById'])
            ->name('training-request.edit-data-lna');
        Route::post('training-request/{id}/edit-data-lna', [TrainingRequestController::class, 'editDataLna'])
            ->name('training-request.update-data-lna');

        Route::delete('training-request/{id}/delete-lna', [TrainingRequestController::class, 'destroyLna'])
            ->name('training-request.delete-lna');
        Route::delete('training-request/{id}/delete-training-request', [TrainingRequestController::class, 'destroyTrainingRequest'])
            ->name('training-request.delete-training-request');

        Route::post('training-request/input-lna', [TrainingRequestController::class, 'inputLna'])
            ->name('training-request.input-lna');
        Route::get('training-request/get-data-units', [TrainingRequestController::class, 'getDataUnits'])
            ->name('training-request.get-data-units');

        //// sdm unit
        Route::get('training-request/data-request', [TrainingRequestController::class, 'getDataRequest'])
            ->name('training-request');
        Route::get('training-request/training-references/{id}', [TrainingRequestController::class, 'getDataTrainingReferences'])
            ->name('training-request.training-reference');
        Route::get('training-request/{id}/get-employee-by-unit', [TrainingRequestController::class, 'getEmployeeByUnit'])
            ->name('training-request.get-employee-by-unit');
        Route::get('training-request/{id}/get-training-request-list', [TrainingRequestController::class, 'getTrainingRequestList'])
            ->name('training-request.get-training-request-list');
        Route::post('training-request/input-training-request', [TrainingRequestController::class, 'inputTrainingRequest'])
            ->name('training-request.input-training-request');
        Route::post('training-request/import-training', [TrainingRequestController::class, 'importTraining'])
            ->name('training-request.import-training');
        // END
            
        // Kepala Unit
        Route::post('training-request/{id}/approve-training-request', [TrainingRequestController::class,'approveTrainingRequest'])
            ->name('training-request.approve-training-request');
        Route::post('training-request/{id}/reject-training-request', [TrainingRequestController::class,'rejectTrainingRequest'])
            ->name('training-request.reject-training-request');

        // Monitoring
        // Route::get('monitoring', fn () => view('training.monitoring.monitoring'))
        //     ->middleware('permission:training.view')->name('monitoring');
        // Route::post('import', [TrainingMonitoringController::class,'import'])
        //     ->name('import');
        
        // Route::get('list', [TrainingMonitoringController::class,'list'])
        //     ->name('list');
        // Route::post('input', [TrainingMonitoringController::class,'input'])
        //     ->name('input');
        // Route::get('edit/{id}/get-data', [TrainingMonitoringController::class,'getEditData'])
        //     ->name('get-data');
        // Route::post('edit/{id}', [TrainingMonitoringController::class,'update'])
        //     ->name('update');
        // Route::delete('delete/{id}', [TrainingMonitoringController::class,'destroy'])
        //     ->name('delete');

        Route::post('monitoring/{id}/approve', [TrainingMonitoringController::class,'approveStatus'])
            ->name('monitoring.approveStatus');
        Route::post('monitoring/{id}/reject', [TrainingMonitoringController::class,'rejectTrainingRequest'])
            ->name('monitoring.rejectStatus');
        Route::post('all-approve', [TrainingMonitoringController::class,'updateAllStatus'])
            ->name('allApprove');
        Route::post('bulk-approve', [TrainingMonitoringController::class,'bulkApprove'])
            ->name('bulkApprove');

        Route::get('download-template', [TrainingMonitoringController::class,'downloadTemplate'])
            ->name('download-template');
        // END 
        
        // Self Learning
        Route::get('self-learning', fn () => view('training.self-learning.index'))
            ->middleware('permission:training.view')->name('self-learning');

        // Principal Approval
        Route::get('principal-approval', fn () => view('training.principal-approval.principal-approval'))
            ->middleware('permission:training.view')->name('principal-approval');
    });
});
