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
use Carbon\Carbon;

class SyncSitmsMasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $page;
    public int $perPage;
    public bool $continuePaging;
    public int $maxPages;
    public bool $dryRun;
    public int $sampleMax;
    public bool $uniqueCount;
    public ?string $rawExportPath;
    protected $reporter;

    protected array $seenExternalIds = [];
    protected array $seenEmployeeIds = [];
    protected array $seenJobHistoryIds = [];
    protected array $seenEducationIds = [];
    protected array $seenTrainingIds = [];
    protected array $seenCertificationIds = [];
    protected array $seenDocumentIds = [];
    protected array $seenPortfolioIds = [];
    protected array $samples = [];
    protected array $summary = [];
    protected array $tableColumnsCache = [];
    protected array $lookupCache = [];
    protected int $errInserts = 0;
    protected int $successfulCount = 0;

    public function __construct(
        int $page,
        int $perPage,
        bool $continuePaging,
        int $maxPages = 0,
        bool $dryRun = false,
        int $sampleMax = 0,
        bool $uniqueCount = true,
        ?string $rawExportPath = null,
        ?callable $reporter = null
    ) {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->continuePaging = $continuePaging;
        $this->maxPages = $maxPages;
        $this->dryRun = $dryRun;
        $this->sampleMax = $sampleMax;
        $this->uniqueCount = $uniqueCount;
        $this->rawExportPath = $rawExportPath;
        $this->reporter = $reporter;
    }

    public function getSummary(): array
    {
        return $this->summary;
    }

    public function handle(HttpSitmsClient $client): void
    {
        $this->resetState();
        $processed = 0;
        $reportedTotal = null;
        $pagesDone = 0;
        $stopReason = '-';
        $current = max(1, $this->page);
        $limit = max(1, $this->perPage);
        $lastFromApi = null;
        $csv = null;

        if ($this->dryRun && $this->rawExportPath) {
            $csv = fopen($this->rawExportPath, 'w');
            if ($csv) fputcsv($csv, ['external_id', 'full_name', 'unit', 'position', 'email']);
        }

        do {
            $resp = $client->fetchEmployeesPage($current, $limit);
            $rows = Arr::get($resp, 'rows', []);
            $total = Arr::get($resp, 'total');
            $lastFromApi = Arr::get($resp, 'last') ?: $lastFromApi;
            
            if (is_numeric($total)) $reportedTotal = (int) $total;

            $countRows = is_countable($rows) ? count($rows) : 0;
            $processed += $countRows;
            $beforeSeen = $this->currentSeenCount();

            foreach ($rows as $row) {
                $row = (array) $row;
                $sitmsId = $this->nullIfEmpty($row['id_sitms'] ?? null);
                $employeeId = $this->nullIfEmpty($row['employee_id'] ?? null);
                $genericId = $this->nullIfEmpty($row['id'] ?? null);
                $externalId = $sitmsId ?? $employeeId ?? $genericId;

                if ($externalId) $this->seenExternalIds[] = (string) $externalId;
                
                if ($this->dryRun) {
                    [$extId, $fullName, $unit, $pos, $email] = $this->rowFingerprint($row);
                    if ($csv) fputcsv($csv, [(string) ($extId ?? ''), $fullName, $unit, $pos, $email]);
                    if (count($this->samples) < $this->sampleMax) {
                        $this->samples[] = compact('extId', 'fullName', 'unit', 'pos', 'email');
                    }
                } else {
                    try {
                        if ($this->upsertEmployeeRawFromSitmsRow($row)) {
                            $this->successfulCount++;
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        $this->errInserts++;
                        Log::error('SITMS SQL Error', [
                            'code' => $e->getCode(),
                            'msg' => $e->getMessage(),
                            'row_id' => $externalId
                        ]);
                    } catch (\Throwable $e) {
                        $this->errInserts++;
                        Log::error('SITMS General Error', ['msg' => $e->getMessage(), 'row_id' => $externalId]);
                    }
                }
            }

            $afterSeen = $this->currentSeenCount();
            $grown = $afterSeen - $beforeSeen;

            if (is_callable($this->reporter)) {
                ($this->reporter)([
                    'page' => $current,
                    'rows' => $countRows,
                    'processed' => $processed,
                    'seen_unique' => $afterSeen,
                    'grown' => $grown,
                    'successful_rows' => $this->successfulCount,
                    'total_hint' => $reportedTotal,
                    'attempt' => $resp['attempt'] ?? null
                ]);
            }

            $pagesDone++;
            if (!$this->continuePaging) {
                $stopReason = 'single_page'; break;
            }
            if ($this->maxPages > 0 && $pagesDone >= $this->maxPages) {
                $stopReason = 'max_pages'; break;
            }
            if ($countRows < $limit) {
                $stopReason = 'short_page'; break;
            }
            if ($current > 30000) {
                $stopReason = 'guard_limit'; break;
            }
            $current++;

        } while (true);

        if ($csv) fclose($csv);

        if ($this->continuePaging && !$this->dryRun) {
            $this->mirrorEmployeesSoft($this->uniqueEmployeeIds());
        }

        if (!$this->dryRun) {
            $this->mirrorJobHistoriesHard();
            $this->mirrorEducationsHard();
            $this->mirrorTrainingsHard();
            $this->mirrorCertificationsHard();
            $this->mirrorDocumentsHard();
            $this->mirrorPortfolioHard();
        }

        $this->summary = [
            'processed_total' => $processed,
            'reported_total' => $reportedTotal,
            'seen_unique' => $this->uniqueCount ? count($this->uniqueExternalIds()) : $processed,
            'successful_rows' => $this->successfulCount,
            'pages' => $pagesDone,
            'stop_reason' => $stopReason,
            'err_inserts' => $this->errInserts
        ];
        
        Log::info('SITMS Sync Finished', $this->summary);
    }

    protected function resetState(): void
    {
        $this->seenExternalIds = [];
        $this->seenEmployeeIds = [];
        $this->seenJobHistoryIds = [];
        $this->seenEducationIds = [];
        $this->seenTrainingIds = [];
        $this->seenCertificationIds = [];
        $this->seenDocumentIds = [];
        $this->seenPortfolioIds = [];
        $this->samples = [];
        $this->errInserts = 0;
        $this->successfulCount = 0;
        $this->lookupCache = [];
    }

    protected function mirrorEmployeesSoft(array $seenIds): void
    {
        if (!Schema::hasTable('employees') || empty($seenIds)) return;
        DB::table('employees')->whereNotIn('employee_id', $seenIds)->update(['is_active' => 0, 'updated_at' => now()]);
    }

    protected function mirrorJobHistoriesHard(): void { $this->genericMirrorHard('job_histories', $this->seenJobHistoryIds); }
    protected function mirrorEducationsHard(): void { $this->genericMirrorHard('educations', $this->seenEducationIds); }
    protected function mirrorTrainingsHard(): void { $this->genericMirrorHard('trainings', $this->seenTrainingIds); }
    protected function mirrorCertificationsHard(): void { $this->genericMirrorHard('certifications', $this->seenCertificationIds); }
    protected function mirrorDocumentsHard(): void { $this->genericMirrorHard('documents', $this->seenDocumentIds); }
    protected function mirrorPortfolioHard(): void { $this->genericMirrorHard('portfolio_histories', $this->seenPortfolioIds); }

    protected function genericMirrorHard(string $table, array $seenMap): void
    {
        if (!Schema::hasTable($table) || empty($seenMap)) return;
        $cols = $this->tableColumns($table);
        $hasSource = in_array('source_system', $cols, true);

        foreach ($seenMap as $personId => $ids) {
            $ids = array_values(array_unique(array_filter($ids)));
            $q = DB::table($table)->where('person_id', $personId);
            if ($hasSource) $q->where(fn($qq) => $qq->where('source_system', 'sitms')->orWhereNull('source_system'));
            if (!empty($ids)) $q->whereNotIn('id', $ids)->delete();
        }
    }

    protected function upsertEmployeeRawFromSitmsRow(array $row): bool
    {
        $sitmsId = $this->nullIfEmpty($row['id_sitms'] ?? null);
        $employeeId = $this->nullIfEmpty($row['employee_id'] ?? null);
        $genericId = $this->nullIfEmpty($row['id'] ?? null);
        
        $existingPersonId = $this->findExistingPersonIdComplex($row);
        $personId = $this->ensurePersonIdForRow($row, $existingPersonId);

        if (!$personId) {
            throw new \Exception("Gagal resolve Person ID untuk row: " . json_encode($row));
        }

        [$hbProv, $hbCity] = $this->sitmsParseHomeBase($this->nullIfEmpty($row['home_base'] ?? null));
        
        $directorateId = $this->sitmsEnsureLookupId('directorates', $row['directorat_name'] ?? null);
        $unitId = $this->sitmsEnsureLookupId('units', $row['working_unit_name'] ?? null, ['directorate_id' => $directorateId, 'is_unit' => true]);
        $positionId = $this->sitmsEnsureLookupId('positions', $row['position_name'] ?? null);
        $positionLevelId = $this->sitmsEnsureLookupId('position_levels', $row['position_level_name'] ?? null);
        $locationId = $this->sitmsEnsureLocationId($row['location_name'] ?? null, $hbCity ?: ($row['city'] ?? null), $hbProv);

        $now = now();
        $colsEmp = $this->tableColumns('employees');
        $payload = ['person_id' => $personId, 'updated_at' => $now];
        
        $finalEmpId = $employeeId ?? $sitmsId ?? $genericId ?? (string) Str::ulid();
        
        if (in_array('employee_id', $colsEmp)) {
            $this->seenEmployeeIds[] = (string) $finalEmpId;
            $payload['employee_id'] = $this->cut($finalEmpId);
        }

        if (in_array('id_sitms', $colsEmp) && $sitmsId) $payload['id_sitms'] = $this->cut($sitmsId);
        if (in_array('is_active', $colsEmp)) $payload['is_active'] = isset($row['is_active']) ? ((int) $row['is_active'] ? 1 : 0) : 1;
        
        $refs = [
            'directorate_id' => $directorateId, 'unit_id' => $unitId, 
            'position_id' => $positionId, 'position_level_id' => $positionLevelId, 
            'location_id' => $locationId, 'home_base_city' => $hbCity, 'home_base_province' => $hbProv
        ];
        foreach ($refs as $k => $v) if (in_array($k, $colsEmp)) $payload[$k] = $v;

        $apiFlat = $this->flattenEmployeePayload($row);
        foreach ($apiFlat as $k => $v) {
            if (in_array($k, ['latest_jobs_start_date', 'date_of_birth'])) $v = $this->parseDate($v);
            if (in_array($k, $colsEmp)) $payload[$k] = $this->cut($v);
        }

        if (!empty($apiFlat['profile_picture_url'])) {
            $photoUrl = $this->cut($apiFlat['profile_picture_url'], 500);
            if (in_array('profile_photo_url', $colsEmp)) {
                $payload['profile_photo_url'] = $photoUrl;
            } elseif (in_array('profile_picture_url', $colsEmp)) {
                $payload['profile_picture_url'] = $photoUrl;
            } elseif (in_array('profile_picture', $colsEmp)) {
                $payload['profile_picture'] = $photoUrl;
            } elseif (in_array('foto', $colsEmp)) {
                $payload['foto'] = $photoUrl;
            }
        }

        if (in_array('created_at', $colsEmp)) {
            $exists = DB::table('employees')->where('person_id', $personId)->exists();
            if (!$exists) $payload['created_at'] = $now;
        }

        DB::table('employees')->updateOrInsert(['person_id' => $personId], $payload);

        $this->sitmsSyncPortfolio($personId, $row);
        $this->sitmsSyncDocuments($personId, $row);

        foreach (Arr::get($row, 'education_list.education_data', []) as $e) $this->upsertEducation($personId, (array) $e);
        foreach (Arr::get($row, 'training_list.training_data', []) as $t) $this->upsertTraining($personId, (array) $t);
        foreach (Arr::get($row, 'brevet_list.brevet_data', []) as $c) $this->upsertCertification($personId, (array) $c);
        foreach (Arr::get($row, 'jobs_list.jobs_data', []) as $j) $this->upsertJobHistory($personId, (array) $j);

        return true;
    }

    protected function findExistingPersonIdComplex(array $row): ?string
    {
        if (!Schema::hasTable('employees')) return null;

        $empId = $this->nullIfEmpty($row['employee_id'] ?? null);
        $sitmsId = $this->nullIfEmpty($row['id_sitms'] ?? null);
        
        if ($empId && $pid = DB::table('employees')->where('employee_id', $empId)->value('person_id')) return (string)$pid;
        if ($sitmsId && $pid = DB::table('employees')->where('id_sitms', $sitmsId)->value('person_id')) return (string)$pid;

        $nikRaw = preg_replace('/\D+/', '', (string)($row['nik_number'] ?? ''));
        if (!empty($nikRaw)) {
            $pid = DB::table('persons')->where('nik_hash', $nikRaw)->value('id');
            if ($pid) return (string)$pid;
        }

        $email = $this->nullIfEmpty($row['email'] ?? null);
        if ($email) {
            $pid = DB::table('persons')->where('email', $email)->value('id');
            if ($pid) return (string)$pid;
        }

        return null;
    }

    protected function ensurePersonIdForRow(array $row, ?string $pid = null): string
    {
        $tbl = 'persons';
        $now = now();
        $cols = $this->tableColumns($tbl);
        $nikRaw = preg_replace('/\D+/', '', (string)($row['nik_number'] ?? ''));
        
        $data = [
            'full_name' => $this->cut($row['full_name'] ?? '-'),
            'gender' => $this->cut($row['gender'] ?? null),
            'date_of_birth' => $this->parseDate($row['date_of_birth'] ?? null),
            'place_of_birth' => $this->cut($row['place_of_birth'] ?? null),
            'phone' => $this->cut($row['contact_no'] ?? null, 50),
            'email' => $this->cut($row['email'] ?? null, 150),
            'address' => $this->cut($row['address'] ?? null),
            'city' => $this->cut($row['city'] ?? null, 120),
            'nik_hash' => $nikRaw ?: null,
            'nik_last4' => substr($nikRaw, -4) ?: null,
            'updated_at' => $now
        ];

        $cleanData = array_intersect_key(array_filter($data, fn($v) => !is_null($v)), array_flip($cols));

        if ($pid) {
            DB::table($tbl)->where('id', $pid)->update($cleanData);
            return $pid;
        }

        $newId = (string) Str::ulid();
        $cleanData['id'] = $newId;
        if (in_array('created_at', $cols)) $cleanData['created_at'] = $now;
        
        DB::table($tbl)->insert($cleanData);
        return $newId;
    }

    protected function sitmsEnsureLookupId(string $table, ?string $name, array $extras = []): ?int
    {
        $name = $this->nullIfEmpty($name);
        if (!$name || !Schema::hasTable($table)) return null;

        $cacheKey = $table . ':' . Str::slug($name);
        if (isset($this->lookupCache[$cacheKey])) return $this->lookupCache[$cacheKey];

        if ($table === 'units' && ($extras['is_unit'] ?? false)) {
            $codeMap = [
                'SI Head Office' => 'SIHO', 'Cabang Jakarta' => 'SIJAK', 'Cabang Surabaya' => 'SISUB',
                'Cabang Makassar' => 'SIMAK', 'Cabang Batam' => 'SIBAT', 'Cabang Balikpapan' => 'SIBPP',
                'Cabang Medan' => 'SIMED', 'Cabang Palembang' => 'SIPAL', 'Cabang Pekanbaru' => 'SIPKU',
                'Cabang Semarang' => 'SISMA', 'Cabang Singapura' => 'SISG', 'Sekretariat Perusahaan' => 'SP',
                'Satuan Pengawasan Intern' => 'SPI', 'Divisi Riset, Pemasaran dan Pengembangan Bisnis' => 'DRP2B',
                'Divisi Operasi' => 'DOP', 'Divisi Keuangan dan Akuntansi' => 'DKA',
                'Divisi Perencanaan Korporat dan Manajemen Risiko' => 'DPKMR', 'Divisi Manajemen Aset' => 'DMA',
                'Divisi Human Capital' => 'DHC', 'Divisi Teknologi Informasi' => 'DTI',
                'Strategic Transformation Office' => 'STO', 'Unit Tanggung Jawab Sosial dan Lingkungan' => 'UTJSL',
                'Divisi Bisnis Strategis Oil, Gas and Renewable Energy' => 'DBSOGRE',
                'Divisi Bisnis Strategis Coal and Mineral' => 'DBSCNM',
                'Divisi Bisnis Strategis Government and Institution' => 'DBSGNI',
                'Divisi Bisnis Strategis Industrial Services' => 'DBSINS',
                'Divisi Bisnis Strategis Infrastructure and Transportation' => 'DBSINT',
                'Divisi Bisnis Strategis Sustainability and Environment' => 'DBSSNE'
            ];
            $wantCode = $codeMap[$name] ?? null;
            if ($wantCode) {
                $byCode = DB::table($table)->where('code', $wantCode)->first();
                if ($byCode) {
                    $id = (int) $byCode->id;
                    if (isset($extras['directorate_id']) && Schema::hasColumn($table, 'directorate_id')) {
                        if (is_null($byCode->directorate_id) && $extras['directorate_id']) {
                            DB::table($table)->where('id', $id)->update(['directorate_id' => $extras['directorate_id']]);
                        }
                    }
                    $this->lookupCache[$cacheKey] = $id;
                    return $id;
                }
            }
        }

        $existing = DB::table($table)->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])->first();
        if ($existing) {
            $id = (int) $existing->id;
            if (isset($extras['directorate_id']) && Schema::hasColumn($table, 'directorate_id')) {
                if (is_null($existing->directorate_id) && $extras['directorate_id']) {
                    DB::table($table)->where('id', $id)->update(['directorate_id' => $extras['directorate_id']]);
                }
            }
            $this->lookupCache[$cacheKey] = $id;
            return $id;
        }

        $row = ['name' => $this->cut($name), 'created_at' => now(), 'updated_at' => now()];
        if ($table === 'units' && ($extras['is_unit'] ?? false)) {
            $baseCode = $wantCode ?? Str::upper(Str::slug(substr($name, 0, 10), ''));
            $row['code'] = $this->ensureUniqueCode($table, $baseCode);
        }
        if (isset($extras['directorate_id']) && Schema::hasColumn($table, 'directorate_id')) {
            $row['directorate_id'] = $extras['directorate_id'];
        }

        try {
            $id = DB::table($table)->insertGetId($row);
        } catch (\Exception $e) {
            $id = DB::table($table)->where('name', $name)->value('id');
        }

        if ($id) $this->lookupCache[$cacheKey] = (int) $id;
        return (int) $id;
    }

    protected function ensureUniqueCode(string $tbl, string $base): string
    {
        $c = $base; 
        $i = 1;
        while (DB::table($tbl)->where('code', $c)->exists()) {
            $c = $base . $i;
            $i++;
        }
        return $c;
    }

    protected function sitmsEnsureLocationId(?string $name, ?string $city, ?string $prov): ?int
    {
        if (!Schema::hasTable('locations')) return null;
        
        if ($name && in_array(strtolower($name), ['branch office', 'head office', 'kantor pusat', 'proyek', 'site'])) {
            $name = $city ?: $prov ?: $name;
        }
        
        $target = $this->nullIfEmpty($name) ?? ($city ?: $prov);
        if (!$target) return null;
        
        $cacheKey = 'loc:' . Str::slug($target);
        if (isset($this->lookupCache[$cacheKey])) return $this->lookupCache[$cacheKey];
        
        $id = DB::table('locations')->whereRaw('LOWER(name) = ?', [strtolower(trim($target))])->value('id');
        if (!$id && $city) $id = DB::table('locations')->whereRaw('LOWER(city) = ?', [strtolower(trim($city))])->value('id');
        
        if (!$id) {
            try {
                $id = DB::table('locations')->insertGetId([
                    'name' => $this->cut($target), 
                    'city' => $this->cut($city),
                    'province' => $this->cut($prov), 
                    'created_at' => now(), 'updated_at' => now()
                ]);
            } catch (\Exception $e) {
                $id = DB::table('locations')->where('name', $target)->value('id');
            }
        }
        if($id) $this->lookupCache[$cacheKey] = (int) $id;
        return (int) $id;
    }

    protected function sitmsSyncPortfolio(string $pid, array $row): void
    {
        if (!Schema::hasTable('portfolio_histories')) return;
        
        foreach (Arr::get($row, 'jobs_list.jobs_data', []) as $j) {
            $this->sitmsInsertPortfolio($pid, 'job', [
                'title' => $j['jobs'] ?? null,
                'organization' => $j['jobs_unit'] ?? $j['jobs_company'] ?? null,
                'start_date' => $this->parseDate($j['jobs_start_date'] ?? null),
                'end_date' => $this->parseDate($j['jobs_end_date'] ?? null),
                'description' => $j['jobs_description'] ?? null
            ]);
        }
        foreach (Arr::get($row, 'education_list.education_data', []) as $e) {
             $this->sitmsInsertPortfolio($pid, 'education', [
                'title' => $e['education_name'] ?? null,
                'organization' => $e['education_name'] ?? null,
                'start_date' => $this->parseDate(($e['graduation_year'] ?? '') . '-01-01'),
                'meta' => ['level' => $e['education_level'] ?? null, 'major' => $e['major_name'] ?? null]
            ]);
        }
        foreach (Arr::get($row, 'assignments_list.assignments_data', []) as $a) {
             $this->sitmsInsertPortfolio($pid, 'assignment', [
                'title' => $a['assignment_title'] ?? null,
                'organization' => $a['assignment_company'] ?? null,
                'start_date' => $this->parseDate($a['assignment_start_date'] ?? null),
                'end_date' => $this->parseDate($a['assignment_end_date'] ?? null),
                'description' => $a['assignment_description'] ?? null
            ]);
        }
        foreach (Arr::get($row, 'taskforces_list.taskforces_data', []) as $tf) {
             $this->sitmsInsertPortfolio($pid, 'taskforce', [
                'title' => $tf['taskforce_name'] ?? null,
                'organization' => $tf['taskforce_company'] ?? null,
                'start_date' => $this->parseDate(($tf['taskforce_year_start'] ?? '') . '-01-01'),
                'end_date' => $this->parseDate(($tf['taskforce_year_end'] ?? '') . '-12-31'),
                'description' => $tf['taskforce_desc'] ?? null
            ]);
        }
        foreach (Arr::get($row, 'training_list.training_data', []) as $tr) {
             $this->sitmsInsertPortfolio($pid, 'training', [
                'title' => $tr['training_name'] ?? null,
                'organization' => $tr['training_organizer'] ?? null,
                'start_date' => $this->parseDate(($tr['training_year'] ?? '') . '-01-01')
            ]);
        }
        
        // FIX: SIMPAN SEBAGAI KATEGORI 'brevet' (BUKAN 'certification')
        foreach (Arr::get($row, 'brevet_list.brevet_data', []) as $b) {
             $this->sitmsInsertPortfolio($pid, 'brevet', [
                'title' => $b['brevet_name'] ?? null,
                'organization' => $b['brevet_organizer'] ?? null,
                'start_date' => $this->parseDate(($b['brevet_year'] ?? '') . '-01-01'),
                'end_date' => $this->parseDate($b['certificate_due'] ?? null),
                'meta' => [
                    'number' => $b['certificate_number'] ?? null, 
                    'level' => $b['brevet_level'] ?? null
                ]
            ]);
        }
    }

    protected function sitmsInsertPortfolio(string $pid, string $cat, array $data): void
    {
        $cols = $this->tableColumns('portfolio_histories');
        $desc = $this->cut($data['description'] ?? null, 300);
        $title = $this->cut($data['title'], 150);
        if (!$title) return;

        $pl = [
            'person_id' => $pid, 'category' => $cat,
            'title' => $title,
            'organization' => $this->cut($data['organization'] ?? 'N/A', 150),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'description' => $desc,
            'meta' => isset($data['meta']) ? json_encode($data['meta']) : null,
            'updated_at' => now()
        ];
        if (in_array('source_system', $cols)) $pl['source_system'] = 'sitms';
        if (in_array('created_at', $cols)) $pl['created_at'] = DB::raw('COALESCE(created_at, NOW())');

        DB::table('portfolio_histories')->updateOrInsert([
            'person_id' => $pid, 
            'category' => $cat, 
            'title' => $pl['title'], 
            'start_date' => $pl['start_date'],
        ], $pl);

        $id = DB::table('portfolio_histories')
            ->where('person_id', $pid)
            ->where('category', $cat)
            ->where('title', $pl['title'])
            ->where('start_date', $pl['start_date'])
            ->value('id');
            
        if ($id) $this->seenPortfolioIds[$pid][] = (int) $id;
    }
    
    protected function sitmsSyncDocuments(string $pid, array $row): void {
        if (!Schema::hasTable('documents')) return;
        $cols = $this->tableColumns('documents');
        foreach (Arr::get($row, 'documents_list.documents_data', []) as $d) {
            $rawPath = $this->nullIfEmpty($d['document_file'] ?? null);
            if (!$rawPath) continue;

            $pl = [
                'person_id' => $pid, 
                'title' => $this->cut($d['document_title'] ?? null),
                'doc_type' => $this->cut($d['document_type'] ?? 'unknown', 150), 
                'updated_at' => now()
            ];
            
            if (in_array('path', $cols)) $pl['path'] = $rawPath;
            if (in_array('source_system', $cols)) $pl['source_system'] = 'sitms';
            
            DB::table('documents')->updateOrInsert([
                'person_id' => $pid, 
                'title' => $pl['title'],
                'doc_type' => $pl['doc_type']
            ], $pl);
            
            $id = DB::table('documents')
                ->where('person_id', $pid)
                ->where('title', $pl['title'])
                ->where('doc_type', $pl['doc_type'])
                ->value('id');
            if ($id) $this->seenDocumentIds[$pid][] = (int) $id;
        }
    }
    
    protected function upsertEducation(string $pid, array $e): void {
        if (!Schema::hasTable('educations')) return;
        $pl = ['person_id' => $pid, 'institution' => $this->cut($e['education_name']??null), 'level' => $this->cut($e['education_level']??null), 'major' => $this->cut($e['major_name']??null), 'graduation_year' => (int)($e['graduation_year']??0)?:null, 'updated_at' => now()];
        if(Schema::hasColumn('educations','source_system')) $pl['source_system']='sitms';
        DB::table('educations')->updateOrInsert(['person_id' => $pid, 'institution' => $pl['institution'], 'level' => $pl['level']], $pl);
        $id = DB::table('educations')->where('person_id',$pid)->where('institution',$pl['institution'])->value('id');
        if($id) $this->seenEducationIds[$pid][]=(int)$id;
    }
    protected function upsertTraining(string $pid, array $t): void {
        if (!Schema::hasTable('trainings')) return;
        $pl = ['person_id' => $pid, 'title' => $this->cut($t['training_name']??null), 'provider' => $this->cut($t['training_organizer']??null), 'start_date' => $this->parseDate(($t['training_year']??'').'-01-01'), 'updated_at' => now()];
        if(Schema::hasColumn('trainings','source_system')) $pl['source_system']='sitms';
        DB::table('trainings')->updateOrInsert(['person_id' => $pid, 'title' => $pl['title'], 'start_date' => $pl['start_date']], $pl);
        $id = DB::table('trainings')->where('person_id',$pid)->where('title',$pl['title'])->value('id');
        if($id) $this->seenTrainingIds[$pid][]=(int)$id;
    }
    protected function upsertCertification(string $pid, array $c): void {
        if (!Schema::hasTable('certifications')) return;
        $pl = ['person_id' => $pid, 'name' => $this->cut($c['brevet_name']??null), 'issuer' => $this->cut($c['brevet_organizer']??null), 'number' => $this->cut($c['certificate_number']??null), 'updated_at' => now()];
        if(Schema::hasColumn('certifications','source_system')) $pl['source_system']='sitms';
        DB::table('certifications')->updateOrInsert(['person_id' => $pid, 'name' => $pl['name'], 'number' => $pl['number']], $pl);
        $id = DB::table('certifications')->where('person_id',$pid)->where('name',$pl['name'])->value('id');
        if($id) $this->seenCertificationIds[$pid][]=(int)$id;
    }
    protected function upsertJobHistory(string $pid, array $j): void {
        if (!Schema::hasTable('job_histories')) return;
        $pl = ['person_id' => $pid, 'title' => $this->cut($j['jobs']??null), 'unit_name' => $this->cut($j['jobs_unit']??null), 'start_date' => $this->parseDate($j['jobs_start_date']??null), 'updated_at' => now()];
        if(Schema::hasColumn('job_histories','source_system')) $pl['source_system']='sitms';
        DB::table('job_histories')->updateOrInsert(['person_id' => $pid, 'title' => $pl['title'], 'start_date' => $pl['start_date']], $pl);
        $id = DB::table('job_histories')->where('person_id',$pid)->where('title',$pl['title'])->where('start_date',$pl['start_date'])->value('id');
        if($id) $this->seenJobHistoryIds[$pid][]=(int)$id;
    }

    protected function sitmsParseHomeBase(?string $hb): array
    {
        if (!$hb) return [null, null];
        $city = preg_match('~<i>(.*?)</i>~u', $hb, $m) ? trim($m[1]) : null;
        $prov = trim(strip_tags(explode('<br', $hb)[0]));
        return [$prov, $city];
    }

    protected function flattenEmployeePayload(array $emp): array
    {
        $out = $emp;
        if (!empty($emp['home_base'])) {
            $raw = (string)$emp['home_base'];
            $out['home_base_raw'] = $raw;
            if (preg_match('/<i>(.*?)<\/i>/', $raw, $m)) $out['home_base_city'] = trim($m[1]);
            $out['home_base_province'] = trim(strip_tags(explode('<br', $raw)[0]));
        }
        if (isset($emp['latest_jobs'])) $out['latest_jobs_title'] = $emp['latest_jobs'];
        return $out;
    }

    protected function nullIfEmpty($v) { $s = trim((string)$v); return $s === '' ? null : $s; }
    protected function cut($v, int $l = 255) { return $v === null ? null : mb_substr((string)$v, 0, $l); }
    
    protected function parseDate($v): ?string 
    { 
        if (empty($v) || $v === '0000-00-00' || str_contains($v, '-0001')) return null; 
        try { 
            $d = Carbon::parse($v);
            return $d->year < 1900 ? null : $d->format('Y-m-d'); 
        } catch (\Throwable $e) { 
            return null; 
        } 
    }
    
    protected function tableColumns($t): array { return $this->tableColumnsCache[$t] ??= Schema::getColumnListing($t); }
    protected function currentSeenCount(): int { return $this->uniqueCount ? count(array_unique($this->seenExternalIds)) : count($this->seenExternalIds); }
    protected function uniqueExternalIds(): array { return array_unique(array_filter($this->seenExternalIds)); }
    protected function uniqueEmployeeIds(): array { return array_unique(array_filter($this->seenEmployeeIds)); }
    protected function rowFingerprint($row): array { return [$row['id_sitms']??$row['employee_id']??null, $row['full_name']??'', $row['working_unit_name']??'', $row['position_name']??'', $row['email']??'']; }
}