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
    protected array $seenPortfolioJobIds = [];
    protected array $samples = [];
    protected array $summary = [];
    protected array $tableColumnsCache = [];
    protected array $tableColumnsMeta = [];
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
                if ($employeeId) $this->seenEmployeeIds[] = (string) $employeeId;

                if ($this->dryRun) {
                    [$extId, $fullName, $unit, $pos, $email] = $this->rowFingerprint($row);
                    $this->seenExternalIds[] = (string) ($extId ?? uniqid('raw-', true));
                    if ($csv) fputcsv($csv, [(string) ($extId ?? ''), $fullName, $unit, $pos, $email]);
                    if (count($this->samples) < $this->sampleMax) {
                        $this->samples[] = ['external_id' => (string) ($extId ?? ''), 'full_name' => $fullName, 'unit' => $unit, 'position' => $pos, 'email' => $email];
                    }
                } else {
                    try {
                        if ($this->upsertEmployeeRawFromSitmsRow($row)) {
                            $this->successfulCount++;
                        }
                    } catch (\Throwable $e) {
                        $this->errInserts++;
                        Log::error('SITMS employees insert failed', ['error' => $e->getMessage(), 'peek' => Arr::only($row, ['id', 'employee_id', 'full_name'])]);
                    }
                }
            }

            $afterSeen = $this->currentSeenCount();
            $grown = $afterSeen - $beforeSeen;

            Log::info('SITMS page result', ['page' => $current, 'rows' => $countRows, 'processed' => $processed, 'grown' => $grown]);

            if (is_callable($this->reporter)) {
                ($this->reporter)(['page' => $current, 'rows' => $countRows, 'processed' => $processed, 'seen_unique' => $afterSeen, 'grown' => $grown, 'successful_rows' => $this->successfulCount, 'total_hint' => $reportedTotal, 'attempt' => $resp['attempt'] ?? null]);
            }

            $pagesDone++;
            if (!$this->continuePaging) { $stopReason = 'single_page'; break; }
            if ($this->maxPages > 0 && $pagesDone >= $this->maxPages) { $stopReason = 'max_pages'; break; }
            if ($countRows < $limit) { $stopReason = 'short_page'; break; }
            if ($current > 30000) { $stopReason = 'guard_page_limit'; break; }
            $current++;
        } while (true);

        if ($csv) fclose($csv);

        if ($this->continuePaging && !$this->dryRun) {
            $this->mirrorEmployeesSoft($this->uniqueEmployeeIds());
        }

        if (!$this->dryRun) {
            if (!empty($this->seenJobHistoryIds)) $this->mirrorJobHistoriesHard();
            if (!empty($this->seenEducationIds)) $this->mirrorEducationsHard();
            if (!empty($this->seenTrainingIds)) $this->mirrorTrainingsHard();
            if (!empty($this->seenCertificationIds)) $this->mirrorCertificationsHard();
            if (!empty($this->seenDocumentIds)) $this->mirrorDocumentsHard();
            if (!empty($this->seenPortfolioIds)) $this->mirrorPortfolioHard();
        }

        $this->summary = [
            'processed_total' => $processed,
            'reported_total' => $reportedTotal,
            'seen_unique' => $this->uniqueCount ? count($this->uniqueExternalIds()) : $processed,
            'successful_rows' => $this->successfulCount,
            'pages' => $pagesDone,
            'stop_reason' => $stopReason,
            'samples' => $this->samples,
            'last' => $lastFromApi,
            'err_inserts' => $this->errInserts
        ];

        Log::info('SITMS sync summary', $this->summary);
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
        $this->seenPortfolioJobIds = [];
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

    protected function mirrorJobHistoriesHard(): void
    {
        $this->genericMirrorHard('job_histories', $this->seenJobHistoryIds);
    }

    protected function mirrorEducationsHard(): void
    {
        $this->genericMirrorHard('educations', $this->seenEducationIds);
    }

    protected function mirrorTrainingsHard(): void
    {
        $this->genericMirrorHard('trainings', $this->seenTrainingIds);
    }

    protected function mirrorCertificationsHard(): void
    {
        $this->genericMirrorHard('certifications', $this->seenCertificationIds);
    }

    protected function mirrorDocumentsHard(): void
    {
        $this->genericMirrorHard('documents', $this->seenDocumentIds);
    }

    protected function mirrorPortfolioHard(): void
    {
        $this->genericMirrorHard('portfolio_histories', $this->seenPortfolioIds);
    }

    protected function genericMirrorHard(string $table, array $seenMap): void
    {
        if (!Schema::hasTable($table) || empty($seenMap)) return;
        $cols = $this->tableColumns($table);
        $hasSource = in_array('source_system', $cols, true);

        foreach ($seenMap as $personId => $ids) {
            $ids = array_values(array_unique(array_filter($ids)));
            $q = DB::table($table)->where('person_id', $personId);
            if ($hasSource) {
                $q->where(fn($qq) => $qq->where('source_system', 'sitms')->orWhereNull('source_system'));
            }
            if (!empty($ids)) {
                $q->whereNotIn('id', $ids);
            }
            $q->delete();
        }
    }

    protected function upsertEmployeeRawFromSitmsRow(array $row): bool
    {
        $sitmsId = $this->nullIfEmpty($row['id_sitms'] ?? null);
        $employeeId = $this->nullIfEmpty($row['employee_id'] ?? null);
        $genericId = $this->nullIfEmpty($row['id'] ?? null);
        $externalId = $sitmsId ?? $employeeId ?? $genericId;

        if ($externalId) $this->seenExternalIds[] = (string) $externalId;

        $existingPersonId = $this->findExistingPersonIdByEmployeeKeys($employeeId, $sitmsId);
        $personId = $this->ensurePersonIdForRow($row, $existingPersonId);

        [$hbProv, $hbCity] = $this->sitmsParseHomeBase($this->nullIfEmpty($row['home_base'] ?? null));

        $directorateId = $this->sitmsEnsureLookupId('directorates', $row['directorat_name'] ?? null);
        $unitId = $this->sitmsEnsureLookupId('units', $row['working_unit_name'] ?? null, ['directorate_id' => $directorateId, 'normalize_unit_code' => true]);
        $positionId = $this->sitmsEnsureLookupId('positions', $row['position_name'] ?? null);
        $positionLevelId = $this->sitmsEnsureLookupId('position_levels', $row['position_level_name'] ?? null);
        $locationId = $this->sitmsEnsureLocationId($row['location_name'] ?? null, $hbCity ?: ($row['city'] ?? null), $hbProv);

        $now = now();
        $colsEmp = $this->tableColumns('employees');
        $payload = [];
        $employeeIdFinal = $employeeId ?? $sitmsId ?? $genericId ?? (string) Str::ulid();

        if (isset($this->columnMeta('employees')['employee_id'])) {
            $this->seenEmployeeIds[] = (string) $employeeIdFinal;
        }

        $payload['person_id'] = $personId;
        if (in_array('employee_id', $colsEmp, true)) $payload['employee_id'] = $this->cut($employeeIdFinal);
        if (in_array('id_sitms', $colsEmp, true) && $sitmsId !== null) $payload['id_sitms'] = $this->cut($sitmsId);
        if (in_array('is_active', $colsEmp, true)) $payload['is_active'] = isset($row['is_active']) ? ((int) $row['is_active'] ? 1 : 0) : 1;
        if (in_array('directorate_id', $colsEmp, true)) $payload['directorate_id'] = $directorateId;
        if (in_array('unit_id', $colsEmp, true)) $payload['unit_id'] = $unitId;
        if (in_array('position_id', $colsEmp, true)) $payload['position_id'] = $positionId;
        if (in_array('position_level_id', $colsEmp, true)) $payload['position_level_id'] = $positionLevelId;
        if (in_array('location_id', $colsEmp, true)) $payload['location_id'] = $locationId;
        if (in_array('home_base_raw', $colsEmp, true)) $payload['home_base_raw'] = isset($row['home_base']) ? $this->cut(strip_tags((string) $row['home_base']), 800) : null;
        if (in_array('home_base_city', $colsEmp, true)) $payload['home_base_city'] = $this->cut($hbCity);
        if (in_array('home_base_province', $colsEmp, true)) $payload['home_base_province'] = $this->cut($hbProv);

        $apiFlat = $this->flattenEmployeePayload($row);
        foreach ($apiFlat as $k => $v) {
            if (in_array($k, ['latest_jobs_start_date', 'date_of_birth'], true)) {
                $apiFlat[$k] = $this->parseDate($v);
                continue;
            }
            if (is_string($v)) $apiFlat[$k] = $this->cut($v);
        }
        if (isset($row['email']) && in_array('email', $colsEmp, true)) $apiFlat['email'] = $this->cut($row['email'], 150);
        if (!empty($apiFlat['profile_picture_url']) && in_array('profile_photo_url', $colsEmp, true)) $payload['profile_photo_url'] = $this->cut($apiFlat['profile_picture_url'], 500);

        $payload = array_merge($this->filterColumns('employees', $apiFlat), $payload);
        if (in_array('updated_at', $colsEmp, true)) $payload['updated_at'] = $now;
        if (in_array('created_at', $colsEmp, true)) {
            $exists = DB::table('employees')->where('person_id', $personId)->exists();
            if (!$exists) $payload['created_at'] = $now;
        }

        try {
            DB::table('employees')->updateOrInsert(['person_id' => $personId], $payload);
        } catch (\Throwable $e) {
            $this->errInserts++;
            Log::error('SITMS employees insert failed', ['error' => $e->getMessage()]);
            return false;
        }

        if (Schema::hasTable('employees_snapshot')) {
            $colsSnap = $this->tableColumns('employees_snapshot');
            $snapshotKey = in_array('person_id', $colsSnap, true) ? 'person_id' : (in_array('employee_id', $colsSnap, true) ? 'employee_id' : null);
            if ($snapshotKey) {
                $snapshotKeyValue = $snapshotKey === 'person_id' ? $personId : ($payload['employee_id'] ?? ($payload['id_sitms'] ?? null));
                if ($snapshotKeyValue) {
                    $snap = [$snapshotKey => $snapshotKeyValue, 'payload' => json_encode($row, JSON_UNESCAPED_UNICODE), 'captured_at' => $now, 'updated_at' => $now];
                    if (in_array('created_at', $colsSnap, true)) $snap['created_at'] = DB::raw('COALESCE(created_at, NOW())');
                    try {
                        DB::table('employees_snapshot')->updateOrInsert([$snapshotKey => $snapshotKeyValue], array_intersect_key($snap, array_flip($colsSnap)));
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        try { $this->sitmsSyncPortfolio($personId, $row); } catch (\Throwable $e) {}
        try { $this->sitmsSyncDocuments($personId, $row); } catch (\Throwable $e) {}

        foreach (Arr::get($row, 'education_list.education_data', []) as $e) $this->upsertEducation($personId, (array) $e);
        foreach (Arr::get($row, 'training_list.training_data', []) as $t) $this->upsertTraining($personId, (array) $t);
        foreach (Arr::get($row, 'brevet_list.brevet_data', []) as $c) $this->upsertCertification($personId, (array) $c);
        foreach (Arr::get($row, 'jobs_list.jobs_data', []) as $j) $this->upsertJobHistory($personId, (array) $j);

        return true;
    }

    protected function findExistingPersonIdByEmployeeKeys(?string $employeeId, ?string $sitmsId): ?string
    {
        if (!Schema::hasTable('employees')) return null;
        if ($employeeId) {
            $pid = DB::table('employees')->where('employee_id', $employeeId)->value('person_id');
            if ($pid) return (string) $pid;
        }
        if ($sitmsId) {
            $pid = DB::table('employees')->where('id_sitms', $sitmsId)->value('person_id');
            if ($pid) return (string) $pid;
        }
        return null;
    }

    protected function ensurePersonIdForRow(array $row, ?string $existingPersonId = null): string
    {
        $tbl = 'persons';
        $now = now();
        if (!Schema::hasTable($tbl)) return (string) Str::ulid();
        $cols = $this->tableColumns($tbl);
        $nik = $this->nullIfEmpty($row['nik_number'] ?? null);
        $rawNik = preg_replace('/\D+/', '', (string) $nik);
        $nikLast4 = substr($rawNik, -4);

        if ($existingPersonId) {
            $upd = [];
            if (in_array('full_name', $cols, true) && !empty($row['full_name'])) $upd['full_name'] = $this->cut($row['full_name']);
            if (in_array('gender', $cols, true) && isset($row['gender'])) $upd['gender'] = $this->cut($row['gender']);
            if (in_array('date_of_birth', $cols, true)) $upd['date_of_birth'] = $this->parseDate($row['date_of_birth'] ?? null);
            if (in_array('place_of_birth', $cols, true) && isset($row['place_of_birth'])) $upd['place_of_birth'] = $this->cut($row['place_of_birth']);
            if (in_array('phone', $cols, true) && isset($row['contact_no'])) $upd['phone'] = $this->cut($row['contact_no'], 50);
            if (in_array('email', $cols, true) && isset($row['email'])) $upd['email'] = $this->cut($row['email'], 150);
            if (in_array('address', $cols, true) && isset($row['address'])) $upd['address'] = $this->cut($row['address']);
            if (in_array('city', $cols, true) && isset($row['city'])) $upd['city'] = $this->cut($row['city'], 120);
            if (in_array('nik_hash', $cols, true) && !empty($rawNik)) $upd['nik_hash'] = $rawNik;
            if (in_array('nik_last4', $cols, true)) $upd['nik_last4'] = $nikLast4;
            if (in_array('updated_at', $cols, true)) $upd['updated_at'] = $now;
            if (!empty($upd)) DB::table($tbl)->where('id', $existingPersonId)->update($upd);
            return (string) $existingPersonId;
        }

        $newId = (string) Str::ulid();
        $person = ['id' => $newId];
        if (in_array('full_name', $cols, true)) $person['full_name'] = $this->cut($this->nullIfEmpty($row['full_name'] ?? null)) ?? '-';
        if (in_array('gender', $cols, true)) $person['gender'] = $this->cut($this->nullIfEmpty($row['gender'] ?? null));
        if (in_array('date_of_birth', $cols, true)) $person['date_of_birth'] = $this->parseDate($row['date_of_birth'] ?? null);
        if (in_array('place_of_birth', $cols, true)) $person['place_of_birth'] = $this->cut($this->nullIfEmpty($row['place_of_birth'] ?? null));
        if (in_array('phone', $cols, true)) $person['phone'] = $this->cut($this->nullIfEmpty($row['contact_no'] ?? null), 50);
        if (in_array('email', $cols, true)) $person['email'] = $this->cut($this->nullIfEmpty($row['email'] ?? null), 150);
        if (in_array('address', $cols, true)) $person['address'] = $this->cut($this->nullIfEmpty($row['address'] ?? null));
        if (in_array('city', $cols, true)) $person['city'] = $this->cut($this->nullIfEmpty($row['city'] ?? null), 120);
        if (in_array('nik_hash', $cols, true) && !empty($rawNik)) $person['nik_hash'] = $rawNik;
        if (in_array('nik_last4', $cols, true)) $person['nik_last4'] = $nikLast4;
        if (in_array('created_at', $cols, true)) $person['created_at'] = $now;
        if (in_array('updated_at', $cols, true)) $person['updated_at'] = $now;
        DB::table($tbl)->insert(array_intersect_key($person, array_flip($cols)));
        return $newId;
    }

    protected function flattenEmployeePayload(array $emp): array
    {
        $keys = ['id_sitms', 'employee_id', 'full_name', 'nik_number', 'gender', 'place_of_birth', 'date_of_birth', 'address', 'city', 'employee_status', 'company_name', 'directorat_id', 'directorat_name', 'working_unit_id', 'working_unit_name', 'location_name', 'position_level_name', 'position_name', 'home_base', 'education_level_name', 'major_name', 'education_name', 'email', 'contact_no', 'talent_class_level', 'is_active', 'latest_jobs_start_date', 'latest_jobs_unit', 'latest_jobs', 'profile_picture_url'];
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $emp)) $out[$k] = $this->nullIfEmpty($emp[$k]);
        }
        if (!empty($emp['home_base'])) {
            $raw = (string) $emp['home_base'];
            $out['home_base_raw'] = $raw;
            $noTags = strip_tags($raw, '<i>');
            if (preg_match('/<i>(.*?)<\/i>/', $noTags, $m)) $out['home_base_city'] = trim($m[1] ?? '');
            $first = explode('<br', $raw)[0] ?? '';
            $out['home_base_province'] = trim(strip_tags($first));
        }
        if (isset($emp['latest_jobs'])) $out['latest_jobs_title'] = $emp['latest_jobs'];
        return $out;
    }

    protected function rowFingerprint(array $row): array
    {
        $genericId = $this->nullIfEmpty($row['id'] ?? null);
        $employeeId = $this->nullIfEmpty($row['employee_id'] ?? null);
        $sitmsId = $this->nullIfEmpty($row['id_sitms'] ?? null);
        $externalId = $sitmsId ?? $employeeId ?? $genericId;
        return [$externalId, trim($row['full_name'] ?? ''), trim($row['working_unit_name'] ?? ''), trim($row['position_name'] ?? ''), trim($row['email'] ?? '')];
    }

    protected function nullIfEmpty($v)
    {
        $s = trim((string) ($v ?? ''));
        return $s === '' ? null : $s;
    }
    protected function cut($v, int $len = 255)
    {
        if ($v === null) return null;
        return mb_substr((string) $v, 0, $len);
    }
    protected function currentSeenCount(): int
    {
        return $this->uniqueCount ? count(array_unique($this->seenExternalIds)) : count($this->seenExternalIds);
    }
    protected function uniqueExternalIds(): array
    {
        return array_values(array_unique(array_filter($this->seenExternalIds, fn($v) => (string) $v !== '')));
    }
    protected function uniqueEmployeeIds(): array
    {
        return array_values(array_unique(array_filter($this->seenEmployeeIds, fn($v) => (string) $v !== '')));
    }
    protected function filterColumns(string $table, array $payload): array
    {
        return array_intersect_key($payload, array_flip($this->tableColumns($table)));
    }
    protected function tableColumns(string $table): array
    {
        if (!isset($this->tableColumnsCache[$table])) {
            $this->tableColumnsCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }
        return $this->tableColumnsCache[$table];
    }
    protected function columnMeta(string $table): array
    {
        if (isset($this->tableColumnsMeta[$table])) return $this->tableColumnsMeta[$table];
        $meta = [];
        if (!Schema::hasTable($table)) return $meta;
        try {
            $cols = DB::select("SHOW COLUMNS FROM `$table`");
            foreach ($cols as $c) $meta[$c->Field] = ['type' => $c->Type, 'null' => strtoupper((string) $c->Null) === 'YES', 'default' => $c->Default];
        } catch (\Throwable $e) {
        }
        return $this->tableColumnsMeta[$table] = $meta;
    }

    protected function parseDate($value): ?string
    {
        if (!$value) return null;
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function sitmsSyncPortfolio(string $personId, array $row): void
    {
        if (!Schema::hasTable('portfolio_histories')) return;
        foreach (Arr::get($row, 'education_list.education_data', []) as $e) {
            $this->sitmsInsertPortfolio($personId, 'education', ['title' => $this->nullIfEmpty($e['education_name'] ?? null), 'organization' => $this->nullIfEmpty($e['education_name'] ?? null), 'start_date' => $this->parseDate(($e['graduation_year'] ?? null) ? ($e['graduation_year'] . '-01-01') : null), 'meta' => ['level' => $this->nullIfEmpty($e['education_level'] ?? null), 'major' => $this->nullIfEmpty($e['major_name'] ?? null)]]);
        }
        foreach (Arr::get($row, 'jobs_list.jobs_data', []) as $j) {
            $this->sitmsInsertPortfolio($personId, 'job', ['title' => $this->nullIfEmpty($j['jobs'] ?? null), 'organization' => $this->nullIfEmpty($j['jobs_unit'] ?? $j['jobs_company'] ?? null), 'start_date' => $this->parseDate($j['jobs_start_date'] ?? null), 'end_date' => $this->parseDate($j['jobs_end_date'] ?? null), 'description' => $this->nullIfEmpty($j['jobs_description'] ?? null)]);
        }
        foreach (Arr::get($row, 'assignments_list.assignments_data', []) as $a) {
            $this->sitmsInsertPortfolio($personId, 'assignment', ['title' => $this->nullIfEmpty($a['assignment_title'] ?? null), 'organization' => $this->nullIfEmpty($a['assignment_company'] ?? null), 'start_date' => $this->parseDate($a['assignment_start_date'] ?? null), 'end_date' => $this->parseDate($a['assignment_end_date'] ?? null)]);
        }
        foreach (Arr::get($row, 'training_list.training_data', []) as $tr) {
            $this->sitmsInsertPortfolio($personId, 'training', ['title' => $this->nullIfEmpty($tr['training_name'] ?? null), 'organization' => $this->nullIfEmpty($tr['training_organizer'] ?? null), 'start_date' => $this->parseDate(($tr['training_year'] ?? null) ? ($tr['training_year'] . '-01-01') : null)]);
        }
    }

    protected function sitmsInsertPortfolio(string $personId, string $category, array $data): void
    {
        if (!Schema::hasTable('portfolio_histories')) return;
        $cols = $this->tableColumns('portfolio_histories');
        $row = [
            'person_id' => $personId, 'category' => $category, 'title' => $this->cut($data['title'] ?? null, 150),
            'organization' => $this->cut($data['organization'] ?? null, 150), 'start_date' => $data['start_date'] ?? null, 'end_date' => $data['end_date'] ?? null,
            'description' => $this->cut($data['description'] ?? null, 300), 'meta' => isset($data['meta']) ? json_encode($data['meta']) : null,
            'created_at' => now(), 'updated_at' => now()
        ];
        if (in_array('source_system', $cols, true)) $row['source_system'] = 'sitms';
        $row = array_intersect_key($row, array_flip($cols));
        
        $q = DB::table('portfolio_histories')->where('person_id', $personId)->where('category', $category)->where('title', $row['title']);
        $existing = $q->first();
        if ($existing) {
            $id = $existing->id;
            DB::table('portfolio_histories')->where('id', $id)->update($row);
        } else {
            $id = DB::table('portfolio_histories')->insertGetId($row);
        }
        if ($id) {
            if (!isset($this->seenPortfolioIds[$personId])) $this->seenPortfolioIds[$personId] = [];
            $this->seenPortfolioIds[$personId][] = (int) $id;
        }
    }

    protected function sitmsSyncDocuments(string $personId, array $row): void
    {
        if (!Schema::hasTable('documents')) return;
        $cols = $this->tableColumns('documents');
        foreach (Arr::get($row, 'documents_list.documents_data', []) as $d) {
            $payload = ['person_id' => $personId, 'title' => $this->nullIfEmpty($d['document_title'] ?? null), 'file_path' => $this->nullIfEmpty($d['document_file'] ?? null), 'updated_at' => now()];
            if (in_array('doc_type', $cols, true)) $payload['doc_type'] = $this->cut($this->nullIfEmpty($d['document_type'] ?? null) ?? 'unknown', 50);
            if (in_array('path', $cols, true)) $payload['path'] = $payload['file_path'];
            if (in_array('source_system', $cols, true)) $payload['source_system'] = 'sitms';
            
            $existing = DB::table('documents')->where('person_id', $personId)->where('title', $payload['title'])->first();
            if ($existing) {
                $id = $existing->id;
                DB::table('documents')->where('id', $id)->update(array_intersect_key($payload, array_flip($cols)));
            } else {
                $payload['created_at'] = now();
                $id = DB::table('documents')->insertGetId(array_intersect_key($payload, array_flip($cols)));
            }
            if ($id) {
                if (!isset($this->seenDocumentIds[$personId])) $this->seenDocumentIds[$personId] = [];
                $this->seenDocumentIds[$personId][] = (int) $id;
            }
        }
    }

    protected function upsertEducation(string $personId, array $e): void
    {
        if (!Schema::hasTable('educations')) return;
        $row = ['person_id' => $personId, 'level' => $this->nullIfEmpty($e['education_level'] ?? null), 'institution' => $this->nullIfEmpty($e['education_name'] ?? null), 'major' => $this->nullIfEmpty($e['major_name'] ?? null), 'graduation_year' => (int) ($e['graduation_year'] ?? 0) ?: null, 'updated_at' => now()];
        if (in_array('source_system', $this->tableColumns('educations'), true)) $row['source_system'] = 'sitms';
        DB::table('educations')->updateOrInsert(['person_id' => $personId, 'institution' => $row['institution'], 'major' => $row['major']], $row);
        $id = DB::table('educations')->where('person_id', $personId)->where('institution', $row['institution'])->value('id');
        if ($id) {
            if (!isset($this->seenEducationIds[$personId])) $this->seenEducationIds[$personId] = [];
            $this->seenEducationIds[$personId][] = (int) $id;
        }
    }

    protected function upsertTraining(string $personId, array $t): void
    {
        if (!Schema::hasTable('trainings')) return;
        $row = ['person_id' => $personId, 'title' => $this->nullIfEmpty($t['training_name'] ?? null), 'provider' => $this->nullIfEmpty($t['training_organizer'] ?? null), 'start_date' => $this->parseDate(($t['training_year'] ?? null) ? ($t['training_year'] . '-01-01') : null), 'updated_at' => now()];
        if (in_array('source_system', $this->tableColumns('trainings'), true)) $row['source_system'] = 'sitms';
        DB::table('trainings')->updateOrInsert(['person_id' => $personId, 'title' => $row['title'], 'start_date' => $row['start_date']], $row);
        $id = DB::table('trainings')->where('person_id', $personId)->where('title', $row['title'])->value('id');
        if ($id) {
            if (!isset($this->seenTrainingIds[$personId])) $this->seenTrainingIds[$personId] = [];
            $this->seenTrainingIds[$personId][] = (int) $id;
        }
    }

    protected function upsertCertification(string $personId, array $c): void
    {
        if (!Schema::hasTable('certifications')) return;
        $row = ['person_id' => $personId, 'name' => $this->nullIfEmpty($c['brevet_name'] ?? null), 'issuer' => $this->nullIfEmpty($c['brevet_organizer'] ?? null), 'number' => $this->nullIfEmpty($c['certificate_number'] ?? null), 'updated_at' => now()];
        if (in_array('source_system', $this->tableColumns('certifications'), true)) $row['source_system'] = 'sitms';
        DB::table('certifications')->updateOrInsert(['person_id' => $personId, 'name' => $row['name'], 'number' => $row['number']], $row);
        $id = DB::table('certifications')->where('person_id', $personId)->where('name', $row['name'])->value('id');
        if ($id) {
            if (!isset($this->seenCertificationIds[$personId])) $this->seenCertificationIds[$personId] = [];
            $this->seenCertificationIds[$personId][] = (int) $id;
        }
    }

    protected function upsertJobHistory(string $personId, array $j): void
    {
        if (!Schema::hasTable('job_histories')) return;
        $row = ['person_id' => $personId, 'title' => $this->nullIfEmpty($j['jobs'] ?? null), 'unit_name' => $this->nullIfEmpty($j['jobs_unit'] ?? null), 'start_date' => $this->parseDate($j['jobs_start_date'] ?? null), 'updated_at' => now()];
        if (in_array('source_system', $this->tableColumns('job_histories'), true)) $row['source_system'] = 'sitms';
        DB::table('job_histories')->updateOrInsert(['person_id' => $personId, 'title' => $row['title'], 'start_date' => $row['start_date']], $row);
        $id = DB::table('job_histories')->where('person_id', $personId)->where('title', $row['title'])->value('id');
        if ($id) {
            if (!isset($this->seenJobHistoryIds[$personId])) $this->seenJobHistoryIds[$personId] = [];
            $this->seenJobHistoryIds[$personId][] = (int) $id;
        }
    }

    protected function sitmsEnsureLookupId(string $table, ?string $name, array $extras = []): ?int
    {
        $name = $this->nullIfEmpty($name);
        if (!$name || !Schema::hasTable($table)) return null;

        $cacheKey = $table . '|' . strtolower($name);
        if (isset($this->lookupCache[$cacheKey])) return $this->lookupCache[$cacheKey];

        $cols = $this->tableColumns($table);
        $existing = in_array('name', $cols, true) ? DB::table($table)->whereRaw('LOWER(`name`)=?', [mb_strtolower($name)])->first() : null;

        if ($table === 'units') {
            $codeMap = [
                'SI Head Office' => 'SIHO',
                'Cabang Jakarta' => 'SIJAK',
            ];
            $wantCode = $codeMap[$name] ?? null;
            $dirId = $extras['directorate_id'] ?? null;
            if ($existing) {
                $upd = [];
                if ($dirId && in_array('directorate_id', $cols, true)) $upd['directorate_id'] = $dirId;
                if (!empty($upd)) DB::table($table)->where('id', $existing->id)->update($upd);
                $this->lookupCache[$cacheKey] = (int) $existing->id;
                return (int) $existing->id;
            }
            $row = [];
            if (in_array('name', $cols, true)) $row['name'] = $this->cut($name);
            if (in_array('code', $cols, true)) $row['code'] = $wantCode ?: $this->ensureUniqueCode($table, Str::upper(Str::substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10)));
            if ($dirId && in_array('directorate_id', $cols, true)) $row['directorate_id'] = $dirId;
            $id = DB::table($table)->insertGetId($row);
            $this->lookupCache[$cacheKey] = (int) $id;
            return (int) $id;
        }

        if ($existing) {
            $this->lookupCache[$cacheKey] = (int) $existing->id;
            return (int) $existing->id;
        }

        $row = [];
        if (in_array('name', $cols, true)) $row['name'] = $this->cut($name);
        if (in_array('code', $cols, true)) $row['code'] = $this->ensureUniqueCode($table, Str::upper(Str::substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10)));
        $id = DB::table($table)->insertGetId($row);
        $this->lookupCache[$cacheKey] = (int) $id;
        return (int) $id;
    }

    protected function sitmsEnsureLocationId(?string $locationName, ?string $city, ?string $province): ?int
    {
        if (!Schema::hasTable('locations')) return null;
        $name = $this->nullIfEmpty($locationName) ?? ($city ?: $province);
        if (!$name) return null;
        $id = DB::table('locations')->whereRaw('LOWER(`name`)=?', [mb_strtolower($name)])->value('id');
        if ($id) return (int) $id;
        $id = DB::table('locations')->insertGetId(['name' => $this->cut($name), 'city' => $this->cut($city), 'province' => $this->cut($province), 'created_at' => now(), 'updated_at' => now()]);
        return (int) $id;
    }

    protected function sitmsParseHomeBase(?string $homeBase): array
    {
        if (!$homeBase) return [null, null];
        $txt = trim(strip_tags($homeBase, '<i>'));
        $city = null;
        $prov = null;
        if (preg_match('~<i>(.*?)</i>~u', $homeBase, $m)) $city = $this->nullIfEmpty($m[1] ?? null);
        $first = explode('<br', (string) $homeBase)[0] ?? '';
        $prov = $this->nullIfEmpty(strip_tags($first)) ?? null;
        if (!$city && preg_match('~(Kota|Kab\.)\s+[A-Za-z].*$~u', $txt, $m)) {
            $city = $this->nullIfEmpty($m[0]);
            $prov = $this->nullIfEmpty(trim(Str::replaceLast($m[0], '', $txt)));
        }
        return [$prov, $city];
    }

    protected function ensureUniqueCode(string $table, string $base, ?int $ignoreId = null): string
    {
        $candidate = $base;
        $i = 1;
        while (DB::table($table)->when($ignoreId, fn($q) => $q->where('id', '<>', $ignoreId))->where('code', $candidate)->exists()) {
            $candidate = $base . $i;
            $i++;
            if ($i > 100) {
                $candidate = $base . '_' . Str::upper(Str::random(4));
                break;
            }
        }
        return $candidate;
    }
}