<?php

namespace App\Http\Controllers\Recruitment;

use App\Models\Project; 
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Exports\RecruitmentRequestExport;
use Maatwebsite\Excel\Facades\Excel;

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
            ['key' => 'avp_hc_ops',   'roles' => ['AVP Human Capital Operation']], 
            ['key' => 'vp_hc',        'roles' => ['VP Human Capital']],
            ['key' => 'dir_sdm',      'roles' => ['Dir SDM']],
        ];
    }

    protected function canSeeAll($user): bool
    {
        if (!$user) return false;
        $jobTitle = $this->getUserJobTitle($user->id);
        return $user->hasRole('Superadmin') 
            || $user->hasRole('DHC') 
            || $user->hasRole('Dir SDM')
            || $user->hasRole('AVP Human Capital Operation')
            || $user->hasRole('VP Human Capital')
            || $jobTitle === 'AVP Human Capital Operation'
            || $jobTitle === 'VP Human Capital';
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
        $units = $canSeeAll
            ? DB::table('units')->select('id', 'name')->orderBy('name')->get()
            : DB::table('units')->select('id', 'name')->where('id', $me?->unit_id)->get();
        $query = $this->getBaseQuery($me, $canSeeAll, $selectedUnitId);

        if ($r->filled('open_ticket_id')) {
            $ticketId = $r->input('open_ticket_id');
            $query->where('id', $ticketId);
        }

        $list = $query->with(['approvals' => fn($q) => $q->orderBy('id', 'asc')])
                      ->latest()
                      ->paginate(50)
                      ->withQueryString();

        $projects = [];
        $locations = DB::table('locations')->select('id', 'city', 'name')->orderBy('city')->get();

        return view('recruitment.principal-approval.index', [
            'list'           => $list,
            'units'          => $units,
            'canSeeAll'      => $canSeeAll,
            'selectedUnitId' => $selectedUnitId,
            'projects'       => $projects,  
            'locations'      => $locations, 
        ]);
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
                // Jika TIDAK ADA Kepala MP, baru lompat ke SDM Unit (Stage 1)
                $startStage = 1;
            }
            // Jika ADA Kepala MP, tetap $startStage = 0 (masuk ke Kepala MP)
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
        
        // Pastikan user punya hak akses
        if (!$this->canActOnStage(Auth::user(), $stageIdx, $req->unit_id)) {
            abort(403, 'Unauthorized.');
        }
        
        $note = $r->input('note'); 
        $extendedNote = $r->input('extended_note');
        if (!empty($extendedNote)) {
            $cleanNote = strip_tags($extendedNote, '<b><i><u><ol><ul><li><br><p>'); 
            $note = $note ? $note . "\n<hr>\n" . $cleanNote : $cleanNote; 
        }
        $this->closePending($req, 'approved', $note);

        $totalStages = count($this->stages());
        $nextStage = $stageIdx + 1;

        // Cek Final Stage
        if ($nextStage >= $totalStages) {
            if (Schema::hasColumn($req->getTable(), 'status')) {
                $req->update(['status' => 'approved']);
            }        
            $req->generateTicketNumber();
            return back()->with('ok', 'Izin Prinsip sepenuhnya disetujui. Nomor Ticket: ' . $req->ticket_number);
        }

        // Buat approval untuk stage berikutnya
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
                return 0; // Fallback logic
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

    // GANTI METHOD canActOnStage() SEPENUHNYA:
    protected function canActOnStage($user, int $stageIdx, $reqUnitId): bool
    {
        if (!$user) return false;
        if ($user->hasRole('Superadmin')) return true;
        
        $stage = $this->stages()[$stageIdx] ?? null;
        if (!$stage) return false;
        
        $jobTitle = $this->getUserJobTitle($user->id);
        $cleanJobTitle = trim(strtoupper($jobTitle));

        // Logic Default Role Spatie
        $allowed = false;
        foreach ($stage['roles'] as $r) {
            if ($user->hasRole($r)) {
                $allowed = true;
                break;
            }
        }

        // Logic Spesifik per Stage Key
        if ($stage['key'] === 'kepala_mp') {
             // Cek Role atau Jabatan "Kepala Proyek (MP)" + Wajib satu unit
             $isTitleMatch = str_contains($cleanJobTitle, 'KEPALA PROYEK (MP)');
             return ($allowed || $isTitleMatch) && ((string) $user->unit_id === (string) $reqUnitId);
        }

        if ($stage['key'] === 'sdm_unit') {
             // Cek Role SDM Unit + Wajib satu unit
             return $allowed && ((string) $user->unit_id === (string) $reqUnitId);
        }

        if ($stage['key'] === 'kepala_unit') {
            // Cek Role Kepala Unit + Wajib satu unit
            return $allowed && ((string) $user->unit_id === (string) $reqUnitId);
        }

        if ($stage['key'] === 'dhc_checker') {
            // DHC (Pusat) atau Kepala Unit DHC (Cabang DHC)
            if ($allowed) return true;
            $isKepalaUnit = $user->hasRole('Kepala Unit');
            return $isKepalaUnit && $this->dhcUnitId() && ((string) $user->unit_id === (string) $this->dhcUnitId());
        }

        if ($stage['key'] === 'avp_hc_ops') {
            return $allowed || ($cleanJobTitle === 'AVP HUMAN CAPITAL OPERATION');
        }

        if ($stage['key'] === 'vp_hc') {
            return $allowed || ($cleanJobTitle === 'VP HUMAN CAPITAL');
        }

        // Dir SDM (Default allowed check sudah cukup)
        return $allowed;
    }

    protected function authorizeUnit($unitId): void
    {
        $me = Auth::user();
        if (!$me) abort(401);
        $jobTitle = $this->getUserJobTitle($me->id);
        
        // Daftar role pusat yang boleh lintas unit
        if ($me->hasRole('Superadmin') 
            || $me->hasRole('DHC') 
            || $me->hasRole('Dir SDM')
            || $me->hasRole('AVP Human Capital Operation') 
            || $me->hasRole('VP Human Capital')
            || $jobTitle === 'AVP Human Capital Operation'
            || $jobTitle === 'VP Human Capital') {
            return;
        }

        // Untuk unit user biasa, harus sama unit_id nya
        if ($me->unit_id && $unitId && (string) $me->unit_id !== (string) $unitId) {
            abort(403);
        }
    }

    public function previewUraianPdf(Request $request)
    {
        $json = $request->input('data');
        $d = json_decode($json, true);
        if (!$d) {
            return "Data uraian jabatan tidak valid atau kosong.";
        }
        $pdf = Pdf::loadView('pdf.uraian_jabatan', compact('d'));
        $pdf->setPaper('a4', 'portrait');
        $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $d['nama'] ?? 'Draft');
        $filename = 'Uraian_Jabatan_' . $safeName . '.pdf';
        return $pdf->stream($filename);
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
            'description' => 'required|string'
        ]);
        $req->update([
            'is_published' => true,
            'published_at' => now(),
            'description'  => $request->description 
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