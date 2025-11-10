<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $cred = $request->validate([
            'login'    => ['required','string'],
            'password' => ['required','string'],
        ]);

        $login    = trim($cred['login']);
        $password = $cred['password'];

        // 1) Cari user by email dulu
        $user = User::query()->where('email', $login)->first();

        // 2) Kalau tidak ada & login numerik → cek employees (employee_id / id_sitms)
        if (!$user) {
            $normalized = preg_replace('/\D+/', '', $login);
            $emp = Employee::query()
                ->where('employee_id', $normalized)
                ->orWhere('id_sitms', $normalized)
                ->first();

            if ($emp) {
                // Cari user existing by employee_id
                $user = User::query()->where('employee_id', $emp->employee_id)->first();

                // Auto-create user (password default "password")
                if (!$user) {
                    $user = new User();
                    $user->person_id   = $emp->person_id;
                    $user->employee_id = $emp->employee_id;
                    $user->unit_id     = $emp->unit_id;
                    $user->name        = $this->resolveDisplayNameFromEmployee($emp);
                    $user->email       = $emp->email; // boleh null
                    $user->password    = Hash::make('password');
                    $user->save();
                }
            }
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => 'Akun tidak ditemukan.',
            ]);
        }

        // 3) Login
        $loggedIn = false;
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(['email' => $login, 'password' => $password], true)) {
                $request->session()->regenerate();
                $loggedIn = true;
            }
        } else {
            if (Hash::check($password, (string)$user->password)) {
                Auth::login($user, true);
                $request->session()->regenerate();
                $loggedIn = true;
            }
        }

        if (!$loggedIn) {
            throw ValidationException::withMessages([
                'password' => 'Kredensial salah.',
            ]);
        }

        // 4) Set team scope = unit_id user
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->unit_id);

        // 5) Auto-roles additive (teams=true)
        $this->autoAssignRoles($user);

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

    private function resolveDisplayNameFromEmployee(?Employee $emp): string
    {
        if (!$emp) return 'User';
        try {
            $person = method_exists($emp, 'person') ? $emp->person()->first() : null;
            if ($person && $person->full_name) return $person->full_name;
        } catch (\Throwable $e) {}
        return $emp->full_name ?: ($emp->email ?: ('Employee '.$emp->employee_id));
    }

    private function autoAssignRoles(User $user): void
    {
        $emp = Employee::query()->where('employee_id', $user->employee_id)->first();
        $job = $emp?->latest_jobs_title ?: $emp?->job_title ?: '';

        $roleTarget = $this->decideRoleName($job); // Kepala Unit / Karyawan

        // pastikan role exist
        $role = Role::query()->where('name', $roleTarget)->where('guard_name','web')->first();
        if (!$role) $role = Role::create(['name'=>$roleTarget,'guard_name'=>'web']);

        // scope by team (unit)
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->unit_id);

        if (!$user->hasRole($roleTarget)) {
            $user->assignRole($roleTarget);
        }

        // rapikan name
        if (!$user->name) {
            $user->name = $this->resolveDisplayNameFromEmployee($emp);
            $user->save();
        }
    }

    private function decideRoleName(?string $title): string
    {
        $t = mb_strtolower($title ?? '');
        if ($t === '') return 'Karyawan';
        if (str_contains($t, 'general manager') || preg_match('/\bgm\b/i', $t)) return 'Kepala Unit';
        if (str_contains($t, 'vice president') || preg_match('/\bvp\b/i', $t)) return 'Kepala Unit';
        return 'Karyawan';
    }
}
