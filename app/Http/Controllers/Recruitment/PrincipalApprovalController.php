<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;

class PrincipalApprovalController extends Controller
{
    public function index()
    {
        $list = RecruitmentRequest::query()
            ->when(auth()->user()->unit_id, fn($q, $uid) => $q->where('unit_id', $uid))
            ->latest()
            ->paginate(12);

        // ✅ view path sudah english
        return view('recruitment.principal-approval.index', compact('list'));
    }

    // CREATE via modal (same page), so only store()
    public function store(Request $r)
    {
        $data = $r->validate([
            'title'         => 'required',
            'position'      => 'required',
            'headcount'     => 'required|integer|min:1',
            'justification' => 'nullable|string'
        ]);

        RecruitmentRequest::create([
            'unit_id'      => auth()->user()->unit_id,
            'requested_by' => auth()->id(),
            'status'       => 'draft',
        ] + $data);

        return back()->with('ok', 'Principal approval draft created.');
    }

    public function submit(RecruitmentRequest $req)
    {
        $this->authorizeUnit($req->unit_id);
        if ($req->status !== 'draft') {
            return back()->withErrors('Only drafts can be submitted.');
        }

        $req->update(['status' => 'submitted']);
        $req->approvals()->create(['level' => 1, 'role_key' => 'vp_gm', 'status' => 'pending']);

        return back()->with('ok', 'Submitted to VP/GM.');
    }

    public function approve(RecruitmentRequest $req, Request $r)
    {
        $this->authorizeUnit($req->unit_id);
        if ($req->status !== 'submitted') {
            return back()->withErrors('Must be in submitted status.');
        }

        $req->update([
            'status'       => 'approved',
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);

        $req->approvals()
            ->where('status', 'pending')
            ->update([
                'status'     => 'approved',
                'user_id'    => auth()->id(),
                'decided_at' => now(),
                'note'       => $r->note
            ]);

        return back()->with('ok', 'Principal approval approved.');
    }

    public function reject(RecruitmentRequest $req, Request $r)
    {
        $this->authorizeUnit($req->unit_id);
        if ($req->status !== 'submitted') {
            return back()->withErrors('Must be in submitted status.');
        }

        $req->update(['status' => 'rejected']);
        $req->approvals()
            ->where('status', 'pending')
            ->update([
                'status'     => 'rejected',
                'user_id'    => auth()->id(),
                'decided_at' => now(),
                'note'       => $r->note
            ]);

        return back()->with('ok', 'Principal approval rejected.');
    }

    protected function authorizeUnit($unitId): void
    {
        $my = auth()->user()->unit_id;
        if ($my && $my != $unitId && !auth()->user()->hasRole('Superadmin')) {
            abort(403);
        }
    }
}
