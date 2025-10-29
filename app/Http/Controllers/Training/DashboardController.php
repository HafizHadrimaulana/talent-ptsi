<?php

namespace App\Http\Controllers\Training;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\StatusApprovalTraining;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    
    public function dataDashboard()
    {
        $counts = Training::select('status_approval_training_id', DB::raw('count(*) as total'))
            ->groupBy('status_approval_training_id')
            ->pluck('total', 'status_approval_training_id');
        
        $statuses = StatusApprovalTraining::all();

        return view('training.dashboard.index', compact('counts', 'statuses'));
    }
}
