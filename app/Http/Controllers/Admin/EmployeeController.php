<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

class EmployeeController extends Controller
{
    public function index(Request $req)
    {
        $q = trim((string) $req->get('q', ''));
        $perPage = 20;

        $rows = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('position_levels as pl', 'pl.id', '=', 'e.position_level_id')
            ->leftJoin('locations as loc', 'loc.id', '=', 'e.location_id')
  
            ->leftJoin('emails as em', function ($j) {
                $j->on('em.person_id', '=', 'p.id')->where('em.is_primary', '=', 1);
            })
            ->selectRaw("
                e.id,
                COALESCE(e.sitms_employee_id, e.sitms_id) as employee_id,
                p.full_name,
                COALESCE(e.latest_jobs_title, pos.name) as job_title,
                COALESCE(e.latest_jobs_unit, u.name) as unit_name,
                em.email,
                p.photo_url as person_photo,
                e.company_name, e.employee_status,
                e.home_base_raw, e.home_base_city, e.home_base_province,
                e.latest_jobs_start_date
            ")
            ->when($q !== '', function ($qr) use ($q) {
                $like = '%' . str_replace('%', '\%', $q) . '%';
                $qr->where(function ($w) use ($like) {
                    $w->where('p.full_name', 'like', $like)
                      ->orWhere('u.name', 'like', $like)
                      ->orWhere('pos.name', 'like', $like)
                      ->orWhere('e.latest_jobs_unit', 'like', $like)
                      ->orWhere('e.latest_jobs_title', 'like', $like)
                      ->orWhere('e.sitms_employee_id', 'like', $like)
                      ->orWhere('e.sitms_id', 'like', $like);
                });
            })

            ->orderByRaw("COALESCE(NULLIF(p.full_name,''), COALESCE(e.sitms_employee_id, e.sitms_id)) ASC")
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.employees.index', [
            'rows' => $rows,
            'q'    => $q,
        ]);
    }

    public function show(string $id)
    {
        // HEAD (person + employee)
        $emp = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('directorates as d', 'd.id', '=', 'u.directorate_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('position_levels as pl', 'pl.id', '=', 'e.position_level_id')
            ->leftJoin('locations as loc', 'loc.id', '=', 'e.location_id')
            ->leftJoin('emails as em', function ($j) {
                $j->on('em.person_id', '=', 'p.id')->where('em.is_primary', '=', 1);
            })
            ->where('e.id', $id)
            ->selectRaw("
                e.id, e.person_id,
                COALESCE(e.sitms_employee_id, e.sitms_id) as employee_id,
                p.full_name, p.gender, p.date_of_birth, p.place_of_birth, p.phone,
                p.photo_url as person_photo,
                em.email,
                e.company_name, e.employee_status, e.is_active,
                d.name as directorate_name,
                COALESCE(e.latest_jobs_unit, u.name) as unit_name,
                COALESCE(e.latest_jobs_title, pos.name) as job_title,
                pl.name as level_name,
                e.talent_class_level,
                e.home_base_raw, e.home_base_city, e.home_base_province,
                e.latest_jobs_start_date, e.created_at, e.updated_at
            ")
            ->first();

        if (!$emp) {
            abort(404);
        }

        $personId = $emp->person_id;

        // TABS DATA
        // certificates/brevet
        $certs = DB::table('certifications')
            ->where('person_id', $personId)
            ->orderByDesc('issued_at')
            ->limit(200)->get();

        // job histories
        $jobs = DB::table('job_histories as jh')
            ->leftJoin('units as u', 'u.id', '=', 'jh.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'jh.position_id')
            ->where('jh.person_id', $personId)
            ->orderByDesc('jh.start_date')
            ->limit(200)
            ->get([
                'jh.start_date','jh.end_date',
                DB::raw('COALESCE(jh.title, pos.name) as title'),
                DB::raw('COALESCE(jh.unit_name, u.name) as unit_name'),
                'jh.location','jh.notes'
            ]);

        // educations
        $edus = DB::table('educations')
            ->where('person_id', $personId)
            ->orderByDesc('graduation_year')
            ->limit(200)->get();

        // trainings
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
