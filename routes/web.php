<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController; // <--- TAMBAHAN: Untuk Register
use App\Http\Controllers\Public\CareersController;
use App\Http\Controllers\Public\ApplicationController as PublicApplicationController;
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Recruitment\PrincipalApprovalController;
use App\Http\Controllers\Recruitment\SalaryController;
use App\Http\Controllers\Recruitment\ExternalRecruitmentController;
use App\Http\Controllers\Recruitment\ApplicantDataController; // <--- TAMBAHAN: Controller Baru

Route::middleware('web')->group(function () {

    // ====== PUBLIC (NO AUTH) ======
    Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
    Route::post('/careers/apply', [PublicApplicationController::class, 'store'])->name('careers.apply');

    // ====== GUEST (AUTH) ======
    Route::middleware('guest')->group(function () {
        Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
        
        // <--- TAMBAHAN: Route Register (Penting agar RegisteredUserController terpakai)
        Route::post('register', [RegisteredUserController::class, 'store'])->name('register');
    });

    // ====== AUTHENTICATED SHELL ======
    Route::middleware(['auth', 'team.scope'])->group(function () {
        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

        // <--- TAMBAHAN: ROUTE DASHBOARD PELAMAR (Biodata & Status)
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
    
            // 1. Halaman Utama (Daftar Lowongan - POV DHC)
            Route::get('/', [ExternalRecruitmentController::class, 'index'])->name('index');

            // 2. Proses Submit Lamaran
            Route::post('/apply', [ExternalRecruitmentController::class, 'apply'])->name('apply');

            // 3. API: Ambil Data Pelamar (Untuk Modal DHC)
            Route::get('/{id}/applicants', [ExternalRecruitmentController::class, 'getApplicants'])->name('getApplicants');

            // 4. Proses Update Status Pelamar (Terima/Tolak oleh DHC)
            Route::post('/applicant/{id}/update', [ExternalRecruitmentController::class, 'updateApplicantStatus'])->name('updateApplicantStatus');

            // 5. Tampilkan Biodata Pelamar (Readonly untuk DHC dan SDM Unit)
            Route::get('/applicant/{id}/biodata', [ExternalRecruitmentController::class, 'showApplicantBiodata'])->name('applicant.biodata');

        });
    });

    Route::get('recruitment/principal-approval/export', [PrincipalApprovalController::class, 'exportExcel'])
        ->name('recruitment.principal-approval.export');

    Route::post('/ajax/calculate-salary', [SalaryController::class, 'calculate'])
        ->name('api.calculate.salary');

    Route::post('recruitment/project/store', [PrincipalApprovalController::class, 'storeProject'])
        ->name('recruitment.project.store');

    // ====== LOAD INTERNAL / ADMIN ROUTES ======
    require __DIR__ . '/admin.php';
});