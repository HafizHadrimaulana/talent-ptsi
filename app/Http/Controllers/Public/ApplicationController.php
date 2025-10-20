<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreApplicationRequest;
use App\Models\Applicant;
use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $r)
    {
        // We receive hidden input "slug" from the modal form
        $slug = $r->string('slug')->toString();
        $job  = RecruitmentRequest::where('is_published', true)->where('slug',$slug)->firstOrFail();

        DB::transaction(function() use ($r, $job) {
            $data = $r->validated();

            $attachments = [];
            foreach (['cv','cover'] as $key) {
                if ($r->hasFile($key)) {
                    $attachments[$key] = $r->file($key)->store("applications/{$job->id}", 'public');
                }
            }

            Applicant::create([
                'unit_id'                => $job->unit_id,
                'recruitment_request_id' => $job->id,
                'full_name'              => $data['full_name'],
                'email'                  => $data['email'],
                'phone'                  => $data['phone'] ?? null,
                'nik_number'             => $data['nik_number'] ?? null,
                'position_applied'       => $job->position ?? $job->title,
                'status'                 => 'new',
                'notes'                  => $data['notes'] ?? null,
                'attachments'            => $attachments,
            ]);
        });

        // Back to /careers with success toast; keep ?job closed
        return redirect()
            ->route('careers.index')
            ->with('success', 'Lamaran berhasil dikirim. Terima kasih!');
    }
}
