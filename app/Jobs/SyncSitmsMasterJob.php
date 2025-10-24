<?php

namespace App\Jobs;

use App\Services\SITMS\HttpSitmsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\DateSanitizer as DS;

class SyncSitmsMasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $page;
    public int $perPage;
    public bool $continuePaging;

    protected static array $seenExternalIds = [];
    protected static array $seenEmployeeIds = [];   // NEW: untuk mirror employees
    protected static bool  $dryRun      = false;
    protected static bool  $uniqueCount = true;
    protected static int   $sampleMax   = 0;
    protected static array $samples     = [];
    protected static ?string $rawExportPath = null;

    /** @var null|callable */
    protected static $reporter = null;
    protected static array $lastSummary = [];

    /** cache kolom tabel */
    protected static array $tableColumnsCache = [];

    public function __construct(int $page, int $perPage, bool $continuePaging)
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->continuePaging = $continuePaging;
    }

    public static function setDryRun(bool $dry, int $sampleMax = 0, bool $unique = true, ?string $rawExportPath = null): void
    {
        self::$dryRun = $dry;
        self::$sampleMax = max(0, $sampleMax);
        self::$uniqueCount = $unique;
        self::$rawExportPath = $rawExportPath;
    }

    public static function setReporter(?callable $fn): void
    {
        self::$reporter = $fn;
    }

    public static function getLastSummary(): array
    {
        return self::$lastSummary;
    }

    public static function dispatchSync(
        HttpSitmsClient $client,
        int $page,
        int $perPage,
        bool $continuePaging,
        int $maxPages = 0,
        int $stopNoGrowth = 3
    ): void {
        self::runSinglePage($client, $page, $perPage, $continuePaging, $maxPages, $stopNoGrowth);
    }

    public static function runSinglePage(
        HttpSitmsClient $client,
        int $page,
        int $perPage,
        bool $continuePaging,
        int $maxPages = 0,
        int $stopNoGrowth = 3
    ): void {
        self::$seenExternalIds = [];
        self::$seenEmployeeIds = []; // reset
        self::$samples = [];
        $processed = 0;
        $reportedTotal = null;
        $noGrowthStreak = 0;
        $pagesDone = 0;
        $stopReason = '-';

        $current = max(1, $page);
        $askedPerPage = max(1, $perPage);
        $lastFromApi = null;

        $csv = null;
        if (self::$dryRun && self::$rawExportPath) {
            $csv = fopen(self::$rawExportPath, 'w');
            if ($csv) fputcsv($csv, ['external_id','full_name','unit','position','email']);
        }

        do {
            $resp   = $client->fetchEmployeesPage($current, $askedPerPage);
            $rows   = Arr::get($resp, 'rows', []);
            $total  = Arr::get($resp, 'total');
            $lastFromApi = Arr::get($resp, 'last') ?: $lastFromApi;
            if (is_numeric($total)) $reportedTotal = (int)$total;
            $countRows = is_countable($rows) ? count($rows) : 0;

            $processed += $countRows;
            $beforeSeen = self::currentSeenCount();

            foreach ($rows as $row) {
                $row = (array)$row;

                // ambil id.id_sitms yang paling stabil buat mirror employees
                $genericId  = self::nullIfEmpty($row['id'] ?? null);
                $employeeId = self::nullIfEmpty($row['employee_id'] ?? null) ?? (self::nullIfEmpty($row['id_sitms'] ?? null) ?: $genericId);
                if ($employeeId) self::$seenEmployeeIds[] = (string)$employeeId;

                if (self::$dryRun) {
                    [$extId, $fullName, $unit, $pos, $email] = self::rowFingerprint($row);

                    if (self::$uniqueCount) {
                        if ($extId !== null) self::$seenExternalIds[] = (string)$extId;
                    } else {
                        self::$seenExternalIds[] = uniqid('raw-', true);
                    }

                    if ($csv) fputcsv($csv, [(string)($extId ?? ''), $fullName, $unit, $pos, $email]);

                    if (count(self::$samples) < self::$sampleMax) {
                        self::$samples[] = [
                            'external_id' => (string)($extId ?? ''),
                            'full_name'   => $fullName,
                            'unit'        => $unit,
                            'position'    => $pos,
                            'email'       => $email,
                        ];
                    }
                } else {
                    try {
                        self::upsertEmployeeRawFromSitmsRow($row);
                    } catch (\Throwable $e) {
                        Log::error('SITMS employees insert failed', [
                            'error'      => $e->getMessage(),
                            'row_keys'   => array_keys($row),
                            'sample'     => Arr::only($row, ['id_sitms','employee_id','full_name','company_name','home_base','latest_jobs']),
                        ]);
                    }
                }
            }

            $afterSeen = self::currentSeenCount();
            $grown = $afterSeen - $beforeSeen;

            Log::info('SITMS page result', [
                'page'        => $current,
                'per_page'    => Arr::get($resp, 'per_page'),
                'rows'        => $countRows,
                'processed'   => $processed,
                'seen_unique' => $afterSeen,
                'grown'       => $grown,
                'total_hint'  => $reportedTotal,
                'attempt'     => $resp['attempt'] ?? null,
                'dry'         => self::$dryRun,
                'last'        => $lastFromApi,
            ]);

            if (is_callable(self::$reporter)) {
                (self::$reporter)([
                    'page' => $current,
                    'rows' => $countRows,
                    'processed' => $processed,
                    'seen_unique' => $afterSeen,
                    'grown' => $grown,
                    'total_hint' => $reportedTotal,
                    'attempt' => $resp['attempt'] ?? null,
                ]);
            }

            $pagesDone++;

            if (!$continuePaging) { $stopReason = 'single_page'; break; }
            if (is_numeric($lastFromApi) && $current >= (int)$lastFromApi) { $stopReason = 'reached_last'; break; }
            if ($maxPages > 0 && $pagesDone >= $maxPages) { $stopReason = 'max_pages'; break; }
            if (is_numeric($reportedTotal) && $afterSeen >= $reportedTotal && self::$uniqueCount) { $stopReason = 'reached_reported_total'; break; }
            if (self::$uniqueCount) {
                if ($grown <= 0) $noGrowthStreak++; else $noGrowthStreak = 0;
                if ($noGrowthStreak >= max(1,$stopNoGrowth)) { $stopReason = 'no_growth_streak'; break; }
            }
            if ($countRows === 0) { $stopReason = 'empty_page'; break; }
            if ($current > 30000) { Log::warning('SITMS paging guard tripped (page>30000)'); $stopReason='guard_page_limit'; break; }

            $current++;
        } while (true);

        if ($csv) fclose($csv);

        // === MIRROR employees (soft) saat full run & bukan dry ===
        if ($continuePaging && !self::$dryRun) {
            self::mirrorEmployeesSoft(self::uniqueEmployeeIds());
        }

        self::$lastSummary = [
            'processed_total' => $processed,
            'reported_total'  => $reportedTotal,
            'seen_unique'     => self::$uniqueCount ? count(self::uniqueExternalIds()) : $processed,
            'pages'           => $pagesDone,
            'stop_reason'     => $stopReason,
            'samples'         => self::$samples,
            'last'            => $lastFromApi,
        ];

        Log::info('SITMS sync summary', self::$lastSummary);
    }

    /** SOFT mirror: set is_active=0 utk employee_id yang tidak terlihat di run ini */
    protected static function mirrorEmployeesSoft(array $seenIds): void
    {
        if (!Schema::hasTable('employees') || empty($seenIds)) return;
        $now = now();
        DB::table('employees')
            ->whereNotIn('employee_id', $seenIds)
            ->update(['is_active' => 0, 'updated_at' => $now]);
    }

    /* ========= INSERT/UPDATE ke employees (kolom = sama persis dgn API/tabel) ========= */

    protected static function upsertEmployeeRawFromSitmsRow(array $row): void
    {
        // 1) counter unik untuk summary
        $genericId  = self::nullIfEmpty($row['id'] ?? null);          // id dari API, JANGAN map ke employees.id
        $employeeId = self::nullIfEmpty($row['employee_id'] ?? null);
        $sitmsId    = self::nullIfEmpty($row['id_sitms'] ?? null);
        $externalId = $genericId ?? $employeeId ?? $sitmsId ?? null;
        if ($externalId) self::$seenExternalIds[] = (string)$externalId;

        // 2) PERSON dulu (FK NOT NULL) — mirror-friendly
        //    Reuse person_id dari employees yang sudah ada berdasarkan employee_id/id_sitms
        $existingPersonId = self::findExistingPersonIdByEmployeeKeys($employeeId, $sitmsId);
        $personId = self::ensurePersonIdForRow($row, $existingPersonId);

        // >>> Lookup masters + home_base parsing + lokasi
        [$hbProv, $hbCity] = self::sitmsParseHomeBase(self::nullIfEmpty($row['home_base'] ?? null));
        $directorateId     = self::sitmsEnsureLookupId('directorates', self::nullIfEmpty($row['directorat_name'] ?? null));
        $unitId            = self::sitmsEnsureLookupId('units',        self::nullIfEmpty($row['working_unit_name'] ?? null));
        $positionId        = self::sitmsEnsureLookupId('positions',    self::nullIfEmpty($row['position_name'] ?? null));
        $positionLevelId   = self::sitmsEnsureLookupId('position_levels', self::nullIfEmpty($row['position_level_name'] ?? null));
        $locationId        = self::sitmsEnsureLocationId(
            self::nullIfEmpty($row['location_name'] ?? null),
            $hbCity ?: self::nullIfEmpty($row['city'] ?? null),
            $hbProv
        );
        // <<<

        $now = now();
        $colsEmp = self::tableColumns('employees');

        // 3) minimal payload — HANYA kolom yang ada
        $minimal = [];
        $minimal['person_id']   = $personId;
        if (in_array('employee_id',$colsEmp,true))   $minimal['employee_id']   = $employeeId ?? ($sitmsId ?? $genericId);
        if (in_array('id_sitms',$colsEmp,true))      $minimal['id_sitms']      = $sitmsId;
        if (in_array('company_name',$colsEmp,true))  $minimal['company_name']  = $row['company_name'] ?? 'PT Surveyor Indonesia';
        if (in_array('is_active',$colsEmp,true))     $minimal['is_active']     = isset($row['is_active']) ? ((int)$row['is_active'] ? 1 : 0) : 1;

        // FK & home_base derivative
        if (in_array('directorate_id',$colsEmp,true))     $minimal['directorate_id']     = $directorateId;
        if (in_array('unit_id',$colsEmp,true))            $minimal['unit_id']            = $unitId;
        if (in_array('position_id',$colsEmp,true))        $minimal['position_id']        = $positionId;
        if (in_array('position_level_id',$colsEmp,true))  $minimal['position_level_id']  = $positionLevelId;
        if (in_array('location_id',$colsEmp,true))        $minimal['location_id']        = $locationId;
        if (in_array('home_base_raw',$colsEmp,true))      $minimal['home_base_raw']      = isset($row['home_base']) ? strip_tags((string)$row['home_base']) : null;
        if (in_array('home_base_city',$colsEmp,true))     $minimal['home_base_city']     = $hbCity;
        if (in_array('home_base_province',$colsEmp,true)) $minimal['home_base_province'] = $hbProv;

        // 4) ambil kolom2 dari API yang namanya sama dengan kolom tabel (kecuali `id`)
        $apiFlat = self::flattenEmployeePayload($row);
        $apiFlat = self::filterColumns('employees', $apiFlat);
        unset($apiFlat['id']); // guard ekstra

        // 5) merge + FINAL FILTER (ini penting untuk buang field asing spt full_name)
        $payload = array_merge($minimal, $apiFlat);
        if (in_array('updated_at',$colsEmp,true)) $payload['updated_at'] = $now;
        $exists = false;
        if (!empty($payload['employee_id'])) {
            $exists = DB::table('employees')->where('employee_id', $payload['employee_id'])->exists();
        } elseif (!empty($payload['id_sitms'])) {
            $exists = DB::table('employees')->where('id_sitms', $payload['id_sitms'])->exists();
        }
        if (!$exists && in_array('created_at',$colsEmp,true)) {
            $payload['created_at'] = $now;
        }
        // FINAL FILTER sesuai kolom tabel (menghindari Unknown column 'full_name')
        $payload = self::filterColumns('employees', $payload);

        // 6) upsert
        if (!empty($payload['employee_id'])) {
            DB::table('employees')->updateOrInsert(['employee_id' => $payload['employee_id']], $payload);
        } elseif (!empty($payload['id_sitms'])) {
            DB::table('employees')->updateOrInsert(['id_sitms' => $payload['id_sitms']], $payload);
        } else {
            DB::table('employees')->insert($payload);
        }

        // 7) snapshot
        if (Schema::hasTable('employees_snapshot')) {
            DB::table('employees_snapshot')->updateOrInsert(
                ['employee_id' => $payload['employee_id'] ?? ($payload['id_sitms'] ?? null)],
                [
                    'payload'     => json_encode($row, JSON_UNESCAPED_UNICODE),
                    'captured_at' => $now,
                    'updated_at'  => $now,
                    'created_at'  => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }

        // >>> portfolio & documents
        self::sitmsSyncPortfolio($personId, $row);
        self::sitmsSyncDocuments($personId, $row);
        // <<<
    }

    /** Cari person_id existing dari tabel employees berdasar employee_id / id_sitms */
    protected static function findExistingPersonIdByEmployeeKeys(?string $employeeId, ?string $sitmsId): ?string
    {
        if (!Schema::hasTable('employees')) return null;
        $q = DB::table('employees');
        if ($employeeId) {
            $pid = $q->where('employee_id', $employeeId)->value('person_id');
            if ($pid) return (string)$pid;
        }
        if ($sitmsId) {
            $pid = DB::table('employees')->where('id_sitms', $sitmsId)->value('person_id');
            if ($pid) return (string)$pid;
        }
        return null;
    }

    /**
     * Buat / ambil person_id yang cocok untuk baris employee.
     * Urutan:
     * 0) Jika sudah ada person_id dari employees table → pakai itu (mirror anti-numpuk)
     * 1) Cocokkan via (full_name + date_of_birth) (karena skema persons kamu hanya punya ini)
     * 2) Kalau tidak ada, buat persons minimal (kolom yang tersedia saja)
     */
    protected static function ensurePersonIdForRow(array $row, ?string $existingPersonId = null): string
    {
        $tbl = 'persons';
        $now = now();

        if (!Schema::hasTable($tbl)) {
            return (string) Str::ulid(); // guard (harusnya ada)
        }
        $cols = self::tableColumns($tbl);

        if ($existingPersonId) return (string)$existingPersonId;

        $fullName = self::nullIfEmpty($row['full_name'] ?? null);
        $dob      = DS::toDateOrNull($row['date_of_birth'] ?? null);

        if ($fullName && in_array('full_name',$cols,true)) {
            $q = DB::table($tbl)->where('full_name', $fullName);
            if ($dob && in_array('date_of_birth',$cols,true)) $q->whereDate('date_of_birth', $dob);
            $id = $q->value('id');
            if ($id) return (string) $id;
        }

        // create new person (minimal sesuai kolom yang ada)
        $newId = (string) Str::ulid();
        $person = ['id' => $newId];

        if (in_array('full_name', $cols, true))     $person['full_name'] = $fullName ?? '-';
        if (in_array('gender', $cols, true))        $person['gender']    = self::nullIfEmpty($row['gender'] ?? null);
        if (in_array('date_of_birth', $cols, true)) $person['date_of_birth'] = $dob;
        if (in_array('place_of_birth', $cols, true))$person['place_of_birth'] = self::nullIfEmpty($row['place_of_birth'] ?? null);
        if (in_array('phone', $cols, true))         $person['phone']     = self::nullIfEmpty($row['contact_no'] ?? null);

        if (in_array('created_at', $cols, true)) $person['created_at'] = $now;
        if (in_array('updated_at', $cols, true)) $person['updated_at'] = $now;

        // buang kolom yang tidak ada
        $person = array_intersect_key($person, array_flip($cols));

        DB::table($tbl)->insert($person);

        return $newId;
    }

    /**
     * Ambil key-key top-level dari payload API SITMS yang umum dipakai,
     * tanpa rename—kecuali TIDAK menyertakan 'id' agar tidak bentrok dgn PK tabel.
     */
    protected static function flattenEmployeePayload(array $emp): array
    {
        $keys = [
            // 'id',
            'id_sitms', 'employee_id', 'full_name', 'nik_number', 'gender',
            'place_of_birth', 'date_of_birth', 'address', 'city',
            'employee_status', 'company_name',
            'directorat_id', 'directorat_name',
            'working_unit_id', 'working_unit_name',
            'location_name',
            'position_level_name', 'position_name', 'home_base',
            'education_level_name', 'major_name', 'education_name',
            'email', 'contact_no',
            'talent_class_level', 'is_active',
            'latest_jobs_start_date', 'latest_jobs_unit', 'latest_jobs',
            'profile_picture_url',
        ];

        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $emp)) $out[$k] = $emp[$k];
        }

        // pecahan home_base
        if (!empty($emp['home_base'])) {
            $raw = (string)$emp['home_base'];
            $out['home_base_raw'] = $raw;
            $noTags = strip_tags($raw, '<i>');
            if (preg_match('/<i>(.*?)<\/i>/', $noTags, $m)) {
                $out['home_base_city'] = trim($m[1] ?? '');
            }
            $first = explode('<br', $raw)[0] ?? '';
            $out['home_base_province'] = trim(strip_tags($first));
        }

        // mapping latest_jobs -> latest_jobs_title
        if (isset($emp['latest_jobs'])) {
            $out['latest_jobs_title'] = $emp['latest_jobs'];
        }

        return $out;
    }

    /* ========= helpers ========= */

    protected static function rowFingerprint(array $row): array
    {
        $genericId  = self::nullIfEmpty($row['id'] ?? null);
        $employeeId = self::nullIfEmpty($row['employee_id'] ?? null);
        $sitmsId    = self::nullIfEmpty($row['id_sitms'] ?? null);

        $externalId = $genericId ?? $employeeId ?? $sitmsId;

        $fullName = trim((string)($row['full_name'] ?? ''));
        $unitName = trim((string)($row['working_unit_name'] ?? $row['unit_name'] ?? ''));
        $posName  = trim((string)($row['position_name'] ?? $row['position'] ?? ''));
        $email    = trim((string)($row['email'] ?? ''));

        return [$externalId, $fullName, $unitName, $posName, $email];
    }

    protected static function nullIfEmpty($v)
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? null : $s;
    }

    protected static function currentSeenCount(): int
    {
        return self::$uniqueCount ? count(array_unique(self::$seenExternalIds)) : count(self::$seenExternalIds);
    }

    protected static function uniqueExternalIds(): array
    {
        return array_values(array_unique(array_filter(self::$seenExternalIds, fn($v)=> (string)$v !== '')));
    }

    protected static function uniqueEmployeeIds(): array
    {
        return array_values(array_unique(array_filter(self::$seenEmployeeIds, fn($v)=> (string)$v !== '')));
    }

    /** filter payload berdasarkan kolom yang ada di tabel (aman beda skema) */
    protected static function filterColumns(string $table, array $payload): array
    {
        $cols = self::tableColumns($table);
        return array_intersect_key($payload, array_flip($cols));
    }

    protected static function tableColumns(string $table): array
    {
        if (!isset(self::$tableColumnsCache[$table])) {
            self::$tableColumnsCache[$table] = Schema::hasTable($table)
                ? Schema::getColumnListing($table)
                : [];
        }
        return self::$tableColumnsCache[$table];
    }

    /* ==================== LOOKUP & LOCATION HELPERS ==================== */
    protected static function sitmsEnsureLookupId(string $table, ?string $name, array $extras = []): ?int
    {
        $name = self::nullIfEmpty($name);
        if (!$name || !Schema::hasTable($table)) return null;

        $cols = self::tableColumns($table);
        $id = DB::table($table)->whereRaw('LOWER(`name`) = ?', [mb_strtolower($name)])->value('id');
        if ($id) return (int)$id;

        $row = [];
        if (in_array('name',$cols,true))      $row['name'] = $name;
        if (in_array('code',$cols,true))      $row['code'] = Str::upper(Str::substr(preg_replace('/\s+/','',$name),0,12));
        if (in_array('created_at',$cols,true)) $row['created_at'] = now();
        if (in_array('updated_at',$cols,true)) $row['updated_at'] = now();

        return DB::table($table)->insertGetId($row);
    }

    protected static function sitmsEnsureLocationId(?string $locationName, ?string $city, ?string $province): ?int
    {
        if (!Schema::hasTable('locations')) return null;

        $cols = self::tableColumns('locations');
        $name = self::nullIfEmpty($locationName) ?? ($city ?: $province);
        $name = self::nullIfEmpty($name);
        if (!$name) return null;

        $q = DB::table('locations')->whereRaw('LOWER(`name`) = ?', [mb_strtolower($name)]);
        if ($city && in_array('city',$cols,true))         $q->whereRaw('LOWER(`city`) = ?', [mb_strtolower($city)]);
        if ($province && in_array('province',$cols,true)) $q->whereRaw('LOWER(`province`) = ?', [mb_strtolower($province)]);

        $id = $q->value('id');
        if ($id) return (int)$id;

        $row = [];
        if (in_array('name',$cols,true))     $row['name'] = $name;
        if (in_array('city',$cols,true))     $row['city'] = $city;
        if (in_array('province',$cols,true)) $row['province'] = $province;
        if (in_array('created_at',$cols,true)) $row['created_at'] = now();
        if (in_array('updated_at',$cols,true)) $row['updated_at'] = now();

        return DB::table('locations')->insertGetId($row);
    }

    protected static function sitmsParseHomeBase(?string $homeBase): array
    {
        if (!$homeBase) return [null, null];
        $txt = trim(strip_tags($homeBase, '<i>'));
        $city = null; $prov = null;

        if (preg_match('~<i>(.*?)</i>~u', $homeBase, $m)) $city = self::nullIfEmpty($m[1] ?? null);
        $first = explode('<br', (string)$homeBase)[0] ?? '';
        $prov = self::nullIfEmpty(strip_tags($first)) ?? null;

        // fallback bila format tidak standard
        if (!$city && preg_match('~(Kota|Kab\.)\s+[A-Za-z].*$~u', $txt, $m)) {
            $city = self::nullIfEmpty($m[0]);
            $prov = self::nullIfEmpty(trim(Str::replaceLast($m[0], '', $txt)));
        }
        return [$prov, $city];
    }

    /* ==================== PORTFOLIO & DOCUMENTS ==================== */
    protected static function sitmsSyncPortfolio(string $personId, array $row): void
    {
        if (!Schema::hasTable('portfolio_histories')) return;

        // jobs
        foreach (Arr::get($row,'jobs_list.jobs_data',[]) as $j) {
            self::sitmsInsertPortfolio($personId,'job',[
                'title'        => self::nullIfEmpty($j['jobs'] ?? null),
                'organization' => self::nullIfEmpty($j['jobs_unit'] ?? $j['jobs_company'] ?? null),
                'start_date'   => DS::toDateOrNull($j['jobs_start_date'] ?? null),
                'end_date'     => DS::toDateOrNull($j['jobs_end_date'] ?? null),
                'description'  => self::nullIfEmpty($j['jobs_description'] ?? null),
                'meta'         => [
                    'company'    => self::nullIfEmpty($j['jobs_company'] ?? null),
                    'masterpiece'=> self::nullIfEmpty($j['jobs_masterpiece'] ?? null),
                    'period'     => self::nullIfEmpty($j['jobs_period'] ?? null),
                ],
            ]);
        }

        // assignments
        foreach (Arr::get($row,'assignments_list.assignments_data',[]) as $a) {
            self::sitmsInsertPortfolio($personId,'assignment',[
                'title'        => self::nullIfEmpty($a['assignment_title'] ?? null),
                'organization' => self::nullIfEmpty($a['assignment_company'] ?? null),
                'start_date'   => DS::toDateOrNull($a['assignment_start_date'] ?? null),
                'end_date'     => DS::toDateOrNull($a['assignment_end_date'] ?? null),
                'description'  => self::nullIfEmpty($a['assignment_description'] ?? null),
                'meta'         => ['period'=> self::nullIfEmpty($a['assignment_period'] ?? null)],
            ]);
        }

        // taskforces
        foreach (Arr::get($row,'taskforces_list.taskforces_data',[]) as $t) {
            self::sitmsInsertPortfolio($personId,'taskforce',[
                'title'        => self::nullIfEmpty($t['taskforce_name'] ?? null),
                'organization' => self::nullIfEmpty($t['taskforce_company'] ?? null),
                'start_date'   => DS::toDateOrNull(($t['taskforce_year_start'] ?? null) ? ($t['taskforce_year_start'].'-01-01') : null),
                'end_date'     => DS::toDateOrNull(($t['taskforce_year_end'] ?? null) ? ($t['taskforce_year_end'].'-12-31') : null),
                'description'  => self::nullIfEmpty($t['taskforce_desc'] ?? null),
                'meta'         => [
                    'type'     => self::nullIfEmpty($t['taskforce_type'] ?? null),
                    'position' => self::nullIfEmpty($t['taskforce_position'] ?? null),
                ],
            ]);
        }

        // trainings
        foreach (Arr::get($row,'training_list.training_data',[]) as $tr) {
            self::sitmsInsertPortfolio($personId,'training',[
                'title'        => self::nullIfEmpty($tr['training_name'] ?? null),
                'organization' => self::nullIfEmpty($tr['training_organizer'] ?? null),
                'start_date'   => DS::toDateOrNull(($tr['training_year'] ?? null) ? ($tr['training_year'].'-01-01') : null),
                'end_date'     => null,
                'description'  => null,
                'meta'         => [
                    'level'=> self::nullIfEmpty($tr['training_level'] ?? null),
                    'type' => self::nullIfEmpty($tr['training_type'] ?? null),
                    'year' => self::nullIfEmpty($tr['training_year'] ?? null),
                ],
            ]);
        }

        // brevets (certifications)
        foreach (Arr::get($row,'brevet_list.brevet_data',[]) as $b) {
            self::sitmsInsertPortfolio($personId,'certification',[
                'title'        => self::nullIfEmpty($b['brevet_name'] ?? null),
                'organization' => self::nullIfEmpty($b['brevet_organizer'] ?? null),
                'start_date'   => DS::toDateOrNull(($b['brevet_year'] ?? null) ? ($b['brevet_year'].'-01-01') : null),
                'end_date'     => DS::toDateOrNull($b['certificate_due'] ?? null),
                'description'  => null,
                'meta'         => [
                    'level'          => self::nullIfEmpty($b['brevet_level'] ?? null),
                    'certificate_no' => self::nullIfEmpty($b['certificate_number'] ?? null),
                ],
            ]);
        }
    }

    protected static function sitmsInsertPortfolio(string $personId, string $category, array $data): void
    {
        if (!Schema::hasTable('portfolio_histories')) return;

        $cols = self::tableColumns('portfolio_histories');
        $row = [
            'person_id'    => $personId,
            'category'     => $category,
            'title'        => $data['title'] ?? null,
            'organization' => $data['organization'] ?? null,
            'start_date'   => $data['start_date'] ?? null,
            'end_date'     => $data['end_date'] ?? null,
            'description'  => $data['description'] ?? null,
            'meta'         => $data['meta'] ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
        if (in_array('meta',$cols,true) && is_array($row['meta'])) $row['meta'] = json_encode($row['meta']);

        $row = array_intersect_key($row, array_flip($cols));

        $dup = DB::table('portfolio_histories')
            ->where('person_id', $personId)
            ->when(isset($row['category']), fn($q)=>$q->where('category',$row['category']))
            ->when(isset($row['title']), fn($q)=>$q->where('title',$row['title']))
            ->when(isset($row['organization']), fn($q)=>$q->where('organization',$row['organization']))
            ->when(isset($row['start_date']), fn($q)=>$q->whereDate('start_date',$row['start_date']))
            ->exists();

        if (!$dup) DB::table('portfolio_histories')->insert($row);
    }

    protected static function sitmsSyncDocuments(string $personId, array $row): void
    {
        if (!Schema::hasTable('documents')) return;

        $cols = self::tableColumns('documents');
        foreach (Arr::get($row,'documents_list.documents_data',[]) as $d) {
            $payload = [
                'person_id'  => $personId,
                'type'       => self::nullIfEmpty($d['document_type'] ?? null),
                'title'      => self::nullIfEmpty($d['document_title'] ?? null),
                'file_path'  => self::nullIfEmpty($d['document_file'] ?? null),
                'due_date'   => DS::toDateOrNull($d['document_duedate'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!in_array('type',$cols,true) && in_array('document_type',$cols,true)) {
                $payload['document_type'] = $payload['type']; unset($payload['type']);
            }
            if (!in_array('file_path',$cols,true) && in_array('path',$cols,true)) {
                $payload['path'] = $payload['file_path']; unset($payload['file_path']);
            }

            $payload = array_intersect_key($payload, array_flip($cols));

            $dup = DB::table('documents')
                ->where('person_id',$personId)
                ->when(isset($payload['title']), fn($q)=>$q->where('title',$payload['title']))
                ->when(isset($payload['file_path']), fn($q)=>$q->where('file_path',$payload['file_path']))
                ->when(isset($payload['path']), fn($q)=>$q->where('path',$payload['path']))
                ->exists();

            if (!$dup) DB::table('documents')->insert($payload);
        }
    }
}
