<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Public\CareersController;
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Recruitment\SalaryController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Self\ProfileController;
use App\Http\Controllers\Recruitment\ContractController;
use App\Http\Controllers\Recruitment\ExternalRecruitmentController;
use App\Http\Controllers\Recruitment\PrincipalApprovalController as RecruitmentApprovalController;
use App\Http\Controllers\Recruitment\PublishingController;
use App\Http\Controllers\Recruitment\ApplicantDataController;
use App\Http\Controllers\Training\{
    DashboardController as TrainingDashboardController,
    TrainingRequestController,
    TrainingManagementController,
    TrainingImportController
};

Route::middleware('web')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/', [AuthenticatedSessionController::class, 'store'])->name('login.store');
        Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
        Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
    });

    Route::middleware(['auth', 'team.scope'])->group(function () {

        Route::get('/home', function () {
            return redirect()->route('employee.dashboard');
        })->middleware('guest')->name('dashboard');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('employee.dashboard');

        Route::prefix('self')->name('self.')->group(function () {
            Route::get('profile', [ProfileController::class, 'index'])->name('profile');
            Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        });

        Route::post('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
        
        Route::any('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::prefix('training')->name('training.')->group(function () {

            Route::middleware('permission:training.dashboard.view')->group(function () {
                Route::get('dashboard', [TrainingDashboardController::class, 'index'])->name('dashboard');
                Route::get('dashboard/{unit_id}/get-detail-anggaran', [TrainingDashboardController::class, 'getDetailAnggaran'])->name('dashboard.get-detail-anggaran');
            });

            Route::middleware('permission:training.view')->group(function () {
                Route::get('training-request', [TrainingRequestController::class, 'index'])->name('training-request');

                Route::get('training-request/detail-training-evaluasi/{id}', [TrainingRequestController::class, 'getDetailTrainingRequest'])->name('training-request/detail-training-evaluasi');
                Route::post('training-request/submit-evaluasi-training', [TrainingRequestController::class, 'submitEvaluasiTraining'])->name('training-request/submit-evaluasi-training');

                Route::get('training-request/detail-training-ikdin/{id}', [TrainingRequestController::class, 'getDetailTrainingIkdin'])->name('training-request.detail-training-ikdin');
                Route::post('training-request/update-dokumen-ikdin', [TrainingRequestController::class, 'updateDokumenIkdin'])->name('training-request.update-dokumen-ikdin');
                Route::post('training-request/delete-dokumen-ikdin', [TrainingRequestController::class, 'deleteDokumenIkdin'])->name('training-request.delete-dokumen-ikdin');
                Route::get('training-request/{document}/preview-ikatan-dinas', [TrainingRequestController::class, 'previewDokumenIkdin'])->name('training-request.preview-dokumen-ikdin');

                Route::get('training-request/get-data-lna', [TrainingRequestController::class, 'getDataLna'])->name('training-request.get-data-lna');
                Route::get('training-request/get-data-units', [TrainingRequestController::class, 'getDataUnits'])->name('training-request.get-data-units');
                Route::get('training-request/get-approval-pengajuan-training', [TrainingRequestController::class, 'getApprovalPengajuanTraining'])->name('training-request.get-approval-pengajuan-training');
                Route::get('training-request/training-references/{id}', [TrainingRequestController::class, 'getDataTrainingReferences'])->name('training-request.training-reference');
                Route::get('training-request/{id}/get-employee-by-unit', [TrainingRequestController::class, 'getEmployeeByUnit'])->name('training-request.get-employee-by-unit');
                Route::get('training-request/get-training-request-list', [TrainingRequestController::class, 'getTrainingRequestList'])->name('training-request.get-training-request-list');

                Route::delete('training-request/{id}/delete-lna', [TrainingRequestController::class, 'deleteLna'])->name('training-request.delete-lna');
                Route::delete('training-request/{id}/delete-training-request', [TrainingRequestController::class, 'destroyTrainingRequest'])->name('training-request.delete-training-request');
                Route::post('training-request/input-training-request', [TrainingRequestController::class, 'inputTrainingRequest'])->name('training-request.input-training-request');
                Route::post('training-request/{id}/approve-training-request', [TrainingRequestController::class, 'approveTrainingRequest'])->name('training-request.approve-training-request');
                Route::post('training-request/{id}/reject-training-request', [TrainingRequestController::class, 'rejectTrainingRequest'])->name('training-request.reject-training-request');
                Route::get('/training-request/lampiran_penawaran/{filename}', [TrainingRequestController::class, 'viewDocument'])->name('training.view-document');
            });

            Route::middleware('permission:training.management.view')->group(function () {
                Route::get('training-management', [TrainingManagementController::class, 'index'])->name('training-management');
                Route::post('training-management/{id}/approve-training-submission', [TrainingManagementController::class, 'approveTrainingSubmission']);
                Route::post('training-management/{id}/reject-training-submission', [TrainingManagementController::class, 'rejectTrainingSubmission']);
                Route::post('training-management/{id}/approve-training-reference', [TrainingManagementController::class, 'approveTrainingReference'])->name('training-management.approve-training-reference');
                Route::post('training-management/{id}/reject-training-pengajuan', [TrainingManagementController::class, 'rejectTrainingReference'])->name('training-management.reject-training-pengajuan');
                Route::post('training-management/import-lna', [TrainingImportController::class, 'importLna'])->name('training-management.import-lna');
                Route::get('training-management/get-data-pengajuan-lna', [TrainingManagementController::class, 'getDataPengajuanLna'])
                    ->name('training-management.get-data-pengajuan-lna');
                Route::get('training-management/{unitId}/get-pengajuan-training-peserta', [TrainingManagementController::class, 'getPengajuanTrainingPeserta'])
                    ->name('training-management.get-pengajuan-training-peserta');
                Route::post('training-request/{id}/edit-data-lna', [TrainingManagementController::class, 'editDataLna'])->name('training-request.update-data-lna');
                Route::post('training-request/input-lna', [TrainingManagementController::class, 'inputLna'])->name('training-request.input-lna');
            });

            Route::get('self-learning', fn() => view('training.self-learning.index'))
                ->middleware('permission:training.view')->name('self-learning');

            Route::get('principal-approval', fn() => view('training.principal-approval.principal-approval'))
                ->middleware('permission:training.view')->name('principal-approval');
        });

        Route::prefix('recruitment')->name('recruitment.')->group(function () {

            Route::get('contracts', [ContractController::class, 'index'])->middleware('permission:contract.view')->name('contracts.index');
            Route::get('contracts/api/recruitment-requests', [ContractController::class, 'getRecruitmentRequests'])->middleware('permission:contract.create')->name('contracts.api.recruitment-requests');
            Route::get('contracts/api/recruitment-requests/{recruitmentRequestId}/detail', [ContractController::class, 'getRecruitmentDetail'])->middleware('permission:contract.create')->name('contracts.api.recruitment-detail');
            Route::get('contracts/api/recruitment-requests/{recruitmentRequestId}/applicants', [ContractController::class, 'getApplicantsFromRequest'])->middleware('permission:contract.create')->name('contracts.api.recruitment-applicants');
            Route::get('contracts/api/persons/{personId}', [ContractController::class, 'getPersonData'])->middleware('permission:contract.create')->name('contracts.api.person-data');
            Route::get('contracts/{contract}', [ContractController::class, 'show'])->middleware('permission:contract.view')->name('contracts.show');
            Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])->middleware('permission:contract.delete')->name('contracts.destroy');
            Route::get('contracts/{contract}/document', [ContractController::class, 'document'])
                ->middleware('permission:contract.view')
                ->name('contracts.document');
            Route::post('contracts', [ContractController::class, 'store'])->middleware('permission:contract.create')->name('contracts.store');
            Route::put('contracts/{contract}', [ContractController::class, 'update'])->middleware('permission:contract.update')->name('contracts.update');
            Route::post('contracts/{contract}/submit', [ContractController::class, 'submit'])->middleware('permission:contract.update')->name('contracts.submit');
            Route::post('contracts/{contract}/approve', [ContractController::class, 'approve'])->middleware('permission:contract.approve')->name('contracts.approve');
            Route::post('contracts/{contract}/reject', [ContractController::class, 'reject'])->middleware('permission:contract.approve')->name('contracts.reject');
            Route::post('contracts/{contract}/sign', [ContractController::class, 'sign'])->middleware('permission:contract.sign')->name('contracts.sign');

            Route::group(['prefix' => 'external', 'as' => 'external.'], function () {
                Route::get('/', [ExternalRecruitmentController::class, 'index'])
                    ->middleware('permission:recruitment.external.view')
                    ->name('index');
                Route::post('/apply', [ExternalRecruitmentController::class, 'apply'])
                    ->middleware('permission:recruitment.external.apply')
                    ->name('apply');

                Route::middleware('permission:recruitment.external.manage')->group(function () {
                    Route::post('/applicant/{id}/update', [ExternalRecruitmentController::class, 'updateApplicantStatus'])->name('updateApplicantStatus');
                    Route::post('/{id}/update-description', [ExternalRecruitmentController::class, 'updateDescription'])->name('updateDescription');
                    Route::post('/{id}/unpublish', [ExternalRecruitmentController::class, 'unpublish'])->name('unpublish');
                    Route::post('/{id}/publish', [ExternalRecruitmentController::class, 'publish'])->name('publish');
                });

                Route::middleware('permission:recruitment.external.view')->group(function () {
                    Route::get('/{id}/applicants', [ExternalRecruitmentController::class, 'getApplicants'])->name('getApplicants');
                    Route::get('/applicant/{id}/biodata', [ExternalRecruitmentController::class, 'showApplicantBiodata'])->name('applicant.biodata');
                    Route::get('/applicant/{id}/download-pdf', [ExternalRecruitmentController::class, 'downloadBiodataPdf'])->name('download-pdf');
                });
            });

            Route::post('/project/store', [RecruitmentApprovalController::class, 'storeProject'])->middleware('permission:recruitment.create')->name('project.store');
            Route::post('/uraian-jabatan/preview-pdf', [RecruitmentApprovalController::class, 'previewUraianPdf'])->name('uraian-jabatan.preview-pdf');

            Route::get('principal-approval', [RecruitmentApprovalController::class, 'index'])->middleware('permission:recruitment.view')->name('principal-approval.index');
            Route::post('principal-approval', [RecruitmentApprovalController::class, 'store'])->middleware('permission:recruitment.update')->name('principal-approval.store');
            Route::get('principal-approval/export', [RecruitmentApprovalController::class, 'exportExcel'])->name('principal-approval.export');
            Route::post('principal-approval/{req}/publish', [RecruitmentApprovalController::class, 'publish'])->name('principal-approval.publish');

            Route::delete('principal-approval/{req}', [RecruitmentApprovalController::class, 'destroy'])->middleware('permission:recruitment.update')->name('principal-approval.destroy');
            Route::put('principal-approval/{req}', [RecruitmentApprovalController::class, 'update'])->middleware('permission:recruitment.update')->name('principal-approval.update');
            Route::post('principal-approval/{req}/submit', [RecruitmentApprovalController::class, 'submit'])->middleware('permission:recruitment.submit')->name('principal-approval.submit');
            Route::post('principal-approval/{req}/approve', [RecruitmentApprovalController::class, 'approve'])->middleware('permission:recruitment.approve')->name('principal-approval.approve');
            Route::post('principal-approval/{req}/reject', [RecruitmentApprovalController::class, 'reject'])->middleware('permission:recruitment.reject')->name('principal-approval.reject');

            Route::middleware('permission:recruitment.update')->group(function () {
                Route::get('requests/{req}/publish', [PublishingController::class, 'edit'])->name('publish.edit');
                Route::put('requests/{req}/publish', [PublishingController::class, 'update'])->name('publish.update');
                Route::post('requests/{req}/toggle', [PublishingController::class, 'toggle'])->name('publish.toggle');
            });

            Route::prefix('applicant-data')
                ->name('applicant-data.')
                ->middleware('permission:applicant.data.view')
                ->group(function () {
                    Route::get('/', [ApplicantDataController::class, 'index'])->name('index');
                    Route::post('/update', [ApplicantDataController::class, 'update'])->name('update');
                });
        });

        Route::post('/ajax/calculate-salary', [SalaryController::class, 'calculate'])->name('api.calculate.salary');
        Route::get('/api/project-codes', [\App\Http\Controllers\ProjectCodeController::class, 'index'])
            ->name('api.project_codes.index');


    });

    require __DIR__ . '/admin.php';
});