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
use Illuminate\Support\Str;
use Throwable;

class SyncSitmsMasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $page;
    public int $perPage;
    public bool $continuePaging;

    protected static array $seenExternalIds = [];

    public function __construct(int $page, int $perPage, bool $continuePaging)
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->continuePaging = $continuePaging;
    }

    public static function dispatchSync(HttpSitmsClient $client, int $page, int $perPage, bool $continuePaging): void
    {
        self::runSinglePage($client, $page, $perPage, $continuePaging);
    }

    public static function runSinglePage(HttpSitmsClient $client, int $page, int $perPage, bool $continuePaging): void
    {
        self::$seenExternalIds = [];

        $current = max(1, $page);
        $askedPerPage = max(1, $perPage);
        $processed = 0;
        $reportedTotal = null;
        $noGrowthStreak = 0; // guard kalau server tetap balikin subset yang sama

        do {
            $resp   = $client->fetchEmployeesPage($current, $askedPerPage);
            $rows   = Arr::get($resp, 'rows', []);
            $total  = Arr::get($resp, 'total');
            if (is_numeric($total)) $reportedTotal = (int)$total;

            $countRows = is_countable($rows) ? count($rows) : 0;
            $processed += $countRows;

            $beforeSeen = count(self::$seenExternalIds);

            foreach ($rows as $row) {
                try {
                    self::upsertFromSitmsRow((array)$row);
                } catch (Throwable $e) {
                    Log::warning('SITMS upsert skipped', [
                        'error'      => $e->getMessage(),
                        'row_sample' => Arr::only((array)$row, ['id','id_sitms','employee_id','full_name','nik_number','email']),
                    ]);
                }
            }

            $afterSeen = count(array_unique(self::$seenExternalIds));
            $grown = $afterSeen - $beforeSeen;

            Log::info('SITMS page result', [
                'page'        => $current,
                'per_page'    => Arr::get($resp, 'per_page'),
                'rows'        => $countRows,
                'processed'   => $processed,
                'seen_unique' => $afterSeen,
                'grown'       => $grown,
                'total_hint'  => $reportedTotal,
            ]);

            if (!$continuePaging) break;

            // Berhenti kalau sudah memenuhi total dengan ID unik
            if (is_numeric($reportedTotal) && $afterSeen >= $reportedTotal) break;

            // Kalau tidak ada pertumbuhan ID unik beberapa kali, hentikan (server kasih subset yang sama)
            if ($grown <= 0) {
                $noGrowthStreak++;
            } else {
                $noGrowthStreak = 0;
            }
            if ($noGrowthStreak >= 3) { // 3 halaman berturut-turut ga nambah apa2
                Log::warning('SITMS paging: no growth streak reached, stopping.');
                break;
            }

            // Stop jika halaman kosong
            if ($countRows === 0) break;

            $current++;
            if ($current > 30000) { Log::warning('SITMS paging guard tripped (page>30000)'); break; }
        } while (true);

        if ($continuePaging) {
            self::pruneEmployeesNotInFeed(self::$seenExternalIds);
            Log::info('SITMS sync summary', [
                'processed_total' => $processed,
                'reported_total'  => $reportedTotal,
                'seen_externalIds'=> count(array_unique(self::$seenExternalIds)),
            ]);
        }
    }

    protected static function upsertFromSitmsRow(array $row): void
    {
        $sitmsEmployeeId = self::nullIfEmpty($row['employee_id'] ?? null);
        $sitmsId         = self::nullIfEmpty($row['id_sitms'] ?? null);
        $genericId       = self::nullIfEmpty($row['id'] ?? null);

        $externalId = $sitmsEmployeeId ?? $sitmsId ?? $genericId;

        if (!$externalId) {
            $name   = trim((string)($row['full_name'] ?? $row['name'] ?? ''));
            $nik    = trim((string)($row['nik_number'] ?? $row['nik'] ?? ''));
            $dob    = trim((string)($row['date_of_birth'] ?? $row['tgl_lahir'] ?? ''));
            $email  = trim((string)($row['email'] ?? ''));
            $finger = implode('|', [$name, $nik, $dob, $email]);
            $externalId = 'SURR-'.sha1($finger);
            $sitmsId = $externalId;
        }

        self::$seenExternalIds[] = (string)$externalId;

        $fullName = trim((string)($row['full_name'] ?? $row['name'] ?? '')) ?: 'Unknown';
        $nik       = trim((string)($row['nik_number'] ?? $row['nik'] ?? ''));
        $email     = trim((string)($row['email'] ?? ''));
        $phone     = trim((string)($row['phone'] ?? ''));
        $gender    = self::mapGender($row['gender'] ?? $row['jenis_kelamin'] ?? null);
        $dob       = self::toDate($row['date_of_birth'] ?? $row['tgl_lahir'] ?? null);
        $pob       = trim((string)($row['place_of_birth'] ?? $row['tmp_lahir'] ?? ''));

        $companyName    = (string)($row['company_name'] ?? 'PT Surveyor Indonesia');
        $employeeStatus = self::nullIfEmpty($row['employee_status'] ?? $row['status_karyawan'] ?? null);
        $isActive       = self::boolish($row['is_active'] ?? $row['active'] ?? true);

        $dirCode  = self::nullIfEmpty($row['directorate_code'] ?? null);
        $dirName  = self::nullIfEmpty($row['directorate'] ?? $row['direktorat'] ?? null);
        $unitCode = self::nullIfEmpty($row['unit_code'] ?? null);
        $unitName = self::nullIfEmpty($row['unit_name'] ?? $row['unit'] ?? null);

        $posName   = self::nullIfEmpty($row['position'] ?? $row['jabatan'] ?? null);
        $levelCode = self::nullIfEmpty($row['level_code'] ?? null);
        $levelName = self::nullIfEmpty($row['level_name'] ?? $row['golongan'] ?? null);

        $homeBaseRaw      = self::nullIfEmpty($row['home_base'] ?? $row['lokasi_kerja'] ?? null);
        $homeBaseCity     = self::nullIfEmpty($row['home_base_city'] ?? null);
        $homeBaseProvince = self::nullIfEmpty($row['home_base_province'] ?? null);

        $latestStart = self::toDate($row['latest_jobs_start_date'] ?? null);
        $latestUnit  = self::nullIfEmpty($row['latest_jobs_unit'] ?? null);
        $latestTitle = self::nullIfEmpty($row['latest_jobs_title'] ?? null);

        DB::transaction(function () use (
            $externalId,$fullName,$nik,$email,$phone,$gender,$dob,$pob,
            $companyName,$employeeStatus,$isActive,
            $dirCode,$dirName,$unitCode,$unitName,$posName,$levelCode,$levelName,
            $homeBaseRaw,$homeBaseCity,$homeBaseProvince,
            $latestStart,$latestUnit,$latestTitle,$sitmsEmployeeId,$sitmsId
        ) {
            $personId = self::resolvePersonByExternalOrNik('SITMS', (string)$externalId, $fullName, $nik, $phone, $gender, $dob, $pob);

            self::upsertIdentity($personId, 'SITMS', (string)$externalId);
            if ($email !== '') self::upsertEmail($personId, $email);

            $directorateId = self::upsertDirectorate($dirCode, $dirName);
            $unitId        = self::upsertUnit($unitCode, $unitName, $directorateId);
            $positionId    = self::upsertPosition($posName);
            $levelId       = self::upsertLevel($levelCode, $levelName);
            $locationId    = self::upsertLocation($homeBaseRaw, $homeBaseCity, $homeBaseProvince);

            self::upsertEmployee(
                $personId, $companyName, $employeeStatus, $directorateId, $unitId,
                $locationId, $positionId, $levelId, null,
                $isActive, $sitmsEmployeeId, $sitmsId,
                $homeBaseRaw, $homeBaseCity, $homeBaseProvince,
                $latestStart, $latestUnit, $latestTitle,
                (string)$externalId
            );
        });
    }

    protected static function resolvePersonByExternalOrNik(
        string $system, string $externalId,
        string $fullName, string $nik, ?string $phone=null, ?string $gender=null, ?string $dob=null, ?string $pob=null
    ): string {
        $viaIdentity = DB::table('identities')
            ->where('system', $system)
            ->where('external_id', $externalId)
            ->value('person_id');
        if ($viaIdentity) {
            DB::table('persons')->where('id',$viaIdentity)->update([
                'full_name'      => $fullName ?: DB::raw('full_name'),
                'gender'         => $gender ?? DB::raw('gender'),
                'date_of_birth'  => $dob ?? DB::raw('date_of_birth'),
                'place_of_birth' => $pob ?? DB::raw('place_of_birth'),
                'phone'          => $phone ?: DB::raw('phone'),
                'updated_at'     => now(),
            ]);
            return (string)$viaIdentity;
        }

        $nikHash  = $nik !== '' ? hash('sha256', $nik) : null;
        $nikLast4 = $nik !== '' ? substr($nik, -4) : null;
        if ($nikHash) {
            $id = DB::table('persons')->where('nik_hash',$nikHash)->value('id');
            if ($id) {
                DB::table('persons')->where('id',$id)->update([
                    'full_name'      => $fullName ?: DB::raw('full_name'),
                    'gender'         => $gender ?? DB::raw('gender'),
                    'date_of_birth'  => $dob ?? DB::raw('date_of_birth'),
                    'place_of_birth' => $pob ?? DB::raw('place_of_birth'),
                    'nik_last4'      => $nikLast4 ?? DB::raw('nik_last4'),
                    'phone'          => $phone ?: DB::raw('phone'),
                    'updated_at'     => now(),
                ]);
                return (string)$id;
            }
        }

        $newId = (string) Str::ulid();
        DB::table('persons')->insert([
            'id'             => $newId,
            'full_name'      => $fullName,
            'gender'         => $gender,
            'date_of_birth'  => $dob,
            'place_of_birth' => $pob,
            'nik_hash'       => $nikHash,
            'nik_last4'      => $nikLast4,
            'phone'          => $phone,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        return $newId;
    }

    protected static function upsertEmail(string $personId, string $email): void
    {
        $exists = DB::table('emails')->where('person_id', $personId)->where('email', $email)->exists();
        if ($exists) return;
        $hasPrimary = DB::table('emails')->where('person_id', $personId)->where('is_primary', true)->exists();

        DB::table('emails')->insert([
            'person_id'   => $personId,
            'email'       => $email,
            'is_primary'  => !$hasPrimary,
            'is_verified' => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    protected static function upsertIdentity(string $personId, string $system, string $externalId): void
    {
        $exists = DB::table('identities')
            ->where('system', $system)
            ->where('external_id', $externalId)
            ->exists();

        if ($exists) {
            DB::table('identities')
                ->where('system', $system)
                ->where('external_id', $externalId)
                ->update(['person_id' => $personId, 'updated_at' => now()]);
        } else {
            DB::table('identities')->insert([
                'person_id'   => $personId,
                'system'      => $system,
                'external_id' => $externalId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    protected static function upsertDirectorate(?string $code, ?string $name): ?int
    {
        if (!$name && !$code) return null;
        $q = DB::table('directorates');
        $code ? $q->where('code', $code) : $q->where('name', $name);
        $id = $q->value('id');

        if ($id) {
            if ($code && !DB::table('directorates')->where('id',$id)->value('code')) {
                DB::table('directorates')->where('id',$id)->update(['code'=>$code,'updated_at'=>now()]);
            }
            if ($name) DB::table('directorates')->where('id',$id)->update(['name'=>$name,'updated_at'=>now()]);
            return (int)$id;
        }

        return DB::table('directorates')->insertGetId([
            'code'       => $code,
            'name'       => $name ?? ($code ?: 'Unknown'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected static function upsertUnit(?string $code, ?string $name, ?int $directorateId): ?int
    {
        if (!$name && !$code) return null;

        if ($code) {
            $id = DB::table('units')->where('code', $code)->value('id');
            if ($id) {
                DB::table('units')->where('id',$id)->update([
                    'name'          => $name ?? DB::raw('name'),
                    'directorate_id'=> $directorateId,
                    'updated_at'    => now(),
                ]);
                return (int)$id;
            }
        }

        if ($name) {
            $id = DB::table('units')->where('name', $name)->value('id');
            if ($id) {
                DB::table('units')->where('id',$id)->update([
                    'directorate_id'=> $directorateId,
                    'updated_at'    => now(),
                ]);
                return (int)$id;
            }
        }

        return DB::table('units')->insertGetId([
            'code'          => $code ?: null,
            'name'          => $name ?? ($code ?: 'Unknown'),
            'directorate_id'=> $directorateId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    protected static function upsertPosition(?string $name): ?int
    {
        if (!$name) return null;
        $id = DB::table('positions')->where('name', $name)->value('id');
        if ($id) {
            DB::table('positions')->where('id',$id)->update(['is_active'=>true,'updated_at'=>now()]);
            return (int)$id;
        }
        return DB::table('positions')->insertGetId([
            'name'=>$name,'is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
        ]);
    }

    protected static function upsertLevel(?string $code, ?string $name): ?int
    {
        if (!$code && !$name) return null;

        if ($code) {
            $id = DB::table('position_levels')->where('code', $code)->value('id');
            if ($id) {
                if ($name) DB::table('position_levels')->where('id',$id)->update(['name'=>$name,'updated_at'=>now()]);
                return (int)$id;
            }
        }

        if ($name) {
            $id = DB::table('position_levels')->where('name', $name)->value('id');
            if ($id) {
                if ($code) DB::table('position_levels')->where('id',$id)->update(['code'=>$code,'updated_at'=>now()]);
                return (int)$id;
            }
        }

        return DB::table('position_levels')->insertGetId([
            'code'=>$code, 'name'=>$name ?? ($code ?: 'Unknown'), 'created_at'=>now(), 'updated_at'=>now(),
        ]);
    }

    protected static function upsertLocation(?string $raw, ?string $city, ?string $province): ?int
    {
        if (!$raw && !$city && !$province) return null;

        if ($raw) {
            $id = DB::table('locations')->where('name', $raw)->value('id');
            if ($id) {
                DB::table('locations')->where('id',$id)->update([
                    'city'       => $city ?? DB::raw('city'),
                    'province'   => $province ?? DB::raw('province'),
                    'updated_at' => now(),
                ]);
                return (int)$id;
            }
        }

        if ($city) {
            $id = DB::table('locations')->where('city', $city)->where('province', $province)->value('id');
            if ($id) return (int)$id;
        }

        return DB::table('locations')->insertGetId([
            'name'       => $raw ?? (($city && $province) ? "{$city}, {$province}" : ($city ?? $province ?? 'Unknown')),
            'type'       => null,
            'city'       => $city,
            'province'   => $province,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected static function upsertEmployee(
        string $personId,
        string $companyName,
        ?string $employeeStatus,
        ?int $directorateId,
        ?int $unitId,
        ?int $locationId,
        ?int $positionId,
        ?int $positionLevelId,
        ?string $talentClassLevel,
        bool $isActive,
        ?string $sitmsEmployeeId,
        ?string $sitmsId,
        ?string $homeBaseRaw,
        ?string $homeBaseCity,
        ?string $homeBaseProvince,
        ?string $latestStartDate,
        ?string $latestUnit,
        ?string $latestTitle,
        ?string $externalIdForIdentity
    ): void {
        $exists = null;

        if ($sitmsEmployeeId) {
            $exists = DB::table('employees')->where('sitms_employee_id', $sitmsEmployeeId)->value('id');
        }
        if (!$exists && $sitmsId) {
            $exists = DB::table('employees')->where('sitms_id', $sitmsId)->value('id');
        }
        if (!$exists && $externalIdForIdentity) {
            $exists = DB::table('identities')
                ->where('system', 'SITMS')
                ->where('external_id', $externalIdForIdentity)
                ->join('employees', 'employees.person_id', '=', 'identities.person_id')
                ->value('employees.id');
        }

        $payload = [
            'company_name'           => $companyName,
            'employee_status'        => $employeeStatus,
            'directorate_id'         => $directorateId,
            'unit_id'                => $unitId,
            'location_id'            => $locationId,
            'position_id'            => $positionId,
            'position_level_id'      => $positionLevelId,
            'talent_class_level'     => $talentClassLevel,
            'is_active'              => $isActive,
            'sitms_employee_id'      => $sitmsEmployeeId,
            'sitms_id'               => $sitmsId,
            'home_base_raw'          => $homeBaseRaw,
            'home_base_city'         => $homeBaseCity,
            'home_base_province'     => $homeBaseProvince,
            'latest_jobs_start_date' => $latestStartDate,
            'latest_jobs_unit'       => $latestUnit,
            'latest_jobs_title'      => $latestTitle,
            'updated_at'             => now(),
        ];

        if ($exists) {
            DB::table('employees')->where('id', $exists)->update($payload);
        } else {
            $payload['person_id']  = $personId;
            $payload['created_at'] = now();
            DB::table('employees')->insert($payload);
        }
    }

    protected static function mapGender($v): ?string
    {
        $s = strtolower(trim((string)$v));
        return match(true) {
            $s === 'l' || str_contains($s,'laki') || str_contains($s,'male')   => 'Pria',
            $s === 'p' || str_contains($s,'perem') || str_contains($s,'female')=> 'Wanita',
            default => null,
        };
    }

    protected static function toDate($v): ?string
    {
        if (!$v) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '0000-00-00') return null;
        if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $s)) return $s;
        if (preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $s, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
        return null;
    }

    protected static function boolish($v): bool
    {
        if (is_bool($v)) return $v;
        $s = strtolower(trim((string)$v));
        return !in_array($s, ['0','false','no','tidak','nonaktif','inactive'], true);
    }

    protected static function nullIfEmpty($v)
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? null : $s;
    }

    protected static function pruneEmployeesNotInFeed(array $externalIds): void
    {
        $ids = array_values(array_unique(array_filter($externalIds, fn($v)=> (string)$v !== '')));
        if (empty($ids)) return;

        DB::table('employees')
            ->whereNotNull('sitms_employee_id')
            ->whereNotIn('sitms_employee_id', $ids)
            ->update(['is_active' => false, 'updated_at' => now()]);

        DB::table('employees')
            ->whereNull('sitms_employee_id')
            ->whereNotNull('sitms_id')
            ->whereNotIn('sitms_id', $ids)
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
}
