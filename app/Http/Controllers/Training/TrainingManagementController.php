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
        $role = $user->getRoleNames()->first();

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
                    ]
                ],
            ],

            'SDM Unit' => [
                'tabs' => ['pengajuan-training-peserta', 'data-LNA'],
                'default_tab' => 'pengajuan-training-peserta',

                'tab_configs' => [

                    'data-LNA' => [
                        'buttons' => ['lna-input'],
                        'tables' => [
                            'data-lna-table',
                        ],
                    ],

                    'pengajuan-training-peserta' => [
                        'tables' => [
                            'training-peserta-table',
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

            'DBS Unit' => [
                'tabs' => ['approval-training-data'],
                'default_tab' => 'approval-training-data',

                'tab_configs' => [

                   'approval-training-data' => [
                        'tables' => [
                            'approval-data-training-table',
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

        $ui = $uiMap[$role] ?? [
            'tabs' => [],
            'buttons' => [],
            'tables' => [
                'data-LNA',
            ],
        ];

        $activeTab = $request->get('tab', $ui['default_tab'] ?? null);

        Log::info('training.index.ui', [
            'role' => $role,
            'activeTab' => $activeTab,
        ]);

        Log::info("Role index: " . $role);
    
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
                'DBS Unit' => $trainingReference->status_training_reference === 'pending'
                    ? 'in_review_dhc'
                    : null,

                'DHC' => $trainingReference->status_training_reference === 'in_review_dhc'
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
                'DBS Unit' => $trainingReference->status_training_reference === 'pending',
                'DHC'      => $trainingReference->status_training_reference === 'in_review_dhc',
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

    /// UTILS APPROVAL ///
    private function processApproval(TrainingRequest $trainingRequest, string $action, ?string $note = null): array
    {
        $currentStatus = $trainingRequest->status_approval_training;
        $user          = auth()->user();
        $roleNames     = $user->getRoleNames()->toArray();
        $isHumanCapital = $this->isHumanCapital($user);

        Log::info('Process approval', [
            'training_id' => $trainingRequest->id,
            'current_status' => $currentStatus,
            'roles' => $roleNames,
            'isHumanCapital' => $isHumanCapital,
        ]);

        /**
         * =========================================
         * APPROVAL FLOW CONFIG (SINGLE SOURCE)
         * =========================================
         */
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
