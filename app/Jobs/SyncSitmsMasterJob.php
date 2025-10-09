<?php
namespace App\Jobs;

use App\Services\SITMS\SitmsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncSitmsMasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $page Halaman yang sedang diproses (1-based).
     * @param int $size Ukuran halaman yang diminta (akan disesuaikan oleh server jika ada limit).
     */
    public function __construct(public int $page = 1, public int $size = 1000) {}

    public function handle(SitmsClient $api): void
    {
        if (!config('sitms.read_enabled')) return;

        $resp = $api->fetchEmployeesPage($this->page, $this->size);
        $rows = $resp['data'] ?? [];

        // Commit per 100 row agar tidak berat
        $chunks = array_chunk($rows, 100);
        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $r) {
                    try {
                        $this->upsertOne($r);
                    } catch (\Throwable $e) {
                        logger()->warning('SITMS upsertOne skipped', [
                            'error'      => $e->getMessage(),
                            'row_sample' => array_slice($r ?? [], 0, 5, true),
                        ]);
                    }
                }
            });
        }

        // ==== META PAGINATION ====
        $meta   = $resp['meta']  ?? [];
        $links  = $resp['links'] ?? [];

        // Coba ambil header via client jika tersedia (opsional)
        $headersHasNext = false;
        try {
            if (method_exists($api, 'getLastHeaders')) {
                $headers        = $api->getLastHeaders() ?: [];
                $headersHasNext = isset($headers['X-Has-Next']) ? (bool)$headers['X-Has-Next'] : false;
            }
        } catch (\Throwable $e) {}

        $current        = (int)($meta['current_page'] ?? $meta['page'] ?? $this->page);
        $last           = (int)($meta['last_page'] ?? $meta['total_pages'] ?? 0);
        $serverPerPage  = (int)($meta['per_page']   ?? $meta['page_size']   ?? 0);
        $total          = (int)($meta['total']      ?? 0);

        logger()->info('SITMS page result', [
            'asked_size'    => $this->size,
            'server_perpg'  => $serverPerPage ?: null,
            'count_rows'    => count($rows),
            'page'          => $current,
            'last'          => $last ?: null,
            'total'         => $total ?: null,
            'has_next_hdr'  => $headersHasNext,
            'has_next_link' => !empty($links['next'] ?? null),
        ]);

        // ==== DETEKSI "ADA HALAMAN BERIKUTNYA?" ====
        $hasMoreByMeta  = ($last > 0 && $current < $last);
        $hasMoreByLink  = !empty($links['next'] ?? null);
        $hasMoreByHead  = $headersHasNext === true;

        // Fallback yang aman: selama halaman ini masih ada data, lanjutkan.
        $hasMoreByCount = (count($rows) > 0);

        $hasMore = $hasMoreByMeta || $hasMoreByLink || $hasMoreByHead || $hasMoreByCount;

        // Guard anti infinite loop
        $hardStop = 2000; // batas wajar jumlah halaman
        if ($this->page >= $hardStop) {
            logger()->warning('SITMS hard stop triggered to avoid infinite pagination loop', [
                'at_page' => $this->page
            ]);
            return;
        }

        if ($hasMore) {
            // Hormati page size aktual server kalau ada
            $nextSize = $serverPerPage > 0 ? $serverPerPage : $this->size;

            self::dispatch($this->page + 1, $nextSize)
                ->delay(now()->addSecond());
        }
    }

    protected function upsertOne(array $r): void
    {
        // --- (Opsional) Normalisasi key jika nama field dari API berbeda ---
        $r['id_sitms']            = $r['id_sitms']            ?? ($r['idSitms'] ?? ($r['id'] ?? null));
        $r['employee_id']         = $r['employee_id']         ?? ($r['employeeId'] ?? null);
        $r['nik_number']          = $r['nik_number']          ?? ($r['nik'] ?? ($r['no_ktp'] ?? null));
        $r['full_name']           = $r['full_name']           ?? ($r['name'] ?? ($r['employee_name'] ?? null));
        $r['email']               = $r['email']               ?? ($r['email_address'] ?? null);
        $r['working_unit_name']   = $r['working_unit_name']   ?? ($r['unit_name'] ?? ($r['unit'] ?? null));
        $r['directorat_name']     = $r['directorat_name']     ?? ($r['directorate_name'] ?? null);
        $r['position_level_name'] = $r['position_level_name'] ?? ($r['level_name'] ?? null);
        $r['position_name']       = $r['position_name']       ?? ($r['job_title'] ?? null);
        $r['location_name']       = $r['location_name']       ?? ($r['office_name'] ?? null);
        $r['city']                = $r['city']                ?? ($r['kota'] ?? null);

        // --- Resolve person_id ---
        $idSitms = (string) ($r['id_sitms'] ?? '');
        $empId   = (string) ($r['employee_id'] ?? '');
        $nik     = (string) ($r['nik_number'] ?? '');
        $nikHash = $nik ? hash('sha256', $nik) : null;
        $nikLast4= $nik ? substr($nik, -4) : null;

        $personId = DB::table('identities')->where(['system'=>'SITMS','external_id'=>$idSitms])->value('person_id');
        if (!$personId && $empId) $personId = DB::table('identities')->where(['system'=>'SITMS','external_id'=>$empId])->value('person_id');
        if (!$personId && $nikHash) $personId = DB::table('persons')->where('nik_hash',$nikHash)->value('id');
        if (!$personId) {
            $personId = Str::ulid()->toBase32();
            DB::table('persons')->insert([
                'id'            => $personId,
                'full_name'     => $r['full_name'] ?? null,
                'gender'        => $r['gender'] ?? null,
                'date_of_birth' => $this->normDate($r['date_of_birth'] ?? null),
                'place_of_birth'=> $r['place_of_birth'] ?? null,
                'nik_hash'      => $nikHash,
                'nik_last4'     => $nikLast4,
                'phone'         => $r['contact_no'] ?? null,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);
        }

        // email primary (opsional)
        if (!empty($r['email'])) {
            DB::table('emails')->updateOrInsert(
                ['person_id'=>$personId,'email'=>$r['email']],
                ['is_primary'=>true,'is_verified'=>false,'updated_at'=>now(),'created_at'=>now()]
            );
        }

        // identities mapping
        if ($idSitms) DB::table('identities')->updateOrInsert(
            ['system'=>'SITMS','external_id'=>$idSitms],
            ['person_id'=>$personId,'verified_at'=>now(),'updated_at'=>now(),'created_at'=>now()]
        );
        if ($empId) DB::table('identities')->updateOrInsert(
            ['system'=>'SITMS','external_id'=>$empId],
            ['person_id'=>$personId,'verified_at'=>now(),'updated_at'=>now(),'created_at'=>now()]
        );

        // masters
        $dirName  = $r['directorat_name']   ?? null;
        $unitName = $r['working_unit_name'] ?? null;

        $dirId = $this->firstId(
            table: 'directorates',
            lookup: array_filter(['name'=>$dirName]),
            insertAttrs: array_filter([
                'name'=>$dirName,
                'code'=>$this->makeCode($dirName, 'directorates'),
            ])
        );

        $unitId= $this->firstId(
            table: 'units',
            lookup: array_filter(['name'=>$unitName]),
            insertAttrs: array_filter([
                'name'=>$unitName,
                'code'=>$this->makeCode($unitName, 'units'),
                'directorate_id'=>$dirId,
            ])
        );

        $locId = $this->firstId(
            table: 'locations',
            lookup: array_filter(['name'=>$r['location_name'] ?? null, 'city'=>$r['city'] ?? null]),
            insertAttrs: array_filter([
                'name'=>$r['location_name'] ?? null,
                'type'=>$r['location_name'] ?? null
                    ? ($r['location_name']==='Head Office' ? 'Head Office'
                       : ($r['location_name']==='Branch Office' ? 'Branch Office' : null))
                    : null,
                'city'=>$r['city'] ?? null,
            ])
        );

        $lvlName = $r['position_level_name'] ?? null;
        $lvlId = $this->firstId(
            table: 'position_levels',
            lookup: array_filter(['name'=>$lvlName]),
            insertAttrs: array_filter([
                'name'=>$lvlName,
                'code'=>$this->makeCode($lvlName, 'position_levels'),
            ])
        );

        $posId = $this->firstId(
            table: 'positions',
            lookup: array_filter(['name'=>$r['position_name'] ?? null]),
            insertAttrs: array_filter(['name'=>$r['position_name'] ?? null, 'is_active'=>true])
        );

        // employees snapshot
        [$homeCity, $homeProv] = $this->parseHomeBase($r['home_base'] ?? null);
        DB::table('employees')->updateOrInsert(['person_id'=>$personId], [
            'company_name'          => $r['company_name'] ?? 'PT Surveyor Indonesia',
            'employee_status'       => $r['employee_status'] ?? null,
            'directorate_id'        => $dirId,
            'unit_id'               => $unitId,
            'location_id'           => $locId,
            'position_id'           => $posId,
            'position_level_id'     => $lvlId,
            'talent_class_level'    => $r['talent_class_level'] ?? null,
            'is_active'             => ($r['is_active'] ?? '1') === '1',
            'sitms_employee_id'     => $empId ?: null,
            'sitms_id'              => $idSitms ?: null,
            'home_base_raw'         => $r['home_base'] ?? null,
            'home_base_city'        => $homeCity,
            'home_base_province'    => $homeProv,
            'latest_jobs_start_date'=> $this->normDate($r['latest_jobs_start_date'] ?? null),
            'latest_jobs_unit'      => $r['latest_jobs_unit'] ?? null,
            'latest_jobs_title'     => $r['latest_jobs'] ?? null,
            'updated_at'            => now(),
            'created_at'            => now()
        ]);

        // education
        foreach (($r['education_list']['education_data'] ?? []) as $e) {
            try {
                DB::table('educations')->updateOrInsert([
                    'person_id'       => $personId,
                    'level'           => $e['education_level'] ?? 'n/a',
                    'institution'     => $e['education_name'] ?? '',
                    'major'           => $e['major_name'] ?? null,
                    'graduation_year' => $this->normYear($this->pick($e, ['graduation_year','year','tahun','graduation_date'])),
                ], ['updated_at'=>now(),'created_at'=>now()]);
            } catch (\Throwable $ex) {
                logger()->warning('Skip education', ['err'=>$ex->getMessage()]);
            }
        }

        // trainings
        foreach (($r['training_list']['training_data'] ?? []) as $e) {
            try {
                $year = $this->normYear($this->pick($e, ['training_year','year','tahun','training_date']));
                DB::table('trainings')->updateOrInsert([
                    'person_id' => $personId,
                    'name'      => $e['training_name'] ?? '',
                    'organizer' => $e['training_organizer'] ?? null,
                    'year'      => $year,
                ], [
                    'level'      => $e['training_level'] ?? null,
                    'type'       => $e['training_type'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now()
                ]);
            } catch (\Throwable $ex) {
                logger()->warning('Skip training', [
                    'raw_year' => $this->pick($e, ['training_year','year','tahun','training_date']),
                    'err'      => $ex->getMessage()
                ]);
            }
        }

        // certifications (brevet)
        foreach (($r['brevet_list']['brevet_data'] ?? []) as $e) {
            try {
                DB::table('certifications')->updateOrInsert([
                    'person_id'          => $personId,
                    'name'               => $e['brevet_name'] ?? '',
                    'certificate_number' => $e['certificate_number'] ?? null,
                ], [
                    'organizer'  => $e['brevet_organizer'] ?? null,
                    'level'      => $e['brevet_level'] ?? null,
                    'issued_date'=> null,
                    'due_date'   => $this->normDate($e['certificate_due'] ?? null),
                    'updated_at' => now(),
                    'created_at' => now()
                ]);
            } catch (\Throwable $ex) {
                logger()->warning('Skip certification', ['err'=>$ex->getMessage()]);
            }
        }

        // job histories
        foreach (($r['jobs_list']['jobs_data'] ?? []) as $e) {
            try {
                DB::table('job_histories')->updateOrInsert([
                    'person_id'  => $personId,
                    'company'    => $e['jobs_company'] ?? '',
                    'unit_name'  => $e['jobs_unit'] ?? null,
                    'title'      => $e['jobs'] ?? null,
                    'start_date' => $this->normDate($e['jobs_start_date'] ?? null),
                ], [
                    'end_date'   => $this->normDate($e['jobs_end_date'] ?? null),
                    'updated_at' => now(),
                    'created_at' => now()
                ]);
            } catch (\Throwable $ex) {
                logger()->warning('Skip job_history', ['err'=>$ex->getMessage()]);
            }
        }

        // assignments
        foreach (($r['assignments_list']['assignments_data'] ?? []) as $e) {
            try {
                DB::table('assignments')->updateOrInsert([
                    'person_id' => $personId,
                    'title'     => $e['assignment_title'] ?? ''
                ], [
                    'company'     => $e['assignment_company'] ?? null,
                    'period_text' => $e['assignment_period'] ?? null,
                    'start_date'  => $this->normDate($e['assignment_start_date'] ?? null),
                    'end_date'    => $this->normDate($e['assignment_end_date'] ?? null),
                    'description' => $e['assignment_description'] ?? null,
                    'updated_at'  => now(),
                    'created_at'  => now()
                ]);
            } catch (\Throwable $ex) {
                logger()->warning('Skip assignment', ['err'=>$ex->getMessage()]);
            }
        }

        // taskforces
        foreach (($r['taskforces_list']['taskforces_data'] ?? []) as $e) {
            try {
                DB::table('taskforces')->updateOrInsert([
                    'person_id' => $personId,
                    'name'      => $e['taskforce_name'] ?? ''
                ], [
                    'type'       => $e['taskforce_type'] ?? null,
                    'company'    => $e['taskforce_company'] ?? null,
                    'year_start' => $this->normYear($this->pick($e, ['taskforce_year_start','year_start','tahun_mulai'])),
                    'year_end'   => $this->normYear($this->pick($e, ['taskforce_year_end','year_end','tahun_selesai'])),
                    'position'   => $e['taskforce_position'] ?? null,
                    'desc'       => $e['taskforce_desc'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now()
                ]);
            } catch (\Throwable $ex) {
                logger()->warning('Skip taskforce', ['err'=>$ex->getMessage()]);
            }
        }

        // documents
        foreach (($r['documents_list']['documents_data'] ?? []) as $e) {
            try {
                DB::table('documents')->updateOrInsert([
                    'person_id' => $personId,
                    'doc_type'  => $e['document_type'] ?? 'UNKNOWN',
                    'title'     => $e['document_title'] ?? null,
                ], [
                    'file_path'    => $e['document_file'] ?? '',
                    'due_date'     => $this->normDate($e['document_duedate'] ?? null),
                    'source_system'=> 'SITMS',
                    'updated_at'   => now(),
                    'created_at'   => now()
                ]);
            } catch (\Throwable $ex) {
                logger()->warning('Skip document', ['err'=>$ex->getMessage()]);
            }
        }
    }

    protected function firstId(string $table, array $lookup, array $insertAttrs = []): ?int
    {
        $lookup = array_filter($lookup, fn($v) => filled($v));
        if (empty($lookup)) return null;

        $existing = DB::table($table)->where($lookup)->value('id');
        if ($existing) return (int) $existing;

        return (int) DB::table($table)->insertGetId(array_merge($insertAttrs, [
            'created_at'=>now(),'updated_at'=>now()
        ]));
    }

    /** @return array{0:?string,1:?string} */
    protected function parseHomeBase(?string $html): array
    {
        if (!$html) return [null,null];
        $plain = trim(strip_tags(str_replace(['<br/>','<br>','<br />'], "\n", $html)));
        $parts = array_values(array_filter(array_map('trim', explode("\n", $plain))));
        $prov = $parts[0] ?? null; $city = $parts[1] ?? null;
        return [$city, $prov];
    }

    protected function getColumnMaxLength(string $table, string $column = 'code', int $fallback = 32): int
    {
        try {
            $dbName = config('database.connections.mysql.database');
            $row = DB::selectOne(
                "SELECT CHARACTER_MAXIMUM_LENGTH AS len
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$dbName, $table, $column]
            );
            if ($row && isset($row->len) && (int)$row->len > 0) {
                return (int)$row->len;
            }
        } catch (\Throwable $e) {}
        return $fallback;
    }

    protected function makeCode(?string $name, ?string $table = null, string $column = 'code', int $fallbackLen = 32): ?string
    {
        if (!$name) return 'UNK';

        $max = $table ? $this->getColumnMaxLength($table, $column, $fallbackLen) : $fallbackLen;

        $slug = Str::slug($name, '_');
        $slug = $slug !== '' ? $slug : 'UNK';

        if (mb_strlen($slug) <= $max) return $slug;

        $words = array_values(array_filter(explode('_', $slug)));
        if (count($words) > 1) {
            $abbr = '';
            foreach ($words as $i => $w) {
                $abbr .= mb_substr($w, 0, 1);
                if ($i < count($words) - 1) $abbr .= '_';
                if (mb_strlen($abbr) >= $max) break;
            }
            $abbr = rtrim($abbr, '_');
            if (mb_strlen($abbr) >= 3 && mb_strlen($abbr) <= $max) {
                return $abbr;
            }
        }

        $hash   = substr(hash('crc32b', $slug), 0, 6);
        $baseMax= max(1, $max - (1 + mb_strlen($hash)));
        $base   = mb_substr($slug, 0, $baseMax);
        return rtrim($base, '_') . '_' . $hash;
    }

    protected function normDate($v): ?string
    {
        if (empty($v)) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '0000-00-00' || $s === '0000-00-00 00:00:00') return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
        $ts = strtotime($s);
        if ($ts === false) return null;
        return date('Y-m-d', $ts);
    }

    protected function pick(array $arr, array $keys): mixed
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $arr) && $arr[$k] !== null && $arr[$k] !== '') {
                return $arr[$k];
            }
        }
        return null;
    }

    protected function normYear($v): ?int
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        if (preg_match('/(?<!\d)(\d{4})(?!\d)/', $s, $m)) {
            $y = (int)$m[1];
        } else {
            if (preg_match('/^\d+$/', $s) && strlen($s) >= 4) {
                $y = (int) substr($s, 0, 4);
            } else {
                return null;
            }
        }
        if ($y < 1900 || $y > 2100) return null;
        return $y;
    }
}
