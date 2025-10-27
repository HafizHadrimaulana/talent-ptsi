<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\{Contract, Applicant};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index()
    {
        $uid = auth()->user()->unit_id;

        $list = Contract::query()
            ->when($uid, fn($q, $u) => $q->where('unit_id', $u))
            ->latest()
            ->paginate(12);

        // Applicants opsional (aman jika tabel/kolom belum ada)
        $applicants = collect();
        if (class_exists(Applicant::class) && Schema::hasTable((new Applicant)->getTable())) {
            $tbl = (new Applicant)->getTable();
            $cols = ['id'];
            foreach (['full_name','position_applied','unit_id','status','created_at'] as $c) {
                if (Schema::hasColumn($tbl, $c)) $cols[] = $c;
            }

            $q = Applicant::query()->select($cols);
            if ($uid && Schema::hasColumn($tbl, 'unit_id')) {
                $q->where('unit_id', $uid);
            }
            if (Schema::hasColumn($tbl, 'status')) {
                $q->whereIn('status', ['shortlisted','selected']);
            }

            $applicants = $q->latest()->limit(50)->get();
        }

        return view('recruitment.contracts.index', compact('list', 'applicants'));
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

        $pick = function(array $candidates) use ($tbl) {
            foreach ($candidates as $c) {
                if (Schema::hasColumn($tbl, $c)) return $c;
            }
            return null;
        };

        $insert = [];
        if (Schema::hasColumn($tbl, 'unit_id'))      $insert['unit_id']       = auth()->user()->unit_id;
        if (Schema::hasColumn($tbl, 'status'))       $insert['status']        = 'draft';
        if (Schema::hasColumn($tbl, 'created_by'))   $insert['created_by']    = auth()->id();
        if (Schema::hasColumn($tbl, 'created_by_user_id')) $insert['created_by_user_id'] = auth()->id();

        $map = [
            'type'        => ['type', 'contract_type'],
            'person_name' => ['person_name', 'candidate_name', 'name'],
            'position'    => ['position', 'position_name', 'job_title'],
            'start_date'  => ['start_date', 'date_start', 'from_date'],
            'end_date'    => ['end_date', 'date_end', 'to_date'],
            'salary'      => ['salary', 'amount', 'gross_salary'],
            'applicant_id'=> ['applicant_id'],
            'employee_id' => ['employee_id', 'person_id'],
        ];

        foreach ($map as $logical => $candidates) {
            if (!array_key_exists($logical, $data)) continue;
            $col = $pick($candidates);
            if ($col !== null) $insert[$col] = $data[$logical];
        }

        if (!isset($insert['type']) && !isset($insert['contract_type'])) {
            $typeCol = $pick(['type','contract_type']);
            if ($typeCol) $insert[$typeCol] = $data['type'];
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
                $approvalModel = $rel->getRelated();
                $apprTable = $approvalModel->getTable();

                if (Schema::hasTable($apprTable)) {
                    $payload = [];
                    if (Schema::hasColumn($apprTable, 'level'))     $payload['level'] = 1;
                    if (Schema::hasColumn($apprTable, 'role_key'))  $payload['role_key'] = 'vp_gm';
                    if (Schema::hasColumn($apprTable, 'status'))    $payload['status'] = 'pending';
                    if (Schema::hasColumn($apprTable, 'step'))      $payload['step'] = 1;
                    if (Schema::hasColumn($apprTable, 'state'))     $payload['state'] = $payload['status'] ?? 'pending';

                    if (!empty($payload)) {
                        $approval = $approvalModel->newInstance();
                        $approval->forceFill($payload);
                        $rel->save($approval);
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
        if (Schema::hasColumn($contract->getTable(), 'status'))        $update['status']       = 'approved';
        if (Schema::hasColumn($contract->getTable(), 'approved_by'))   $update['approved_by']  = auth()->id();
        if (Schema::hasColumn($contract->getTable(), 'approved_at'))   $update['approved_at']  = now();
        if ($update) $contract->update($update);

        // Update approval record (jika ada)
        if (method_exists($contract, 'approvals')) {
            try {
                $rel = $contract->approvals();
                $approvalModel = $rel->getRelated();
                $apprTable = $approvalModel->getTable();

                if (Schema::hasTable($apprTable)) {
                    $q = $rel->getQuery();

                    if (Schema::hasColumn($apprTable, 'status')) {
                        $q->where('status', 'pending');
                    } elseif (Schema::hasColumn($apprTable, 'state')) {
                        $q->where('state', 'pending');
                    }

                    $changes = [];
                    if (Schema::hasColumn($apprTable, 'status'))   $changes['status'] = 'approved';
                    if (Schema::hasColumn($apprTable, 'state'))    $changes['state']  = 'approved';
                    if (Schema::hasColumn($apprTable, 'user_id'))  $changes['user_id'] = auth()->id();
                    if (Schema::hasColumn($apprTable, 'decided_at')) $changes['decided_at'] = now();
                    if (Schema::hasColumn($apprTable, 'note') && $r->filled('note')) $changes['note'] = $r->note;

                    if ($changes) $q->update($changes);
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        // ===== Generate nomor kontrak: {TYPE}-{seqTypeBulanan}/{UNITCODE}-{seqUnitBulanan}/{INISIAL}/{YEAR}
        $tbl = $contract->getTable();
        $typeCol   = Schema::hasColumn($tbl, 'type') ? 'type' : (Schema::hasColumn($tbl, 'contract_type') ? 'contract_type' : null);
        $numberCol = Schema::hasColumn($tbl, 'number') ? 'number' : (Schema::hasColumn($tbl, 'contract_no') ? 'contract_no' : null);

        if ($numberCol && $typeCol && empty($contract->$numberCol)) {
            $typeVal = strtoupper($contract->$typeCol);

            // referensi tanggal untuk reset bulanan
            $dateCol = collect(['approved_at', 'created_at', 'updated_at'])
                ->first(fn($c) => Schema::hasColumn($tbl, $c));
            $refDate = $dateCol ? \Illuminate\Support\Carbon::parse($contract->$dateCol) : now();
            $year  = (int) $refDate->format('Y');
            $month = (int) $refDate->format('m');

            // inisial approver "Deddi Nurmal" -> "DN"
            $initials = function (?string $name): string {
                if (!$name) return 'XX';
                $parts = preg_split('/\s+/', trim($name));
                $first = mb_substr($parts[0] ?? '', 0, 1);
                $last  = mb_substr(end($parts) ?: '', 0, 1);
                return mb_strtoupper(($first ?: '') . ($last ?: ''));
            };
            $inisial = $initials(optional(auth()->user())->name);

            // kode unit: units.code|abbr|short_name|NAME-initials|fallback U{id}
            $unitCode = 'U' . $contract->unit_id;
            if (Schema::hasTable('units')) {
                $u = DB::table('units')->where('id', $contract->unit_id)->first();
                if ($u) {
                    $unitCode = strtoupper(
                        $u->code
                        ?? $u->abbr
                        ?? $u->short_name
                        ?? (isset($u->name)
                                ? (preg_replace('/[^A-Z]/', '', mb_strtoupper($u->name)) ?: ('U'.$contract->unit_id))
                                : ('U'.$contract->unit_id))
                    );
                }
            }

            // sequence TYPE (reset bulanan)
            $seqType = DB::table($tbl)
                ->when($dateCol, fn($q) => $q->whereYear($dateCol, $year)->whereMonth($dateCol, $month))
                ->where($typeCol, $typeVal)
                ->count() + 1;

            // sequence UNIT (reset bulanan)
            $seqUnit = DB::table($tbl)
                ->when($dateCol, fn($q) => $q->whereYear($dateCol, $year)->whereMonth($dateCol, $month))
                ->where('unit_id', $contract->unit_id)
                ->count() + 1;

            $numType = str_pad((string)$seqType, 3, '0', STR_PAD_LEFT);
            $numUnit = str_pad((string)$seqUnit, 3, '0', STR_PAD_LEFT);

            $formatted = "{$typeVal}-{$numType}/{$unitCode}-{$numUnit}/{$inisial}/{$year}";
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
                $sigModel = $rel->getRelated();
                $sigTable = $sigModel->getTable();

                if (Schema::hasTable($sigTable)) {
                    $payload = [];
                    if (Schema::hasColumn($sigTable, 'signer_role'))  $payload['signer_role']  = 'candidate';
                    if (Schema::hasColumn($sigTable, 'signer_name'))  $payload['signer_name']  = $contract->person_name ?? 'Candidate';
                    if (Schema::hasColumn($sigTable, 'signer_email')) $payload['signer_email'] = null;
                    if (Schema::hasColumn($sigTable, 'signed_at'))    $payload['signed_at']    = now();
                    if (Schema::hasColumn($sigTable, 'ip_address'))   $payload['ip_address']   = $r->ip();
                    if (Schema::hasColumn($sigTable, 'payload'))      $payload['payload']      = ['method' => 'manual-confirm'];

                    if (!empty($payload)) {
                        $sig = $sigModel->newInstance();
                        $sig->forceFill($payload);
                        $rel->save($sig);
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
        $my = auth()->user()->unit_id;
        if ($my && $my != $unitId && !auth()->user()->hasRole('Superadmin')) {
            abort(403);
        }
    }
}
