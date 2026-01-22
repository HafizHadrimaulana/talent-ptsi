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

        if (!$employee) {
            return view('dashboard.dashboard', [
                'totalPelatihan' => 0, 
                'pelatihanSelesai' => 0,
                'totalTrainingUnit' => null, 
                'recruitmentApproved' => null, 
                'recruitmentInReview' => null,
                'applications' => null
            ]);
        }

        $userTrainings = TrainingRequest::where('employee_id', $employee->id)
            ->where('status_approval_training', 'approved')
            ->get();

        $totalPelatihan = $userTrainings->count();
        $pelatihanSelesai = $userTrainings->filter(function ($item) {
            return $item->end_date && Carbon::parse($item->end_date)->lt(now());
        })->count();

        $totalTrainingUnit = null;
        $recruitmentApproved = null;
        $recruitmentInReview = null;

        $isAdminOrHR = $user->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit']);
        
        if ($isAdminOrHR) {
            $totalTrainingUnit = TrainingRequest::where('status_approval_training', 'approved');
            
            if (!$user->hasRole(['Superadmin', 'DHC'])) {
                $totalTrainingUnit->whereHas('employee', function ($q) use ($employee) {
                    $q->where('unit_id', $employee->unit_id);
                });
            }
            $totalTrainingUnit = $totalTrainingUnit->count();

            $recruitmentQuery = RecruitmentRequest::query();
            
            if (!$user->hasRole(['Superadmin', 'DHC'])) {
                $recruitmentQuery->whereHas('employee', function ($q) use ($employee) {
                    $q->where('unit_id', $employee->unit_id);
                });
            }

            $recruitmentApproved = (clone $recruitmentQuery)->where('status', 'approved')->count();
            $recruitmentInReview = (clone $recruitmentQuery)->where('status', 'in_review')->count();
        }

        return view('dashboard.dashboard', [
            'totalPelatihan' => $totalPelatihan,
            'pelatihanSelesai' => $pelatihanSelesai,
            'totalTrainingUnit' => $totalTrainingUnit,
            'recruitmentApproved' => $recruitmentApproved,
            'recruitmentInReview' => $recruitmentInReview,
            'applications' => null
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
        ]);
    }
}   