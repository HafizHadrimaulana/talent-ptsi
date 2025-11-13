<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ======================================
        // Privilege "lihat semua unit"
        // ======================================
        $hasRoleAll = $user?->hasRole('Superadmin') || $user?->hasRole('DHC');

        // Deteksi Head Office (fleksibel by code/name)
        $hoUnitId = DB::table('units')
            ->select('id')
            ->where(function ($q) {
                $q->where('code', 'HO')
                  ->orWhere('code', 'HEADOFFICE')
                  ->orWhere('name', 'SI Head Office')
                  ->orWhere('name', 'Head Office')
                  ->orWhere('name', 'LIKE', '%Head Office%');
            })
            ->value('id');

        $isHeadOfficeUser = $hoUnitId && $user?->unit_id == $hoUnitId;
        $canSeeAll = $hasRoleAll || $isHeadOfficeUser;

        // ======================================
        // Resolve selected unit (query ?unit_id= )
        // ======================================
        $selectedUnitId = null;
        if ($canSeeAll) {
            $selectedUnitId = $request->filled('unit_id')
                ? (int) $request->integer('unit_id')
                : null; // null = all units
        } else {
            $selectedUnitId = (int) ($user?->unit_id);
        }

        // ======================================
        // Unit options untuk dropdown
        // ======================================
        $unitsQ = DB::table('units')->select('id', 'name')->orderBy('name');
        $units  = $canSeeAll
            ? $unitsQ->get()
            : $unitsQ->where('id', $user?->unit_id)->get();

        // ======================================
        // ===== Recruitment Requests =====
        // ======================================
        $reqTable = (new RecruitmentRequest)->getTable();

        $reqCols = ['id', 'status', 'updated_at'];
        foreach (['title','position','headcount','created_at'] as $c) {
            if (Schema::hasColumn($reqTable, $c)) $reqCols[] = $c;
        }

        // unit_id untuk filter jika ada
        $reqHasUnit = Schema::hasColumn($reqTable, 'unit_id');
        if ($reqHasUnit) {
            $reqCols[] = 'unit_id';
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

        $requestsQ = RecruitmentRequest::query()->select($reqCols);
        if ($reqHasUnit && $selectedUnitId) {
            $requestsQ->where('unit_id', $selectedUnitId);
        }
        $requests = $requestsQ->orderBy($reqOrder, 'desc')->limit(10)->get();

        // ======================================
        // ===== Contracts ======================
        // ======================================
        $ctrTable = (new Contract)->getTable();
        $ctrCols  = ['id', 'updated_at'];

        foreach (['type','person_name','position','status','start_date','end_date','number'] as $c) {
            if (Schema::hasColumn($ctrTable, $c)) $ctrCols[] = $c;
        }

        // unit_id untuk filter jika ada
        $ctrHasUnit = Schema::hasColumn($ctrTable, 'unit_id');
        if ($ctrHasUnit) {
            $ctrCols[] = 'unit_id';
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

        $contractsQ = Contract::query()->select($ctrCols);
        if ($ctrHasUnit && $selectedUnitId) {
            $contractsQ->where('unit_id', $selectedUnitId);
        }
        $contracts = $contractsQ->latest()->limit(10)->get();

        // ======================================
        // Return view
        // ======================================
        return view('recruitment.monitoring', [
            'requests'       => $requests,
            'contracts'      => $contracts,
            'units'          => $units,
            'canSeeAll'      => $canSeeAll,
            'selectedUnitId' => $selectedUnitId,
            'hoUnitId'       => $hoUnitId,
        ]);
    }
}
