<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingRequest;
use App\Models\TrainingRequestApproval;

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
                        'buttons' => [
                            'import',
                            'lna-input',
                        ],
                        'show_download_template' => true,
                        'tables' => [
                            'data-lna-table',
                        ],
                    ],

                    'approval-training-peserta' => [
                        'buttons' => [],
                        'tables' => [
                            'training-peserta-table',
                        ],
                    ],

                    'approval-training-data' => [
                        'buttons' => [],
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
                        'buttons' => [
                            'import',
                            'training-input'
                        ],
                        'show_download_template' => true,
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
                        'buttons' => [],
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
                        'buttons' => [],
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
                        'buttons' => [],
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
}
