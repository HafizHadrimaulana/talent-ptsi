<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrainingRequest;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentApplicant;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('Pelamar')) {
            return $this->applicantDashboard($user);
        }

        $employee = $user->employee;
        
        // Inisialisasi default
        $totalPelatihan = 0;
        $pelatihanSelesai = 0;
        $totalTrainingUnit = 0;
        $recruitmentApproved = 0;
        $recruitmentInReview = 0;
        $recruitmentRejected = 0;

        // 1. Logika Pelatihan Personal (Jika user adalah karyawan)
        if ($employee) {
            $userTrainings = TrainingRequest::where('employee_id', $employee->id)
                ->where('status_approval_training', 'approved')
                ->get();

            $totalPelatihan = $userTrainings->count();
            $pelatihanSelesai = $userTrainings->filter(function ($item) {
                return $item->end_date && Carbon::parse($item->end_date)->lt(now());
            })->count();
        }

        // 2. Logika Rekap Izin Prinsip (Untuk Role Superadmin, DHC, SDM Unit)
        if ($user->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit'])) {
            $recruitmentQuery = RecruitmentRequest::query();

            // Filter berdasarkan Unit jika BUKAN Superadmin/DHC
            if (!$user->hasRole(['Superadmin', 'DHC'])) {
                // Ambil unit_id dari user atau employee
                $unitId = $user->unit_id ?? ($employee ? $employee->unit_id : null);
                
                if ($unitId) {
                    $recruitmentQuery->where('unit_id', $unitId);
                } else {
                    // Jika tidak ada unit_id sama sekali, paksa hasil kosong
                    $recruitmentQuery->whereRaw('1 = 0');
                }
            }

            // Ambil data real-time dari database
            // SESUAIKAN: Jika di DB menggunakan 'in_review', pastikan query-nya sama
            $recruitmentApproved = (clone $recruitmentQuery)->where('status', 'approved')->count();
            $recruitmentInReview = (clone $recruitmentQuery)->whereIn('status', ['in_review', 'submitted', 'in-review'])->count();
            $recruitmentRejected = (clone $recruitmentQuery)->where('status', 'rejected')->count();

            // Hitung Total Training Unit (Opsional jika ingin ditampilkan di kartu bawah)
            $trainingUnitQuery = TrainingRequest::where('status_approval_training', 'approved');
            if (!$user->hasRole(['Superadmin', 'DHC']) && $employee) {
                $trainingUnitQuery->whereHas('employee', function ($q) use ($employee) {
                    $q->where('unit_id', $employee->unit_id);
                });
            }
            $totalTrainingUnit = $trainingUnitQuery->count();
        }

        return view('dashboard.dashboard', [
            'totalPelatihan' => $totalPelatihan,
            'pelatihanSelesai' => $pelatihanSelesai,
            'totalTrainingUnit' => $totalTrainingUnit,
            'recruitmentApproved' => $recruitmentApproved,
            'recruitmentInReview' => $recruitmentInReview,
            'recruitmentRejected' => $recruitmentRejected,
            'applications' => null // Admin biasanya tidak melihat status lamaran personal di sini
        ]);
    }

    private function applicantDashboard($user)
    {
        $applications = RecruitmentApplicant::with(['recruitmentRequest.unit', 'recruitmentRequest.positionObj'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.dashboard', [
            'applications' => $applications,
            'totalPelatihan' => 0,
            'pelatihanSelesai' => 0,
            'totalTrainingUnit' => null,
            'recruitmentApproved' => null,
            'recruitmentInReview' => null,
            'recruitmentRejected' => 0,
        ]);
    }
}