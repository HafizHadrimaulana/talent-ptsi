<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\TrainingImportServices;
use Illuminate\Support\Facades\Storage;
use App\Models\TrainingReference;
use App\Models\Employee;
use App\Models\TrainingRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingRequestController extends Controller
{
    protected $importService;

    public function __construct(TrainingImportServices $importService)
    {
        $this->importService = $importService;
    }

    public function getDataLna()
    {
        return view('training.training-request.index');
    }
    
    public function importLna(Request $request)
    {
        Log::info("Mulai import LNA.");
        try {
            $request->validate([
                "chunk" => "required|file",
                "index" => "required|integer",
                "total" => "required|integer",
                "filename" => "required|string",
            ]);

            $chunk = $request->file('chunk');
            $index = $request->index;
            $total = $request->total;
            $filename = $request->filename;

            $tempDir = "chunks/{$filename}";

            if (!Storage::exists($tempDir)) {
                Storage::makeDirectory($tempDir);
            }

            $chunkPath = "{$tempDir}/chunk_{$index}.part";
            Storage::put($chunkPath, file_get_contents($chunk));

            Log::info("Chunk " . $index . " berhasil di-upload.");

            if ($index + 1 < $total) {
                return response()->json([
                    "status"  => "success",
                    "message" => "Chunk {$index} uploaded."
                ]);
            }

            $finalName = time() . "_" . $filename;
            $finalPath = "uploads/{$finalName}";
            
            Log::info("File final selesai digabung: {$finalName}");

            $output = fopen(storage_path("app/{$finalPath}"), "ab");

            for ($i = 0; $i < $total; $i++) {
                $cPath = "{$tempDir}/chunk_{$i}.part";
    
                if (!Storage::exists($cPath)) {
                    return response()->json([
                        "status" => "error",
                        "message" => "Missing chunk {$i}"
                    ], 500);
                }
    
                fwrite($output, Storage::get($cPath));
            }

            fclose($output);

            // Hapus chunk
            Storage::deleteDirectory($tempDir);
    
            Log::info("File final selesai digabung: {$finalName}");

            $importResult = $this->importService->handleImport(
                storage_path("app/{$finalPath}"),
                auth()->id()
            );

            return response()->json([
                "status" => "success",
                "message" => "Chunk {$index} uploaded.",
                "data" => $importResult
            ]);
    
        } catch (\Exception $e) {
            Log::error("Error import chunk: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    public function getDataTrainingReferences($unitId)
    {
        try {
            Log::info("Mulai fetch data training references.", ["unit_id" => $unitId]);
            $data = TrainingReference::where('unit_id', $unitId)
                ->select(
                    'id',
                    'judul_sertifikasi',
                    'penyelenggara',
                    'jumlah_jam',
                    'jenis_portofolio',
                    'fungsi',
                    'waktu_pelaksanaan',
                    'nama_proyek',
                    'biaya_pelatihan',
                    'uhpd',
                    'biaya_akomodasi',
                    'estimasi_total_biaya'
                )
                ->orderBy('judul_sertifikasi')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetch data: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    public function getEmployeeByUnit($unitId)
    {
        try {
            Log::info("Mulai fetch employee by unit.", ["unit_id" => $unitId]);
        
            $employees = Employee::with('person')
                ->where('unit_id', $unitId)
                ->get()
                ->map(function ($emp) {
                    return [
                        'id' => $emp->id,
                        'name' => $emp->person->full_name ?? '-',
                        'person_id' => $emp->person_id,
                    ];
                });
        
            return response()->json([
                'status' => 'success',
                'data' => $employees
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetch data: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    public function inputTrainingRequest(Request $request)
    {
        DB::beginTransaction();

        try {
            Log::info("Mulai input training request:", $request->all());

            $payload = json_decode($request->data, true);

            if (!$payload) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payload tidak valid'
                ], 400);
            }

            Log::info("Payload hasil decode:", $payload);

            $employeeIds = collect($payload['peserta_list'])
                ->pluck('id')
                ->filter()
                ->values()
                ->all();

            if (empty($employeeIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Peserta kosong'
                ], 400);
            }

            // Pastikan employee ada
            $validEmployees = Employee::whereIn('id', $employeeIds)
                ->pluck('id')
                ->all();

            $missing = array_values(array_diff($employeeIds, $validEmployees));

            if (!empty($missing)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Employee tidak ditemukan',
                    'missing_ids' => $missing
                ], 400);
            }

            // Upload file lampiran jika ada
            $lampiran = null;
            if ($request->hasFile('lampiran_penawaran')) {
                $lampiran = $request->file('lampiran_penawaran')
                    ->store('lampiran_penawaran', 'public');
            }
            $insertedRequests = [];

            foreach ($employeeIds as $employeeId) {
                $created[] = TrainingRequest::create([
                    'training_reference_id'      => $payload['judul_sertifikasi'],
                    'employee_id'                => $employeeId,
                    'status_approval_training'   => 'created',
                    'start_date'                 => $payload['start_date'],
                    'end_date'                   => $payload['end_date'],
                    'estimasi_total_biaya'       => $payload['estimasi_total_biaya'],
                    'lampiran_penawaran'         => $lampiran,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Training request berhasil disimpan',
                'data' => $insertedRequests
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error input training request: " . $e->getMessage());

            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    public function getTrainingRequestList(Request $request, $unitId)
    {
        try {
            $trainingRequest = TrainingRequest::with([
                'trainingReference',
                'employee.person',
                'employee.unit'
            ])
            ->whereHas('employee', function ($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            })
            ->orderBy('id', 'desc')
            ->get();

            return response()->json([
                "status" => "success",
                "data" => $trainingRequest
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
        }
    }
}
