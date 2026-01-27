<?php

namespace App\Http\Controllers\Training;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\TrainingTemp;
use App\Models\Evaluation;
use App\Models\TrainingReference;
use App\Models\TrainingRequest;
use App\Models\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $employee = $user->employee;
        $unitId   = $employee?->unit_id;
        $role     = $user->getRoleNames()->first();

        // 1. Logika Penentuan Hak Akses
        $isHumanCapital  = $this->isHumanCapital($user);
        $isKepalaUnitHC  = ($role === 'Kepala Unit' && $isHumanCapital);
        $canViewAllUnits = ($role === 'DHC' || $isKepalaUnitHC);

        // 2. Filter Unit Berdasarkan Hak Akses
        $allowedCategories = ['operasi', 'cabang', 'enabler'];
        
        $unitQuery = DB::table('units')->whereIn('category', $allowedCategories);

        // JIKA bukan DHC/Kepala Unit HC, batasi hanya untuk unit milik user sendiri
        if (!$canViewAllUnits) {
            $unitQuery->where('id', $unitId);
        }

        $allValidUnits = $unitQuery->get();
        $allValidUnitIds = $allValidUnits->pluck('id')->toArray();

        // Jika user tidak punya unit dan tidak boleh akses semua, berikan array kosong agar data dashboard jadi 0
        if (empty($allValidUnitIds)) {
            $allValidUnitIds = [0]; 
        }

        // 3. BUDGET PER UNIT (Data otomatis terfilter karena menggunakan $allValidUnitIds)
        $anggaranData = DB::table('training_anggaran')
            ->whereIn('unit_id', $allValidUnitIds)
            ->pluck('limit_anggaran', 'unit_id');

        $budgetData = DB::table('training_request')
            ->join('employees', 'training_request.employee_id', '=', 'employees.id')
            ->select(
                'employees.unit_id',
                DB::raw('SUM(training_request.realisasi_biaya_pelatihan) as total_used')
            )
            ->where('training_request.status_approval_training', 'approved')
            ->whereIn('employees.unit_id', $allValidUnitIds)
            ->groupBy('employees.unit_id')
            ->get()
            ->keyBy('unit_id');

        $unitBudgets = $allValidUnits->map(function ($unit) use ($budgetData, $anggaranData) {
            $used  = $budgetData->get($unit->id)->total_used ?? 0;
            $limit = $anggaranData[$unit->id] ?? null;

            return [
                'unit_id'    => $unit->id,
                'unit_name'  => $unit->name,
                'category'   => $unit->category,
                'used'       => (float) $used,
                'limit'      => $limit,
                'remaining'  => $limit !== null ? max($limit - $used, 0) : null,
                'percentage' => ($limit && $used > 0)
                    ? min(round(($used / $limit) * 100, 1), 100)
                    : 0,
            ];
        });

        // 4. STATISTIK LNA (Data otomatis terfilter karena menggunakan $allValidUnitIds)
        $referenceCounts = TrainingReference::whereIn('unit_id', $allValidUnitIds)
            ->groupBy('status_training_reference')
            ->select('status_training_reference', DB::raw('count(*) as total'))
            ->pluck('total', 'status_training_reference');

        $requestCounts = TrainingRequest::whereHas('employee', fn($q) => $q->whereIn('unit_id', $allValidUnitIds))
            ->groupBy('status_approval_training')
            ->select('status_approval_training', DB::raw('count(*) as total'))
            ->pluck('total', 'status_approval_training');

        $dashboardItems = collect([
            ['key' => 'pending', 'label' => 'Pending (LNA)', 'total' => $referenceCounts['pending'] ?? 0],
            ['key' => 'active', 'label' => 'Active (LNA)', 'total' => $referenceCounts['active'] ?? 0],
            ['key' => 'in_review_dhc', 'label' => 'Review DHC', 'total' => $requestCounts['in_review_dhc'] ?? 0],
            ['key' => 'in_review_gmvp', 'label' => 'Review Kepala Unit', 'total' => $requestCounts['in_review_gmvp'] ?? 0],
            ['key' => 'in_review_avpdhc', 'label' => 'Review AVP DHC', 'total' => $requestCounts['in_review_avpdhc'] ?? 0],
            ['key' => 'in_review_vpdhc', 'label' => 'Review VP DHC', 'total' => $requestCounts['in_review_vpdhc'] ?? 0],
            ['key' => 'approved', 'label' => 'Approved (Req)', 'total' => $requestCounts['approved'] ?? 0],
            ['key' => 'rejected', 'label' => 'Rejected', 'total' => $requestCounts['rejected'] ?? 0],
        ]);

        return view('training.dashboard.index', compact(
            'dashboardItems',
            'unitBudgets',
        ));
    }

    public function getDetailAnggaran($unitId)
    {
        // Pastikan Unit ada
        $unit = Unit::findOrFail($unitId);

        // Ambil Limit dari tabel anggaran
        $limit = DB::table('training_anggaran')
            ->where('unit_id', $unitId)
            ->value('limit_anggaran') ?? 0;

        // Ambil Data dari training_request (Query yang disinkronkan)
        $details = DB::table('training_request')
            ->join('employees', 'training_request.employee_id', '=', 'employees.id')
            ->join('persons', 'employees.person_id', '=', 'persons.id')
            ->join('training_references', 'training_request.training_reference_id', '=', 'training_references.id')
            ->select(
                'training_references.judul_sertifikasi as nama_training',
                'persons.full_name as nama_peserta',    
                'employees.employee_id as nik',
                'training_request.realisasi_biaya_pelatihan as biaya',
                'training_request.start_date as tanggal',
            )
            ->where('employees.unit_id', $unitId)
            ->where('training_request.status_approval_training', 'approved')
            ->orderBy('training_request.start_date', 'desc')
            ->get();

        // Hitung Total Terpakai
        $used = $details->sum('biaya');

        return response()->json([
            'unit' => $unit->name,
            'summary' => [
                'limit' => $limit,
                'used' => $used,
                'remaining' => max($limit - $used, 0),
                'percentage' => $limit > 0 ? round(($used / $limit) * 100, 1) : 0,
            ],
            'details' => $details->map(function($item) {
                return [
                    'training' => $item->nama_training,
                    'peserta'  => $item->nama_peserta,
                    'nik'      => $item->nik,
                    'biaya'    => $item->biaya,
                    'tanggal'  => $item->tanggal ? date('d M Y', strtotime($item->tanggal)) : '-'
                ];
            })
        ]);
    }

    // HELPER FUNCTION
    protected function isHumanCapital($user): bool
    {
        if (!$user->unit_id) {
            Log::warning('isHumanCapital: unit_id null');
            return false;
        }

        $exists = DB::table('units')
            ->where('id', $user->unit_id)
            ->where(function ($q) {
                $q->where('code', 'HC')
                ->orWhere('name', 'LIKE', '%Human Capital%')
                ->orWhere('name', 'LIKE', '%Human Capital Division%');
            })
            ->exists();

        Log::info('isHumanCapital check result', [
            'unit_id' => $user->unit_id,
            'exists'  => $exists
        ]);

        return $exists;
    }
}
