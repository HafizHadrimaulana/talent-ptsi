<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\PublishRequest;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublishingController extends Controller
{
  // show simple form (kalau perlu modal di SPA, endpoint JSON pun siap)
  public function edit(RecruitmentRequest $req)
  {
    $this->authorize('recruitment.update');
    return view('recruitment.publish', ['req'=>$req]);
  }

  public function update(PublishRequest $r, RecruitmentRequest $req)
  {
    $data = $r->validated();

    if (empty($data['slug'] ?? null)) {
      $data['slug'] = Str::slug(Str::limit($data['title'] ?? $req->title, 60, ''));
    }

    // publish toggle
    if (isset($data['is_published']) && $data['is_published']) {
      $data['published_at'] = now();
    } elseif (array_key_exists('is_published', $data) && !$data['is_published']) {
      $data['published_at'] = null;
    }

    $req->update($data);

    return back()->with('success', 'Lowongan telah diperbarui.');
  }

  public function toggle(RecruitmentRequest $req)
  {
    $this->authorize('recruitment.update');
    $req->update([
      'is_published' => !$req->is_published,
      'published_at' => !$req->is_published ? now() : null,
    ]);
    return back()->with('success', $req->is_published ? 'Dipublish' : 'Disembunyikan');
  }
}
