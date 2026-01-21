<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
            'dokumen_sertifikat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        Log::info('request evaluation', ['request' => $request->all()]);

        $userId     = auth()->id();
        $trainingId = $request->training_request_id;

        try {
            DB::beginTransaction();

            foreach ($request->answers as $questionId => $answer) {

                if ($answer === null || $answer === '') {
                    continue;
                }

                $isNumeric = is_numeric($answer);

                TrainingEvaluationAnswer::updateOrCreate(
                    [
                        'training_request_id' => $trainingId,
                        'question_id'         => $questionId,
                        'user_id'             => $userId,
                    ],
                    [
                        'score'       => $isNumeric ? (int) $answer : null,
                        'text_answer' => !$isNumeric ? trim($answer) : null,
                    ]
                );
            }

            $trainingRequest = TrainingRequest::findOrFail($trainingId);

            if ($request->hasFile('dokumen_sertifikat')) {

                $path = $request->file('dokumen_sertifikat')
                    ->store('sertifikat_training', 'public');

                $trainingRequest->dokumen_sertifikat = $path;
            }

            $trainingRequest->is_evaluated = true;
            $trainingRequest->save();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Evaluasi & sertifikat berhasil disimpan',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('submitEvaluasiTraining error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan evaluasi',
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
            $roles = $user->getRoleNames()->toArray();

            $unitId = optional($user->employee)->unit_id
                ?? optional($user->person)->unit_id;

            Log::info('getApprovalPengajuanTraining', [
                'user_id' => $user->id,
                'roles'   => $roles,
                'unit_id' => $unitId,
            ]);

            $perPage = $request->input('per_page', 12);

            $query = TrainingReference::with('unit')
                ->orderByDesc('created_at');

            if (in_array('DHC', $roles)) {
                $query->where('status_training_reference', 'pending');
            }

            $data = $query->paginate($perPage);

            Log::info("Jumlah data mentah: " . count($data->items()));
            Log::info("Isi data mentah pengajuan traininig: ", $data->items());

            $mappedData = collect($data->items())->map(function ($item) {
                return [
                    'id'                => $item->id,
                    'judul_sertifikasi' => $item->judul_sertifikasi ?? '-',
                    'unit_id'           => $item->unit_id,
                    'unit_kerja'        => optional($item->unit)->name ?? '-',
                    'penyelenggara'     => $item->penyelenggara ?? '-',
                    'jumlah_jam'        => $item->jumlah_jam ?? 0,
                    'waktu_pelaksanaan' => $item->waktu_pelaksanaan,
                    'biaya_pelatihan'   => $item->biaya_pelatihan ?? 0,
                    'nama_proyek'       => $item->nama_proyek ?? '-',
                    'jenis_portofolio'  => $item->jenis_portofolio ?? '-',
                    'fungsi'            => $item->fungsi ?? '-',
                    'status_training_reference' => $item->status_training_reference,
                    'created_at'        => optional($item->created_at)
                                            ? $item->created_at->format('Y-m-d H:i:s')
                                            : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data'   => $mappedData,
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page'    => $data->lastPage(),
                    'per_page'     => $data->perPage(),
                    'total'        => $data->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetch approval pengajuan training", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server'
            ], 500);
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

                $realisasi = (empty($payload['realisasi_biaya_pelatihan']) || $payload['realisasi_biaya_pelatihan'] == 0) 
                    ? $payload['biaya_pelatihan'] 
                    : $payload['realisasi_biaya_pelatihan'];

                $created[] = TrainingRequest::create([
                    'training_reference_id'      => $payload['judul_sertifikasi'],
                    'employee_id'                => $employeeId,
                    'start_date'                 => $payload['start_date'],
                    'end_date'                   => $payload['end_date'],
                    'realisasi_biaya_pelatihan'  => $realisasi,
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
            $user     = auth()->user();
            $employee = $user->employee;
            $roles    = $user->getRoleNames()->toArray();

            if (!$employee) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak memiliki data employee'
                ], 403);
            }

            $userUnitId = $employee->unit_id;

            $query = TrainingRequest::query()
                ->select([
                    'id',
                    'training_reference_id',
                    'employee_id',
                    'status_approval_training',
                    'start_date',
                    'end_date',
                    'realisasi_biaya_pelatihan',
                    // 'estimasi_total_biaya',
                    'lampiran_penawaran',
                ])
                ->with([
                    'trainingReference:id,judul_sertifikasi,biaya_pelatihan',
                    'employee:id,person_id,employee_id',
                    'employee.person:id,full_name',
                ]);

            $isDHC         = $user->hasRole('DHC');
            $isAVP         = $user->hasRole('AVP');
            $isKepalaUnit  = $user->hasRole('Kepala Unit');
            $isHumanCapital = $this->isHumanCapital($user);

            $canSeeAllUnit = $isDHC || (($isAVP || $isKepalaUnit) && $isHumanCapital);

            if (!$canSeeAllUnit) {
                $query->whereHas('employee', function ($q) use ($userUnitId) {
                    $q->where('unit_id', $userUnitId);
                });
            } elseif ($unitId) {
                $query->whereHas('employee', function ($q) use ($unitId) {
                    $q->where('unit_id', $unitId);
                });
            }
            
            $allowedStatuses = [];
            // $DHC_UNIT_ID = 'Divisi Human Capital';

            if ($user->hasRole('Kepala Unit') && !$isHumanCapital) {
                $allowedStatuses = ['in_review_gmvp'];
            }
            elseif ($user->hasRole('DHC')) {
                $allowedStatuses = ['in_review_dhc'];
            }
            elseif ($user->hasRole('AVP') && $isHumanCapital) {
                $allowedStatuses = ['in_review_avpdhc'];
            }
            elseif ($user->hasRole('Kepala Unit') && $isHumanCapital) {
                $allowedStatuses = ['in_review_vpdhc'];

                // $query->whereHas('employee', function ($q) use ($DHC_UNIT_ID) {
                //     $q->where('unit_id', $DHC_UNIT_ID);
                // });
            }

            if (!empty($allowedStatuses)) {
                $query->whereIn('status_approval_training', $allowedStatuses);
            }

            /**
             * ==================================================
             * FETCH DATA
             * ==================================================
             */
            $perPage = $request->input('per_page', 12);

            $trainingRequest = $query
                ->orderByDesc('id')
                ->paginate($perPage);

            $data = collect($trainingRequest->items())->map(function ($item) {
                return [
                    'id'                => $item->id,
                    'judul_sertifikasi' => $item->trainingReference?->judul_sertifikasi ?? '-',
                    'peserta'           => $item->employee?->person?->full_name ?? '-',
                    'nik'               => $item->employee?->employee_id ?? '-',
                    'tanggal_mulai'     => $item->start_date,
                    'tanggal_berakhir'  => $item->end_date,
                    'biaya_pelatihan'   => $item->trainingReference?->biaya_pelatihan ?? 0,
                    'realisasi_biaya_pelatihan'   => $item->realisasi_biaya_pelatihan,
                    'lampiran_penawaran' => $item->lampiran_penawaran,
                    'status_approval_training' => $item->status_approval_training,
                ];
            });

            Log::info('Training Request Fetch OK', [
                'role'              => $roles,
                'user_unit_id'      => $userUnitId,
                'can_see_all_unit'  => $canSeeAllUnit,
                'filtered_unit_id' => $unitId,
                'allowed_statuses' => $allowedStatuses,
                'total'            => $trainingRequest->total(),
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $data,
                'pagination' => [
                    'current_page' => $trainingRequest->currentPage(),
                    'last_page'    => $trainingRequest->lastPage(),
                    'per_page'     => $trainingRequest->perPage(),
                    'total'        => $trainingRequest->total(),
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('getTrainingRequestList error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data.'
            ], 500);
        }
    }

    // pengajuan LNA DHC dan SDM

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

    // HELPER FUNCTION
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
