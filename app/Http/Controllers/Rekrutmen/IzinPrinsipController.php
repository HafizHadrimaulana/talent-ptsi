<?php

namespace App\Http\Controllers\Rekrutmen;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;

class IzinPrinsipController extends Controller
{
    public function index()
    {
        $list = RecruitmentRequest::query()
            ->when(auth()->user()->unit_id, fn($q,$uid)=>$q->where('unit_id',$uid))
            ->latest()->paginate(12);
        return view('rekrutmen.izin-prinsip.index', compact('list'));
    }

    // CREATE via modal (same page), so only store()
    public function store(Request $r)
    {
        $data = $r->validate([
            'title'=>'required',
            'position'=>'required',
            'headcount'=>'required|integer|min:1',
            'justification'=>'nullable|string'
        ]);

        RecruitmentRequest::create([
            'unit_id'=>auth()->user()->unit_id,
            'requested_by'=>auth()->id(),
            'status'=>'draft',
        ] + $data);

        return back()->with('ok','Draft izin prinsip dibuat.');
    }

    public function submit(RecruitmentRequest $req)
    {
        $this->authorizeUnit($req->unit_id);
        if ($req->status !== 'draft') return back()->withErrors('Hanya draft yang bisa diajukan.');
        $req->update(['status'=>'submitted']);
        $req->approvals()->create(['level'=>1, 'role_key'=>'vp_gm', 'status'=>'pending']);
        return back()->with('ok','Diajukan ke VP/GM.');
    }

    public function approve(RecruitmentRequest $req, Request $r)
    {
        $this->authorizeUnit($req->unit_id);
        if ($req->status !== 'submitted') return back()->withErrors('Harus submitted.');
        $req->update(['status'=>'approved','approved_by'=>auth()->id(),'approved_at'=>now()]);
        $req->approvals()->where('status','pending')->update([
            'status'=>'approved','user_id'=>auth()->id(),'decided_at'=>now(),'note'=>$r->note
        ]);
        return back()->with('ok','Izin prinsip disetujui.');
    }

    public function reject(RecruitmentRequest $req, Request $r)
    {
        $this->authorizeUnit($req->unit_id);
        if ($req->status !== 'submitted') return back()->withErrors('Harus submitted.');
        $req->update(['status'=>'rejected']);
        $req->approvals()->where('status','pending')->update([
            'status'=>'rejected','user_id'=>auth()->id(),'decided_at'=>now(),'note'=>$r->note
        ]);
        return back()->with('ok','Izin prinsip ditolak.');
    }

    protected function authorizeUnit($unitId): void
    {
        $my = auth()->user()->unit_id;
        if ($my && $my != $unitId && !auth()->user()->hasRole('Superadmin')) abort(403);
    }
}
