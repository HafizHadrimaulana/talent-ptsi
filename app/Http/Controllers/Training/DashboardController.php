<?php

namespace App\Http\Controllers\Training;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\TrainingTemp;
use App\Models\StatusApprovalTraining;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\QueryException;

use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    
    public function dataDashboard()
    {
        $counts = TrainingTemp::select('status_approval_training_id', DB::raw('count(*) as total'))
            ->groupBy('status_approval_training_id')
            ->pluck('total', 'status_approval_training_id');
        
        $statuses = StatusApprovalTraining::all();

        return view('training.dashboard.index', compact('counts', 'statuses'));
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
            'nama_pelatihan' => 'required',
            'nama_peserta' => 'required',
            'tanggal_realisasi' => 'nullable',
            'certificate_document' => 'nullable',
        ]);

        if ($request->hasFile('certificate_document')) {
            $validated['certificate_document'] = $request->file('certificate_document')->store('certificate_document', 'public');
        }

        Log::info('Validated data:', $validated);

        try {
            Evaluation::create($validated);
    
            return redirect()->back()->with('success', 'Data evaluasi berhasil ditambahkan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function getDataUploadCertif($id)
    {
        try {
            $training = Training::with('trainingTemp')
                ->findOrFail($id);

            $data = [
                'id' => $training->id,
                'nama_pelatihan' => $training->nama_pelatihan,
                'nama_peserta' => $training->nama_peserta,
                'end_date' => $training->trainingTemp?->end_date,
                'realisasi_date' => $training->realisasi_date,
                'certificate_document' => $training->certificate_document,
            ];

            Log::info("message", $data);

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            
        }
    }

    public function uploadCertifEvaluation(Request $request)
    {
        try {
            Log::info('Request data:', $request->all());

            $validated = $request->validate([
                'training_id' => 'required|integer|exists:trainings,id',
                'certificate_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);
    
            $dataTraining = Training::findOrFail($validated['training_id']);
            $trainingTemp = TrainingTemp::find($validated['training_id']);
            
            if (!$trainingTemp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data training temp tidak ditemukan'
                ], 400);
            }

            $realisasiDate = $dataTraining->realisasi_date;
            $endDate = $trainingTemp->end_date;
    
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
}
