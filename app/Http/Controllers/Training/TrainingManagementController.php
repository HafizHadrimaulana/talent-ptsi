<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingRequest;
use App\Models\TrainingRequestApproval;
use App\Models\TrainingReference;

use Illuminate\Support\Facades\Log;

class TrainingManagementController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $roles = $user->getRoleNames()->toArray();

        // |--------------------------------------------------------------------------
        // |  UI CONFIG
        // |--------------------------------------------------------------------------
        $uiMap = [

            'DHC' => [
                'tabs' => ['update-data-LNA', 'approval-training-peserta', 'approval-training-data'],
                'default_tab' => 'update-data-LNA',

                'tab_configs' => [

                    'update-data-LNA' => [
                        'tables' => [
                            'data-lna-table',
                        ],
                    ],

                    'approval-training-peserta' => [
                        'tables' => [
                            'training-peserta-table',
                        ],
                    ],

                    'approval-training-data' => [
                        'tables' => [
                            'approval-data-training-table',
                        ],
                    ],

                    
                ],
            ],

            'SDM Unit' => [
                'tabs' => ['pengajuan-training-peserta', 'pengajuan-data-LNA'],
                'default_tab' => 'pengajuan-training-peserta',

                'tab_configs' => [

                    'pengajuan-training-peserta' => [
                        'tables' => [
                            'pengajuan-training-peserta-table',
                        ],
                    ],

                    'pengajuan-data-LNA' => [
                        'tables' => [
                            'pengajuan-data-lna-table',
                        ],
                    ]
                ],
            ],

            'Kepala Unit' => [
                'tabs' => ['training-peserta'],
                'default_tab' => 'training-peserta',

                'tab_configs' => [

                    'training-peserta' => [
                        'tables' => [
                            'training-peserta-table',
                        ],
                    ]
                ],
            ],

            'AVP' => [
                'tabs' => ['training-peserta'],
                'default_tab' => 'training-peserta',

                'tab_configs' => [

                    'training-peserta' => [
                        'tables' => [
                            'training-peserta-table',
                        ],
                    ]
                ],
            ],

        ];

        $ui = [
            'tabs' => [],
            'tab_configs' => [],
            'default_tab' => null,
        ];

        foreach ($roles as $role) {
            if (!isset($uiMap[$role])) {
                continue;
            }

            $roleUi = $uiMap[$role];

            // Merge tabs
            $ui['tabs'] = array_values(array_unique(array_merge(
                $ui['tabs'],
                $roleUi['tabs'] ?? []
            )));

            // Merge tab configs
            foreach ($roleUi['tab_configs'] ?? [] as $tab => $config) {

                if (!isset($ui['tab_configs'][$tab])) {
                    $ui['tab_configs'][$tab] = $config;
                } else {
                    // merge buttons
                    if (!empty($config['buttons'])) {
                        $ui['tab_configs'][$tab]['buttons'] = array_values(array_unique(array_merge(
                            $ui['tab_configs'][$tab]['buttons'] ?? [],
                            $config['buttons']
                        )));
                    }

                    // merge tables
                    if (!empty($config['tables'])) {
                        $ui['tab_configs'][$tab]['tables'] = array_values(array_unique(array_merge(
                            $ui['tab_configs'][$tab]['tables'] ?? [],
                            $config['tables']
                        )));
                    }
                }
            }

            // default tab (ambil role pertama yang punya default)
            if (!$ui['default_tab'] && isset($roleUi['default_tab'])) {
                $ui['default_tab'] = $roleUi['default_tab'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIVE TAB
        |--------------------------------------------------------------------------
        */
        $activeTab = $request->get(
            'tab',
            $ui['default_tab'] ?? ($ui['tabs'][0] ?? null)
        );

        /*
        |--------------------------------------------------------------------------
        | LOGGING (DEBUG FRIENDLY)
        |--------------------------------------------------------------------------
        */
        Log::info('training.index.ui', [
            'roles' => $roles,
            'tabs' => $ui['tabs'],
            'activeTab' => $activeTab,
        ]);

        return view('training.training-management.index', [
            'ui' => $ui,
            'activeTab' => $activeTab,
        ]);
    }

    public function approveTrainingSubmission(Request $request, $id)
    {
        $trainingRequest = TrainingRequest::find($id);

        if (!$trainingRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        $user = auth()->user();
        
        try {
            DB::beginTransaction();

            $result = $this->processApproval($trainingRequest, 'approve', $request->note);

            Log::info('note', [
                'note' => $request->note
            ] );

            TrainingRequestApproval::create([
                'training_request_id' => $id,
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first() ?? 'User', 
                'action' => 'approve',
                'from_status' => $result['from_status'],
                'to_status' => $result['to_status'],
                'note' => $request->input('note'), 
            ]);

            DB::commit();

            Log::info('aaaa Data ID ' . $id . ' berhasil di-approve oleh ' . $user->name);

            return response()->json([
                'status' => 'success',
                'message' => "Data ID {$id} berhasil di-approve oleh {$user->name}.",
            ]);
        } catch (\Exception $e) {
            Log::error('Approve error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function rejectTrainingSubmission(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $trainingRequest = TrainingRequest::findOrFail($id);
            $user = auth()->user();
            $role = $user->getRoleNames()->first();

            // Jika sudah final, tidak boleh reject
            if (in_array($trainingRequest->status_approval, ['approve', 'reject'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data ini sudah final dan tidak bisa ditolak.',
                ], 400);
            }

            $noteValue = $request->input('note');

            // Kirim ke processApproval()
            $result = $this->processApproval($trainingRequest, 'reject', $noteValue);

            TrainingRequestApproval::create([
                'training_request_id' => $id,
                'user_id'             => $user->id,
                'role'                => $role,
                'action'              => 'reject',
                'from_status'         => $result['from_status'],
                'to_status'           => $result['to_status'],
                'note'                => $noteValue,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Data ID {$id} berhasil direject oleh {$role}.",
            ]);

        } catch (\Exception $e) {
            Log::error("Reject failed", [
                'id' => $id,
                'role' => $role ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menolak data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function approveTrainingReference($id)
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        try {
            $trainingReference = TrainingReference::findOrFail($id);

            if (in_array($trainingReference->status_training_reference, ['active', 'rejected'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data ini sudah final dan tidak dapat di-approve.',
                ], 400);
            }

            $nextStatus = match ($role) {
                'DHC' => $trainingReference->status_training_reference === 'pending'
                    ? 'active'
                    : null,

                default => null,
            };

            if (!$nextStatus) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Anda tidak memiliki hak untuk approve pada status ini.',
                ], 403);
            }

            $trainingReference->update([
                'status_training_reference' => $nextStatus,
                // 'approved_by'               => $user->id,
                // 'approved_at'               => now(),
            ]);

            Log::info('Training reference approved', [
                'training_reference_id' => $id,
                'from_status' => $trainingReference->getOriginal('status_training_reference'),
                'to_status'   => $nextStatus,
                'role'        => $role,
                'user_id'     => $user->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => "Data berhasil di-approve oleh {$role}.",
                'data' => [
                    'id' => $trainingReference->id,
                    'status' => $nextStatus,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Approve error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function rejectTrainingReference($id)
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        
        try {
            $trainingReference = TrainingReference::findOrFail($id);

            // Jika sudah final, tidak boleh reject
            if (in_array($trainingReference->status_training_reference, ['active', 'rejected'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data ini sudah final dan tidak dapat direject.',
                ], 400);
            }

            // validasi
            $allowedReject = match ($role) {
                'DHC'      => $trainingReference->status_training_reference === 'pending',
                default    => false,
            };

            if (!$allowedReject) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Anda tidak memiliki hak untuk menolak data ini.',
                ], 403);
            }

            // update
            $trainingReference->update([
                'status_training_reference' => 'rejected',
                // 'rejected_reason'        => $request->input('reason'), // optional
            ]);

            Log::info('Training reference rejected', [
                'training_reference_id' => $id,
                'role' => $role,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Data ID {$id} berhasil direject oleh {$role}.",
            ]);

        } catch (\Exception $e) {
            Log::error("Reject failed", [
                'id' => $id,
                'role' => $role ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menolak data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function editDataLna(Request $request, $id)
    {
        Log::info('Mulai edit data lna', ['id' => $id, 'payload' => $request->all()]);

        try {
            $item = TrainingReference::findOrFail($id);
            $data = [];
            $fields = [
                'judul_sertifikasi', 'penyelenggara', 'jumlah_jam', 
                'waktu_pelaksanaan', 'nama_proyek', 'jenis_portofolio', 'fungsi'
            ];

            // Field biasa (string)
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    $data[$field] = ($value === '' || $value === null) ? null : $value;
                }
            }

            if ($request->filled('unit_id')) {
                $data['unit_id'] = $request->unit_id;
            }
            
            // Field decimal (bersihkan Rupiah)
            foreach ([
                'biaya_pelatihan',
            ] as $field) {
                $value = $request->input($field);
                $cleanValue = preg_replace('/[^\d]/', '', $value);
                $data[$field] = ($cleanValue === '') ? 0 : $cleanValue;
            }
            
            $item->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diperbarui!',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan.',
            ], 404);
        } catch (ValidationException $e) {
            Log::info('eror', $e->errors());
            return response()->json([
                'status' => 'error',
                'message' => 'Edit gagal',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function getDataPengajuanLna(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user->hasRole('SDM Unit')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Akses ditolak'
                ], 403);
            }

            $unitId = optional($user->employee)->unit_id
                ?? optional($user->person)->unit_id;

            if (!$unitId) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unit kerja tidak ditemukan'
                ], 422);
            }

            $perPage = (int) $request->input('per_page', 10);
            $page    = (int) $request->input('page', 1);
            $search  = $request->input('search');

            $query = TrainingReference::with('unit')
                ->where('unit_id', $unitId) 
                ->orderByDesc('created_at');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('judul_sertifikasi', 'like', "%{$search}%")
                    ->orWhere('penyelenggara', 'like', "%{$search}%")
                    ->orWhereHas('unit', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%");
                    });
                });
            }

            $data = $query->paginate($perPage, ['*'], 'page', $page);

            $mappedItems = $data->getCollection()->map(function ($item) {
                return [
                    "id"        => $item->id,
                    "judul_sertifikasi" => $item->judul_sertifikasi ?? "-",
                    "unit_id"   => $item->unit_id,
                    "unit_kerja"=> optional($item->unit)->name ?? "-",
                    "penyelenggara" => $item->penyelenggara ?? "-",
                    "jumlah_jam" => $item->jumlah_jam ?? "-",
                    "waktu_pelaksanaan" => $item->waktu_pelaksanaan ?? "-",
                    "biaya_pelatihan" => $item->biaya_pelatihan ?? 0,
                    "nama_proyek" => $item->nama_proyek ?? "-",
                    "jenis_portofolio" => $item->jenis_portofolio ?? "-",
                    "fungsi" => $item->fungsi ?? "-",
                    "status_training_reference" => $item->status_training_reference,
                    "created_at" => optional($item->created_at)?->format('Y-m-d H:i:s'),
                ];
            })->values();

            return response()->json([
                "status" => "success",
                "data" => $mappedItems,
                "pagination" => [
                    "current_page" => $data->currentPage(),
                    "last_page" => $data->lastPage(),
                    "per_page" => $data->perPage(),
                    "total" => $data->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetch pengajuan LNA", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan server"
            ], 500);
        }
    }

    public function getPengajuanTrainingPeserta(Request $request, $unitId = null)
    {
        try {
            $user   = auth()->user();
            $roles  = $user->getRoleNames()->toArray();

            $employeeUnitId = optional($user->employee)->unit_id
                ?? optional($user->person)->unit_id;

            if (!$employeeUnitId && in_array('SDM Unit', $roles)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unit user tidak ditemukan'
                ], 422);
            }

            $perPage = $request->input('per_page', 10);

            $query = TrainingRequest::query()
                ->with([
                    'trainingReference:id,judul_sertifikasi,biaya_pelatihan',
                    'employee:id,unit_id,person_id,employee_id',
                    'employee.person:id,full_name',
                    'approvals' => function ($q) {
                        $q->with('user:id,name')
                        ->orderBy('created_at', 'asc');
                    }
                ])
                ->orderByDesc('id');

            if (in_array('SDM Unit', $roles)) {
                $query->whereHas('employee', function ($q) use ($employeeUnitId) {
                    $q->where('unit_id', $employeeUnitId);
                });

            } elseif ($unitId) {
                $query->whereHas('employee', function ($q) use ($unitId) {
                    $q->where('unit_id', $unitId);
                });
            } else {
                $query->whereIn('status_approval_training', [
                    'in_review_gmvp',
                    'in_review_avpdhc',
                    'in_review_vpdhc',
                ]);
            }

            $data = $query->paginate($perPage);

            $mappedData = collect($data->items())->map(function ($item, $index) use ($data) {
                Log::info('getPengajuanTrainingPeserta', [
                    'item' => $item
                ]);
                return [
                    'id'                       => $item->id,
                    'judul_sertifikasi'        => $item->trainingReference?->judul_sertifikasi ?? '-',
                    'peserta'                  => $item->employee?->person?->full_name ?? '-',
                    'nik'                      => $item->employee?->employee_id ?? '-',
                    'tanggal_mulai'            => $item->start_date,
                    'tanggal_berakhir'         => $item->end_date,
                    'biaya_pelatihan'          => $item->trainingReference?->biaya_pelatihan ?? 0,
                    'realisasi_biaya_pelatihan'=> $item->realisasi_biaya_pelatihan ?? 0,
                    'lampiran_penawaran'       => $item->lampiran_penawaran,
                    'status_approval_training' => $item->status_approval_training,
                    'approvals' => $item->approvals->map(fn ($approval) => [
                        'id'          => $approval->id,
                        'role'        => $approval->role,
                        'action'      => $approval->action, // approve | reject | in_review
                        'from_status' => $approval->from_status,
                        'to_status'   => $approval->to_status,
                        'note'        => $approval->note,
                        'created_at'  => $approval->created_at,
                        'user'        => [
                            'name' => $a->user?->name ?? '-',
                        ],
                    ]),
                ];
            });

            Log::info('getPengajuanTrainingPeserta OK', [
                'user_id' => $user->id,
                'roles'   => $roles,
                'unit'    => in_array('SDM Unit', $roles) ? $employeeUnitId : $unitId,
                'total'   => $data->total(),
            ]);

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

        } catch (\Throwable $e) {

            Log::error('getPengajuanTrainingPeserta ERROR', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data'
            ], 500);
        }
    }

    public function inputLna(Request $request) 
    {
        $user  = auth()->user();
        $roles = $user->getRoleNames()->toArray();

        Log::info("Mulai input LNA request:", [
            'roles' => $roles,
            'payload' => $request->all(),
        ]);

        $statusTrainingReference = 'active';

        $isSdmUnit = in_array('SDM Unit', $roles);

        if ($isSdmUnit) {
            $unitId = optional($user->employee)->unit_id
                ?? optional($user->person)->unit_id;

            if (!$unitId) {
                return response()->json([
                    "status"  => "error",
                    "message" => "Unit kerja SDM Unit tidak ditemukan"
                ], 422);
            }

            $request->merge([
                'unit_id' => $unitId
            ]);

            $statusTrainingReference = 'pending';
        }

        $request->merge([
            'biaya_pelatihan' => $this->cleanRupiah($request->biaya_pelatihan),
        ]);

        try {
            $request->validate([
                'unit_id'               => 'required|exists:units,id',
                'judul_sertifikasi'     => 'required|string|max:255',
                'penyelenggara'         => 'required|string|max:255',
                'jumlah_jam'            => 'required|string|max:255',
                'waktu_pelaksanaan'     => 'required|string|max:255',
                'biaya_pelatihan'       => 'required|numeric',
                'nama_proyek'           => 'required|string|max:255',
                'jenis_portofolio'      => 'required|string|max:255',
                'fungsi'                => 'required|string|max:255',
            ]);

            $data = TrainingReference::create([
                'judul_sertifikasi'           => $request->judul_sertifikasi,
                'unit_id'                     => $request->unit_id,
                'penyelenggara'               => $request->penyelenggara,
                'jumlah_jam'                  => $request->jumlah_jam,
                'waktu_pelaksanaan'           => $request->waktu_pelaksanaan,
                'nama_proyek'                 => $request->nama_proyek,
                'jenis_portofolio'            => $request->jenis_portofolio,
                'fungsi'                      => $request->fungsi,
                'biaya_pelatihan'             => $request->biaya_pelatihan,
                'status_training_reference'   => $statusTrainingReference,
            ]);

            Log::info("Data training berhasil disimpan.", $data->toArray());

            return response()->json([
                "status"  => "success",
                "message" => "Data training berhasil disimpan.",
                "data"    => $data
            ]);

        } catch (\Exception $e) {
            Log::error("Gagal input LNA", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                "status"  => "error",
                "message" => "Terjadi kesalahan server"
            ], 500);
        }
    }

    /// UTILS APPROVAL ///
    private function processApproval(TrainingRequest $trainingRequest, string $action, ?string $note = null): array
    {
        $currentStatus = $trainingRequest->status_approval_training;
        $user          = auth()->user();
        $roleNames     = $user->getRoleNames()->toArray();
        $isHumanCapital  = $this->isHumanCapital($user);
        $isFromDHC       = $this->isTrainingFromDHC($trainingRequest);

        Log::info('Process approval', [
            'training_id' => $trainingRequest->id,
            'current_status' => $currentStatus,
            'roles' => $roleNames,
            'isHumanCapital' => $isHumanCapital,
        ]);

        $approvalFlow = [
            'in_review_gmvp' => [
                'next' => 'in_review_dhc',
                'canApprove' => fn () =>
                    in_array('Kepala Unit', $roleNames) && !$isHumanCapital,
            ],

            'in_review_dhc' => [
                'next' => 'in_review_avpdhc',
                'canApprove' => fn () =>
                    in_array('DHC', $roleNames),
            ],

            'in_review_avpdhc' => [
                'next' => 'in_review_vpdhc',
                'canApprove' => fn () =>
                    in_array('AVP', $roleNames) && $isHumanCapital,
            ],

            'in_review_vpdhc' => [
                'next' => 'approved',
                'canApprove' => fn () =>
                    in_array('Kepala Unit', $roleNames) && $isHumanCapital, // VP DHC
            ],
        ];

        if ($action === 'reject') {
            $toStatus = 'rejected';
            $trainingRequest->update([
                'status_approval_training' => $toStatus,
                'updated_at' => now()
            ]);

            Log::warning('Training rejected', [
                'training_id' => $trainingRequest->id,
            ]);
            
            return [
                'from_status' => $currentStatus,
                'to_status'   => $toStatus
            ];
        }

        if (!isset($approvalFlow[$currentStatus])) {
            throw new \Exception("Status {$currentStatus} tidak valid.");
        }

        if (
            $currentStatus === 'in_review_gmvp'
            && in_array('Kepala Unit', $roleNames)
            && $isHumanCapital
            && $isFromDHC
        ) {
            $trainingRequest->update([
                'status_approval_training' => 'approved',
                'updated_at' => now()
            ]);

            Log::info('Training approved (GMVP DHC by VP DHC)', [
                'from' => $currentStatus,
                'to'   => 'approved',
            ]);

            return [
                'from_status' => $currentStatus,
                'to_status'   => 'approved'
            ];
        }

        $step = $approvalFlow[$currentStatus];

        if (!$step['canApprove']()) {
            throw new \Exception("User tidak berhak approve status {$currentStatus}");
        }

        $toStatus = $step['next'];
        $trainingRequest->update([
            'status_approval_training' => $step['next'],
            'updated_at' => now()
        ]);

        Log::info('Training approved', [
            'from' => $currentStatus,
            'to'   => $step['next'],
        ]);

        return [
            'from_status' => $currentStatus,
            'to_status'   => $toStatus
        ];
    }

    private function isTrainingFromDHC(TrainingRequest $trainingRequest): bool
    {
        return optional($trainingRequest->trainingReference?->unit)->name === 'Divisi Human Capital';
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

    private function cleanRupiah($value)
    {
        if (!$value) return 0;
        return (int) preg_replace('/[^\d]/', '', $value);
    }
}
