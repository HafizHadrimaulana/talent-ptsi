<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter; // <-- penting buat hint

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $rows = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('directorates as dir', 'dir.id', '=', 'e.directorate_id')
            ->selectRaw("
                e.id,
                COALESCE(NULLIF(e.employee_id,''), e.id_sitms, CAST(e.id AS CHAR))   as employee_key,
                COALESCE(p.full_name, e.employee_id, CAST(e.id AS CHAR))             as full_name,
                COALESCE(pos.name, e.latest_jobs_title)                               as job_title,
                COALESCE(u.name,  e.latest_jobs_unit)                                 as unit_name,
                COALESCE(e.email, p.email)                                            as email,
                p.phone                                                               as phone,
                e.profile_photo_url                                                   as person_photo,

                dir.name                                                              as directorate_name,
                e.home_base_city                                                      as location_city,
                e.home_base_province                                                  as location_province,
                e.company_name, 
                e.employee_status,
                e.talent_class_level,
                e.latest_jobs_start_date
            ")
            ->when($q !== '', function ($qb) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $qb->where(function ($w) use ($like) {
                    $w->where('e.employee_id', 'like', $like)
                        ->orWhere('e.id_sitms', 'like', $like)
                        ->orWhere('p.full_name', 'like', $like)
                        ->orWhere('pos.name', 'like', $like)
                        ->orWhere('u.name', 'like', $like)
                        ->orWhere('e.latest_jobs_unit', 'like', $like)
                        ->orWhere('e.latest_jobs_title', 'like', $like)
                        ->orWhere('e.company_name', 'like', $like)
                        ->orWhere('e.home_base_city', 'like', $like)
                        ->orWhere('e.home_base_province', 'like', $like)
                        ->orWhere('e.email', 'like', $like)
                        ->orWhere('p.email', 'like', $like);
                });
            })
            ->orderBy('full_name', 'asc')
            ->get();

        return view('admin.employees.index', [
            'rows' => $rows,
            'q'    => $q,
        ]);
    }

    public function show($id, Request $request)
    {
        $e = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('directorates as dir', 'dir.id', '=', 'e.directorate_id')
            ->where('e.id', $id)
            ->selectRaw("
            e.id, e.person_id, e.employee_id, e.id_sitms,
            COALESCE(NULLIF(e.employee_id,''), e.id_sitms, CAST(e.id AS CHAR))    as employee_key,
            COALESCE(p.full_name, e.employee_id, CAST(e.id AS CHAR))              as full_name,
            COALESCE(pos.name, e.latest_jobs_title)                                as job_title,
            COALESCE(u.name,  e.latest_jobs_unit)                                  as unit_name,
            COALESCE(e.email, p.email)                                            as email,
            p.phone                                                               as phone,
            e.profile_photo_url                                                   as person_photo,

            dir.name                                                              as directorate_name,
            e.home_base_city                                                      as location_city,
            e.home_base_province                                                  as location_province,
            e.company_name, 
            e.employee_status, 
            e.talent_class_level,
            e.latest_jobs_start_date
        ")
            ->first();

        if (!$e) return response()->json(['error' => 'Not found'], 404);

        $hasPortfolio = $this->tableExists('portfolio_histories');

        $port = function (array $cats) use ($e, $hasPortfolio) {
            if (!$hasPortfolio) return collect();

            $qb = DB::table('portfolio_histories')
                ->select(['id', 'person_id', 'employee_id', 'category', 'title', 'organization', 'unit_name', 'start_date', 'end_date', 'description', 'meta'])
                ->whereIn(DB::raw('LOWER(category)'), array_map('strtolower', $cats));

            $qb->where(function ($w) use ($e) {
                if (!empty($e->person_id))   $w->orWhere('person_id',   $e->person_id);
                if (!empty($e->employee_id)) $w->orWhere('employee_id', $e->employee_id);
            });

            return $qb->orderByRaw("COALESCE(end_date, start_date, '0001-01-01') desc")->get();
        };

        $brevets        = $port(['brevet']);
        $educations     = $port(['education', 'pendidikan']);
        $trainings      = $port(['training']);
        $jobs           = $port(['job', 'job_history', 'experience', 'work', 'assignment']); // job-like
        $taskforces     = $port(['taskforce', 'project']);
        $assignments    = $port(['assignment']); // <<— TAMBAHAN SPESIFIK ASSIGNMENT

        $documents = $this->tableExists('documents')
            ? DB::table('documents as d')
            ->where(function ($w) use ($e) {
                if (!empty($e->person_id)  && Schema::hasColumn('documents', 'person_id'))   $w->orWhere('d.person_id',  $e->person_id);
                if (!empty($e->employee_id) && Schema::hasColumn('documents', 'employee_id')) $w->orWhere('d.employee_id', $e->employee_id);
                if (!empty($e->id_sitms)   && Schema::hasColumn('documents', 'id_sitms'))    $w->orWhere('d.id_sitms',   $e->id_sitms);
            })
            ->selectRaw("
                d.doc_type,
                d.storage_disk,
                d.path,
                d.mime,
                d.size_bytes,
                d.hash_sha256,
                JSON_UNQUOTE(JSON_EXTRACT(d.meta, '$.document_title'))   as meta_title,
                JSON_UNQUOTE(JSON_EXTRACT(d.meta, '$.document_duedate')) as meta_due_date,
                d.created_at
            ")
            ->orderByRaw("
                COALESCE(
                  JSON_UNQUOTE(JSON_EXTRACT(d.meta, '$.document_duedate')),
                  DATE_FORMAT(d.created_at, '%Y-%m-%d')
                ) desc
            ")
            ->get()
            ->map(function ($d) {
                $d->url = $this->fsUrl($d->storage_disk, $d->path);
                return $d;
            })
            : collect();

        return response()->json([
            'employee'       => [
                'id'                     => $e->id,
                'employee_key'           => $e->employee_key,
                'full_name'              => $e->full_name,
                'job_title'              => $e->job_title,
                'unit_name'              => $e->unit_name,
                'email'                  => $e->email,
                'phone'                  => $e->phone,
                'person_photo'           => $e->person_photo,
                'directorate_name'       => $e->directorate_name,
                'location_city'          => $e->location_city,
                'location_province'      => $e->location_province,
                'company_name'           => $e->company_name,
                'employee_status'        => $e->employee_status,
                'talent_class_level'     => $e->talent_class_level,
                'latest_jobs_start_date' => $e->latest_jobs_start_date,
            ],
            // Kategori yang kita pakai:
            'assignments'    => $assignments,
            'brevet_list'    => $brevets,
            'educations'     => $educations,
            'job_histories'  => $jobs,
            'taskforces'     => $taskforces,
            'trainings'      => $trainings,
            // certifications DIHAPUS dari payload
            'documents'      => $documents,
        ]);
    }


    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Safely resolve file URL from a given disk + path, with IDE-friendly typing.
     */
    private function fsUrl(?string $disk, ?string $path): string
    {
        if (!$path) return '';
        $disk = $disk ?: config('filesystems.default');

        try {
            /** @var FilesystemAdapter $fs */
            $fs = Storage::disk($disk);
            if (method_exists($fs, 'url')) {
                return (string) $fs->url($path);
            }
        } catch (\Throwable $e) {
            // ignore and try fallback
        }

        // Fallback: untuk local 'public' biasanya file bisa diakses via /storage/...
        if ($disk === 'public') {
            return asset('storage/' . ltrim($path, '/'));
        }

        // Last resort: kembalikan path mentah (tetap berguna kalau sudah absolute URL)
        return $path;
    }
}
