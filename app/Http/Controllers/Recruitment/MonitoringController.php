<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use App\Models\Contract;

class MonitoringController extends Controller
{
    public function index()
    {
        $requests = RecruitmentRequest::select('id','title','position','headcount','status','updated_at')
            ->latest()->take(10)->get();

        $contracts = Contract::select('id','type','person_name','position','status','start_date','end_date','updated_at')
            ->latest()->take(10)->get();

        return view('recruitment.monitoring', compact('requests', 'contracts'));
    }
}
