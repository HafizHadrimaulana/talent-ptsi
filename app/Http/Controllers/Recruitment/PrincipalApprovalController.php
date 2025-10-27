<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PrincipalApprovalController extends Controller
{
    public function index()
    {
        $list = RecruitmentRequest::query()
            ->forViewer(auth()->user())
            ->latest()
            ->paginate(12);

        return view('recruitment.principal-approval.index', compact('list'));
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

        $model = new RecruitmentRequest();
        $tbl   = $model->getTable();

        // helper untuk ambil kolom pertama yang ada di tabel
        $pick = function(array $cands) use ($tbl) {
            foreach ($cands as $c) if (Schema::hasColumn($tbl, $c)) return $c;
            return null;
        };

        $insert = [];

        // kolom meta yang umum
        if (Schema::hasColumn($tbl, 'unit_id'))  $insert['unit_id'] = auth()->user()->unit_id;
        if (Schema::hasColumn($tbl, 'status'))   $insert['status']  = 'draft';

        // requested_by fallback ke varian lain
        if ($col = $pick(['requested_by','requested_by_user_id','created_by','created_by_user_id'])) {
            $insert[$col] = auth()->id();
        }

        // map field logis -> kemungkinan nama kolom di DB
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

        // simpan aman dari mass-assignment
        $model->forceFill($insert)->save();

        return back()->with('ok', 'Principal approval draft created.');
    }

    public function submit(RecruitmentRequest $req)
    {
        $this->authorizeUnit($req->unit_id);

        // status guard
        if (isset($req->status) && $req->status !== 'draft') {
            return back()->withErrors('Only DRAFT can be submitted.');
        }

        // update status -> submitted kalau kolomnya ada
        $tbl = $req->getTable();
        if (Schema::hasColumn($tbl, 'status')) {
            $req->update(['status' => 'submitted']);
        }

        // buat approval pertama (optional: hanya jika relasi & kolomnya ada)
        if (method_exists($req, 'approvals')) {
            try {
                $rel = $req->approvals();
                $apprModel = $rel->getRelated();
                $apprTable = $apprModel->getTable();

                if (Schema::hasTable($apprTable)) {
                    $payload = [];

                    // isi hanya kolom yang tersedia
                    if (Schema::hasColumn($apprTable, 'level'))    $payload['level'] = 1;
                    if (Schema::hasColumn($apprTable, 'step'))     $payload['step']  = 1; // alternatif
                    if (Schema::hasColumn($apprTable, 'role_key')) $payload['role_key'] = 'vp_gm';
                    if (Schema::hasColumn($apprTable, 'status'))   $payload['status'] = 'pending';
                    if (Schema::hasColumn($apprTable, 'state'))    $payload['state']  = 'pending';

                    if (!empty($payload)) {
                        $m = $apprModel->newInstance();
                        $m->forceFill($payload);
                        $rel->save($m); // morph keys diisi otomatis
                    }
                }
            } catch (\Throwable $e) {
                // skip silently agar flow tetap lanjut
            }
        }

        return back()->with('ok', 'Submitted to VP/GM.');
    }

    public function approve(RecruitmentRequest $req, Request $r)
    {
        $this->authorizeUnit($req->unit_id);

        if (isset($req->status) && $req->status !== 'submitted') {
            return back()->withErrors('Only SUBMITTED can be approved.');
        }

        // update status approved (kolom-kolom opsional)
        $tbl = $req->getTable();
        $changes = [];
        if (Schema::hasColumn($tbl, 'status'))        $changes['status'] = 'approved';
        if (Schema::hasColumn($tbl, 'approved_by'))   $changes['approved_by'] = auth()->id();
        if (Schema::hasColumn($tbl, 'approved_at'))   $changes['approved_at'] = now();
        if ($changes) $req->update($changes);

        // update approval record pending -> approved
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
                    if (Schema::hasColumn($apprTable, 'user_id'))   $apprChanges['user_id'] = auth()->id();
                    if (Schema::hasColumn($apprTable, 'decided_at'))$apprChanges['decided_at'] = now();
                    if (Schema::hasColumn($apprTable, 'note') && $r->filled('note')) $apprChanges['note'] = $r->note;

                    if ($apprChanges) $q->latest()->first()?->update($apprChanges);
                }
            } catch (\Throwable $e) {
                // skip
            }
        }

        return back()->with('ok', 'Request approved.');
    }

    public function reject(RecruitmentRequest $req, Request $r)
    {
        $this->authorizeUnit($req->unit_id);

        if (isset($req->status) && $req->status !== 'submitted') {
            return back()->withErrors('Only SUBMITTED can be rejected.');
        }

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
                    if (Schema::hasColumn($apprTable, 'user_id'))   $apprChanges['user_id'] = auth()->id();
                    if (Schema::hasColumn($apprTable, 'decided_at'))$apprChanges['decided_at'] = now();
                    if (Schema::hasColumn($apprTable, 'note') && $r->filled('note')) $apprChanges['note'] = $r->note;

                    if ($apprChanges) $q->latest()->first()?->update($apprChanges);
                }
            } catch (\Throwable $e) {
                // skip
            }
        }

        return back()->with('ok', 'Request rejected.');
    }

    protected function authorizeUnit($unitId): void
    {
        $meUnit = auth()->user()->unit_id;
        if ($meUnit && $meUnit != $unitId && !auth()->user()->hasRole('Superadmin')) {
            abort(403);
        }
    }
}
