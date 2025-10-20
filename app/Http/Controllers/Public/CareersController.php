<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;

class CareersController extends Controller
{
    public function index(Request $r)
    {
        // List published jobs
        $jobs = RecruitmentRequest::query()
            ->where('is_published', true)
            ->latest('published_at')
            ->when($r->filled('q'), function($q) use ($r){
                $s = $r->string('q')->toString();
                $q->where(function($w) use ($s){
                    $w->where('title','like',"%$s%")
                      ->orWhere('position','like',"%$s%")
                      ->orWhere('work_location','like',"%$s%");
                });
            })
            ->paginate(12)
            ->withQueryString();

        // If ?job=slug present, fetch that job to open modal
        $activeJob = null;
        if ($slug = $r->string('job')->toString()) {
            $activeJob = RecruitmentRequest::where('is_published', true)->where('slug',$slug)->first();
        }

        return view('public.careers.index', compact('jobs','activeJob'));
    }
}
