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

    public function index()
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
    
        // mapping role ke table-name
        $tableMap = [
            'DHC'     => 'dhc-unit-table',
            'SDM Unit'     => 'sdm-unit-table',
            'GM/VP Unit'   => 'sdm-unit-table',
            'Kepala Unit'  => 'kepala-unit-table',
        ];

        Log::info("Role: " . $role);
        Log::info("Table: " . $tableMap[$role]);
    
        // fallback jika role tidak ada dalam map
        $tableView = $tableMap[$role] ?? 'default-table';

        return view('training.training-request.index', compact('tableView'));
    }
    
    public function importLna(Request $request)
    {
        $request->validate([
            "chunk" => "required|file",
            "index" => "required|integer",
            "total" => "required|integer",
            "filename" => "required|string",
        ]);

        try {
            $chunk = $request->file('chunk');
            $index    = (int) $request->index;
            $total    = (int) $request->total;
            $filename = trim($request->filename);
            
            if ($index < 0 || $index >= $total) {
                return response()->json([
                    "status"  => "error",
                    "message" => "Index chunk tidak valid.",
                ], 422);
            }

            $safeFilename = basename($filename);

            $tempDir = "chunks/{$safeFilename}";
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

            $uploadsDir = 'uploads';
            if (!Storage::exists($uploadsDir)) {
                Storage::makeDirectory($uploadsDir);
            }

            $finalName = time() . "_" . $safeFilename;
            $finalPath = "{$uploadsDir}/{$finalName}";
            $fullPath  = storage_path("app/{$finalPath}");
            
            Log::info("File final selesai digabung: {$finalName}");

            $output = @fopen($fullPath, "ab");
            if ($output === false) {
                Log::error("Gagal membuka file final untuk ditulis: {$fullPath}");
                return response()->json([
                    "status"  => "error",
                    "message" => "Tidak dapat membuat file gabungan di server.",
                ], 500);
            }

            for ($i = 0; $i < $total; $i++) {
                $cPath = "{$tempDir}/chunk_{$i}.part";
    
                if (!Storage::exists($cPath)) {
                    return response()->json([
                        "status" => "error",
                        "message" => "Missing chunk {$i}"
                    ], 500);
                }
    
                $content = Storage::get($cPath);
                if (fwrite($output, $content) === false) {
                    fclose($output);
                    Log::error("Gagal menulis chunk {$i} ke file final.");

                    return response()->json([
                        "status"  => "error",
                        "message" => "Gagal menulis chunk {$i} ke file final.",
                    ], 500);
                }
            }

            fclose($output);

            // Hapus chunk
            Storage::deleteDirectory($tempDir);
    
            Log::info("File final selesai digabung: {$finalName}");

            $importResult = $this->importService->handleImport(
                $fullPath,
                auth()->id()
            );

            // Hapus file final setelah berhasil diproses
            if (Storage::exists($finalPath)) {
                Storage::delete($finalPath);
                Log::info("File final {$finalName} dihapus setelah import.");
            }

            // Gunakan angka dari service
            $importedRows  = $importResult['imported_rows']  ?? 0;
            $processedRows = $importResult['processed_rows'] ?? 0;
            $message       = $importResult['message']
                ?? "Berhasil mengimport {$importedRows} baris.";

            return response()->json([
                "status"         => "success",
                "message"        => $message,
                "imported_rows"  => $importedRows,
                "processed_rows" => $processedRows,
                "data"           => $importResult,
            ]);
    
        } catch (\Exception $e) {
            Log::error("Error import chunk: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    public function getDataLna(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 12);
            $page = $request->input('page', 1);
    
            $data = TrainingReference::orderBy('created_at', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                "status" => "success",
                "data" => $data->items(),
                "pagination" => [
                    "current_page" => $data->currentPage(),
                    "last_page" => $data->lastPage(),
                    "per_page" => $data->perPage(),
                    "total" => $data->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetch data: " . $e->getMessage());
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

            Log::info("Training request list:", $trainingRequest);

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

    public function lnaStore(Request $request) {
        Log::info('store');
    }
}
