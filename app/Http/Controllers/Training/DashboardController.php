<?php

namespace App\Http\Controllers\Training;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\StatusApprovalTraining;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\QueryException;

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

    public function getDataEvaluation()
    {
        try {
            $dataEvaluation = Evaluation::all();

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
            'dokumen_sertifikat' => 'nullable',
        ]);

        if ($request->hasFile('dokumen_sertifikat')) {
            $validated['dokumen_sertifikat'] = $request->file('dokumen_sertifikat')->store('dokumen_sertifikat', 'public');
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
        $dataEvaluation = Evaluation::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $dataEvaluation
        ]);
    }

    public function uploadCertifEvaluation(Request $request)
    {
        try {
            Log::info('Request data:', $request->all());

            // $request->validate([
            //     'evaluation_id' => 'required|exists:evaluations,id',
            //     'dokumen_sertifikat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            // ]);

            if (!$request->hasFile('dokumen_sertifikat')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dokumen sertifikat tidak ditemukan'
                ], 400);
            }
            
            $dataEvaluation = Evaluation::findOrFail($request->evaluation_id);
        
            if ($request->hasFile('dokumen_sertifikat')) {
                $path = $request->file('dokumen_sertifikat')->store('sertifikat', 'public');
                $dataEvaluation->dokumen_sertifikat = $path;
            }
        
            $dataEvaluation->save();
        
            Log::info('Data saved successfully', ['id' => $dataEvaluation->id]);
        
            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen sertifikat berhasil diupload',
                'path' => $dataEvaluation->dokumen_sertifikat
            ]);

        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
