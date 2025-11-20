<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FileTraining;
use App\Models\Training;
use App\Models\TrainingTemp;
use App\Models\StatusApprovalTraining;
use App\Imports\TrainingImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TrainingImportServices;
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
                'message' => $fileTraining->rows . ' data berhasil diimport!',
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
        $query = TrainingTemp::query()
            ->with('statusApproval');
    
        if (auth()->user()->hasRole('GM/VP Unit')) {
            $query->where('status_approval_training_id', 2);
        } elseif (auth()->user()->hasRole('DHC Unit') || auth()->user()->hasRole('VP DHC')) {
            $query->where('status_approval_training_id', 3);
        } elseif (auth()->user()->hasRole('DBS Unit')) {
            $query->where('status_approval_training_id', 6);
        }
    
        $list = $query->orderBy('id', 'asc')->paginate(12);

        return response()->json([
            'status' => 'success',
            'message' => 'Data training berhasil ditambahkan',
            'data' => $list,
        ], 200);
    }

    // Approve all data
    public function updateAllStatus(Request $request)
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        $query = TrainingTemp::query()
            ->whereNotIn('status_approval_training_id', [4, 5]);

        $trainings = $query->get();

        if ($trainings->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data yang dapat di-approve.',
            ], 404);
        }

        $invalidTrainings = $trainings->filter(function ($training) {
            return empty($training->jenis_pelatihan) || empty($training->start_date) || empty($training->end_date);
        });
    
        if ($invalidTrainings->count() > 0) {
            $invalidIds = $invalidTrainings->pluck('id')->implode(', ');
            Log::warning('Data belum lengkap saat updateAllStatus', [
                'invalid_ids' => $invalidIds,
                'count' => $invalidTrainings->count(),
                'user' => $user->id,
                'role' => $role,
            ]);
    
            return response()->json([
                'status' => 'error',
                'message' => "Terdapat data yang belum lengkap (jenis pelatihan / tanggal mulai / tanggal selesai belum diisi): ID {$invalidIds}",
            ], 422);
        }

        $count = 0;
    
        foreach ($trainings as $training) {
            try {
                $this->processApproval($training, $role);
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "{$count} data berhasil di-approve oleh {$role}.",
        ]);
    }

    // Approve bulk data
    public function bulkApprove(Request $request)
    {
        $selectedIds = (array) $request->input('selected', []);
        Log::info('selected id', $selectedIds);
        
        if (empty($selectedIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data yang dipilih.',
            ], 400);
        }

        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        $trainings = TrainingTemp::whereIn('id', $selectedIds)
            ->whereNotIn('status_approval_training_id', [4, 5])
            ->get();

        $invalidTrainings = $trainings->filter(function ($t) {
            return empty($t->jenis_pelatihan) || empty($t->start_date) || empty($t->end_date);
        });
    
        if ($invalidTrainings->count() > 0) {
            $invalidIds = $invalidTrainings->pluck('id')->implode(', ');
            return response()->json([
                'status' => 'error',
                'message' => "Terdapat data yang belum lengkap (jenis pelatihan / tanggal belum diisi): ID {$invalidIds}",
            ], 422);
        }

        $approvedCount = 0;

        foreach ($trainings as $trainingTemp) {
            try {
                $this->processApproval($trainingTemp, $role);
                $approvedCount++;
            } catch (\Exception $e) {
                Log::warning("Skip ID {$trainingTemp->id}: {$e->getMessage()}");
                continue;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "{$approvedCount} data berhasil di-approve oleh {$role}.",
        ]);
    }

    // Approve single data
    public function approveStatus($id)
    {
        $trainingTemp = TrainingTemp::find($id);
        if (!$trainingTemp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        try {
            $this->processApproval($trainingTemp, $role);
            return response()->json([
                'status' => 'success',
                'message' => "Data ID {$id} berhasil di-approve oleh {$role}.",
            ]);
        } catch (\Exception $e) {
            Log::error('Approve error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function rejectStatus($id)
    {
        $training = TrainingTemp::find($id);

        if (!$training) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }
    
        $user = auth()->user()->load('roles');
        $role = $user->getRoleNames()->first();
    
        if (in_array($training->status_approval_training_id, [4, 5])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data ini sudah final dan tidak bisa ditolak.',
            ], 400);
        }
    
        if (!in_array($role, ['GM/VP Unit', 'VP DHC', 'Admin', 'DBS Unit'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk menolak data ini.',
            ], 403);
        }

        switch ($role) {
            case 'GM/VP Unit':
                if ($training->status_approval_training_id !== 2) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Hanya data dengan status SDM Unit yang bisa ditolak oleh GM/VP Unit.',
                    ], 400);
                }
                break;
    
            case 'VP DHC':
                if ($training->status_approval_training_id !== 3) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Hanya data dengan status GM/VP Unit yang bisa ditolak oleh VP DHC.',
                    ], 400);
                }
                break;
    
            case 'DBS Unit':
                if ($training->status_approval_training_id !== 6) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Hanya data dengan status DBS yang bisa ditolak oleh DBS Unit.',
                    ], 400);
                }
                break;
        }
    
        $training->update([
            'status_approval_training_id' => 5,
            'updated_at' => now(),
        ]);
    
        return response()->json([
            'status' => 'success',
            'message' => "Data ID {$id} berhasil ditolak oleh {$role}.",
        ]);
    }

    public function destroy($id)
    {
        $item = TrainingTemp::find($id);

        if (!$item) {
            Log::warning('Data training tidak ditemukan saat delete', ['id' => $id]);
    
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }
    
        $item->delete();
    
        Log::info('Data training berhasil dihapus', ['id' => $id]);
    
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
            'jenis_pelatihan' => 'nullable',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
        ]);

        $latestFileTraining = FileTraining::latest()->first();

        $validated['file_training_id'] = $latestFileTraining?->id;
        $validated['status_approval_training_id'] = 6;

        try {
            $training = TrainingTemp::create($validated);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Data pelatihan berhasil ditambahkan!',
                'data' => $training,
            ], 201);
    
        } catch (\Exception $e) {

            Log::error('Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEditData($id)
    {
        $item = TrainingTemp::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $item
        ]);
    }

    public function update(Request $request, $id)
    {
        $training = TrainingTemp::findOrFail($id);

        try {
            $training->update([
                'nik' => $request->nik,
                'nama_peserta' => $request->nama_peserta,
                'unit_kerja' => $request->unit_kerja,
                'judul_sertifikasi' => $request->judul_sertifikasi,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diperbarui!',
            ], 200);

        } catch (ValidationException $e) {
            Log::info('eror', $e->errors());
            return response()->json([
                'status' => 'error',
                'message' => 'Edit gagal',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function downloadTemplate()
    {
        $filePath = public_path('templates/training-header-template.xlsx');

        if (!file_exists($filePath)) {
            abort(404, 'File template tidak ditemukan.');
        }

        return response()->download($filePath, 'Template_Training.xlsx');
    }

    // To approve training
    private function processApproval(TrainingTemp $trainingTemp, string $role): void
    {
        $statusMap = [
            'SDM Unit'   => 2,
            'GM/VP Unit' => 3,
            'VP DHC'     => 4,
            'DBS Unit'     => 2,
        ];

        if (!isset($statusMap[$role])) {
            throw new \Exception('Anda tidak memiliki izin untuk approve.');
        }

        $newStatus = $statusMap[$role];

        $allowedStatus = [
            1 => 2,
            2 => 3,
            3 => 4,
        ];

        if (isset($allowedStatus[$trainingTemp->status_approval_training_id]) &&
            $allowedStatus[$trainingTemp->status_approval_training_id] !== $newStatus) {
            throw new \Exception("Data ID {$trainingTemp->id} tidak bisa di-approve oleh {$role}.");
        }

        $trainingTemp->update([
            'status_approval_training_id' => $newStatus,
            'updated_at' => now(),
        ]);

        Log::info("TrainingTemp ID {$trainingTemp->id} updated", [
            'new_status' => $newStatus,
            'role' => $role,
        ]);

        if ($newStatus === 4) {
            $this->insertToTraining($trainingTemp);
        }
    }

    private function insertToTraining(TrainingTemp $trainingTemp)
    {
        Log::info('trainign temp');
        Training::updateOrCreate(
            ['training_temp_id' => $trainingTemp->id],
            [
                'status_approval_training_id' => 4,
                'nama_pelatihan' => $trainingTemp->judul_sertifikasi,
                'nama_peserta' => $trainingTemp->nama_peserta,
                ]
            );
        Log::info('asde');
    }
}