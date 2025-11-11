<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrincipalApprovalController extends Controller
{
    public function index(Request $r)
    {
        /** @var \App\Models\User|null $me */
        $me = Auth::user();

        // === Hanya Superadmin & DHC yang bisa lihat semua ===
        $canSeeAll = $me?->hasRole('Superadmin') || $me?->hasRole('DHC');

        // unit terpilih dari GET (aktif hanya jika canSeeAll). Null = all units
        $selectedUnitId = $canSeeAll
            ? ($r->filled('unit_id') ? (int) $r->integer('unit_id') : null)
            : (int) ($me?->unit_id);

        // opsi unit untuk dropdown
        $units = $canSeeAll
            ? DB::table('units')->select('id','name')->orderBy('name')->get()
            : DB::table('units')->select('id','name')->where('id', $me?->unit_id)->get();

        $tbl = (new RecruitmentRequest())->getTable();

        // ==== FIX INTI: jika canSeeAll => JANGAN pakai forViewer(), agar tidak ke-lock unit DHC/HO ====
        $query = $canSeeAll
            ? RecruitmentRequest::query()
            : RecruitmentRequest::query()->forViewer($me);

        // filter unit jika dipilih
        if ($selectedUnitId && Schema::hasColumn($tbl, 'unit_id')) {
            $query->where('unit_id', $selectedUnitId);
        }
        // catatan: saat canSeeAll && no unit_id => tampilkan SEMUA tanpa filter

        $list = $query->latest()->paginate(12)->withQueryString();

        return view('recruitment.principal-approval.index', [
            'list'           => $list,
            'units'          => $units,
            'canSeeAll'      => $canSeeAll,
            'selectedUnitId' => $selectedUnitId,
        ]);
    }

    // CREATE via modal (same page)
    public function store(Request $r)
    {
        $data = $r->validate([
            'title'         => 'required|string',
            'position'      => 'required|string',
            'headcount'     => 'required|integer|min:1',
            'justification' => 'nullable|string',
        ]);

        /** @var \App\Models\User|null $me */
        $me   = Auth::user();
        $meId = Auth::id();

        $model = new RecruitmentRequest();
        $tbl   = $model->getTable();

        $pick = function(array $cands) use ($tbl) {
            foreach ($cands as $c) if (Schema::hasColumn($tbl, $c)) return $c;
            return null;
        };

        $insert = [];
        if (Schema::hasColumn($tbl, 'unit_id'))  $insert['unit_id'] = $me?->unit_id;
        if (Schema::hasColumn($tbl, 'status'))   $insert['status']  = 'draft';

        if ($col = $pick(['requested_by','requested_by_user_id','created_by','created_by_user_id'])) {
            $insert[$col] = $meId;
        }

        $map = [
            'title'         => ['title','job_title','name'],
            'position'      => ['position','position_name','job_title'],
            'headcount'     => ['headcount','requested_headcount','qty'],
            'justification' => ['justification','reason','notes','note','description'],
        ];
        foreach ($map as $logical => $cands) {
            if (!array_key_exists($logical, $data)) continue;
            if ($data[$logical] === null || $data[$logical] === '') continue;
            if ($col = $pick($cands)) $insert[$col] = $data[$logical];
        }

        $model->forceFill($insert)->save();

        return back()->with('ok', 'Principal approval draft created.');
    }

    public function submit(RecruitmentRequest $req)
    {
        $this->authorizeUnit($req->unit_id);

        if (isset($req->status) && $req->status !== 'draft') {
            return back()->withErrors('Only DRAFT can be submitted.');
        }

        $tbl = $req->getTable();
        if (Schema::hasColumn($tbl, 'status')) {
            $req->update(['status' => 'submitted']);
        }

        if (method_exists($req, 'approvals')) {
            try {
                $rel = $req->approvals();
                $apprModel = $rel->getRelated();
                $apprTable = $apprModel->getTable();

                if (Schema::hasTable($apprTable)) {
                    $payload = [];
                    if (Schema::hasColumn($apprTable, 'level'))    $payload['level'] = 1;
                    if (Schema::hasColumn($apprTable, 'step'))     $payload['step']  = 1;
                    if (Schema::hasColumn($apprTable, 'role_key')) $payload['role_key'] = 'vp_gm';
                    if (Schema::hasColumn($apprTable, 'status'))   $payload['status'] = 'pending';
                    if (Schema::hasColumn($apprTable, 'state'))    $payload['state']  = 'pending';

                    if ($payload) {
                        $m = $apprModel->newInstance();
                        $m->forceFill($payload);
                        $rel->save($m);
                    }
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        return back()->with('ok', 'Submitted to VP/GM.');
    }

    public function approve(RecruitmentRequest $req, Request $r)
    {
        $this->authorizeUnit($req->unit_id);

        if (isset($req->status) && $req->status !== 'submitted') {
            return back()->withErrors('Only SUBMITTED can be approved.');
        }

        $meId = Auth::id();

        $tbl = $req->getTable();
        $changes = [];
        if (Schema::hasColumn($tbl, 'status'))        $changes['status'] = 'approved';
        if (Schema::hasColumn($tbl, 'approved_by'))   $changes['approved_by'] = $meId;
        if (Schema::hasColumn($tbl, 'approved_at'))   $changes['approved_at'] = now();
        if ($changes) $req->update($changes);

        if (method_exists($req, 'approvals')) {
            try {
                $rel = $req->approvals();
                $apprModel = $rel->getRelated();
                $apprTable = $apprModel->getTable();
                if (Schema::hasTable($apprTable)) {
                    $q = $rel->getQuery();
                    if (Schema::hasColumn($apprTable, 'status')) $q->where('status','pending');
                    elseif (Schema::hasColumn($apprTable, 'state')) $q->where('state','pending');

                    $apprChanges = [];
                    if (Schema::hasColumn($apprTable, 'status'))    $apprChanges['status'] = 'approved';
                    if (Schema::hasColumn($apprTable, 'state'))     $apprChanges['state']  = 'approved';
                    if (Schema::hasColumn($apprTable, 'user_id'))   $apprChanges['user_id'] = $meId;
                    if (Schema::hasColumn($apprTable, 'decided_at'))$apprChanges['decided_at'] = now();
                    if (Schema::hasColumn($apprTable, 'note') && $r->filled('note')) $apprChanges['note'] = $r->note;

                    if ($apprChanges) $q->latest()->first()?->update($apprChanges);
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        return back()->with('ok', 'Request approved.');
    }

    public function reject(RecruitmentRequest $req, Request $r)
    {
        $this->authorizeUnit($req->unit_id);

        if (isset($req->status) && $req->status !== 'submitted') {
            return back()->withErrors('Only SUBMITTED can be rejected.');
        }

        $meId = Auth::id();

        $tbl = $req->getTable();
        if (Schema::hasColumn($tbl, 'status')) {
            $req->update(['status' => 'rejected']);
        }

        if (method_exists($req, 'approvals')) {
            try {
                $rel = $req->approvals();
                $apprModel = $rel->getRelated();
                $apprTable = $apprModel->getTable();
                if (Schema::hasTable($apprTable)) {
                    $q = $rel->getQuery();
                    if (Schema::hasColumn($apprTable, 'status')) $q->where('status','pending');
                    elseif (Schema::hasColumn($apprTable, 'state')) $q->where('state','pending');

                    $apprChanges = [];
                    if (Schema::hasColumn($apprTable, 'status'))    $apprChanges['status'] = 'rejected';
                    if (Schema::hasColumn($apprTable, 'state'))     $apprChanges['state']  = 'rejected';
                    if (Schema::hasColumn($apprTable, 'user_id'))   $apprChanges['user_id'] = $meId;
                    if (Schema::hasColumn($apprTable, 'decided_at'))$apprChanges['decided_at'] = now();
                    if (Schema::hasColumn($apprTable, 'note') && $r->filled('note')) $apprChanges['note'] = $r->note;

                    if ($apprChanges) $q->latest()->first()?->update($apprChanges);
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        return back()->with('ok', 'Request rejected.');
    }

    protected function authorizeUnit($unitId): void
    {
        /** @var \App\Models\User|null $me */
        $me = Auth::user();

        // Bypass hanya untuk Superadmin & DHC
        if ($me && ($me->hasRole('Superadmin') || $me->hasRole('DHC'))) {
            return;
        }

        // Selain itu, wajib unit sama
        $meUnit = $me?->unit_id;
        if ($meUnit && $unitId && (string)$meUnit !== (string)$unitId) {
            abort(403);
        }
    }
}
