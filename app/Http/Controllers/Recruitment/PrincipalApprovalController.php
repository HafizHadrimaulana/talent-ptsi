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
    protected function stages(): array
    {
        return [
            ['key' => 'kepala_unit', 'roles' => ['Kepala Unit']],
            ['key' => 'dhc_checker', 'roles' => ['DHC']],
            ['key' => 'dir_sdm',     'roles' => ['Dir SDM']],
        ];
    }

    protected function canSeeAll($user): bool
    {
        return $user?->hasRole('Superadmin') || $user?->hasRole('DHC') || $user?->hasRole('Dir SDM');
    }

    protected function dhcUnitId(): ?int
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $cache = DB::table('units')->where(function($q){
            $q->where('code', 'DHC')
              ->orWhere('name', 'Divisi Human Capital')
              ->orWhere('name', 'like', 'Divisi Human Capital%');
        })->value('id');

        return $cache ? (int)$cache : null;
    }

    protected function has(string $table, string $col): bool
    {
        try { return Schema::hasColumn($table, $col); } catch (\Throwable $e) { return false; }
    }

    public function index(Request $r)
    {
        $me = Auth::user();
        $tbl = (new RecruitmentRequest())->getTable();

        $canSeeAll = $this->canSeeAll($me);

        $selectedUnitId = $canSeeAll
            ? ($r->filled('unit_id') ? (int) $r->integer('unit_id') : null)
            : (int) ($me?->unit_id);

        $units = $canSeeAll
            ? DB::table('units')->select('id','name')->orderBy('name')->get()
            : DB::table('units')->select('id','name')->where('id', $me?->unit_id)->get();

        $query = RecruitmentRequest::query();

        if (!$canSeeAll) {
            $isKepalaUnit = $me?->hasRole('Kepala Unit');

            $query->where(function($q) use ($me, $tbl, $isKepalaUnit) {
                $q->where('unit_id', $me->unit_id);

                $q->where(function($qq) use ($tbl, $me, $isKepalaUnit) {
                    $qq->whereNull('status')->orWhere('status', '!=', 'draft');

                    $creatorCols = ['requested_by','requested_by_user_id','created_by','created_by_user_id'];
                    $qq->orWhere(function($qDraft) use ($tbl, $creatorCols, $me, $isKepalaUnit) {
                        $qDraft->where('status', 'draft');

                        if ($isKepalaUnit) {
                            $qDraft->whereRaw('1 = 0');
                        } else {
                            $qDraft->where(function($qOwner) use ($tbl, $creatorCols) {
                                foreach ($creatorCols as $c) {
                                    if (Schema::hasColumn($tbl, $c)) {
                                        $qOwner->orWhere($c, Auth::id());
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

        $list = $query->with(['approvals' => fn($q) => $q->orderBy('id','asc')])
                      ->latest()->paginate(12)->withQueryString();

        return view('recruitment.principal-approval.index', [
            'list'           => $list,
            'units'          => $units,
            'canSeeAll'      => $canSeeAll,
            'selectedUnitId' => $selectedUnitId,
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title'         => 'required|string',
            'position'      => 'required|string',
            'headcount'     => 'required|integer|min:1',
            'justification' => 'nullable|string',
        ]);

        $me   = Auth::user();
        $meId = Auth::id();

        $model = new RecruitmentRequest();
        $tbl   = $model->getTable();

        $pick = function(array $cands) use ($tbl) {
            foreach ($cands as $c) if (Schema::hasColumn($tbl, $c)) return $c;
            return null;
        };

        $insert = [];
        if ($this->has($tbl, 'unit_id')) $insert['unit_id'] = $me?->unit_id;
        if ($this->has($tbl, 'status'))  $insert['status']  = 'draft';

        if ($col = $pick(['requested_by','requested_by_user_id','created_by','created_by_user_id'])) {
            $insert[$col] = $meId;
        }

        $map = [
            'title'         => ['title','job_title','name'],
            'position'      => ['position','position_name','job_title'],
            'headcount'     => ['headcount','requested_headcount','qty'],
            'justification' => ['justification','reason','notes','note','description'],
        ];
        foreach ($map as $key => $cands) {
            if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') continue;
            if ($col = $pick($cands)) $insert[$col] = $data[$key];
        }

        $model->forceFill($insert)->save();

        return back()->with('ok', 'Principal approval draft created.');
    }

    public function submit(RecruitmentRequest $req)
    {
        $this->authorizeUnit($req->unit_id);

        if (($req->status ?? null) !== 'draft') {
            return back()->withErrors('Only DRAFT can be submitted.');
        }

        if (Schema::hasColumn($req->getTable(), 'status')) {
            $req->update(['status' => 'submitted']);
        }

        $this->createPendingApproval($req, 0);
        if (Schema::hasColumn($req->getTable(), 'status')) {
            $req->update(['status' => 'in_review']);
        }

        return back()->with('ok', 'Submitted to Kepala Unit.');
    }

    public function approve(RecruitmentRequest $req, Request $r)
    {
        $stageIdx = $this->currentStageIndex($req);
        if ($stageIdx === null) return back()->withErrors('No pending approval.');
        if (!$this->canActOnStage(Auth::user(), $stageIdx, $req->unit_id)) abort(403);

        $this->closePending($req, 'approved', $r->input('note'));

        $isLast = $stageIdx >= (count($this->stages()) - 1);
        if ($isLast) {
            if (Schema::hasColumn($req->getTable(), 'status')) $req->update(['status' => 'approved']);
            return back()->with('ok', 'Request fully approved.');
        }

        $this->createPendingApproval($req, $stageIdx + 1);
        return back()->with('ok', 'Approved. Sent to next approver.');
    }

    public function reject(RecruitmentRequest $req, Request $r)
    {
        $stageIdx = $this->currentStageIndex($req);
        if ($stageIdx === null) return back()->withErrors('No pending approval.');
        if (!$this->canActOnStage(Auth::user(), $stageIdx, $req->unit_id)) abort(403);

        $this->closePending($req, 'rejected', $r->input('note'));
        if (Schema::hasColumn($req->getTable(), 'status')) $req->update(['status' => 'rejected']);

        return back()->with('ok', 'Request rejected.');
    }

    protected function currentStageIndex(RecruitmentRequest $req): ?int
    {
        foreach ($req->approvals()->orderBy('id','asc')->get() as $i => $ap) {
            if (($ap->status ?? 'pending') === 'pending') return $i;
        }
        return null;
    }

    protected function createPendingApproval(RecruitmentRequest $req, int $stageIdx): void
    {
        $rel = $req->approvals();
        $m = $rel->getRelated()->newInstance();
        $stageKey = $this->stages()[$stageIdx]['key'] ?? ('stage_'.$stageIdx);
        $m->forceFill(['status' => 'pending', 'note' => "[stage={$stageKey}]"]);
        $rel->save($m);
    }

    protected function closePending(RecruitmentRequest $req, string $status, ?string $note = null): void
    {
        $appr = $req->approvals()->where('status','pending')->latest('id')->first();
        if ($appr) {
            $payload = ['status' => $status, 'decided_at' => now(), 'approver_user_id' => Auth::id()];
            if ($note) $payload['note'] = trim(($appr->note ? $appr->note.' ' : '').$note);
            $appr->update($payload);
        }
    }

    protected function canActOnStage($user, int $stageIdx, $reqUnitId): bool
    {
        if (!$user) return false;
        if ($user->hasRole('Superadmin')) return true;

        $stage = $this->stages()[$stageIdx] ?? null;
        if (!$stage) return false;

        $allowed = false;
        foreach ($stage['roles'] as $r) {
            if ($user->hasRole($r)) { $allowed = true; break; }
        }

        if ($stage['key'] === 'kepala_unit') {
            return $allowed && ((string)$user->unit_id === (string)$reqUnitId);
        }

        if ($stage['key'] === 'dhc_checker') {
            if ($allowed) return true;
            $isKepalaUnit = $user->hasRole('Kepala Unit');
            $isKepalaUnitDhc = $isKepalaUnit && $this->dhcUnitId() && ((string)$user->unit_id === (string)$this->dhcUnitId());
            return $isKepalaUnitDhc;
        }

        return $allowed;
    }

    protected function authorizeUnit($unitId): void
    {
        $me = Auth::user();
        if (!$me) abort(401);
        if ($me->hasRole('Superadmin') || $me->hasRole('DHC') || $me->hasRole('Dir SDM')) return;
        if ($me->unit_id && $unitId && (string)$me->unit_id !== (string)$unitId) abort(403);
    }
}
