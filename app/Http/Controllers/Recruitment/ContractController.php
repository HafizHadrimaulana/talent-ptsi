<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\{Contract, Applicant};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index(Request $r)
    {
        $me = auth()->user();

        // === Hanya Superadmin & DHC yang bisa lihat semua ===
        $canSeeAll = $me?->hasRole('Superadmin') || $me?->hasRole('DHC');

        $selectedUnitId = $canSeeAll
            ? ($r->filled('unit_id') ? (int)$r->integer('unit_id') : null)
            : (int)($me?->unit_id);

        $units = $canSeeAll
            ? DB::table('units')->select('id','name')->orderBy('name')->get()
            : DB::table('units')->select('id','name')->where('id', $me?->unit_id)->get();

        $tbl   = (new Contract())->getTable();
        $listQ = Contract::query();
        if ($selectedUnitId && Schema::hasColumn($tbl, 'unit_id')) {
            $listQ->where('unit_id', $selectedUnitId);
        }
        $list = $listQ->latest()->paginate(12)->withQueryString();

        // Applicants opsional
        $applicants = collect();
        if (class_exists(Applicant::class) && Schema::hasTable((new Applicant)->getTable())) {
            $atbl = (new Applicant)->getTable();
            $cols = ['id'];
            foreach (['full_name','position_applied','unit_id','status','created_at'] as $c) {
                if (Schema::hasColumn($atbl, $c)) $cols[] = $c;
            }

            $aq = Applicant::query()->select($cols);
            if ($selectedUnitId && Schema::hasColumn($atbl, 'unit_id')) {
                $aq->where('unit_id', $selectedUnitId);
            }
            if (Schema::hasColumn($atbl, 'status')) {
                $aq->whereIn('status', ['shortlisted','selected']);
            }

            $applicants = $aq->latest()->limit(50)->get();
        }

        return view('recruitment.contracts.index', [
            'list'           => $list,
            'applicants'     => $applicants,
            'units'          => $units,
            'canSeeAll'      => $canSeeAll,
            'selectedUnitId' => $selectedUnitId,
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'type'         => 'required|in:SPK,PKWT',
            'person_name'  => 'required',
            'position'     => 'required',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'salary'       => 'nullable|numeric',
            'applicant_id' => 'nullable|integer',
            'employee_id'  => 'nullable|integer',
        ]);

        $tbl = (new Contract)->getTable();
        $pick = function(array $cands) use ($tbl) { foreach ($cands as $c) if (Schema::hasColumn($tbl,$c)) return $c; return null; };

        $insert = [];
        if (Schema::hasColumn($tbl, 'unit_id'))      $insert['unit_id']       = auth()->user()->unit_id;
        if (Schema::hasColumn($tbl, 'status'))       $insert['status']        = 'draft';
        if (Schema::hasColumn($tbl, 'created_by'))   $insert['created_by']    = auth()->id();
        if (Schema::hasColumn($tbl, 'created_by_user_id')) $insert['created_by_user_id'] = auth()->id();

        $map = [
            'type'        => ['type','contract_type'],
            'person_name' => ['person_name','candidate_name','name'],
            'position'    => ['position','position_name','job_title'],
            'start_date'  => ['start_date','date_start','from_date'],
            'end_date'    => ['end_date','date_end','to_date'],
            'salary'      => ['salary','amount','gross_salary'],
            'applicant_id'=> ['applicant_id'],
            'employee_id' => ['employee_id','person_id'],
        ];
        foreach ($map as $logical => $cands) {
            if (!array_key_exists($logical,$data)) continue;
            if ($col = $pick($cands)) $insert[$col] = $data[$logical];
        }
        if (!isset($insert['type']) && !isset($insert['contract_type'])) {
            if ($col = $pick(['type','contract_type'])) $insert[$col] = $data['type'];
        }

        $c = new Contract();
        $c->forceFill($insert)->save();

        if (!empty($c->applicant_id)
            && class_exists(Applicant::class)
            && Schema::hasTable((new Applicant)->getTable())
            && Schema::hasColumn((new Applicant)->getTable(), 'status')) {
            Applicant::whereKey($c->applicant_id)->update(['status' => 'selected']);
        }

        return back()->with('ok', 'Contract draft created.');
    }

    public function submit(Contract $contract)
    {
        $this->authorizeUnit($contract->unit_id);

        if ($contract->status !== 'draft' && $contract->status !== 'review') {
            return back()->withErrors('Only drafts can be submitted.');
        }

        if (Schema::hasColumn($contract->getTable(), 'status')) {
            $contract->update(['status' => 'review']);
        }

        if (method_exists($contract, 'approvals')) {
            try {
                $rel = $contract->approvals();
                $m   = $rel->getRelated();
                $t   = $m->getTable();

                if (Schema::hasTable($t)) {
                    $payload = [];
                    if (Schema::hasColumn($t,'level'))    $payload['level'] = 1;
                    if (Schema::hasColumn($t,'role_key')) $payload['role_key'] = 'vp_gm';
                    if (Schema::hasColumn($t,'status'))   $payload['status'] = 'pending';
                    if (Schema::hasColumn($t,'step'))     $payload['step'] = 1;
                    if (Schema::hasColumn($t,'state'))    $payload['state'] = 'pending';

                    if ($payload) {
                        $rec = $m->newInstance();
                        $rec->forceFill($payload);
                        $rel->save($rec);
                    }
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        return back()->with('ok', 'Contract submitted for review.');
    }

    public function approve(Contract $contract, Request $r)
    {
        $this->authorizeUnit($contract->unit_id);

        if ($contract->status !== 'review') {
            return back()->withErrors('Contract must be in review status.');
        }

        $update = [];
        if (Schema::hasColumn($contract->getTable(), 'status'))      $update['status']      = 'approved';
        if (Schema::hasColumn($contract->getTable(), 'approved_by')) $update['approved_by'] = auth()->id();
        if (Schema::hasColumn($contract->getTable(), 'approved_at')) $update['approved_at'] = now();
        if ($update) $contract->update($update);

        if (method_exists($contract, 'approvals')) {
            try {
                $rel = $contract->approvals();
                $m   = $rel->getRelated();
                $t   = $m->getTable();

                if (Schema::hasTable($t)) {
                    $q = $rel->getQuery();
                    if (Schema::hasColumn($t,'status')) $q->where('status','pending');
                    elseif (Schema::hasColumn($t,'state')) $q->where('state','pending');

                    $changes = [];
                    if (Schema::hasColumn($t,'status'))   $changes['status'] = 'approved';
                    if (Schema::hasColumn($t,'state'))    $changes['state']  = 'approved';
                    if (Schema::hasColumn($t,'user_id'))  $changes['user_id'] = auth()->id();
                    if (Schema::hasColumn($t,'decided_at')) $changes['decided_at'] = now();
                    if (Schema::hasColumn($t,'note') && $r->filled('note')) $changes['note'] = $r->note;

                    if ($changes) $q->update($changes);
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Generate nomor kontrak (TYPE-SEQTYPE/UNIT-SEQUNIT/INISIAL/YEAR)
        $tbl = $contract->getTable();
        $typeCol   = Schema::hasColumn($tbl,'type') ? 'type' : (Schema::hasColumn($tbl,'contract_type') ? 'contract_type' : null);
        $numberCol = Schema::hasColumn($tbl,'number') ? 'number' : (Schema::hasColumn($tbl,'contract_no') ? 'contract_no' : null);

        if ($numberCol && $typeCol && empty($contract->$numberCol)) {
            $typeVal = strtoupper($contract->$typeCol);

            $dateCol = collect(['approved_at','created_at','updated_at'])->first(fn($c)=>Schema::hasColumn($tbl,$c));
            $refDate = $dateCol ? \Illuminate\Support\Carbon::parse($contract->$dateCol) : now();
            $year  = (int)$refDate->format('Y');
            $month = (int)$refDate->format('m');

            $initials = function (?string $name): string {
                if (!$name) return 'XX';
                $parts = preg_split('/\s+/', trim($name));
                $first = mb_substr($parts[0] ?? '', 0, 1);
                $last  = mb_substr(end($parts) ?: '', 0, 1);
                return mb_strtoupper(($first ?: '') . ($last ?: ''));
            };
            $inisial = $initials(optional(auth()->user())->name);

            $unitCode = 'U'.$contract->unit_id;
            if (Schema::hasTable('units')) {
                $u = DB::table('units')->where('id', $contract->unit_id)->first();
                if ($u) {
                    $unitCode = strtoupper(
                        $u->code ?? $u->abbr ?? $u->short_name
                        ?? (isset($u->name) ? (preg_replace('/[^A-Z]/','', mb_strtoupper($u->name)) ?: 'U'.$contract->unit_id) : 'U'.$contract->unit_id)
                    );
                }
            }

            $seqType = DB::table($tbl)
                ->when($dateCol, fn($q)=>$q->whereYear($dateCol,$year)->whereMonth($dateCol,$month))
                ->where($typeCol,$typeVal)->count() + 1;

            $seqUnit = DB::table($tbl)
                ->when($dateCol, fn($q)=>$q->whereYear($dateCol,$year)->whereMonth($dateCol,$month))
                ->where('unit_id',$contract->unit_id)->count() + 1;

            $formatted = sprintf('%s-%03d/%s-%03d/%s/%d', $typeVal, $seqType, $unitCode, $seqUnit, $inisial, $year);
            $contract->update([$numberCol => $formatted]);
        }

        return back()->with('ok', 'Contract approved.');
    }

    public function sign(Contract $contract, Request $r)
    {
        $this->authorizeUnit($contract->unit_id);

        if ($contract->status !== 'approved') {
            return back()->withErrors('Contract must be approved first.');
        }

        if (method_exists($contract, 'signatures')) {
            try {
                $rel = $contract->signatures();
                $m   = $rel->getRelated();
                $t   = $m->getTable();

                if (Schema::hasTable($t)) {
                    $payload = [];
                    if (Schema::hasColumn($t,'signer_role'))  $payload['signer_role']  = 'candidate';
                    if (Schema::hasColumn($t,'signer_name'))  $payload['signer_name']  = $contract->person_name ?? 'Candidate';
                    if (Schema::hasColumn($t,'signer_email')) $payload['signer_email'] = null;
                    if (Schema::hasColumn($t,'signed_at'))    $payload['signed_at']    = now();
                    if (Schema::hasColumn($t,'ip_address'))   $payload['ip_address']   = $r->ip();
                    if (Schema::hasColumn($t,'payload'))      $payload['payload']      = ['method'=>'manual-confirm'];

                    if ($payload) {
                        $rec = $m->newInstance();
                        $rec->forceFill($payload);
                        $rel->save($rec);
                    }
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        if (Schema::hasColumn($contract->getTable(), 'status')) {
            $contract->update(['status' => 'signed']);
        }

        return back()->with('ok', 'Contract signed (manual dummy).');
    }

    protected function authorizeUnit($unitId): void
    {
        $me = auth()->user();

        // Bypass hanya untuk Superadmin & DHC
        if ($me && ($me->hasRole('Superadmin') || $me->hasRole('DHC'))) {
            return;
        }

        // Selain itu, wajib sama unit
        $my = $me?->unit_id;
        if ($my && $unitId && (string)$my !== (string)$unitId) {
            abort(403);
        }
    }
}
