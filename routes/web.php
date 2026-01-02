<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Public\ApplicationController as PublicApplicationController;
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Recruitment\PrincipalApprovalController;
use App\Http\Controllers\Recruitment\SalaryController;
use App\Http\Controllers\Recruitment\ExternalRecruitmentController;
use App\Http\Controllers\Recruitment\ApplicantDataController;

Route::middleware('web')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
        Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
    });

    Route::middleware(['auth', 'team.scope'])->group(function () {
        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
        Route::group(['prefix' => 'recruitment/applicant-data', 'as' => 'recruitment.applicant-data.'], function () {
            Route::get('/', [ApplicantDataController::class, 'index'])->name('index');
            Route::post('/update', [ApplicantDataController::class, 'update'])->name('update');
        });
        Route::post('/account/profile',  [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::post('/recruitment/uraian-jabatan/preview-pdf', [PrincipalApprovalController::class, 'previewUraianPdf'])
            ->name('recruitment.uraian-jabatan.preview-pdf');

        Route::group(['prefix' => 'recruitment/external', 'as' => 'recruitment.external.', 'middleware' => ['auth']], function () {
            Route::get('/', [ExternalRecruitmentController::class, 'index'])->name('index');
            Route::post('/apply', [ExternalRecruitmentController::class, 'apply'])->name('apply');
            Route::get('/{id}/applicants', [ExternalRecruitmentController::class, 'getApplicants'])->name('getApplicants');
            Route::post('/applicant/{id}/update', [ExternalRecruitmentController::class, 'updateApplicantStatus'])->name('updateApplicantStatus');
            Route::get('/applicant/{id}/biodata', [ExternalRecruitmentController::class, 'showApplicantBiodata'])->name('applicant.biodata');
        });

        Route::post('recruitment/principal-approval/{req}/publish', [PrincipalApprovalController::class, 'publish'])
            ->name('recruitment.principal-approval.publish');

    });

    Route::get('recruitment/principal-approval/export', [PrincipalApprovalController::class, 'exportExcel'])
        ->name('recruitment.principal-approval.export');

    Route::post('/ajax/calculate-salary', [SalaryController::class, 'calculate'])
        ->name('api.calculate.salary');

    Route::post('recruitment/project/store', [PrincipalApprovalController::class, 'storeProject'])
        ->name('recruitment.project.store');

    Route::get('/recruitment/external/applicant/{id}/download-pdf', [App\Http\Controllers\Recruitment\ExternalRecruitmentController::class, 'downloadBiodataPdf'])
            ->name('recruitment.external.download-pdf');
    require __DIR__ . '/admin.php';
});