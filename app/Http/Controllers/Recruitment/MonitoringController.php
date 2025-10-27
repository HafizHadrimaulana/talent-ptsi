<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use App\Models\Contract;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function index()
    {
        // ===== Recruitment Requests =====
        $reqTable = (new RecruitmentRequest)->getTable();

        $reqCols = ['id', 'status', 'updated_at'];
        foreach (['title','position','headcount','created_at'] as $c) {
            if (Schema::hasColumn($reqTable, $c)) $reqCols[] = $c;
        }
        // alias untuk title jika beda nama
        if (!in_array('title', $reqCols, true)) {
            if (Schema::hasColumn($reqTable, 'job_title')) {
                $reqCols[] = DB::raw('job_title as title');
            } elseif (Schema::hasColumn($reqTable, 'name')) {
                $reqCols[] = DB::raw('name as title');
            }
        }
        $reqOrder = Schema::hasColumn($reqTable, 'created_at') ? 'created_at' : 'updated_at';

        $requests = RecruitmentRequest::query()
            ->select($reqCols)
            ->orderBy($reqOrder, 'desc')
            ->limit(10)
            ->get();

        // ===== Contracts =====
        $ctrTable = (new Contract)->getTable();
        $ctrCols  = ['id', 'updated_at'];

        // kolom langsung ada
        foreach (['type','person_name','position','status','start_date','end_date','number'] as $c) {
            if (Schema::hasColumn($ctrTable, $c)) $ctrCols[] = $c;
        }

        // alias utk type
        if (!in_array('type', $ctrCols, true) && Schema::hasColumn($ctrTable, 'contract_type')) {
            $ctrCols[] = DB::raw('contract_type as type');
        }

        // alias utk person_name
        if (!in_array('person_name', $ctrCols, true)) {
            if (Schema::hasColumn($ctrTable, 'candidate_name')) {
                $ctrCols[] = DB::raw('candidate_name as person_name');
            } elseif (Schema::hasColumn($ctrTable, 'name')) {
                $ctrCols[] = DB::raw('name as person_name');
            }
        }

        // alias utk position
        if (!in_array('position', $ctrCols, true)) {
            if (Schema::hasColumn($ctrTable, 'position_name')) {
                $ctrCols[] = DB::raw('position_name as position');
            } elseif (Schema::hasColumn($ctrTable, 'job_title')) {
                $ctrCols[] = DB::raw('job_title as position');
            }
        }

        $contracts = Contract::query()
            ->select($ctrCols)
            ->latest()
            ->limit(10)
            ->get();

        return view('recruitment.monitoring', compact('requests', 'contracts'));
    }
}
