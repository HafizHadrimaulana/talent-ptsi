<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Access\UserController;
use App\Http\Controllers\Admin\Access\RoleController;
use App\Http\Controllers\Admin\Access\PermissionController;
use App\Http\Controllers\Admin\ContractTemplateController;
use App\Http\Controllers\Self\ProfileController;
use App\Http\Controllers\Recruitment\ExternalRecruitmentController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Recruitment\MonitoringController as RecruitmentMonitoringController;
use App\Http\Controllers\Recruitment\PrincipalApprovalController as RecruitmentApprovalController;
use App\Http\Controllers\Recruitment\ContractController;
use App\Http\Controllers\Recruitment\PublishingController;
use App\Http\Controllers\Recruitment\ApplicantDataController;
use App\Http\Controllers\Public\CareerController;
use App\Http\Controllers\Training\{
    DashboardController as TrainingDashboardController,
    TrainingRequestController,
    TrainingManagementController,
    TrainingImportController
};

use App\Http\Controllers\Admin\Org\OrgController;

Route::middleware(['web', 'auth', 'team.scope'])->group(function () {

    Route::prefix('admin/settings/access')->name('admin.')->group(function () {
        Route::get('users',           [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::post('users',          [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('users/{id}',      [UserController::class, 'show'])->middleware('permission:users.view')->name('users.show');
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
        Route::resource('contract-templates', ContractTemplateController::class);
    });
    
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
    
    Route::prefix('admin/org')->name('admin.org.')->middleware('permission:org.view')->group(function () {
        Route::get('/', [OrgController::class, 'index'])->name('index');
        Route::get('tree', [OrgController::class, 'tree'])->name('tree');
        
        Route::get('directorates',            [OrgController::class, 'directorates'])->name('directorates.list');
        Route::get('directorates/options',    [OrgController::class, 'directorateOptions'])->name('directorates.options');
        Route::post('directorates',           [OrgController::class, 'directorateStore'])->middleware('permission:org.create')->name('directorates.store');
        Route::put('directorates/{id}',       [OrgController::class, 'directorateUpdate'])->middleware('permission:org.update')->name('directorates.update');
        Route::delete('directorates/{id}',    [OrgController::class, 'directorateDestroy'])->middleware('permission:org.delete')->name('directorates.destroy');

        Route::get('units',                   [OrgController::class, 'units'])->name('units.list');
        Route::post('units',                  [OrgController::class, 'unitStore'])->middleware('permission:org.create')->name('units.store');
        Route::put('units/{id}',              [OrgController::class, 'unitUpdate'])->middleware('permission:org.update')->name('units.update');
        Route::put('units/{id}/reassign',     [OrgController::class, 'unitReassign'])->middleware('permission:org.update')->name('units.reassign');
        Route::delete('units/{id}',           [OrgController::class, 'unitDestroy'])->middleware('permission:org.delete')->name('units.destroy');
    });

    Route::prefix('recruitment')->name('recruitment.')->group(function () {

        Route::group(['prefix' => 'external', 'as' => 'external.'], function () {
            Route::get('/', [ExternalRecruitmentController::class, 'index'])->name('index');
            Route::post('/apply', [ExternalRecruitmentController::class, 'apply'])->name('apply');
            Route::get('/{id}/applicants', [ExternalRecruitmentController::class, 'getApplicants'])->name('getApplicants');
            Route::post('/applicant/{id}/update', [ExternalRecruitmentController::class, 'updateApplicantStatus'])->name('updateApplicantStatus');
            Route::get('/applicant/{id}/biodata', [ExternalRecruitmentController::class, 'showApplicantBiodata'])->name('applicant.biodata');
            Route::get('/applicant/{id}/download-pdf', [ExternalRecruitmentController::class, 'downloadBiodataPdf'])->name('download-pdf');
            Route::post('/{id}/update-description', [ExternalRecruitmentController::class, 'updateDescription'])->name('updateDescription');
            Route::post('/{id}/unpublish', [ExternalRecruitmentController::class, 'unpublish'])->name('unpublish');
            Route::post('/{id}/publish', [ExternalRecruitmentController::class, 'publish'])->name('publish');
        });

        // 2. Project & Uraian Jabatan (Pindahan dari web.php)
        Route::post('/project/store', [RecruitmentApprovalController::class, 'storeProject'])->name('project.store');
        Route::post('/uraian-jabatan/preview-pdf', [RecruitmentApprovalController::class, 'previewUraianPdf'])->name('uraian-jabatan.preview-pdf');

        // 3. Principal Approval
        Route::get('principal-approval', [RecruitmentApprovalController::class, 'index'])->middleware('permission:recruitment.view')->name('principal-approval.index');
        Route::post('principal-approval', [RecruitmentApprovalController::class, 'store'])->middleware('permission:recruitment.update')->name('principal-approval.store');
        
        // Pindahan dari web.php (Export & Publish spesifik) digabung disini
        Route::get('principal-approval/export', [RecruitmentApprovalController::class, 'exportExcel'])->name('principal-approval.export');
        Route::post('principal-approval/{req}/publish', [RecruitmentApprovalController::class, 'publish'])->name('principal-approval.publish');

        Route::delete('principal-approval/{req}', [RecruitmentApprovalController::class, 'destroy'])->middleware('permission:recruitment.update')->name('principal-approval.destroy');
        Route::put('principal-approval/{req}', [RecruitmentApprovalController::class, 'update'])->middleware('permission:recruitment.update')->name('principal-approval.update');
        Route::post('principal-approval/{req}/submit',  [RecruitmentApprovalController::class, 'submit'])->middleware('permission:recruitment.submit')->name('principal-approval.submit');
        Route::post('principal-approval/{req}/approve', [RecruitmentApprovalController::class, 'approve'])->middleware('permission:recruitment.approve')->name('principal-approval.approve');
        Route::post('principal-approval/{req}/reject',  [RecruitmentApprovalController::class, 'reject'])->middleware('permission:recruitment.reject')->name('principal-approval.reject');

        Route::get('contracts', [ContractController::class, 'index'])->middleware('permission:contract.view')->name('contracts.index');
        Route::get('contracts/{contract}', [ContractController::class, 'show'])->middleware('permission:contract.view')->name('contracts.show');
        
        Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])->middleware('permission:contract.delete')->name('contracts.destroy');

        Route::get('contracts/{contract}/document', [ContractController::class, 'document'])
            ->middleware('permission:contract.view')
            ->name('contracts.document');

        Route::post('contracts', [ContractController::class, 'store'])->middleware('permission:contract.create')->name('contracts.store');
        Route::put('contracts/{contract}', [ContractController::class, 'update'])->middleware('permission:contract.update')->name('contracts.update');
        Route::post('contracts/{contract}/submit',  [ContractController::class, 'submit'])->middleware('permission:contract.update')->name('contracts.submit');
        Route::post('contracts/{contract}/approve', [ContractController::class, 'approve'])->middleware('permission:contract.approve')->name('contracts.approve');
        Route::post('contracts/{contract}/reject',  [ContractController::class, 'reject'])->middleware('permission:contract.approve')->name('contracts.reject');
        Route::post('contracts/{contract}/sign',    [ContractController::class, 'sign'])->middleware('permission:contract.sign')->name('contracts.sign');

        Route::middleware('permission:recruitment.update')->group(function () {
            Route::get('requests/{req}/publish',  [PublishingController::class, 'edit'])->name('publish.edit');
            Route::put('requests/{req}/publish',  [PublishingController::class, 'update'])->name('publish.update');
            Route::post('requests/{req}/toggle',  [PublishingController::class, 'toggle'])->name('publish.toggle');
        });
    });

    Route::prefix('training')->name('training.')->group(function () {

    // DASHBOARD
        Route::get('dashboard', [TrainingDashboardController::class,'index'])
            ->middleware('permission:training.dashboard.view')->name('dashboard');
        Route::get('dashboard/{unit_id}/get-detail-anggaran', [TrainingDashboardController::class,'getDetailAnggaran'])
            ->name('dashboard.get-detail-anggaran');
    /// END DASHBOARD ///

    /// Start Training ///
        Route::get('training-request', [TrainingRequestController::class, 'index'])
            ->name('training-request');
        Route::get('training-request/detail-training-request/{id}', [TrainingRequestController::class, 'getDetailTrainingRequest'])
            ->name('training-request/detail-training-request');
        Route::post('training-request/submit-evaluasi-training', [TrainingRequestController::class, 'submitEvaluasiTraining'])
            ->name('training-request/submit-evaluasi-training');
        Route::get('training-request/get-data-lna', [TrainingRequestController::class, 'getDataLna'])
            ->name('training-request.get-data-lna');

        Route::delete('training-request/{id}/delete-lna', [TrainingRequestController::class, 'destroyLna'])
            ->name('training-request.delete-lna');
        Route::delete('training-request/{id}/delete-training-request', [TrainingRequestController::class, 'destroyTrainingRequest'])
            ->name('training-request.delete-training-request');

        Route::get('training-request/get-data-units', [TrainingRequestController::class, 'getDataUnits'])
            ->name('training-request.get-data-units');

        Route::get('training-request/get-approval-pengajuan-training', [TrainingRequestController::class, 'getApprovalPengajuanTraining'])
            ->name('training-request.get-approval-pengajuan-training');

        Route::get('training-request/training-references/{id}', [TrainingRequestController::class, 'getDataTrainingReferences'])->middleware('permission:training.view')->name('training-request.training-reference');
        Route::get('training-request/{id}/get-employee-by-unit', [TrainingRequestController::class, 'getEmployeeByUnit'])
            ->name('training-request.get-employee-by-unit');
        Route::get('training-request/get-training-request-list', [TrainingRequestController::class, 'getTrainingRequestList'])
            ->name('training-request.get-training-request-list');
        Route::post('training-request/input-training-request', [TrainingRequestController::class, 'inputTrainingRequest'])
            ->name('training-request.input-training-request');

        Route::post('training-request/{id}/approve-training-request', [TrainingRequestController::class,'approveTrainingRequest'])
            ->name('training-request.approve-training-request');
        Route::post('training-request/{id}/reject-training-request', [TrainingRequestController::class,'rejectTrainingRequest'])
            ->name('training-request.reject-training-request');
        
        Route::get('training-management', [TrainingManagementController::class, 'index'])->middleware('permission:training.management.view')->name('training-management');

        Route::get('training-management/get-data-pengajuan-lna', [TrainingManagementController::class, 'getDataPengajuanLna'])
            ->name('training-management.get-data-pengajuan-lna');
        Route::get('training-management/{unitId}/get-pengajuan-training-peserta', [TrainingManagementController::class, 'getPengajuanTrainingPeserta'])
            ->name('training-management.get-pengajuan-training-peserta');

        Route::post('training-management/{id}/approve-training-submission', [TrainingManagementController::class,'approveTrainingSubmission']);
        Route::post('training-management/{id}/reject-training-submission', [TrainingManagementController::class,'rejectTrainingSubmission']);
        
        Route::post('training-management/{id}/approve-training-reference', [TrainingManagementController::class,'approveTrainingReference'])
            ->name('training-management.approve-training-reference');
        Route::post('training-management/{id}/reject-training-pengajuan', [TrainingManagementController::class,'rejectTrainingReference'])
            ->name('training-management.reject-training-pengajuan');

        Route::post('training-management/import-lna', [TrainingImportController::class, 'importLna'])
            ->name('training-management.import-lna');

        Route::post('training-request/{id}/edit-data-lna', [TrainingManagementController::class, 'editDataLna'])
            ->name('training-request.update-data-lna');
        Route::post('training-request/input-lna', [TrainingManagementController::class, 'inputLna'])
            ->name('training-request.input-lna');

        Route::get('self-learning', fn () => view('training.self-learning.index'))
            ->middleware('permission:training.view')->name('self-learning');

        Route::get('principal-approval', fn () => view('training.principal-approval.principal-approval'))
            ->middleware('permission:training.view')->name('principal-approval');
    });

    Route::prefix('self')->name('self.')->group(function () {
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:reports.export')->group(function () {
        Route::get('contracts', function () { return view('reports.contracts'); })->name('contracts');
        Route::get('recruitment', function () { return view('reports.recruitment'); })->name('recruitment');
        Route::get('training', function () { return view('reports.training'); })->name('training');
    });
    
    Route::prefix('recruitment/applicant-data')
        ->name('recruitment.applicant-data.')
        ->middleware('permission:applicant.data.view')
        ->group(function () {
            Route::get('/', [ApplicantDataController::class, 'index'])->name('index');
            Route::post('/update', [ApplicantDataController::class, 'update'])->name('update');
        });

    Route::get('careers', [CareerController::class, 'index'])
        ->middleware('permission:careers.view')
        ->name('careers.index');

});