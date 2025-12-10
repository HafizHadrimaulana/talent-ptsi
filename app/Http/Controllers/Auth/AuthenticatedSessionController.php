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

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('layouts.public');
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

        // 1) Cari user by email → kalau nggak ada, coba by employee/id_sitms → auto-provision user dari Employee (hanya sekali di sini)
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
                        // === name harus full_name, bukan email ===
                        $user->name        = $this->resolveDisplayNameFromEmployee($emp);
                        $user->email       = $emp->email; // bisa null
                        // default password (silakan ganti via UI)
                        $user->password    = Hash::make('password');
                        $user->save();

                        $newlyProvisioned = true;
                    }
                }
            }
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => 'Akun tidak ditemukan.',
            ]);
        }

        // 2) Auth
        $loggedIn = false;
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(['email' => $login, 'password' => $password], $request->boolean('remember'))) {
                $request->session()->regenerate();
                $loggedIn = true;
            }
        } else {
            if (Hash::check($password, (string) $user->password)) {
                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();
                $loggedIn = true;
            }
        }

        if (!$loggedIn) {
            throw ValidationException::withMessages([
                'password' => 'Kredensial salah.',
            ]);
        }

        // 3) Set Spatie team scope ke unit user
        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($user->unit_id);

        // 4) Sinkronisasi display name → paksa full_name kalau name kosong/berbentuk email
        $this->syncDisplayNameFromHR($user);

        // 5) HANYA SAAT AUTO-PROVISION (dibuat otomatis dari Employee), assign initial roles.
        //    TIDAK ada auto-role setiap login normal → agar role "Karyawan" tidak balik lagi setelah dihapus manual.
        if ($newlyProvisioned) {
            $this->provisionInitialRoles($user);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // ================= Helpers =================

    /**
     * Ambil nama tampilan dari HR tables (prioritas persons.full_name, fallback ke employees.full_name, lalu label generik).
     */
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

    /**
     * Kalau user->name kosong atau terlihat seperti email, sinkronkan dengan full_name dari HR.
     */
    private function syncDisplayNameFromHR(User $user): void
    {
        $needsUpdate = false;

        if (empty($user->name)) {
            $needsUpdate = true;
        } else {
            // jika name terlihat seperti email, kita ganti ke full_name
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

    /**
     * Assign initial roles saat user diprovision OTOMATIS di login pertama.
     * Tidak dipanggil saat login normal. Additive dan unit-scoped.
     */
    private function provisionInitialRoles(User $user): void
    {
        $emp = Employee::query()->where('employee_id', $user->employee_id)->first();
        $job = $emp?->latest_jobs_title ?: $emp?->job_title ?: '';

        $targets = $this->decideRoleNames($job);

        // Pastikan default "Karyawan" ikut saat provisioning awal (sesuai kebijakan awalmu).
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

            // Cari role sesuai unit scope (teams=true)
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

            // Cegah duplikasi pivot (Spatie teams)
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

        // GM variants
        if (preg_match('/(?:(?:general)\s*manager|\bgm\b)/u', $t)) {
            $roles[] = 'Kepala Unit';
        }
        // VP variants
        if (preg_match('/(?:vice\s*president|\bvp\b|v\.p\.|vp\s*\/\s*gm|wakil\s*presiden)/u', $t)) {
            $roles[] = 'Kepala Unit';
        }
        // Head of Unit
        if (preg_match('/kepala\s*unit|unit\s*head|head\s*of\s*unit/u', $t)) {
            $roles[] = 'Kepala Unit';
        }

        return $roles;
    }
}
