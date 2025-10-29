<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FileTraining;
use App\Models\Training;
use App\Models\StatusApprovalTraining;
use App\Imports\Training\TrainingImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\Training\TrainingImportServices;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    protected $importService;

    public function __construct(TrainingImportServices $importService)
    {
        $this->importService = $importService;
    }

    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => ['required', 'mimes:xlsx,xls']
            ]);
    
            $fileTraining = $this->importService->handleImport(
                $request->file('file'),
                auth()->user()->id ?? 0
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diimport!',
                'data' => [
                    'file_name' => $fileTraining->file_name,
                    'rows' => $fileTraining->rows,
                    'imported_by' => $fileTraining->imported_by,
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
    
        } catch (\Exception $e) {
            \Log::error('Error saat import data', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengimport data.',
            ], 500);
        }
    }

    public function list()
    {
        $query = Training::query()
            ->with('statusApproval');
    
        if (auth()->user()->hasRole('GM/VP Unit')) {
            $query->where('status_approval_training_id', 2);
        } elseif (auth()->user()->hasRole('DHC Unit') || auth()->user()->hasRole('VP DHC')) {
            $query->where('status_approval_training_id', 3);
        }
    
        $list = $query->orderBy('id', 'asc')->paginate(12);

        return response()->json([
            'status' => 'success',
            'data' => $list,
        ], 200);
    }

    public function updateAllStatus(Request $request)
    {
        $user = auth()->user()->load('roles');
        \Log::info("User yang melakukan approve semua data", [
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $user->getRoleNames(),
        ]);
    
        $statusId = match (true) {
            $user->hasRole('SDM Unit') => 2,
            $user->hasRole('GM/VP Unit') => 3,
            $user->hasRole('DHC Unit'),
            $user->hasRole('VP DHC') => 4,
            $user->hasRole('Admin') => 4,
            default => null,
        };
    
        if (!$statusId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk melakukan approval.',
            ], 403);
        }
    
        $count = Training::whereNull('status_approval_training_id')
            ->orWhere('status_approval_training_id', '<', $statusId)
            ->update([
                'status_approval_training_id' => $statusId,
            ]);
    
        return response()->json([
            'status' => 'success',
            'message' => $count . ' data berhasil di-approve oleh ' . $user->getRoleNames()->first(),
        ]);
    }

    public function destroy($id)
    {
        $item = Training::find($id);
        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil dihapus!',
        ], 200);
    }

    public function input(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'nullable',
            'nama_peserta' => 'nullable',
            'status_pegawai' => 'nullable',
            'jabatan_saat_ini' => 'nullable',
            'unit_kerja' => 'nullable',
            'judul_sertifikasi' => 'nullable',
            'penyelenggara' => 'nullable',
            'jumlah_jam' => 'nullable|integer',
            'waktu_pelaksanaan' => 'nullable',
            'nama_proyek' => 'nullable',
            'biaya_pelatihan' => 'nullable|numeric',
            'uhpd' => 'nullable|numeric',
            'biaya_akomodasi' => 'nullable|numeric',
            'estimasi_total_biaya' => 'nullable|numeric',
            'jenis_portofolio' => 'nullable',
            'alasan' => 'nullable',
        ]);

        $latestFileTraining = FileTraining::latest()->first();

        $validated['file_training_id'] = $latestFileTraining?->id;
        $validated['status_approval_training_id'] = 6;

        try {
            $training = Training::create($validated);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Data pelatihan berhasil ditambahkan!',
                'data' => $training,
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejectStatus($id)
    {
        $training = Training::find($id);

        if (in_array($training->status_approval_training_id, [4, 5])) {
            return redirect()->back()->with('error', 'Data ini sudah tidak bisa ditolak.');
        }
        
        $training->update([
            'status_approval_training_id' => 5,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('ok', 'Status approval berhasil diperbarui menjadi ditolak.');
    }

    public function bulkApprove(Request $request)
    {
        $selectedIds = $request->input('selected', []);
        \Log::info("selected id", $selectedIds);

        if (empty($selectedIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data yang dipilih.',
            ], 400);
        }

        $user = auth()->user()->load('roles');
        \Log::info("User yang melakukan approval", [
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $user->getRoleNames(),
        ]);

        $statusId = match (true) {
            $user->hasRole('SDM Unit') => 2,
            $user->hasRole('GM/VP Unit') => 3,
            $user->hasRole('VP DHC') => 4,
            default => null,
        };
    
        if (!$statusId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk melakukan approval.',
            ], 403);
        }

        Training::whereIn('id', $selectedIds)->update([
            'status_approval_training_id' => $statusId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => count($selectedIds) . ' data berhasil di-approve oleh ' . $user->getRoleNames()->first(),
        ]);

        // $allowedTransitions = [
        //     1 => 2,
        //     2 => 3,
        //     3 => 4,
        // ];
    
        // $updatedCount = 0;
    
        // foreach ($trainings as $training) {
        //     $currentStatus = $training->status_approval_training_id;
    
        //     if (isset($allowedTransitions[$currentStatus])) {
        //         $training->update([
        //             'status_approval_training_id' => $allowedTransitions[$currentStatus],
        //             'updated_at' => now(),
        //         ]);
        //         $updatedCount++;
        //     }
        // }
    
        // return redirect()->back()->with(
        //     'ok',
        //     "{$updatedCount} data berhasil diperbarui status approval-nya."
        // );
    }

    public function getEditData($id)
    {
        $item = Training::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }

    public function update(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        $training->update([
            'nik' => $request->nik,
            'nama_peserta' => $request->nama_peserta,
            'unit_kerja' => $request->unit_kerja,
            'judul_sertifikasi' => $request->judul_sertifikasi,
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diperbarui!',
        ], 200);
    }

    public function downloadTemplate()
    {
        $filePath = public_path('templates/training-header-template.xlsx');

        if (!file_exists($filePath)) {
            abort(404, 'File template tidak ditemukan.');
        }
    
        return response()->download($filePath, 'Template_Training.xlsx');
    }
}