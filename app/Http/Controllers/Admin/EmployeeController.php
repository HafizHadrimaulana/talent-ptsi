<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class EmployeeController extends Controller
{
    /** Kandidat kolom sesuai SITMS + fallback */
    private array $cands = [
        // ID utama yang diminta: SITMS employee id
        'employee_id' => ['sitms_employee_id','employee_id','nip','nik','nrk','emp_no','employee_number'],
        // relasi persons
        'emp_person_fk' => ['person_id','persons_id','people_id','id_person'],
        'person_pk'     => ['person_id','id'],
        'person_name'   => ['full_name','employee_name','nama_lengkap','nama','nm_lengkap','name','display_name'],
        'person_fname'  => ['first_name','nama_depan','given_name'],
        'person_lname'  => ['last_name','nama_belakang','family_name'],

        // job & unit di employees
        'job_title'     => ['latest_jobs_title','job_title','position_name','position','jabatan','nm_jabatan','title'],
        'unit_inline'   => ['latest_jobs_unit','unit_name','org_unit_name','department_name','dept_name','org_name','unit','nama_unit'],
        'unit_fk'       => ['unit_id','org_unit_id','department_id','dept_id'],

        // tabel units
        'units_name'    => ['name','unit_name'],

        // email
        'email'         => ['email','office_email','corporate_email','mail'],
    ];

    /** Ambil list kolom (huruf kecil sesuai DB) */
    private function listColumns(string $table): array
    {
        return DB::table('information_schema.columns')
            ->selectRaw('column_name AS name')
            ->whereRaw('table_schema = schema()')
            ->where('table_name', $table)
            ->orderBy('ordinal_position')
            ->pluck('name')
            ->map(fn($n)=>trim($n))
            ->all();
    }

    private function pick(array $candidates, array $cols): ?string
    {
        foreach ($candidates as $c) if (in_array($c, $cols, true)) return $c;
        return null;
    }

    public function index(Request $req)
    {
        $tblEmp      = 'employees';
        $tblPersons  = 'persons';
        $tblUnits    = 'units';
        $tblJobsHist = 'employee_job_histories';

        // Snapshot struktur
        $empCols  = $this->listColumns($tblEmp);
        $hasPersons = DB::table('information_schema.tables')->whereRaw('table_schema = schema()')->where('table_name',$tblPersons)->exists();
        $hasUnits   = DB::table('information_schema.tables')->whereRaw('table_schema = schema()')->where('table_name',$tblUnits)->exists();
        $hasJobs    = DB::table('information_schema.tables')->whereRaw('table_schema = schema()')->where('table_name',$tblJobsHist)->exists();

        $personsCols = $hasPersons ? $this->listColumns($tblPersons) : [];
        $unitsCols   = $hasUnits   ? $this->listColumns($tblUnits)   : [];
        $jobsCols    = $hasJobs    ? $this->listColumns($tblJobsHist): [];

        // Map kolom di employees
        $colEmpId  = $this->pick($this->cands['employee_id'],  $empCols);
        $colEmpPer = $this->pick($this->cands['emp_person_fk'], $empCols);
        $colJob    = $this->pick($this->cands['job_title'],     $empCols);
        $colEmail  = $this->pick($this->cands['email'],         $empCols);
        $colUnitFk = $this->pick($this->cands['unit_fk'],       $empCols);
        $colUnitIn = $this->pick($this->cands['unit_inline'],   $empCols);

        // Map kolom persons
        $perPk     = $hasPersons ? $this->pick($this->cands['person_pk'],    $personsCols) : null;
        $perName   = $hasPersons ? $this->pick($this->cands['person_name'],  $personsCols) : null;
        $perFName  = $hasPersons ? $this->pick($this->cands['person_fname'], $personsCols) : null;
        $perLName  = $hasPersons ? $this->pick($this->cands['person_lname'], $personsCols) : null;

        // Map units
        $unitName  = $hasUnits ? ($this->pick($this->cands['units_name'], $unitsCols) ?: null) : null;

        // ===== base query
        $q = DB::table("$tblEmp as e");

        // JOIN persons (utamakan nama dari persons)
        $joinPersons = $hasPersons && $colEmpPer && $perPk;
        if ($joinPersons) {
            $q->leftJoin("$tblPersons as p", "p.$perPk", "=", "e.$colEmpPer");
        }

        // JOIN units jika available
        $joinUnits = $hasUnits && $colUnitFk && $unitName;
        if ($joinUnits) {
            $q->leftJoin("$tblUnits as u", "u.id", "=", "e.$colUnitFk");
        }

        // Subquery latest job history (fallback job_title & unit)
        $subJobTitle = null;
        $subJobUnit  = null;
        if ($hasJobs) {
            // cari FK & kolom tanggal/urut
            $jobsEmpFk = null;
            foreach (['employees_id','employee_id','emp_id','person_id'] as $cand) {
                if (in_array($cand, $jobsCols, true)) { $jobsEmpFk = $cand; break; }
            }
            $jobsTitle = null;
            foreach (['job_title','title','position','position_name','jabatan'] as $cand) {
                if (in_array($cand, $jobsCols, true)) { $jobsTitle = $cand; break; }
            }
            $jobsUnit = null;
            foreach (['unit_name','org_name','department_name','dept_name','unit','org_unit_name'] as $cand) {
                if (in_array($cand, $jobsCols, true)) { $jobsUnit = $cand; break; }
            }
            $orderCol = in_array('start_date',$jobsCols,true) ? 'start_date' : (in_array('created_at',$jobsCols,true) ? 'created_at' : null);

            if ($jobsEmpFk && ($jobsTitle || $jobsUnit)) {
                // latest row per employee by orderCol desc
                $sub = DB::table($tblJobsHist.' as j')
                    ->selectRaw("j.$jobsEmpFk as jk, ".($jobsTitle? "j.$jobsTitle":"NULL")." as last_title, ".($jobsUnit? "j.$jobsUnit":"NULL")." as last_unit")
                    ->when($orderCol, fn($qq)=>$qq->orderBy("j.$orderCol",'desc'))
                    ->toSql();
                // gunakan lateral-ish approach via join raw derived table dengan group by
                $sub2 = DB::query()->fromRaw("($sub) JX");
                $subSql = DB::table('('.$sub2->toSql().') as JX')->mergeBindings($sub2)->toSql(); // keep bindings clean (we don't pass bindings anyway)

                // praktisnya: ambil MAX by group dengan trick: kita pakai subquery simpler: pilih latest per emp via window tidak tersedia → fallback skip. 
                // Untuk kesederhanaan dan stabilitas, kita pakai agregasi MAX( last_title )/MAX( last_unit ) saja — cukup untuk fallback display.
                $subAgg = DB::table($tblJobsHist.' as j')
                    ->selectRaw("j.$jobsEmpFk as jk, ".($jobsTitle? "MAX(j.$jobsTitle)":"NULL")." as last_title, ".($jobsUnit? "MAX(j.$jobsUnit)":"NULL")." as last_unit")
                    ->groupBy("j.$jobsEmpFk");

                $q->leftJoinSub($subAgg, 'jh', function($join) use ($jobsEmpFk, $colEmpPer) {
                    // join berdasarkan preferensi: jika jobs menyimpan person_id, cocokkan ke employees.person_id; kalau menyimpan employees_id, cocokkan ke employees.id
                    // Kita cocokin dua kemungkinan:
                    $join->on('jh.jk', '=', DB::raw("e.$colEmpPer"))->orOn('jh.jk','=','e.id');
                });

                $subJobTitle = 'jh.last_title';
                $subJobUnit  = 'jh.last_unit';
            }
        }

        // SELECT
        $selects = ["e.id"];
        $selects[] = $colEmpId ? DB::raw("e.$colEmpId as employee_id") : DB::raw("NULL as employee_id");

        // Full name dari persons > fallback employees
        if ($joinPersons && $perName) {
            $selects[] = DB::raw("p.$perName as full_name");
        } elseif ($joinPersons && ($perFName || $perLName)) {
            $fn = $perFName ? "NULLIF(p.$perFName,'')" : "NULL";
            $ln = $perLName ? "NULLIF(p.$perLName,'')" : "NULL";
            $selects[] = DB::raw("NULLIF(CONCAT_WS(' ', $fn, $ln),'') as full_name");
        } else {
            // fallback: kalau memang ada nama di employees
            $empName = null;
            foreach (['full_name','employee_name','nama_lengkap','nama','nm_lengkap','name','display_name'] as $cand) {
                if (in_array($cand,$empCols,true)) { $empName = $cand; break; }
            }
            $selects[] = $empName ? DB::raw("e.$empName as full_name") : DB::raw("NULL as full_name");
        }

        // Job title: prefer di employees → fallback latest history
        if ($colJob)        $selects[] = DB::raw("e.$colJob as job_title");
        elseif ($subJobTitle) $selects[] = DB::raw("$subJobTitle as job_title");
        else                $selects[] = DB::raw("NULL as job_title");

        // Email
        $selects[] = $colEmail ? DB::raw("e.$colEmail as email") : DB::raw("NULL as email");

        // Unit name: prefer di employees.latest_jobs_unit → join units → fallback subjob unit
        if ($colUnitIn) {
            $selects[] = DB::raw("e.$colUnitIn as unit_name");
        } elseif ($joinUnits) {
            $selects[] = DB::raw("u.$unitName as unit_name");
        } elseif ($subJobUnit) {
            $selects[] = DB::raw("$subJobUnit as unit_name");
        } else {
            $selects[] = DB::raw("NULL as unit_name");
        }

        $q->select($selects);

        // Search
        $term = trim((string)$req->get('q',''));
        if ($term !== '') {
            $q->where(function($w) use ($term, $colEmpId, $joinPersons, $perName, $perFName, $perLName, $colJob, $colUnitIn) {
                if ($colEmpId) $w->orWhere("e.$colEmpId",'like',"%$term%");
                if ($joinPersons && $perName)  $w->orWhere("p.$perName",'like',"%$term%");
                if ($joinPersons && $perFName) $w->orWhere("p.$perFName",'like',"%$term%");
                if ($joinPersons && $perLName) $w->orWhere("p.$perLName",'like',"%$term%");
                if ($colJob)   $w->orWhere("e.$colJob",'like',"%$term%");
                if ($colUnitIn)$w->orWhere("e.$colUnitIn",'like',"%$term%");
            });
        }

        // Order
        if ($joinPersons && ($perName || $perFName || $perLName)) {
            if ($perName)        $q->orderBy("p.$perName");
            else                 $q->orderByRaw("CONCAT_WS(' ', p.$perFName, p.$perLName)");
        } elseif ($colEmpId)     $q->orderBy("e.$colEmpId");
        else                     $q->orderBy("e.id");

        $employees = $q->paginate(20)->withQueryString();

        // Flags utk view
        $hasName = true; // karena kita sudah join persons; kalau gagal juga, biar kolom tetap ada (biar keliatan kosongnya)
        $hasJob  = true;
        $hasUnit = true;

        return view('admin.employees.index', compact('employees','hasName','hasJob','hasUnit'));
    }

    /** Detail JSON untuk modal */
    public function show(int $id)
    {
        $emp = DB::table('employees')->where('id',$id)->first();
        if (!$emp) abort(404);

        // join persons untuk header
        $empCols = $this->listColumns('employees');

        $tblPersons = 'persons';
        $hasPersons = DB::table('information_schema.tables')->whereRaw('table_schema = schema()')->where('table_name',$tblPersons)->exists();
        $personsCols = $hasPersons ? $this->listColumns($tblPersons) : [];

        $colEmpPer = $this->pick($this->cands['emp_person_fk'], $empCols);
        $perPk     = $hasPersons ? $this->pick($this->cands['person_pk'],    $personsCols) : null;
        $perName   = $hasPersons ? $this->pick($this->cands['person_name'],  $personsCols) : null;
        $perFName  = $hasPersons ? $this->pick($this->cands['person_fname'], $personsCols) : null;
        $perLName  = $hasPersons ? $this->pick($this->cands['person_lname'], $personsCols) : null;

        $person = null;
        if ($hasPersons && $colEmpPer && $perPk && isset($emp->{$colEmpPer})) {
            $person = DB::table($tblPersons)->where($perPk, $emp->{$colEmpPer})->first();
        }

        // related yang umum (opsional, tampil kalau ada)
        $maybe = [
            'brevet'       => 'employee_brevets',
            'job_history'  => 'employee_job_histories',
            'education'    => 'employee_educations',
            'training'     => 'employee_trainings',
            'certificates' => 'employee_certificates',
        ];

        $related = [];
        foreach ($maybe as $key => $table) {
            $exists = DB::table('information_schema.tables')
                ->whereRaw('table_schema = schema()')
                ->where('table_name', $table)
                ->exists();

            if (!$exists) { $related[$key] = []; continue; }

            $tCols = $this->listColumns($table);
            $fk = null;
            foreach (['employees_id','employee_id','emp_id','person_id'] as $cand) {
                if (in_array($cand,$tCols,true)) { $fk = $cand; break; }
            }

            // match id lokal atau person_id
            $rows = [];
            if ($fk) {
                $rows = DB::table($table)
                    ->where(function($q) use ($fk, $id, $emp, $colEmpPer){
                        $q->where($fk, $id);
                        if ($colEmpPer && isset($emp->{$colEmpPer})) {
                            $q->orWhere($fk, $emp->{$colEmpPer});
                        }
                    })
                    ->limit(300)->get()->toArray();
            }
            $related[$key] = $rows;
        }

        // header info
        $idStr   = $emp->sitms_employee_id ?? $emp->employee_id ?? $emp->nip ?? $emp->nrk ?? $emp->person_id ?? '—';
        $nameStr = '—';
        if ($person) {
            if ($perName && isset($person->{$perName}) && $person->{$perName} !== '') $nameStr = $person->{$perName};
            elseif (($perFName || $perLName) && (($perFName && !empty($person->{$perFName})) || ($perLName && !empty($person->{$perLName})))) {
                $nameStr = trim(($perFName ? ($person->{$perFName} ?? '') : '').' '.($perLName ? ($person->{$perLName} ?? '') : ''));
            }
        } else {
            foreach (['full_name','employee_name','nama_lengkap','nama','nm_lengkap','name','display_name'] as $cand) {
                if (isset($emp->{$cand}) && $emp->{$cand} !== '') { $nameStr = $emp->{$cand}; break; }
            }
        }

        return response()->json([
            'ok'       => true,
            'header'   => [
                'employee_id' => $idStr,
                'full_name'   => $nameStr,
                'job_title'   => $emp->latest_jobs_title ?? $emp->job_title ?? $emp->position ?? null,
                'unit'        => $emp->latest_jobs_unit  ?? $emp->unit_name ?? null,
            ],
            'employee' => $emp,
            'person'   => $person,
            'columns'  => $this->listColumns('employees'),
            'related'  => $related,
        ]);
    }
}
