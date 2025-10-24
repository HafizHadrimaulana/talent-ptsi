<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

class EmployeeController extends Controller
{
    /** Kunci tampilan ID karyawan (utama employee_id, fallback id_sitms -> id) */
    private function employeeKeyExpr(): string
    {
        // Di schema kamu: employees.employee_id (NOT NULL, unique) + id_sitms (nullable)
        return "COALESCE(NULLIF(e.employee_id, ''), e.id_sitms, CAST(e.id AS CHAR))";
    }

    public function index(Request $req)
    {
        $q       = trim((string) $req->get('q', ''));
        $perPage = 20;
        $empKey  = $this->employeeKeyExpr();

        $rows = DB::table('employees as e')
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
                e.employee_id,         -- ID payroll/internal (string)
                e.id_sitms,            -- ID dari SITMS (string/nullable)
                p.full_name,
                COALESCE(e.latest_jobs_title, pos.name) as job_title,
                COALESCE(e.latest_jobs_unit,  u.name)  as unit_name,
                d.name as directorate_name,
                em.email,
                NULL as person_photo,  -- tidak ada kolom photo di persons; isi nanti dari assets/documents jika perlu
                e.company_name, e.employee_status, e.talent_class_level, e.is_active,
                e.home_base_raw, e.home_base_city, e.home_base_province,
                loc.city  as location_city,
                loc.province as location_province,
                e.latest_jobs_start_date
            ")
            ->when($q !== '', function ($qr) use ($q) {
                $like = '%' . str_replace('%', '\%', $q) . '%';
                $qr->where(function ($w) use ($like) {
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
            })
            ->orderByRaw("COALESCE(NULLIF(p.full_name,''), e.employee_id) ASC")
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.employees.index', [
            'rows' => $rows,
            'q'    => $q,
        ]);
    }

    public function show(string $id)
    {
        $empKey = $this->employeeKeyExpr();

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
                NULL as person_photo, -- placeholder
                em.email,
                e.company_name, e.employee_status, e.is_active,
                d.name as directorate_name,
                COALESCE(e.latest_jobs_unit,  u.name)  as unit_name,
                COALESCE(e.latest_jobs_title, pos.name) as job_title,
                pl.name as level_name,
                e.talent_class_level,
                e.home_base_raw, e.home_base_city, e.home_base_province,
                loc.city  as location_city,
                loc.province as location_province,
                e.latest_jobs_start_date, e.created_at, e.updated_at
            ")
            ->first();

        if (!$emp) {
            abort(404);
        }

        $personId = $emp->person_id;

        // ----- TABS: ambil berdasarkan person_id (paling stabil) -----
        $certs = DB::table('certifications')
            ->where('person_id', $personId)
            ->orderByDesc('issued_at')
            ->limit(200)->get();

        $jobs = DB::table('job_histories as jh')
            ->leftJoin('units as u', 'u.id', '=', 'jh.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'jh.position_id')
            ->where('jh.person_id', $personId)
            ->orderByDesc('jh.start_date')
            ->limit(200)->get();

        $edus = DB::table('educations')
            ->where('person_id', $personId)
            ->orderByDesc('graduation_year')
            ->limit(200)->get();

        $trns = DB::table('trainings')
            ->where('person_id', $personId)
            ->orderByDesc('start_date')
            ->limit(200)->get();

        return response()->json([
            'employee'      => $emp,
            'certifications'=> $certs,
            'job_histories' => $jobs,
            'educations'    => $edus,
            'trainings'     => $trns,
        ]);
    }
}
