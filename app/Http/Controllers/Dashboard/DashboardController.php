<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrainingRequest;
use App\Models\RecruitmentRequest;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return view('dashboard.dashboard', [
                'totalPelatihan'        => 0,
                'pelatihanSelesai'      => 0,
                'totalTrainingUnit'     => null,
                'recruitmentApproved'  => null,
                'recruitmentInReview'  => null,
            ]);
        }

        $userApprovedTrainings = TrainingRequest::where('employee_id', $employee->id)
            ->where('status_approval_training', 'approved')
            ->get();

        $totalPelatihan = $userApprovedTrainings->count();

        $pelatihanSelesai = $userApprovedTrainings->filter(function ($item) {
            return $item->end_date && Carbon::parse($item->end_date)->lt(now());
        })->count();

        $totalTrainingUnit = null;

        if ($user->hasRole('SDM Unit')) {
            $unitId = $employee->unit_id;

            $totalTrainingUnit = TrainingRequest::where('status_approval_training', 'approved')
                ->whereHas('employee', function ($q) use ($unitId) {
                    $q->where('unit_id', $unitId);
                })
                ->count();
        }

        // RECRUITMENT
        $recruitmentApproved = null;
        $recruitmentInReview = null;

        if ($user->hasRole('SDM Unit') || $user->hasRole('DHC')) {

            $recruitmentQuery = RecruitmentRequest::query();

            // SDM Unit → filter unit
            // if ($user->hasRole('SDM Unit') && !$user->hasRole('DHC')) {
            //     $recruitmentQuery->whereHas('employee', function ($q) use ($employee) {
            //         $q->where('unit_id', $employee->unit_id);
            //     });
            // }

            $recruitmentApproved = (clone $recruitmentQuery)
                ->where('status', 'approved')
                ->count();

            $recruitmentInReview = (clone $recruitmentQuery)
                ->where('status', 'in_review')
                ->count();
        }

        Log::info('Dashboard Summary', [
            'roles'                 => $user->getRoleNames(),
            'employee_id'           => $employee->id,
            'unit_id'               => $employee->unit_id,
            'totalPelatihan'        => $totalPelatihan,
            'pelatihanSelesai'      => $pelatihanSelesai,
            'totalTrainingUnit'     => $totalTrainingUnit,
            'recruitmentApproved'   => $recruitmentApproved,
            'recruitmentInReview'   => $recruitmentInReview,
        ]);

        return view('dashboard.dashboard', [
            'totalPelatihan'        => $totalPelatihan,
            'pelatihanSelesai'      => $pelatihanSelesai,
            'totalTrainingUnit'     => $totalTrainingUnit,
            'recruitmentApproved'  => $recruitmentApproved,
            'recruitmentInReview'  => $recruitmentInReview,
        ]);
    }
}
