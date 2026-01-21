<?php

namespace App\Http\Controllers\Recruitment;

use App\Models\Project; 
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\RecruitmentRequestExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;

class PrincipalApprovalController extends Controller
{
    protected function getUserJobTitle($userId)
    {
        $user = DB::table('users')->where('id', $userId)->first();   
        if (!$user || empty($user->person_id)) {
            return null;
        }
        $jobTitle = DB::table('employees')
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->where('employees.person_id', $user->person_id) 
            ->value('positions.name');   
        return $jobTitle;
    }
    protected function stages(): array
    {
        return [
            ['key' => 'kepala_mp',    'roles' => ['Kepala Proyek (MP)'],     'title' => 'Kepala Proyek (MP)'],
            ['key' => 'sdm_unit',     'roles' => ['SDM Unit']],
            ['key' => 'kepala_unit',  'roles' => ['Kepala Unit']],
            ['key' => 'dhc_checker',  'roles' => ['DHC']],
            ['key' => 'avp_hc_ops',   'roles' => ['AVP']], 
            ['key' => 'vp_hc',        'title' => 'VP Human Capital'],
            ['key' => 'dir_sdm',      'roles' => ['Dir SDM']],
        ];
    }

    protected function canSeeAll($user): bool
    {
        if (!$user) return false;
        $jobTitle = $this->getUserJobTitle($user->id);
        $cleanTitle = $jobTitle ? trim(strtoupper($jobTitle)) : '';

        return $user->hasRole('Superadmin') || 
               $user->hasRole('DHC') ||
               $user->hasRole('Dir SDM') ||
               $cleanTitle === 'AVP HUMAN CAPITAL OPERATION'||
               $cleanTitle === 'VP HUMAN CAPITAL';
    }

    protected function dhcUnitId(): ?int
    {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = DB::table('units')->where(function ($q) {
            $q->where('code', 'DHC')
              ->orWhere('name', 'Divisi Human Capital')
              ->orWhere('name', 'like', 'Divisi Human Capital%');
        })->value('id');
        return $cache ? (int) $cache : null;
    }

    protected function has(string $table, string $col): bool
    {
        try {
            return Schema::hasColumn($table, $col);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function getBaseQuery($me, $canSeeAll, $selectedUnitId)
    {
        $query = RecruitmentRequest::query();
        $tbl = (new RecruitmentRequest())->getTable();
        if (!$canSeeAll && $me) {
            $isKepalaUnit = $me->hasRole('Kepala Unit');
            $query->where(function ($q) use ($me, $tbl, $isKepalaUnit) {
                $q->where('unit_id', $me->unit_id);
                $q->where(function ($qq) use ($tbl, $me, $isKepalaUnit) {
                    $qq->whereNull('status')->orWhere('status', '!=', 'draft');
                    $creatorCols = ['requested_by', 'requested_by_user_id', 'created_by', 'created_by_user_id'];
                    $qq->orWhere(function ($qDraft) use ($tbl, $creatorCols, $me, $isKepalaUnit) {
                        $qDraft->where('status', 'draft');
                        if ($isKepalaUnit) {
                            $qDraft->whereRaw('1 = 0');
                        } else {
                            $qDraft->where(function ($qOwner) use ($tbl, $creatorCols, $me) {
                                foreach ($creatorCols as $c) {
                                    if (Schema::hasColumn($tbl, $c)) {
                                        $qOwner->orWhere($c, $me->getKey());
                                    }
                                }
                            });
                        }
                    });
                });
            });
        }
        if ($selectedUnitId && $this->has($tbl, 'unit_id')) {
            $query->where('unit_id', $selectedUnitId);
        }
        return $query;
    }

    public function index(Request $r)
    {
        $me = Auth::user();
        $canSeeAll = $this->canSeeAll($me);
        $selectedUnitId = $canSeeAll
            ? ($r->filled('unit_id') ? (int) $r->integer('unit_id') : null)
            : (int) ($me?->unit_id);

        if ($r->ajax()) {
            $query = $this->getBaseQuery($me, $canSeeAll, $selectedUnitId);
            $query->with(['approvals' => fn($q) => $q->orderBy('id', 'asc'), 'unit']);
            if ($r->has('search') && !empty($r->input('search.value'))) {
                $search = $r->input('search.value');
                $query->where(function($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%")
                      ->orWhere('position', 'like', "%{$search}%")
                      ->orWhereHas('unit', fn($u) => $u->where('name', 'like', "%{$search}%"));
                });
            }
            $currentTab = $r->input('tab', 'berjalan');
            if ($currentTab === 'disetujui') {
                $query->where('status', 'approved');
            } else {
                $query->where(function($q) {
                    $q->where('status', '!=', 'approved')->orWhereNull('status');
                });
            }

            // 3. Sorting
            if ($r->has('order')) {
                $order = $r->input('order')[0];
                $colIdx = $order['column'];
                $dir = $order['dir'];
                $cols = ['ticket_number', 'title', 'unit_id', 'request_type', 'position', 'headcount', 'employment_type', 'status', 'created_at']; 
                if (isset($cols[$colIdx])) {
                    $columnName = $cols[$colIdx];
                    if($columnName === 'unit_id') {
                        $query->join('units', 'recruitment_requests.unit_id', '=', 'units.id')->orderBy('units.name', $dir);
                    } else {
                        $query->orderBy($columnName, $dir);
                    }
                }
            } else {
                $query->latest();
            }

            $countTotal = $query->count(); 
            $start = $r->input('start', 0);
            $length = $r->input('length', 10);
            $data = $query->skip($start)->take($length)->get();

            // 5. Formatting Data
            $formattedData = $data->map(function($row) use ($me) {
                return [
                    $row->ticket_number ?? '-', 
                    $this->renderTitleColumn($row), 
                    $row->unit->name ?? '-', 
                    $row->request_type ?? '-', 
                    $this->renderPositionColumn($row), 
                    $row->headcount . ' Orang', 
                    $row->employment_type ?? '-', 
                    $this->renderProgressColumn($row), 
                    $this->renderSlaColumn($row),
                    $this->renderActionColumn($row, $me) // Fixed Actions & Attributes
                ];
            });

            return response()->json([
                'draw' => intval($r->input('draw')),
                'recordsTotal' => RecruitmentRequest::count(), 
                'recordsFiltered' => $countTotal, 
                'data' => $formattedData
            ]);
        }

        // View non-AJAX
        $units = $canSeeAll
            ? DB::table('units')->select('id', 'name')->orderBy('name')->get()
            : DB::table('units')->select('id', 'name')->where('id', $me?->unit_id)->get();
        $locations = DB::table('locations')->select('id', 'city', 'name')->orderBy('city')->get();

        return view('recruitment.principal-approval.index', [
            'units'          => $units,
            'canSeeAll'      => $canSeeAll,
            'selectedUnitId' => $selectedUnitId,
            'locations'      => $locations,
            'currentTab'     => $r->input('tab', 'berjalan'),
        ]);
    }

    private function renderTitleColumn($r) {
        $date = optional($r->created_at)->format('d M Y') ?? '-';
        $details = $r->meta['recruitment_details'] ?? [];
        $html = '';
        if (count($details) > 1) {
            $html .= '<div class="u-flex u-flex-col u-gap-xs">';
            foreach($details as $d) {
                $t = $d['title'] ?? $r->title;
                $html .= "<div style='border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px; margin-bottom: 4px;'><span class='u-font-medium'>{$t}</span><div class='u-text-2xs u-muted'>Dibuat {$date}</div></div>";
            }
            $html .= '</div>';
        } else {
            $html = "<span class='u-font-medium'>{$r->title}</span><div class='u-text-2xs u-muted'>Dibuat {$date}</div>";
        }
        return $html;
    }

    private function renderPositionColumn($r) {
        $details = $r->meta['recruitment_details'] ?? [];
        if (count($details) > 1) {
            $html = '<div class="u-flex u-flex-col u-gap-xs">';
            foreach($details as $d) {
                $p = $d['position_text'] ?? $d['position'] ?? '-';
                $html .= "<div class='u-text-sm' style='border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px;'>{$p}</div>";
            }
            $html .= '</div>';
            return $html;
        }
        $posName = $r->position;
        if (is_numeric($r->position)) {
             $posName = DB::table('positions')->where('id', $r->position)->value('name') ?? $r->position;
        }
        return $posName;
    }

    private function renderProgressColumn($r) {
        $status = $r->status ?? 'draft';
        $progressText = 'In Review';
        $badgeClass = 'u-badge--warning';
        $activeApp = $r->approvals->where('status', 'pending')->first();
        $currentStage = '';
        if ($activeApp) {
             preg_match('/\[stage=([^\]]+)\]/', $activeApp->note, $m);
             $currentStage = $m[1] ?? '';
        }
        if ($status === 'draft') { $progressText = 'Draft'; $badgeClass = 'u-badge--glass'; }
        elseif ($status === 'rejected') { $progressText = 'Ditolak'; $badgeClass = 'u-badge--danger'; }
        elseif ($status === 'approved') { $progressText = 'Selesai'; $badgeClass = 'u-badge--success'; }
        else {
             $map = [
                 'admin_ops' => 'Menunggu Admin Ops', 'kepala_mp' => 'Menunggu Kepala MP',
                 'sdm_unit' => 'Menunggu SDM Unit', 'kepala_unit' => 'Menunggu Ka. Unit',
                 'dhc_checker' => 'Menunggu DHC', 'avp_hc_ops' => 'Menunggu AVP DHC',
                 'vp_hc' => 'Menunggu VP DHC', 'dir_sdm' => 'Menunggu Dir TSDU'
             ];
             $progressText = $map[$currentStage] ?? 'In Review';
        }
        return "<div class='u-text-2xs'><span class='u-badge {$badgeClass}'>{$progressText}</span></div>";
    }

    private function renderSlaColumn($r) {
        $status = $r->status ?? 'draft';
        $slaBadgeClass = ''; $slaText = '-';
        $kaUnitApp = null;
        
        foreach($r->approvals as $ap) { 
            if(strpos($ap->note, 'stage=kepala_unit')!==false && $ap->status=='approved') $kaUnitApp = $ap; 
        }

        if (in_array($status, ['submitted', 'in_review']) && $kaUnitApp) {
            $slaTimeBase = \Carbon\Carbon::parse($kaUnitApp->decided_at);
            $daysDiff = $slaTimeBase->diffInDays(now());
            $rawText = $slaTimeBase->locale('id')->diffForHumans(['parts'=>2,'join'=>true,'syntax'=>\Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]);
            $cleanText = str_replace(['yang ', 'setelahnya', 'sebelumnya', ' dan '], ['', '', '', ', '], $rawText);
            $slaText = trim($cleanText);

            if ($daysDiff >= 5) { $slaBadgeClass = 'u-badge--danger'; } 
            elseif ($daysDiff >= 3) { $slaBadgeClass = 'u-badge--warning'; } 
            else { $slaBadgeClass = 'u-badge--info'; }

            return "<span class='u-badge {$slaBadgeClass} u-text-2xs' title='Dihitung sejak approval Kepala Unit'>
                        <i class='far fa-clock u-mr-xs'></i> {$slaText}
                    </span>";
        }

        return '<span class="u-text-muted u-text-2xs" title="Menunggu approval Kepala Unit">-</span>'; 
    }

    private function renderActionColumn($r, $me) {
        $meUnit = $me->unit_id;
        $sameUnit = $meUnit && (string)$meUnit === (string)$r->unit_id;
        $jobTitle = $this->getUserJobTitle($me->id);
        $cleanTitle = $jobTitle ? trim(strtoupper($jobTitle)) : '';
        
        $meRoles = [
            'Superadmin' => $me->hasRole('Superadmin'),
            'Admin Ops'  => $me->hasRole('Admin Operasi Unit') || str_contains($cleanTitle, 'STAF ADMINISTRASI OPERASI'),
            'Kepala MP'  => $me->hasRole('Kepala Proyek (MP)') || str_contains($cleanTitle, 'KEPALA PROYEK (MP)'),
            'SDM Unit'   => $me->hasRole('SDM Unit'),
            'Kepala Unit'=> $me->hasRole('Kepala Unit'),
            'DHC'        => $me->hasRole('DHC'),
            'AVP HC Ops' => ($me->hasRole('AVP') && $cleanTitle === 'AVP HUMAN CAPITAL OPERATION'),
            'VP HC'      => ($cleanTitle === 'VP HUMAN CAPITAL'),
            'Dir SDM'    => $me->hasRole('Dir SDM')
        ];

        $approvalHistory = [];
        $activeApp = null;
        $currentStage = '';
        foreach($r->approvals as $app) {
             if ($app->status == 'pending') {
                $activeApp = $app;
                preg_match('/\[stage=([^\]]+)\]/', $app->note, $m);
                $currentStage = $m[1] ?? '';
             }
             $rawNote = $app->note;
             preg_match('/\[stage=([^\]]+)\]/', $rawNote, $matches);
             $histKey = $matches[1] ?? '';
             $lbl = 'Approver';
             $mapLbl = [
                'admin_ops'=>'Admin Ops', 'kepala_mp'=>'Kepala MP', 'sdm_unit'=>'SDM Unit', 
                'kepala_unit'=>'Kepala Unit', 'dhc_checker'=>'DHC', 'avp_hc_ops'=>'AVP DHC', 
                'vp_hc'=>'VP DHC', 'dir_sdm'=>'Dir SDM'
             ];
             $lbl = $mapLbl[$histKey] ?? 'Approver';
             $cleanNote = trim(preg_replace('/\[stage=[^\]]+\]/', '', $rawNote));
             $approvalHistory[] = [
                 'role' => $lbl,
                 'status' => $app->status,
                 'date' => $app->decided_at ? \Carbon\Carbon::parse($app->decided_at)->format('d M Y H:i') : '-',
                 'note' => $cleanNote
             ];
        }
        $canStage = false;
        $isKaUnitDHC = $meRoles['Kepala Unit'] && $this->dhcUnitId() && ((string)$meUnit === (string)$this->dhcUnitId());
        $status = $r->status ?? 'draft';
        if (in_array($status, ['in_review','submitted'])) {
            if ($meRoles['Superadmin']) {
                $canStage = true;
            } else {
                if ($currentStage === 'admin_ops' && $meRoles['Admin Ops'] && $sameUnit) $canStage = true;
                elseif ($currentStage === 'kepala_mp' && $meRoles['Kepala MP'] && $sameUnit) $canStage = true;
                elseif ($currentStage === 'sdm_unit' && $meRoles['SDM Unit'] && $sameUnit) $canStage = true;
                elseif ($currentStage === 'kepala_unit' && $meRoles['Kepala Unit'] && $sameUnit) $canStage = true;
                elseif ($currentStage === 'dhc_checker' && ($meRoles['DHC'] || $isKaUnitDHC)) $canStage = true;
                elseif ($currentStage === 'avp_hc_ops' && $meRoles['AVP HC Ops']) $canStage = true; 
                elseif ($currentStage === 'vp_hc' && $meRoles['VP HC']) $canStage = true;
                elseif ($currentStage === 'dir_sdm' && $meRoles['Dir SDM']) $canStage = true;
            }
        }
        $isPublished = $r->is_published ? '1' : '0';
        $canPublish = ($me->hasRole('DHC') || $me->hasRole('Superadmin') || $me->hasRole('SDM Unit') || $me->hasRole('Kepala Unit')) ? 'true' : 'false';
        $canViewNotes = ($me->id === $r->created_by || $me->id === $r->requested_by || in_array(true, $meRoles, true)) ? 'true' : 'false';
        $canApproveStr = $canStage ? 'true' : 'false';
        $historyJson = htmlspecialchars(json_encode($approvalHistory), ENT_QUOTES, 'UTF-8');
        $metaJson    = htmlspecialchars(json_encode($r->meta['recruitment_details'] ?? []), ENT_QUOTES, 'UTF-8');
        $reqType = e($r->request_type ?? $r->type ?? 'Rekrutmen');
        $title = e($r->title);
        $unitName = e($r->unit->name ?? '-');
        $posName = e($this->renderPositionColumn($r));
        $posNameSimple = strip_tags($posName);
        $headcount = (int) $r->headcount;
        $empType = e($r->employment_type ?? '');
        $tgtStart = $r->target_start_date ? \Carbon\Carbon::parse($r->target_start_date)->format('d M Y') : '-';
        $budgetSrc = e($r->budget_source_type ?? '');
        $budgetRef = e($r->budget_ref ?? '');
        $justif = e($r->justification ?? '');
        $statStr = e(ucfirst($status));
        $approveUrl = route('recruitment.principal-approval.approve',$r->id);
        $rejectUrl = route('recruitment.principal-approval.reject',$r->id);
        $btns = '<div class="flex flex-col gap-2 items-end">';
        $canCreate = Gate::allows('recruitment.create') || Gate::allows('recruitment.update') || $meRoles['SDM Unit'] || $meRoles['Superadmin'];
        if ($status === 'draft' && ($sameUnit || $meRoles['Superadmin'])) {
             if ($canCreate) {
                 $urlUpdate = route('recruitment.principal-approval.update', $r->id);
                 $urlDelete = route('recruitment.principal-approval.destroy', ['req' => $r->id]);
                 $urlSubmit = route('recruitment.principal-approval.submit', $r->id);
                 $csrf = csrf_field();
                 $btns .= "<button type='button' class='u-btn u-btn--outline u-btn--sm u-hover-lift' data-modal-open='createApprovalModal' data-mode='edit'data-update-url='{$urlUpdate}'data-delete-url='{$urlDelete}'data-request-type='{$reqType}'data-title='{$title}'data-position='{$posNameSimple}'data-headcount='{$headcount}'data-employment-type='{$empType}'data-target-start='{$r->target_start_date}'data-budget-source-type='{$budgetSrc}'data-budget-ref='{$budgetRef}'data-justification='{$justif}'data-meta-json='{$metaJson}'>
                            <i class='fas fa-edit u-mr-xs'></i> Edit
                           </button>";
                 $btns .= "<form method='POST' action='{$urlSubmit}' class='u-inline js-confirm'>
                            {$csrf}
                            <button class='u-btn u-btn--outline u-btn--sm u-hover-lift'><i class='fas fa-paper-plane u-mr-xs'></i> Submit</button>
                           </form>";
             }
        }
        $btns .= "<button type='button' class='u-btn u-btn--outline u-btn--sm u-hover-lift js-open-detail'data-modal-open='detailApprovalModal'data-id='{$r->id}'data-ticket-number='{$r->ticket_number}'data-title='{$title}'data-unit='{$unitName}'data-request-type='{$reqType}'data-position='{$posNameSimple}'data-headcount='{$headcount}'data-employment-type='{$empType}'data-target-start='{$tgtStart}'data-budget-source='{$budgetSrc}'data-budget-ref='{$budgetRef}'data-justification='{$justif}'data-status='{$statStr}'data-history='{$historyJson}'data-can-view-notes='{$canViewNotes}'data-can-approve='{$canApproveStr}'data-approve-url='{$approveUrl}'data-reject-url='{$rejectUrl}'data-meta-json='{$metaJson}'data-is-published='{$isPublished}'data-can-publish='{$canPublish}'>
                    <i class='fas fa-info-circle u-mr-xs'></i> Detail
                  </button>";
        $btns .= '</div>';
        return $btns;
    }

    public function storeProject(Request $request)
    {
        $request->validate([
            'project_code' => 'required|unique:projects,project_code',
            'project_name' => 'required|string',
            'location_id'  => 'required|exists:locations,id',
            'document'     => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);
        try {
            $path = null;
            if ($request->hasFile('document')) {
                $path = $request->file('document')->store('project_docs', 'public');
            }
            $project = Project::create([
                'project_code'  => $request->project_code,
                'project_name'  => $request->project_name,
                'location_id'   => $request->location_id,
                'document_path' => $path
            ]);
            return response()->json([
                'status' => 'success',
                'data'   => $project,
                'message'=> 'Project berhasil dibuat!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $r)
    {
        $me = Auth::user();
        $canSeeAll = $this->canSeeAll($me);
        $selectedUnitId = $canSeeAll 
            ? ($r->filled('unit_id') ? (int) $r->integer('unit_id') : null) 
            : (int) ($me?->unit_id);
        $query = $this->getBaseQuery($me, $canSeeAll, $selectedUnitId);
        $query->latest();     
        $positionsMap = DB::table('positions')->pluck('name', 'id')->toArray();
        return Excel::download(
            new RecruitmentRequestExport($query, $positionsMap), 
            'Daftar_Izin_Prinsip_' . date('Y-m-d_H-i') . '.xlsx'
        );
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'request_type'         => 'required|string|in:Rekrutmen,Perpanjang Kontrak', 
            'title'                => 'required|string',
            'position'             => 'required|string',
            'headcount'            => 'required|integer|min:1',
            'justification'        => 'nullable|string',
            'employment_type'      => 'nullable|string|max:100',
            'target_start_date'    => 'nullable|date',
            'budget_source_type'   => 'nullable|string|max:100',
            'budget_ref'           => 'nullable|string',
            'publish_vacancy_pref' => 'nullable|string|max:10',
            'details_json'         => 'nullable|string', 
        ]);

        $me   = Auth::user();
        $meId = Auth::id();
        $model = new RecruitmentRequest();
        $tbl   = $model->getTable();
        $pick = function (array $cands) use ($tbl) {
            foreach ($cands as $c) {
                if (Schema::hasColumn($tbl, $c)) return $c;
            }
            return null;
        };
        $insert = [];
        if ($this->has($tbl, 'unit_id'))  $insert['unit_id'] = $me?->unit_id;
        if ($this->has($tbl, 'status'))   $insert['status']  = 'draft';
        if ($col = $pick(['requested_by', 'requested_by_user_id', 'created_by', 'created_by_user_id'])) {
            $insert[$col] = $meId;
        }
        $map = [
            'request_type'         => ['request_type', 'type'],
            'title'                => ['title', 'job_title', 'name'],
            'position'             => ['position', 'position_name', 'job_title'],
            'headcount'            => ['headcount', 'requested_headcount', 'qty'],
            'justification'        => ['justification', 'reason', 'notes', 'note', 'description'],
            'employment_type'      => ['employment_type', 'contract_type', 'jenis_kontrak'],
            'target_start_date'    => ['target_start_date', 'start_date', 'planned_start_date'],
            'budget_source_type'   => ['budget_source_type', 'budget_source', 'sumber_anggaran'],
            'budget_ref'           => ['budget_ref', 'rkap_ref', 'rab_ref', 'budget_reference'],
            'publish_vacancy_pref' => ['publish_vacancy_pref', 'publish_pref', 'publish_vacancy'],
        ];
        foreach ($map as $key => $cands) {
            if (!array_key_exists($key, $data)) continue;
            if ($data[$key] === null || $data[$key] === '') continue;
            if ($col = $pick($cands)) {
                $insert[$col] = $data[$key];
            }
        }
        if (!empty($data['details_json'])) {
            try {
                $detailsArray = json_decode($data['details_json'], true);
                if (is_array($detailsArray)) {
                    $insert['meta'] = ['recruitment_details' => $detailsArray];
                }
            } catch (\Exception $e) {}
        }
        $model->forceFill($insert)->save();
        return back()->with('ok', 'Draft Izin Prinsip berhasil dibuat.');
    }

    public function destroy(RecruitmentRequest $req)
    {
        $this->authorizeUnit($req->unit_id);
        if (($req->status ?? null) !== 'draft') {
            return back()->withErrors('Hanya permintaan dengan status DRAFT yang dapat dihapus.');
        }
        $req->delete();
        return redirect()->route('recruitment.principal-approval.index')
            ->with('ok', 'Draft Izin Prinsip berhasil dihapus.');
    }

    public function update(Request $r, RecruitmentRequest $req)
    {
        $this->authorizeUnit($req->unit_id);
        if (($req->status ?? null) !== 'draft') {
            return back()->withErrors('Hanya permintaan dengan status DRAFT yang dapat diedit.');
        }
        $data = $r->validate([
            'request_type'         => 'required|string|in:Rekrutmen,Perpanjang Kontrak',
            'title'                => 'required|string',
            'position'             => 'required|string',
            'headcount'            => 'required|integer|min:1',
            'justification'        => 'nullable|string',
            'employment_type'      => 'nullable|string|max:100',
            'target_start_date'    => 'nullable|date',
            'budget_source_type'   => 'nullable|string|max:100',
            'budget_ref'           => 'nullable|string',
            'publish_vacancy_pref' => 'nullable|string|max:10',
            'details_json'         => 'nullable|string',
        ]);
        $tbl = $req->getTable();
        $pick = function (array $cands) use ($tbl) {
            foreach ($cands as $c) {
                if (Schema::hasColumn($tbl, $c)) return $c;
            }
            return null;
        };
        $update = [];
        $map = [
            'request_type'         => ['request_type', 'type'],
            'title'                => ['title', 'job_title', 'name'],
            'position'             => ['position', 'position_name', 'job_title'],
            'headcount'            => ['headcount', 'requested_headcount', 'qty'],
            'justification'        => ['justification', 'reason', 'notes', 'note', 'description'],
            'employment_type'      => ['employment_type', 'contract_type', 'jenis_kontrak'],
            'target_start_date'    => ['target_start_date', 'start_date', 'planned_start_date'],
            'budget_source_type'   => ['budget_source_type', 'budget_source', 'sumber_anggaran'],
            'budget_ref'           => ['budget_ref', 'rkap_ref', 'rab_ref', 'budget_reference'],
            'publish_vacancy_pref' => ['publish_vacancy_pref', 'publish_pref', 'publish_vacancy'],
        ];
        foreach ($map as $key => $cands) {
            if (!array_key_exists($key, $data)) continue;
            if ($data[$key] === null || $data[$key] === '') continue;
            if ($col = $pick($cands)) {
                $update[$col] = $data[$key];
            }
        }
        if (!empty($data['details_json'])) {
            try {
                $detailsArray = json_decode($data['details_json'], true);
                if (is_array($detailsArray)) {
                    $currentMeta = $req->meta ?? [];
                    $currentMeta['recruitment_details'] = $detailsArray;
                    $update['meta'] = $currentMeta;
                }
            } catch (\Exception $e) {}
        }
        if (!empty($update)) {
            $req->forceFill($update)->save();
        }
        return back()->with('ok', 'Draft Izin Prinsip berhasil diperbarui.');
    }
    public function submit(RecruitmentRequest $req)
    {
        $this->authorizeUnit($req->unit_id);
        $user = Auth::user();

        if (($req->status ?? null) !== 'draft') {
            return back()->withErrors('Hanya permintaan dengan status DRAFT yang dapat disubmit.');
        }
        if (Schema::hasColumn($req->getTable(), 'status')) {
            $req->update(['status' => 'submitted']);
        }
        $startStage = 0; 
        if ($user->hasRole('SDM Unit')) {
            $startStage = 2; 
        } 
        else {
            $hasKepalaMP = $this->checkUnitHasPosition($req->unit_id, 'KEPALA PROYEK (MP)');
            
            if (!$hasKepalaMP) {
                $startStage = 1;
            }
        }
        $this->createPendingApproval($req, $startStage);
        if (Schema::hasColumn($req->getTable(), 'status')) {
            $req->update(['status' => 'in_review']);
        }
        return back()->with('ok', 'Permintaan berhasil dikirim.');
    }

    public function approve(RecruitmentRequest $req, Request $r)
    {
        $stageIdx = $this->currentStageIndex($req);
        if ($stageIdx === null) {
            return back()->withErrors('Tidak ada approval yang pending.');
        }
        if (!$this->canActOnStage(Auth::user(), $stageIdx, $req->unit_id)) {
            abort(403, 'Unauthorized.');
        }
        $note = $r->input('note'); 
        $extendedNote = $r->input('extended_note');
        if (!empty($extendedNote)) {
            $cleanNote = strip_tags($extendedNote, '<b><i><u><ol><ul><li><br><p><div><span><strong><em><table><thead><tbody><tr><th><td><hr>');
            $note = $note ? $note . "\n<hr>\n" . $cleanNote : $cleanNote; 
        }
        $this->closePending($req, 'approved', $note);
        $totalStages = count($this->stages());
        $nextStage = $stageIdx + 1;
        if ($nextStage >= $totalStages) {
            if (Schema::hasColumn($req->getTable(), 'status')) {
                $req->update(['status' => 'approved']);
            }        
            $req->generateTicketNumber();
            return back()->with('ok', 'Izin Prinsip sepenuhnya disetujui. Nomor Ticket: ' . $req->ticket_number);
        }
        $this->createPendingApproval($req, $nextStage);
        return back()->with('ok', 'Disetujui dan diteruskan ke approver berikutnya.');
    }

    public function reject(RecruitmentRequest $req, Request $r)
    {
        $stageIdx = $this->currentStageIndex($req);
        if ($stageIdx === null) {
            return back()->withErrors('Tidak ada approval yang pending.');
        }
        if (!$this->canActOnStage(Auth::user(), $stageIdx, $req->unit_id)) {
            abort(403);
        }
        $this->closePending($req, 'rejected', $r->input('note'));
        if (Schema::hasColumn($req->getTable(), 'status')) {
            $req->update(['status' => 'rejected']);
        }
        return back()->with('ok', 'Permintaan ditolak.');
    }
    protected function currentStageIndex(RecruitmentRequest $req): ?int
    {
        foreach ($req->approvals()->orderBy('id', 'asc')->get() as $ap) {
            if (($ap->status ?? 'pending') === 'pending') {
                preg_match('/\[stage=([^\]]+)\]/', $ap->note, $matches);
                $key = $matches[1] ?? null;
                if ($key) {
                    foreach ($this->stages() as $idx => $s) {
                        if ($s['key'] === $key) return $idx;
                    }
                }
                return 0;
            }
        }
        return null;
    }

    protected function createPendingApproval(RecruitmentRequest $req, int $stageIdx): void
    {
        $rel = $req->approvals();
        $m   = $rel->getRelated()->newInstance();
        $stageKey = $this->stages()[$stageIdx]['key'] ?? ('stage_' . $stageIdx);
        $m->forceFill(['status' => 'pending','note'    => "[stage={$stageKey}]",]);
        $rel->save($m);
    }

    protected function closePending(RecruitmentRequest $req, string $status, ?string $note = null): void
    {
        /** @var \App\Models\Approval|null $appr */
        $appr = $req->approvals()->where('status', 'pending')->latest('id')->first();
        if ($appr) {
            $payload = [
                'status'           => $status,
                'decided_at'       => now(),
                'approver_user_id' => Auth::id(),
            ];
            if ($note) {
                $payload['note'] = trim(($appr->note ? $appr->note . ' ' : '') . $note);
            }
            $appr->update($payload);
        }
    }
    protected function canActOnStage($user, int $stageIdx, $reqUnitId): bool
    {
        if (!$user) return false;
        if ($user->hasRole('Superadmin')) return true;        
        
        $stage = $this->stages()[$stageIdx] ?? null;
        if (!$stage) return false;
        
        $jobTitle = $this->getUserJobTitle($user->id);
        $cleanJobTitle = $jobTitle ? trim(strtoupper($jobTitle)) : '';
        
        $allowed = false;
        if (isset($stage['roles'])) {
            foreach ($stage['roles'] as $r) {
                if ($user->hasRole($r)) {
                    $allowed = true;
                    break;
                }
            }
        }

        // Logic Spesifik
        if ($stage['key'] === 'kepala_mp') {
             $isTitleMatch = str_contains($cleanJobTitle, 'KEPALA PROYEK (MP)');
             return ($allowed || $isTitleMatch) && ((string) $user->unit_id === (string) $reqUnitId);
        }
        if ($stage['key'] === 'sdm_unit' || $stage['key'] === 'kepala_unit') {
             return $allowed && ((string) $user->unit_id === (string) $reqUnitId);
        }
        if ($stage['key'] === 'dhc_checker') {
            if ($allowed) return true;
            $isKepalaUnit = $user->hasRole('Kepala Unit');
            return $isKepalaUnit && $this->dhcUnitId() && ((string) $user->unit_id === (string) $this->dhcUnitId());
        }
        if ($stage['key'] === 'avp_hc_ops') {
            return $allowed && ($cleanJobTitle === 'AVP HUMAN CAPITAL OPERATION');
        }
        
        // KHUSUS VP HC: Cek Jabatan Langsung (Case Insensitive)
        if ($stage['key'] === 'vp_hc') {
            return ($cleanJobTitle === 'VP HUMAN CAPITAL');
        }
        
        return $allowed;
    }

    protected function authorizeUnit($unitId): void
    {
        $me = Auth::user();
        if (!$me) abort(401);

        $jobTitle = $this->getUserJobTitle($me->id);
        $cleanTitle = $jobTitle ? trim(strtoupper($jobTitle)) : '';

        // LEVEL 1: BYPASS (Superadmin, DHC, Dir SDM, AVP Ops, VP HC)
        // Orang-orang ini BOLEH mengakses unit manapun.
        if ($me->hasRole('Superadmin') || 
            $me->hasRole('DHC') || 
            $me->hasRole('Dir SDM') || 
            $cleanTitle === 'AVP HUMAN CAPITAL OPERATION' || 
            $cleanTitle === 'VP HUMAN CAPITAL') { 
            return; // Access Granted Immediate
        }

        // LEVEL 2: PENGECEKAN UNIT (Untuk Admin Ops, SDM Unit, Kepala Unit)
        // Jika user bukan "Dewa" (Level 1), dia HANYA boleh submit untuk unitnya sendiri.
        if ($me->unit_id && $unitId && (string) $me->unit_id !== (string) $unitId) {
            abort(403, 'Akses Unit Ditolak. Anda tidak berhak mengakses unit ini.');
        }

        // LEVEL 3: PEMBATASAN KHUSUS ROLE (Jika lolos unit, cek role spesifik jika perlu)
        if ($me->hasRole('AVP') && $cleanTitle !== 'AVP HUMAN CAPITAL OPERATION') {
             abort(403, 'Akses ditolak. Khusus AVP Human Capital Operation.');
        }
        
        // Jika sampai sini (misal Admin Ops di unit yg sama), berarti Lolos.
    }

    public function previewUraianPdf(Request $request)
    {
        $json = $request->input('data');
        $d = json_decode($json, true);
        if (!$d) return "Data error: JSON tidak valid.";
        $template = ContractTemplate::where('code', 'UJ')->first();
        if (!$template) return "Template 'UJ' belum ada.";
        $fontCss = "";
        try {
            $fontRegular = storage_path('app/fonts/tahoma.ttf');
            $fontBold    = storage_path('app/fonts/tahomabd.ttf');
            if (!file_exists($fontRegular)) $fontRegular = public_path('fonts/tahoma.ttf');
            if (!file_exists($fontBold)) $fontBold = public_path('fonts/tahomabd.ttf');
            if (file_exists($fontRegular)) {
                $fReg = base64_encode(file_get_contents($fontRegular));
                $fBld = file_exists($fontBold) ? base64_encode(file_get_contents($fontBold)) : $fReg;
                
                $fontCss = "
                    @font-face {font-family: 'Tahoma'; font-style: normal; font-weight: normal; src: url(data:font/truetype;charset=utf-8;base64,{$fReg}) format('truetype');}
                    @font-face {font-family: 'Tahoma'; font-style: normal; font-weight: bold; src: url(data:font/truetype;charset=utf-8;base64,{$fBld}) format('truetype');}";
            } else {
                $fontCss = "body { font-family: Helvetica, Arial, sans-serif !important; }"; 
            }
        } catch (\Exception $e) { }

        $disk = config('recruitment.pdf.letterhead_disk', 'public');
        $bgImage = '';
        $headerPath = $template->header_image_path; 
        if (!$headerPath && config('recruitment.pdf.letterhead_path')) {
             $headerPath = config('recruitment.pdf.letterhead_path');
        }

        if ($headerPath && Storage::disk($disk)->exists($headerPath)) {
            $path = Storage::disk($disk)->path($headerPath);
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $dataImg = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($dataImg);
            $bgImage = "<img class='letterhead-img' src='{$base64}'>";
        }
        $mt = number_format($template->margin_top ?? 3.5, 2, '.', '');
        $mr = number_format($template->margin_right ?? 2.54, 2, '.', '');
        $mb = number_format($template->margin_bottom ?? 2.54, 2, '.', '');
        $ml = number_format($template->margin_left ?? 2.54, 2, '.', '');
        $dynamicCss = "
            {$fontCss}
            @page { margin: 0cm; }
            body {font-family: 'Tahoma', sans-serif; margin-top: {$mt}cm; margin-right: {$mr}cm; margin-bottom: {$mb}cm; margin-left: {$ml}cm;}
            .letterhead-img {position: fixed; top: 0; left: 0; width: 21cm; height: 29.7cm; z-index: -1000;}
        ";
        $fmt = fn($t) => !empty($t) ? nl2br(e($t)) : '-';
        $orgChart = '-';
        if (!empty($d['struktur_organisasi'])) {
            $src = $d['struktur_organisasi'];
            if (!Str::startsWith($src, 'data:image')) {
                $src = 'data:image/jpeg;base64,' . $src;
            }
            $orgChart = "<img src='{$src}' style='max-width:100%; max-height:400px; object-fit:contain;'>";
        }

        $vars = [
            'job_title' => $d['nama'] ?? '-',
            'unit_name' => $d['unit'] ?? '-',
            'incumbent' => $d['pemangku'] ?? '-',
            'reports_to'=> $d['melapor'] ?? '-',
            'job_purpose'=> $fmt($d['tujuan'] ?? ''),
            'accountabilities'=> $fmt($d['akuntabilitas'] ?? ''),
            'dim_financial'   => $d['dimensi_keuangan'] ?? '-',
            'budget'          => $d['anggaran'] ?? '-',
            'dim_non_financial'=> $d['dimensi_non_keuangan'] ?? '-',
            'direct_subordinates'=> $d['bawahan_langsung'] ?? '-',
            'total_staff'     => $d['total_staff'] ?? '-',
            'total_employees' => $d['total_pegawai'] ?? '-',
            'authority'       => $fmt($d['wewenang'] ?? ''),
            'rel_internal'    => $fmt($d['hub_internal'] ?? ''),
            'rel_external'    => $fmt($d['hub_eksternal'] ?? ''),
            'spec_education'  => $fmt($d['spek_pendidikan'] ?? ''),
            'spec_skills'     => $fmt($d['spek_pengetahuan'] ?? ''),
            'spec_behavior'   => $fmt($d['spek_kompetensi'] ?? ''),
            'spec_mandatory'  => $fmt($d['spek_kompetensi_wajib'] ?? ''),
            'spec_generic'    => $fmt($d['spek_kompetensi_generik'] ?? ''),
            'org_chart'       => $orgChart,
            'today_date'      => now()->translatedFormat('d F Y'),
            'reports_to_name_sig' => !empty($d['melapor']) ? $d['melapor'] : '................................',
            'incumbent_name_sig' => !empty($d['pemangku']) ? $d['pemangku'] : '................................'
        ];
        $html = $this->renderPdfTemplate($template, $vars, $dynamicCss, $bgImage);
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        return $pdf->stream('Uraian_Jabatan.pdf');
    }

    private function renderPdfTemplate($template, $vars, $dynamicCss, $bgImage)
    {
        $body = $template->body ?? '';
        $css  = $dynamicCss . "\n" . ($template->css ?? '');
        foreach ($vars as $key => $val) {$body = str_replace("{{" . $key . "}}", (string)$val, $body);}
        return "
        <!DOCTYPE html>
        <html>
            <head>
                <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
                <style>{$css}</style>
            </head>
            <body>
                {$bgImage}
                {$body}
            </body>
        </html>";
    }

    public function publish(RecruitmentRequest $req, Request $request) 
    {
        $me = Auth::user();
        $isDhc = $me->hasRole('DHC') || $me->hasRole('Superadmin');
        $isSdmUnit = $me->hasRole('SDM Unit') || $me->hasRole('Kepala Unit'); 
        if (!$isDhc && !$isSdmUnit) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mempublikasikan lowongan.'], 403);
        }
        if (strtolower($req->status) !== 'approved' || empty($req->ticket_number)) {
            return response()->json(['success' => false, 'message' => 'Hanya Izin Prinsip yang sudah Approved dan memiliki No Ticket yang dapat dipublikasikan.'], 422);
        }
        $request->validate([
            'description' => 'required|string',
            'publish_start_date' => 'required|date',
            'publish_end_date' => 'required|date|after_or_equal:publish_start_date',
            'publish_location' => 'required|string|max:255',
        ]);
        $req->update([
            'is_published' => true,
            'published_at' => now(),
            'description'  => $request->description,
            'publish_start_date' => $request->publish_start_date,
            'publish_end_date'   => $request->publish_end_date,
            'publish_location'   => $request->publish_location
        ]);
        return response()->json(['success' => true, 'message' => 'Lowongan berhasil dipublikasikan dengan deskripsi!']);
    }
    protected function checkUnitHasPosition($unitId, $positionNameFragment)
    {
        return DB::table('employees')
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->where('employees.unit_id', $unitId)
            ->where('positions.name', 'like', '%' . $positionNameFragment . '%')
            ->exists();
    }
}