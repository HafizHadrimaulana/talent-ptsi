<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\TrainingImportServices;
use Illuminate\Support\Facades\Storage;
use App\Models\TrainingReference;
use App\Models\Employee;
use App\Models\TrainingRequest;
use App\Models\Unit;
use App\Models\User;
use App\Models\TrainingEvaluationQuestion;
use App\Models\TrainingEvaluationAnswer;

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
        $employeeId = $user->employee?->id;

        if (!$employeeId) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $sedangBerjalan = TrainingRequest::where('employee_id', $employeeId)
            ->where('status_approval_training', 'approved')
            ->count();
        
        $selesaiPelatihan = TrainingRequest::where('employee_id', $employeeId)
            ->where('status_approval_training', 'completed')
            ->count();

        $butuhEvaluasi = TrainingRequest::where('employee_id', $employeeId)
            ->whereIn('status_approval_training', [
                'approved'
            ])->count();

        $listTraining = TrainingRequest::with(['trainingReference'])
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'desc')
            ->where('status_approval_training', 'approved')
            ->get();

        Log::info('List Training', ['listTraining' => $listTraining]);

        return view('training.training-request.index', compact(
            'sedangBerjalan', 
            'selesaiPelatihan', 
            'butuhEvaluasi', 
            'listTraining'
        ));
    }

    public function getDetailTrainingRequest($id)
    {
        $item = TrainingRequest::with(['trainingReference', 'approvals'])->find($id);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $questions = [
            'penyelenggaraan' => TrainingEvaluationQuestion::where('category', 'penyelenggaraan')
                ->where('is_active', 1)
                ->orderBy('id')
                ->get(),

            'dampak' => TrainingEvaluationQuestion::where('category', 'dampak')
                ->where('is_active', 1)
                ->orderBy('id')
                ->get(),
        ];

        $data = [
            'id' => $item->id,
            'judul_sertifikasi' => $item->trainingReference?->judul_sertifikasi ?? 'Custom Training',
            'start_date' => $item->start_date,
            'end_date' => $item->end_date,
            'status_approval_training' => $item->status_approval_training,
            'approvals' => $item->approvals, // Jika ingin menampilkan timeline
            // ... tambahkan field lain yang dibutuhkan modal evaluasi
        ];

        Log::info('questions', ['questions' => $questions]);

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'questions' => $questions
        ]);
    }

    public function submitEvaluasiTraining(Request $request) 
    {
        $request->validate([
            'training_request_id' => 'required|exists:training_request,id',
            'answers' => 'required|array', 
        ]);

        $userId = auth()->user()->id;
        $trainingId = $request->training_request_id;

        try {
            DB::beginTransaction();

            foreach ($request->answers as $questionId => $score) {
                if ($score) {
                    TrainingEvaluationAnswer::updateOrCreate(
                        [
                            'training_request_id' => $trainingId,
                            'question_id'         => $questionId,
                            'user_id'             => $userId,
                        ],
                        [
                            'score' => $score,
                            'text_answer' => null
                        ]
                    );
                }
            }

            $trainingRequest = TrainingRequest::find($trainingId);
            // $trainingRequest->update([
            //     'evaluation_comments' => $request->komentar // Pastikan kolom ini sudah ada
            // ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Evaluasi berhasil disimpan']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }
    
    // Import LNA
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
            $index = (int) $request->index;
            $total = (int) $request->total;
            
            $safeName = pathinfo($request->filename, PATHINFO_FILENAME);

            // Simpan chunk
            $tempDir = $this->saveChunkFile($chunk, $index, $safeName);

            Log::info("Temp dir: " . $tempDir);
            
            if ($index + 1 < $total) {
                return response()->json([
                    "status"  => "success",
                    "message" => "Chunk {$index} uploaded."
                ]);
            }

            // Gabungkan chunks
            [$finalPath, $fullPath] = $this->mergeChunks($tempDir, $safeName, $total);

            if (!file_exists($fullPath)) {
                throw new \Exception("File gabungan tidak ditemukan");
            }

            Log::info("File gabungan: " . $fullPath);
            
            // Import sesuai LNA
            $result = $this->processImport($fullPath);

            // Hapus file gabungan
            Storage::delete($finalPath);

            return response()->json([
                "status"         => "success",
                "message"        => $result['message'] ?? 'Import selesai',
                "imported_rows"  => $result['imported_rows'] ?? 0,
                "processed_rows" => $result['processed_rows'] ?? 0,
                "data"           => $result
            ]);
    
        } catch (\Exception $e) {
            Log::error("Error import chunk: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    public function importTraining(Request $request)
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

            $tempDir = $this->saveChunkFile($chunk, $index, $total, $filename);
            
            if ($index + 1 < $total) {
                return response()->json([
                    "status"  => "success",
                    "message" => "Chunk {$index} uploaded."
                ]);
            }

            // Gabungkan chunks
            [$finalPath, $fullPath] = $this->mergeChunks($tempDir, $filename, $total);

            // Import sesuai LNA
            $result = $this->processImport($fullPath);

            // Hapus file gabungan
            Storage::delete($finalPath);

            return response()->json([
                "status"         => "success",
                "message"        => $result['message'] ?? 'Import selesai',
                "data"           => $result
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
            $user = auth()->user();
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search');

            $query = TrainingReference::with('unit')
                ->orderBy('created_at', 'desc');

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('judul_sertifikasi', 'like', "%{$search}%")
                    ->orWhere('penyelenggara', 'like', "%{$search}%")
                    ->orWhereHas('unit', function($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%");
                    });
                });
            }

            // 🔒 AKSES ROLE
            if (!$user->hasRole('DHC')) {
                $query->where('unit_id', $user->unit_id);
            }

            $data = $query->paginate($perPage, ['*'], 'page', $page);

            /**
             * ======================================
             * MAPPING RESPONSE
             * ======================================
             */
            $items = $data->getCollection(); // Mengambil collection dari paginator

            if ($items->isEmpty()) {
                $mappedItems = [];
            } else {
                $mappedItems = $items->map(function ($item) {
                    return [
                        "id"                => $item->id,
                        "judul_sertifikasi" => $item->judul_sertifikasi ?? "-",
                        "unit_id"           => $item->unit_id,
                        "unit_kerja"        => optional($item->unit)->name ?? "-",
                        "penyelenggara"     => $item->penyelenggara ?? "-",
                        "jumlah_jam"        => $item->jumlah_jam ?? 0,
                        "waktu_pelaksanaan" => $item->waktu_pelaksanaan,
                        "biaya_pelatihan"   => $item->biaya_pelatihan ?? 0,
                        "uhpd"              => $item->uhpd ?? 0,
                        "biaya_akomodasi"   => $item->biaya_akomodasi ?? 0,
                        "estimasi_total_biaya" => $item->estimasi_total_biaya ?? 0,
                        "nama_proyek"       => $item->nama_proyek ?? "-",
                        "jenis_portofolio"  => $item->jenis_portofolio ?? "-",
                        "fungsi"            => $item->fungsi ?? "-",
                        "status_training_reference" => $item->status_training_reference,
                        "created_at"        => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                    ];
                })->toArray(); // Pastikan diconvert ke array
            }

            Log::info("Jumlah data mentah: " . count($data->items()));
            Log::info("Isi data mentah: ", $data->items());

            return response()->json([
                "status" => "success",
                "data" => $mappedItems,
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

    public function getApprovalPengajuanTraining(Request $request)
    {
        try {
            $user  = auth()->user();
            $role  = $user->getRoleNames()->first();

            $unitId = optional($user->employee)->unit_id
                ?? optional($user->person)->unit_id;

            Log::info('getDataLna', [
                'user_id' => $user->id,
                'role'    => $role,
                'unit_id' => $unitId,
            ]);

            $perPage = $request->input('per_page', 12);

            $query = TrainingReference::with('unit')
                ->orderBy('created_at', 'desc');


            /**
             * ======================================
             * FILTER UNIT
             * ======================================
             */
            if ($role !== 'DHC') {
                // selain DHC → hanya unit sendiri
                if (!$unitId) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Unit user tidak ditemukan'
                    ], 422);
                }

                $query->where('unit_id', $unitId);
            }

            /**
             * ======================================
             * FILTER STATUS BERDASARKAN ROLE
             * ======================================
             */
            switch ($role) {

                case 'DBS Unit':
                    // hanya pending
                    $query->where('status_training_reference', 'pending');
                    break;

                case 'DHC':
                    // hanya in_review_dhc
                    $query->where('status_training_reference', 'in_review_dhc');
                    break;

                default:
                    // role lain → hanya active
                    $query->where('status_training_reference', 'active');
                    break;
            }

            $data = $query->paginate($perPage);


            return response()->json([
                'status' => 'success',
                'data'   => $data->items(),
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page'    => $data->lastPage(),
                    'per_page'     => $data->perPage(),
                    'total'        => $data->total(),
                ]
            ]);


        } catch (\Exception $e) {
            Log::error("Error fetch data: " . $e->getMessage());
        }
    }

    public function getDataTrainingReferences($unitId)
    {
        try {
            Log::info("Mulai fetch data training references.", ["unit_id" => $unitId]);

            // --- 1. Validasi apakah unit_id ada ---
            $unitExists = Unit::where('id', $unitId)->exists();

            if (!$unitExists) {
                Log::warning("Unit tidak ditemukan.", ["unit_id" => $unitId]);

                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Unit tidak ditemukan.',
                    'data' => []
                ], 404);
            }

            // --- 2. Ambil data training berdasarkan unit_id ---
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

            // --- 3. Jika data kosong ---
            if ($data->isEmpty()) {
                Log::info("Tidak ada data training references untuk unit ini.", ["unit_id" => $unitId]);

                return response()->json([
                    'status' => 'empty',
                    'message' => 'Tidak ada data training reference untuk unit tersebut.',
                    'data' => []
                ]);
            }

            // --- 4. Jika ada data ---
            Log::info("Fetch data training references selesai.", $data->toArray());

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
                        'employee_id' => $emp->employee_id,
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
                    'start_date'                 => $payload['start_date'],
                    'end_date'                   => $payload['end_date'],
                    'realisasi_biaya_pelatihan'  => $payload['realisasi_biaya_pelatihan'] ?? null,
                    'estimasi_total_biaya'       => $payload['estimasi_total_biaya'],
                    'status_approval_training'   => 'in_review_gmvp',
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

    public function getTrainingRequestList(Request $request, $unitId = null)
    {
        try {
            $user  = auth()->user();
            $roles = $user->getRoleNames()->toArray();

            // 1. Setup Query Dasar dengan Eager Loading
            // Memastikan semua relasi termasuk riwayat approval ditarik sekaligus
            $query = TrainingRequest::with([
                'trainingReference',
                'employee.person',
                'employee.unit',
                'approvals' => function($q) {
                    $q->orderBy('created_at', 'desc'); 
                }
            ]);

            Log::info('training.getTrainingRequestList.user', [
                'user_id' => $user->id,
                'roles'   => $roles,
                'unit_id' => $user->unit_id,
            ]);

            $perPage = $request->input('per_page', 12);
            $page    = $request->input('page', 1);

            /**
             * ==================================================
             * RULE AKSES UNIT
             * ==================================================
             */
            $isDHC            = $user->hasRole('DHC');
            $isAvpOrKepalaUnit  = $user->hasAnyRole(['AVP', 'Kepala Unit']);
            $isHumanCapital     = $this->isHumanCapital($user);

            $canSeeAllUnit =
                $isDHC ||
                ($isAvpOrKepalaUnit && $isHumanCapital);

            if (!$canSeeAllUnit) {
                $query->whereHas('employee', function ($q) use ($user) {
                    $q->where('unit_id', $user->unit_id);
                });
            }

            /**
             * ==================================================
             * RULE AKSES STATUS
             * ==================================================
             */
            $allowedStatuses = [];

            if (!in_array('SDM Unit', $roles)) {
                if (in_array('Kepala Unit', $roles) && !$isHumanCapital) {
                    $allowedStatuses = ['in_review_gmvp'];
                }
                elseif (in_array('DHC', $roles)) {
                    $allowedStatuses = ['in_review_dhc'];
                }
                elseif (in_array('AVP', $roles) && $isHumanCapital) {
                    $allowedStatuses = ['in_review_avpdhc'];
                }
                elseif (in_array('Kepala Unit', $roles) && $isHumanCapital) {
                    $allowedStatuses = ['in_review_vpdhc'];
                }
            }

            if (!empty($allowedStatuses)) {
                $query->whereIn('status_approval_training', $allowedStatuses);
            }

            /**
             * ==================================================
             * FETCH DATA (Eksekusi Query)
             * ==================================================
             */
            // Cukup panggil paginate satu kali di akhir rangkaian builder
            $trainingRequest = $query
                ->orderBy('id', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Debugging Log: Cek apakah item pertama punya approval
            $sampleApprovalCount = 0;
            if ($trainingRequest->count() > 0) {
                $sampleApprovalCount = $trainingRequest->items()[0]->approvals->count();
            }

            Log::info("Fetch data training request berhasil.", [
                "total_data" => $trainingRequest->total(),
                "sample_id" => $trainingRequest->count() > 0 ? $trainingRequest->items()[0]->id : null,
                "sample_approval_count" => $sampleApprovalCount
            ]);

            return response()->json([
                "status" => "success",
                "data" => $trainingRequest->items(),
                "pagination" => [
                    "current_page" => $trainingRequest->currentPage(),
                    "last_page"    => $trainingRequest->lastPage(),
                    "per_page"     => $trainingRequest->perPage(),
                    "total"        => $trainingRequest->total()
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('getTrainingRequestList error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat mengambil data."
            ], 500);
        }
    }

    // pengajuan LNA DHC dan SDM 
    public function inputLna(Request $request) 
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        Log::info("Mulai input LNA sdm request:", $request->all());

        $statusTrainingReference = 'active';

        if ($role === 'SDM Unit') {
            $unitId = optional($user->employee)->unit_id 
                ?? optional($user->person)->unit_id;

            if (!$unitId) {
                return response()->json([
                    "status" => "error",
                    "message" => "Unit kerja SDM Unit tidak ditemukan"
                ], 422);
            }

            // unit_id dari backend
            $request->merge([
                'unit_id' => $unitId
            ]);

            $statusTrainingReference = 'pending';
        };

        $request->merge([
            'biaya_pelatihan'      => $this->cleanRupiah($request->biaya_pelatihan),
            'uhpd'                 => $this->cleanRupiah($request->uhpd),
            'biaya_akomodasi'      => $this->cleanRupiah($request->biaya_akomodasi),
            'estimasi_total_biaya' => $this->cleanRupiah($request->estimasi_total_biaya),
        ]);

        try {
            // Validasi sederhana
            $request->validate([
                'unit_id'               => 'nullable|exists:units,id',
                'judul_sertifikasi'     => 'nullable|string|max:255',
                'penyelenggara'         => 'nullable|string|max:255',
                'jumlah_jam'            => 'nullable|string|max:255',
                'waktu_pelaksanaan'     => 'nullable|string|max:255',
                'biaya_pelatihan'       => 'nullable|numeric',
                'uhpd'                  => 'nullable|numeric',
                'biaya_akomodasi'       => 'nullable|numeric',
                'estimasi_total_biaya'  => 'nullable|numeric',
                'nama_proyek'           => 'nullable|string|max:255',
                'jenis_portofolio'      => 'nullable|string|max:255',
                'fungsi'                => 'nullable|string|max:255',
            ]);
    
            // Simpan ke tabel training_reference
            $data = TrainingReference::create([
                'judul_sertifikasi'      => $request->judul_sertifikasi,
                'unit_id'                => $request->unit_id,
                'penyelenggara'          => $request->penyelenggara,
                'jumlah_jam'             => $request->jumlah_jam,
                'waktu_pelaksanaan'      => $request->waktu_pelaksanaan,
                'nama_proyek'            => $request->nama_proyek,
                'jenis_portofolio'       => $request->jenis_portofolio,
                'fungsi'                 => $request->fungsi,
                'biaya_pelatihan'        => $request->biaya_pelatihan,
                'uhpd'                   => $request->uhpd,
                'biaya_akomodasi'        => $request->biaya_akomodasi,
                'estimasi_total_biaya'   => $request->estimasi_total_biaya,

                // pending jika sdm unit
                'status_training_reference' => $statusTrainingReference,
            ]);
    
            Log::info("Data training berhasil disimpan.", $data->toArray());
    
            return response()->json([
                "status" => "success",
                'message' => 'Data training berhasil disimpan.',
                'data'    => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
        }
        
    }

    public function getDataUnits(Request $request) 
    {
        try {
            // Units for selector (label=name, value=id)
            $units = DB::table('units')
                ->select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();
            Log::info("Data units:", $units->toArray());
            return response()->json([
                "status" => "success",
                "data" => $units
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetch data: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    public function getLnaById($id)
    {
        $item = TrainingReference::with('unit:id,name')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $item->id,
                'judul_sertifikasi' => $item->judul_sertifikasi,
                'penyelenggara' => $item->penyelenggara,
                'jumlah_jam' => $item->jumlah_jam,
                'waktu_pelaksanaan' => $item->waktu_pelaksanaan,
                'biaya_pelatihan' => $item->biaya_pelatihan,
                'uhpd' => $item->uhpd,
                'biaya_akomodasi' => $item->biaya_akomodasi,
                'estimasi_total_biaya' => $item->estimasi_total_biaya,
                'nama_proyek' => $item->nama_proyek,
                'jenis_portofolio' => $item->jenis_portofolio,
                'fungsi' => $item->fungsi,

                // hanya nama unit
                'unit_kerja' => $item->unit?->name,
            ]
        ]);
    }

    public function editDataLna(Request $request, $id)
    {
        $item = TrainingReference::findOrFail($id);

        Log::info('item', $item->toArray());

        try {
            $data = [];

            // Field biasa (string)
            foreach ([
                'judul_sertifikasi',
                'penyelenggara',
                'jumlah_jam',
                'waktu_pelaksanaan',
                'nama_proyek',
                'jenis_portofolio',
                'fungsi',
            ] as $field) {
                $value = $request->input($field);
                $data[$field] = $value === '' ? null : $value;
            }

            if ($request->filled('unit_id')) {
                $data['unit_id'] = $request->unit_id;
            }
            
            // Field decimal (bersihkan Rupiah)
            foreach ([
                'biaya_pelatihan',
                'uhpd',
                'biaya_akomodasi',
                'estimasi_total_biaya',
            ] as $field) {
                $value = $request->input($field);

                if ($value === '' || $value === null) {
                    $data[$field] = null;
                } else {
                    // 🔥 HANYA AMBIL ANGKA
                    $data[$field] = preg_replace('/[^\d]/', '', $value);
                }
            }
            
            $item->update($data);

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

    public function destroyLna($id)
    {
        $item = TrainingReference::find($id);

        if (!$item) {
            Log::warning('Data lna tidak ditemukan saat delete', ['id' => $id]);
    
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        Log::info('status_training_reference', ['status_training_reference' => $item->status_training_reference]);

        if ($item->status_training_reference === 'cancelled') {
            Log::info('Percobaan nonaktifkan data LNA yang sudah tidak aktif', [
                'id' => $id
            ]);

            return response()->json([
                'status' => 'warning',
                'message' => 'Data sudah tidak aktif.',
            ], 409);
        }
        
        $item->update([
            'status_training_reference' => 'cancelled'
        ]);
    
        Log::info('Data training berhasil dihapus', ['id' => $id]);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil dihapus!',
        ], 200);
    }

    public function destroyTrainingRequest($id)
    {
        $trainingRequest = TrainingRequest::find($id);
        
        if (!$trainingRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        $trainingRequest->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil dihapus!',
        ], 200);
    }

    // PRIVATE FUNCTION //
    private function saveChunkFile($chunk, int $index, string $safeName): string
    {
        $hashDir = md5($safeName); // 🔥 CEGAH TABRAKAN FILE
        $tempDir = storage_path("app/chunks/{$hashDir}");

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $chunk->move($tempDir, "chunk_{$index}");

        return $tempDir;
    }

    private function mergeChunks(string $tempDir, string $safeName, int $total): array
    {
        $relativePath = "uploads/{$safeName}";
        $fullPath     = storage_path("app/{$relativePath}");

        $uploadDir = dirname($fullPath);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $output = fopen($fullPath, 'ab');

        for ($i = 0; $i < $total; $i++) {
            $chunkPath = "{$tempDir}/chunk_{$i}";

            if (!file_exists($chunkPath)) {
                fclose($output);
                throw new \Exception("Chunk {$i} tidak ditemukan");
            }

            $input = fopen($chunkPath, 'rb');
            stream_copy_to_stream($input, $output);
            fclose($input);
        }

        fclose($output);

        array_map('unlink', glob("{$tempDir}/chunk_*"));
        @rmdir($tempDir);

        return [$relativePath, $fullPath];
    }

    private function processImport($fullPath)
    {
        $result = $this->importService->handleImport(
            $fullPath,
            auth()->id()
        );

        return $result;
    }

    private function cleanRupiah($value)
    {
        if (!$value) return 0;
        return (int) preg_replace('/[^\d]/', '', $value);
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
