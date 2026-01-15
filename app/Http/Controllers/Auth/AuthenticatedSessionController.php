<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\RecruitmentRequest;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        $vacancies = RecruitmentRequest::with(['unit', 'positionObj'])
            ->where('is_published', 1)
            ->where('status', 'approved') 
            ->orderBy('created_at', 'desc')
            ->get();

        // Kirim variabel $vacancies ke view layouts.public
        // Pastikan nama view sesuai dengan lokasi file blade Anda
        return view('layouts.public', compact('vacancies'));
    }

    public function store(Request $request)
    {
        $cred = $request->validate([
            'login'    => ['required','string'],
            'password' => ['required','string'],
        ]);

        $login    = trim($cred['login']);
        $password = $cred['password'];

        $newlyProvisioned = false;

        // 1) Cari user by email → kalau nggak ada, coba by employee/id_sitms
        $user = User::query()->where('email', $login)->first();

        if (!$user) {
            $normalized = preg_replace('/\D+/', '', $login);
            if ($normalized !== '') {
                $emp = Employee::query()
                    ->where('employee_id', $normalized)
                    ->orWhere('id_sitms', $normalized)
                    ->first();

                if ($emp) {
                    $user = User::query()->where('employee_id', $emp->employee_id)->first();

                    if (!$user) {
                        $user = new User();
                        $user->person_id   = $emp->person_id ?? null;
                        $user->employee_id = $emp->employee_id;
                        $user->unit_id     = $emp->unit_id;
                        $user->name        = $this->resolveDisplayNameFromEmployee($emp);
                        $user->email       = $emp->email;
                        $user->password    = Hash::make('password'); // Password default jika auto-create
                        $user->save();

                        $newlyProvisioned = true;
                    }
                }
            }
        }

        // --- PERUBAHAN DI SINI ---
        // Kita HAPUS blok "if (!$user) throw..." yang lama agar tidak bocor informasinya.
        
        // 2) Auth
        $loggedIn = false;

        // Cek login hanya dilakukan jika $user berhasil ditemukan/dibuat di tahap sebelumnya
        if ($user) {
            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                if (Auth::attempt(['email' => $login, 'password' => $password], $request->boolean('remember'))) {
                    $request->session()->regenerate();
                    $loggedIn = true;
                }
            } else {
                // Manual check hash untuk login non-email (NIP/ID)
                if (Hash::check($password, (string) $user->password)) {
                    Auth::login($user, $request->boolean('remember'));
                    $request->session()->regenerate();
                    $loggedIn = true;
                }
            }
        }

        // 3) Satu Pintu Error
        // Jika user tidak ditemukan ($user null) ATAU password salah ($loggedIn false), masuk ke sini.
        if (!$loggedIn) {
            throw ValidationException::withMessages([
                'login' => 'Kredensial Salah.', 
            ]);
        }

        // 4) Set Spatie team scope ke unit user
        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($user->unit_id);

        // 5) Sinkronisasi display name
        $this->syncDisplayNameFromHR($user);

        // 6) Provision Initial Roles (Hanya saat auto-create user dari Employee)
        if ($newlyProvisioned) {
            $this->provisionInitialRoles($user);
        }

        // === LOGIKA REDIRECT DISINI ===
        if ($user->hasRole('Pelamar')) {
            // Arahkan ke Dashboard Data Pelamar
            return redirect()->route('recruitment.applicant-data.index')
                ->with('ok', 'Selamat datang! Silakan lengkapi data diri Anda.');
        } else {
            // Arahkan user internal ke dashboard biasa
            return redirect()->intended(route('dashboard'));
        }
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // ================= Helpers =================

    private function resolveDisplayNameFromEmployee(?Employee $emp): string
    {
        if (!$emp) return 'User';
        try {
            if ($emp->person_id) {
                $row = DB::table('persons')->select('full_name')->where('id', $emp->person_id)->first();
                if ($row && $row->full_name) {
                    return (string) $row->full_name;
                }
            }
        } catch (\Throwable $e) { /* ignore */ }

        if (!empty($emp->full_name)) return (string) $emp->full_name;
        if (!empty($emp->email))     return (string) $emp->email;
        if (!empty($emp->employee_id)) return 'Employee '.$emp->employee_id;

        return 'User';
    }

    private function syncDisplayNameFromHR(User $user): void
    {
        $needsUpdate = false;

        if (empty($user->name)) {
            $needsUpdate = true;
        } else {
            if (filter_var($user->name, FILTER_VALIDATE_EMAIL)) {
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            $emp = null;
            if (!empty($user->employee_id)) {
                $emp = Employee::query()->where('employee_id', $user->employee_id)->first();
            } elseif (!empty($user->person_id)) {
                $emp = Employee::query()->where('person_id', $user->person_id)->first();
            }

            $newName = $this->resolveDisplayNameFromEmployee($emp);
            if ($newName && $newName !== $user->name) {
                $user->name = $newName;
                $user->save();
            }
        }
    }

    private function provisionInitialRoles(User $user): void
    {
        $emp = Employee::query()->where('employee_id', $user->employee_id)->first();
        $job = $emp?->latest_jobs_title ?: $emp?->job_title ?: '';

        $targets = $this->decideRoleNames($job);

        if (!in_array('Karyawan', $targets, true)) {
            array_unshift($targets, 'Karyawan');
        }
        $targets = array_values(array_unique($targets));

        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $currentUnitId = $user->unit_id ?: 0;
        $guard = $this->resolveGuardName($user);

        foreach ($targets as $roleName) {
            $registrar->setPermissionsTeamId($currentUnitId);

            $role = Role::query()
                ->where(function ($q) use ($currentUnitId) {
                    $q->whereNull('unit_id')->orWhere('unit_id', $currentUnitId);
                })
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->orderByRaw('CASE WHEN unit_id = ? THEN 0 ELSE 1 END', [$currentUnitId])
                ->first();

            if (!$role) {
                $role = new Role();
                $role->name = $roleName;
                $role->guard_name = $guard;
                if (Schema::hasColumn($role->getTable(), 'unit_id')) {
                    $role->unit_id = $currentUnitId;
                }
                $role->save();
            }

            $already = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->where('role_id', $role->id)
                ->where(function($w) use ($currentUnitId){
                    $w->where('unit_id', $currentUnitId)->orWhereNull('unit_id');
                })
                ->exists();

            if (!$already) {
                $user->assignRole($role);
            }
        }
    }

    private function resolveGuardName(User $user): string
    {
        if (property_exists($user, 'guard_name') && !empty($user->guard_name)) {
            return (string) $user->guard_name;
        }
        $cfg = (string) config('auth.defaults.guard', 'web');
        return $cfg ?: 'web';
    }

    private function decideRoleNames(?string $title): array
    {
        $t = mb_strtolower($title ?? '');
        if ($t === '') return ['Karyawan'];

        $roles = ['Karyawan'];

        if (preg_match('/(?:(?:general)\s*manager|\bgm\b)/u', $t)) {
            $roles[] = 'Kepala Unit';
        }
        if (preg_match('/(?:vice\s*president|\bvp\b|v\.p\.|vp\s*\/\s*gm|wakil\s*presiden)/u', $t)) {
            $roles[] = 'Kepala Unit';
        }
        if (preg_match('/kepala\s*unit|unit\s*head|head\s*of\s*unit/u', $t)) {
            $roles[] = 'Kepala Unit';
        }

        return $roles;
    }
}