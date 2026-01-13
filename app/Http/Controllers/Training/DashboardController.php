<?php

namespace App\Http\Controllers\Training;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\TrainingTemp;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\TrainingReference;
use App\Models\TrainingRequest;
use App\Models\Unit;

use Illuminate\Database\QueryException;

use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $employee = $user->employee;
        $unitId   = $employee?->unit_id;
        $role     = $user->getRoleNames()->first();

        // Logika Penentuan Hak Akses (DHC & Kepala Unit HC bisa lihat semua)
        $isHumanCapital  = $this->isHumanCapital($user);
        $isKepalaUnitHC  = $role === 'Kepala Unit' && $isHumanCapital;
        $canViewAllUnits = $role === 'DHC' || $isKepalaUnitHC;

        // 2. Filter Kategori Unit Sesuai Ketentuan
        $allowedCategories = ['operasi', 'cabang', 'enabler'];

        // Ambil SEMUA Master Unit yang valid untuk perhitungan limit global
        $allValidUnits = DB::table('units')
            ->whereIn('category', $allowedCategories)
            ->get();
        
        $allValidUnitIds = $allValidUnits->pluck('id')->toArray();

        // =============================================================
        // KALKULASI LIMIT DINAMIS
        // Rumus: (Total Seluruh Biaya LNA Active) / (Jumlah Unit Valid)
        // =============================================================
        $totalBiayaSeluruhUnit = DB::table('training_references')
            ->whereIn('unit_id', $allValidUnitIds)
            ->where('status_training_reference', 'active')
            ->sum('biaya_pelatihan');

        $jumlahUnit = $allValidUnits->count();
        $dynamicLimit = $jumlahUnit > 0 ? ($totalBiayaSeluruhUnit / $jumlahUnit) : 0;

        Log::info('Dashboard Calculation Log', [
            'user'          => $user->username,
            'total_biaya'   => $totalBiayaSeluruhUnit,
            'jumlah_unit'   => $jumlahUnit,
            'limit_result'  => $dynamicLimit,
            'can_view_all'  => $canViewAllUnits
        ]);

        // =============================================================
        // DATA ANGGARAN PER UNIT (Untuk Tabel)
        // =============================================================
        
        // Tentukan unit mana yang akan ditampilkan di tabel user
        $displayedUnits = $allValidUnits;
        if (!$canViewAllUnits && $unitId) {
            $displayedUnits = $allValidUnits->where('id', $unitId);
        }

        // Ambil data 'Used' (biaya yang sudah terpakai/active)
        $budgetData = DB::table('training_request') 
            ->join('employees', 'training_request.employee_id', '=', 'employees.id')
            ->select('employees.unit_id', DB::raw('SUM(training_request.realisasi_biaya_pelatihan) as total_used'))
            ->where('training_request.status_approval_training', 'approved')
            ->whereIn('employees.unit_id', $allValidUnitIds)
            ->groupBy('employees.unit_id')
            ->get()
            ->keyBy('unit_id');

        // Mapping data unit dengan limit dan realisasi
        $unitBudgets = $displayedUnits->map(function ($unit) use ($budgetData, $dynamicLimit) {
            $used = $budgetData->get($unit->id)->total_used ?? 0;

            return [
                'unit_id'    => $unit->id,
                'unit_name'  => $unit->name,
                'category'   => $unit->category,
                'used'       => (float) $used,
                'limit'      => $dynamicLimit,
                'remaining'  => max($dynamicLimit - $used, 0),
                'percentage' => $used > 0 && $dynamicLimit > 0
                    ? min(round(($used / $dynamicLimit) * 100, 1), 100)
                    : 0,
            ];
        });

        // =============================================================
        // 5. STATISTIK COUNTER & DATA TERBARU
        // =============================================================
        
        // Statistik LNA (hanya untuk unit valid)
        $referenceCounts = TrainingReference::whereIn('unit_id', $allValidUnitIds)
            ->groupBy('status_training_reference')
            ->select('status_training_reference', DB::raw('count(*) as total'))
            ->pluck('total', 'status_training_reference');

        // Statistik Request (hanya untuk unit valid)
        $requestCounts = TrainingRequest::whereHas('employee', fn($q) => $q->whereIn('unit_id', $allValidUnitIds))
            ->groupBy('status_approval_training')
            ->select('status_approval_training', DB::raw('count(*) as total'))
            ->pluck('total', 'status_approval_training');

        // Tabel Request Terbaru (5 Data Terakhir)
        // $recentRequests = TrainingRequest::with(['employee.person', 'trainingReference'])
        //     ->whereHas('employee', function ($q) use ($unitId, $canViewAllUnits, $allValidUnitIds) {
        //         $q->whereIn('unit_id', $allValidUnitIds);
        //         if (!$canViewAllUnits && $unitId) {
        //             $q->where('unit_id', $unitId);
        //         }
        //     })
        //     ->orderBy('created_at', 'desc')
        //     ->limit(5)
        //     ->get();

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
            // 'recentRequests',
            'unitBudgets',
            'dynamicLimit'
        ));
    }

    public function getDataEvaluation()
    {
        try {
            $dataEvaluation = Training::where('status_approval_training_id', 4)->get();

            return response()->json([
                'status' => 'success',
                'data' => $dataEvaluation
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function inputEvaluation(Request $request)
    {
        $validated = $request->validate([
            'training_id' => 'required|exists:training,id',
            'evaluasi'    => 'required|string|max:1000',
        ]);

        try {
            $training = Training::where('id', $validated['training_id'])->first();

            if (!$training) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data pelatihan tidak ditemukan.',
                ], 404);
            }
            
            $training->evaluation = $validated['evaluasi'];
            $training->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data evaluasi berhasil disimpan!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data evaluasi.',
            ], 500);
        }
    }

    public function getDetailEvaluation($id)
    {
        try {
            $training = Training::with('trainingTemp')->find($id);
            
            $data = [
                'training_id' => $training->id,
                'nama_pelatihan' => $training->nama_pelatihan,
                'nama_peserta' => $training->nama_peserta,
                'end_date' => $training->trainingTemp?->end_date,
                'realisasi_date' => $training->realisasi_date,
                'certificate_document' => $training->certificate_document,
                'evaluasi' => $training->evaluasi,
            ];

            Log::info('Data yang dikirim:', ['data' => $data]);

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadCertifEvaluation(Request $request)
    {
        try {
            Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'training_id_upload' => 'required|exists:training,id',
                'certificate_document' => 'required',
            ]);
            Log::info('Validated data:', $validated);
    
            $dataTraining = Training::findOrFail($validated['training_id_upload']);

            $trainingTemp = $dataTraining->trainingTemp ?? null;

            Log::info('Data training');
            Log::info('Cek data TrainingTemp (setelah perbaikan)', [
                'training_id_upload' => $validated['training_id_upload'],
                'found' => $trainingTemp ? true : false,
                'data' => $dataTraining->training_temp_id ?? null,
            ]);
            
            if (!$trainingTemp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data training temp tidak ditemukan',
                    'id' => $validated['training_id_upload']
                ], 400);
            } else {
                Log::warning('Data TrainingTemp tidak ditemukan', [
                    'id' => $validated['training_id_upload']
                ]);
            }
            Log::info('abcd');

            $realisasiDate = $dataTraining->realisasi_date;
            $endDate = $trainingTemp->end_date;

            Log::info('Tanggal realisasi dan tanggal selesai pelatihan', [
                'realisasiDate' => $realisasiDate,
                'endDate' => $endDate
            ]);
    
            if (!$realisasiDate || !$endDate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tanggal realisasi atau tanggal selesai pelatihan belum diisi.'
                ], 400);
            }

            $realisasi = Carbon::parse($realisasiDate);
            $end = Carbon::parse($endDate);
            $monthDiff = $end->diffInMonths($realisasi, false);
    
            if ($monthDiff > 3) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengunggahan sertifikat sudah melewati batas waktu 3 bulan setelah tanggal realisasi.'
                ], 400);
            }

            if ($request->hasFile('certificate_document')) {
                $path = $request->file('certificate_document')->store('sertifikat', 'public');
                $dataTraining->certificate_document = $path;
            }
        
            $dataTraining->save();
        
            Log::info('Data saved successfully', ['id' => $dataTraining->id]);
        
            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen sertifikat berhasil diupload',
                'path' => $dataTraining->certificate_document
            ]);

        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateJenisPelatihan(Request $request, $id)
    {
        try {
            Log::info('Mulai update jenis pelatihan', [
                'training_temp_id' => $id,
                'input' => $request->all(),
                'user' => auth()->user()?->id,
            ]);
    
            // ✅ Validasi dasar
            $validated = $request->validate([
                'jenis_pelatihan' => 'required|string|max:255',
            ]);
    
            // 🔍 Log setelah validasi berhasil
            Log::info('Validasi berhasil', [
                'training_temp_id' => $id,
                'jenis_pelatihan_baru' => $validated['jenis_pelatihan']
            ]);

            $training = TrainingTemp::findOrFail($id);
            if(!$training) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pelatihan tidak ditemukan'
                ], 404);
            }
            
            $training->jenis_pelatihan = $validated['jenis_pelatihan'];
            $training->save();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Jenis pelatihan berhasil diperbarui'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function updateRealisasiDate(Request $request, $id)
    {
        try {
            Log::info('Mulai update realisasi date', [
                'training_temp_id' => $id,
                'input' => $request->all(),
                'user' => auth()->user()?->id,
            ]);
    
            $validated = $request->validate([
                'realisasi_date' => 'required|date',
            ]);
    
            Log::info('Validasi berhasil', [
                'training_temp_id' => $id,
                'realisasi_date_baru' => $validated['realisasi_date']
            ]);

            $training = Training::findOrFail($id);
            $training->realisasi_date = $validated['realisasi_date'];
            $training->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggal realisasi berhasil diperbarui'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        }
    }

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
