<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\{Contract, Applicant};
use Illuminate\Http\Request;
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

        // For modal "Draft Contract", show shortlisted/selected candidates
        $applicants = Applicant::query()
            ->when($uid, fn($q, $u) => $q->where('unit_id', $u))
            ->whereIn('status', ['shortlisted', 'selected'])
            ->latest()
            ->limit(50)
            ->get(['id', 'full_name', 'position_applied']);

        // ✅ view path sudah english
        return view('recruitment.contracts.index', compact('list', 'applicants'));
    }

    // CREATE via modal (same page), so only store()
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

        $c = Contract::create([
            'unit_id'    => auth()->user()->unit_id,
            'status'     => 'draft',
            'created_by' => auth()->id(),
        ] + $data);

        if ($c->applicant_id) {
            Applicant::whereKey($c->applicant_id)->update(['status' => 'selected']);
        }

        return back()->with('ok', 'Contract draft created.');
    }

    public function submit(Contract $contract)
    {
        $this->authorizeUnit($contract->unit_id);
        if ($contract->status !== 'draft') {
            return back()->withErrors('Only drafts can be submitted.');
        }

        $contract->update(['status' => 'review']);
        $contract->approvals()->create(['level' => 1, 'role_key' => 'vp_gm', 'status' => 'pending']);

        return back()->with('ok', 'Contract submitted for review to VP/GM.');
    }

    public function approve(Contract $contract, Request $r)
    {
        $this->authorizeUnit($contract->unit_id);
        if ($contract->status !== 'review') {
            return back()->withErrors('Contract must be in review status.');
        }

        $contract->update([
            'status'       => 'approved',
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);

        $contract->approvals()
            ->where('status', 'pending')
            ->update([
                'status'     => 'approved',
                'user_id'    => auth()->id(),
                'decided_at' => now(),
                'note'       => $r->note
            ]);

        if (!$contract->number) {
            $seq = str_pad((string)(DB::table('contracts')->count() + 1), 4, '0', STR_PAD_LEFT);
            $contract->update(['number' => "{$contract->type}/{$contract->unit_id}/{$seq}/" . date('Y')]);
        }

        return back()->with('ok', 'Contract approved by VP/GM.');
    }

    public function sign(Contract $contract, Request $r)
    {
        $this->authorizeUnit($contract->unit_id);
        if ($contract->status !== 'approved') {
            return back()->withErrors('Contract must be approved first.');
        }

        $contract->signatures()->create([
            'signer_role'  => 'candidate',
            'signer_name'  => $contract->person_name,
            'signer_email' => null,
            'signed_at'    => now(),
            'ip_address'   => $r->ip(),
            'payload'      => ['method' => 'manual-confirm'],
        ]);

        $contract->update(['status' => 'signed']);

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
