<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Controller;

class EmployeeController extends Controller
{
    private function employeeKeyExpr(): string
    {
        return "COALESCE(NULLIF(e.employee_id, ''), e.id_sitms, CAST(e.id AS CHAR))";
    }

    private function docPathExpr(): string
    {
        $hasPath = Schema::hasTable('documents') && Schema::hasColumn('documents','path');
        $hasFile = Schema::hasTable('documents') && Schema::hasColumn('documents','file_path');
        if ($hasPath && $hasFile) return "COALESCE(docs.path, docs.file_path)";
        if ($hasPath) return "docs.path";
        if ($hasFile) return "docs.file_path";
        return "NULL";
    }

    private function docTypeFilterSnippet(): string
    {
        $hasDocType      = Schema::hasTable('documents') && Schema::hasColumn('documents','doc_type');
        $hasDocumentType = Schema::hasTable('documents') && Schema::hasColumn('documents','document_type');
        if     ($hasDocType)      return "AND docs.doc_type IN ('profile_photo','photo','avatar')";
        elseif ($hasDocumentType) return "AND docs.document_type IN ('profile_photo','photo','avatar')";
        return "";
    }

    private function docOrderExpr(): string
    {
        $hasUpd = Schema::hasTable('documents') && Schema::hasColumn('documents','updated_at');
        $hasCre = Schema::hasTable('documents') && Schema::hasColumn('documents','created_at');
        if ($hasUpd && $hasCre) return "COALESCE(docs.updated_at, docs.created_at)";
        if ($hasUpd) return "docs.updated_at";
        if ($hasCre) return "docs.created_at";
        return "1";
    }

    private function personPhotoSubquery(): string
    {
        $pathExpr   = $this->docPathExpr();
        if ($pathExpr === "NULL") return "NULL";
        $typeFilter = $this->docTypeFilterSnippet();
        $orderExpr  = $this->docOrderExpr();
        return "(SELECT {$pathExpr}
                  FROM documents docs
                 WHERE docs.person_id = p.id
                   {$typeFilter}
                 ORDER BY {$orderExpr} DESC
                 LIMIT 1)";
    }

    public function index(Request $req)
    {
        $q      = trim((string) $req->get('q', ''));
        $empKey = $this->employeeKeyExpr();
        $docSub = $this->personPhotoSubquery();

        $base = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('directorates as d', 'd.id', '=', 'e.directorate_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('position_levels as pl', 'pl.id', '=', 'e.position_level_id')
            ->leftJoin('locations as loc', 'loc.id', '=', 'e.location_id')
            ->leftJoin('emails as em', function ($j) {
                $j->on('em.person_id', '=', 'p.id')->where('em.is_primary', '=', 1);
            })
            ->selectRaw("
                e.id,
                {$empKey} as employee_key,
                e.employee_id,
                e.id_sitms,
                p.full_name,
                COALESCE(e.latest_jobs_title, pos.name) as job_title,
                COALESCE(e.latest_jobs_unit,  u.name)  as unit_name,
                d.name as directorate_name,
                em.email,
                p.phone as phone,
                COALESCE({$docSub}, e.profile_photo_url) as person_photo,
                e.company_name,
                e.employee_status,
                e.talent_class_level,
                e.is_active,
                e.home_base_city,
                e.home_base_province,
                loc.city  as location_city,
                loc.province as location_province,
                e.latest_jobs_start_date
            ");

        if ($q !== '') {
            $like = '%' . str_replace('%', '\%', $q) . '%';
            $base->where(function ($w) use ($like) {
                $w->where('p.full_name', 'like', $like)
                  ->orWhere('e.employee_id', 'like', $like)
                  ->orWhere('e.id_sitms', 'like', $like)
                  ->orWhere('em.email', 'like', $like)
                  ->orWhere('u.name', 'like', $like)
                  ->orWhere('d.name', 'like', $like)
                  ->orWhere('pos.name', 'like', $like)
                  ->orWhere('pl.name', 'like', $like)
                  ->orWhere('e.latest_jobs_unit', 'like', $like)
                  ->orWhere('e.latest_jobs_title', 'like', $like)
                  ->orWhere('loc.city', 'like', $like)
                  ->orWhere('loc.province', 'like', $like)
                  ->orWhere('e.company_name', 'like', $like)
                  ->orWhere('e.employee_status', 'like', $like);
            });
        }

        $rows = $base
            ->orderByRaw("COALESCE(NULLIF(p.full_name,''), e.employee_id) ASC")
            ->get();

        return view('admin.employees.index', [
            'rows' => $rows,
            'q'    => $q,
        ]);
    }

    public function show(string $id)
    {
        $empKey = $this->employeeKeyExpr();
        $docSub = $this->personPhotoSubquery();

        $emp = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('directorates as d', 'd.id', '=', 'e.directorate_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('position_levels as pl', 'pl.id', '=', 'e.position_level_id')
            ->leftJoin('locations as loc', 'loc.id', '=', 'e.location_id')
            ->leftJoin('emails as em', function ($j) {
                $j->on('em.person_id', '=', 'p.id')->where('em.is_primary', '=', 1);
            })
            ->where('e.id', $id)
            ->selectRaw("
                e.id, e.person_id,
                {$empKey} as employee_key,
                e.employee_id, e.id_sitms,
                p.full_name, p.gender, p.date_of_birth, p.place_of_birth, p.phone,
                COALESCE({$docSub}, e.profile_photo_url) as person_photo,
                em.email,
                e.company_name, e.employee_status, e.is_active,
                d.name as directorate_name,
                COALESCE(e.latest_jobs_unit,  u.name)  as unit_name,
                COALESCE(e.latest_jobs_title, pos.name) as job_title,
                pl.name as level_name,
                e.talent_class_level,
                loc.city  as location_city,
                loc.province as location_province,
                e.latest_jobs_start_date, e.created_at, e.updated_at
            ")
            ->first();

        if (!$emp) abort(404);

        $personId = $emp->person_id;

        // ===== Primary tables kalau ada =====
        $hasCert = Schema::hasTable('certifications');
        $hasJob  = Schema::hasTable('job_histories');
        $hasEdu  = Schema::hasTable('educations');
        $hasTrn  = Schema::hasTable('trainings');
        $hasPort = Schema::hasTable('portfolio_histories');

        // Certifications - ambil dari tabel certifications
        $certs = $hasCert
            ? DB::table('certifications')->where('person_id',$personId)
                ->orderByDesc('issued_at')
                ->limit(500)->get()
            : collect();

        // Job histories
        $jobs  = $hasJob
            ? DB::table('job_histories')->where('person_id',$personId)
                ->orderByDesc('start_date')
                ->limit(500)->get()
            : collect();

        // Educations - FIX: ambil langsung dari tabel educations
        $edus = $hasEdu
            ? DB::table('educations')->where('person_id',$personId)
                ->orderByDesc('graduation_year')
                ->limit(500)->get()
            : collect();

        // Trainings
        $trns = $hasTrn
            ? DB::table('trainings')->where('person_id',$personId)
                ->orderByDesc('start_date')
                ->limit(500)->get()
            : collect();

        // ===== Fallback dari portfolio_histories =====
        $brevets = collect();
        if ($hasPort) {
            $ports = DB::table('portfolio_histories')->where('person_id',$personId)->limit(2000)->get();

            // Brevets khusus dari portfolio (category = 'certification')
            $brevets = $ports->where('category', 'certification')->map(function($r){
                $meta = is_string($r->meta ?? null) ? json_decode($r->meta,true) : (array)($r->meta ?? []);
                return (object)[
                    'id'             => $r->id ?? null,
                    'title'          => $r->title,
                    'organization'   => $r->organization,
                    'issued_at'      => $r->start_date,
                    'valid_until'    => $r->end_date,
                    'level'          => $meta['level'] ?? null,
                    'certificate_no' => $meta['certificate_no'] ?? null,
                    'year'           => $meta['year'] ?? ($r->start_date ? date('Y', strtotime($r->start_date)) : null),
                ];
            })->sortByDesc(function($item) {
                return $item->issued_at ?? $item->year ?? '0000';
            })->values();

            // Jika certifications kosong, gunakan brevets
            if ($certs->isEmpty()) {
                $certs = $brevets;
            }

            // Fallback untuk jobs
            if ($jobs->isEmpty()) {
                $jobs = $ports->whereIn('category', ['job','assignment'])->map(function($r){
                    return (object)[
                        'id'         => $r->id ?? null,
                        'start_date' => $r->start_date,
                        'end_date'   => $r->end_date,
                        'title'      => $r->title,
                        'unit_name'  => $r->organization ?? $r->unit_name,
                        'description'=> $r->description,
                    ];
                })->sortByDesc('start_date')->values();
            }

            // Fallback untuk educations - FIX: ambil dari category 'education'
            if ($edus->isEmpty()) {
                $edus = $ports->where('category','education')->map(function($r){
                    $meta = is_string($r->meta ?? null) ? json_decode($r->meta,true) : (array)($r->meta ?? []);
                    return (object)[
                        'id'              => $r->id ?? null,
                        'level'           => $meta['level'] ?? null,
                        'institution'     => $r->organization ?? $r->title,
                        'major'           => $meta['major'] ?? null,
                        'graduation_year' => $meta['graduation_year'] ?? ($r->start_date ? date('Y', strtotime($r->start_date)) : null),
                    ];
                })->sortByDesc('graduation_year')->values();
            }

            // Fallback untuk trainings
            if ($trns->isEmpty()) {
                $trns = $ports->where('category','training')->map(function($r){
                    $meta = is_string($r->meta ?? null) ? json_decode($r->meta,true) : (array)($r->meta ?? []);
                    return (object)[
                        'id'           => $r->id ?? null,
                        'title'        => $r->title,
                        'provider'     => $r->organization ?? $r->unit_name,
                        'start_date'   => $r->start_date,
                        'end_date'     => $r->end_date,
                        'level'        => $meta['level'] ?? null,
                        'type'         => $meta['type'] ?? null,
                        'year'         => $meta['year'] ?? ($r->start_date ? date('Y', strtotime($r->start_date)) : null),
                    ];
                })->sortByDesc('start_date')->values();
            }
        }

        return response()->json([
            'employee'       => $emp,
            'certifications' => $certs,
            'brevets'        => $brevets,
            'job_histories'  => $jobs,
            'educations'     => $edus,
            'trainings'      => $trns,
        ]);
    }
}