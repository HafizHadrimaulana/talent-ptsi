<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Role;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        // ====== Query param terpisah biar aman kalau mau server-side search ======
        $qEmp = trim((string) $request->get('q_emp', ''));
        $qUser = trim((string) $request->get('q_user', ''));

        // ========================= EMPLOYEE DIRECTORY =========================
        $rows = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('directorates as dir', 'dir.id', '=', 'e.directorate_id')

            // BASE CONSTRAINTS
            ->where(function ($w) {
                $w->whereNull('e.company_name')
                  ->orWhereRaw("TRIM(e.company_name) = ''")
                  ->orWhereRaw("LOWER(TRIM(e.company_name)) IN ('pt surveyor indonesia','pt. surveyor indonesia')");
            })
            ->where(function ($w) {
                $w->whereNull('e.employee_status')
                  ->orWhereRaw("TRIM(e.employee_status) = ''")
                  ->orWhereRaw("LOWER(e.employee_status) NOT LIKE '%alih%'")
                  ->whereRaw("LOWER(e.employee_status) NOT LIKE '%outsour%'");
            })
            ->where(function ($w) {
                $normUnit = "LOWER(REPLACE(TRIM(COALESCE(u.name, e.latest_jobs_unit, '')),'–','-'))";
                $w->whereRaw("$normUnit NOT IN ('kso sci-si','kso sci - si','kso sci si')");
            })

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
                'PT Surveyor Indonesia'                                               as company_name,
                e.employee_status,
                e.talent_class_level,
                e.latest_jobs_start_date
            ")
            ->when($qEmp !== '', function ($qb) use ($qEmp) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $qEmp) . '%';
                $qb->where(function ($w) use ($like) {
                    $w->where('e.employee_id', 'like', $like)
                      ->orWhere('e.id_sitms', 'like', $like)
                      ->orWhere('p.full_name', 'like', $like)
                      ->orWhere('pos.name', 'like', $like)
                      ->orWhere('u.name', 'like', $like)
                      ->orWhere('e.latest_jobs_unit', 'like', $like)
                      ->orWhere('e.latest_jobs_title', 'like', $like)
                      ->orWhere('e.home_base_city', 'like', $like)
                      ->orWhere('e.home_base_province', 'like', $like)
                      ->orWhere('e.email', 'like', $like)
                      ->orWhere('p.email', 'like', $like);
                });
            })
            ->orderBy('full_name', 'asc')
            ->get();

        // =========================== USER MANAGEMENT ==========================
        $users = User::query()
            ->when($qUser !== '', function ($qr) use ($qUser) {
                $qr->where(function ($w) use ($qUser) {
                    $w->where('name', 'like', "%{$qUser}%")
                      ->orWhere('email', 'like', "%{$qUser}%");
                });
            })
            ->with('roles:id,name')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $guard = Guard::getDefaultName(User::class);
        $roles = Role::where('guard_name', $guard)
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.access.people', [
            'rows'  => $rows,
            'qEmp'  => $qEmp,
            'users' => $users,
            'qUser' => $qUser,
            'roles' => $roles,
        ]);
    }
}
