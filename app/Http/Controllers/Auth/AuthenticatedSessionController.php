<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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

        $user = User::query()->where('email', $login)->first();

        if (!$user) {
            $user = User::query()->where('employee_id', $login)->first();
        }

        if (!$user) {
            $normalized = preg_replace('/\D+/', '', $login);
            if ($normalized !== '') {
                $user = User::query()->where('employee_id', $normalized)->first();
            }
        }

        $loggedIn = false;

        if ($user) {
            if ($user->employee_id) {
                $empStatus = DB::table('employees')
                    ->where('employee_id', $user->employee_id)
                    ->value('employee_status');
                
                $allowedStatuses = [
                    'Tetap',
                    'Kontrak Organik',
                    'Kontrak-Project Based',
                    'Kontrak-MPS',
                    'Kontrak-Tenaga Ahli',
                    'Kontrak-On Call'
                ];
                
                if ($empStatus && !in_array($empStatus, $allowedStatuses)) {
                    throw ValidationException::withMessages([
                        'login' => 'Status kepegawaian Anda tidak diizinkan untuk login.', 
                    ]);
                }
            }

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
        }

        if (!$loggedIn) {
            throw ValidationException::withMessages([
                'login' => 'Kredensial Salah atau Akun tidak ditemukan.', 
            ]);
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($user->unit_id);

        $this->syncDisplayNameFromHR($user);

        if ($user->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit', 'Admin Operasi Unit'])) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('employee.dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

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
        } catch (\Throwable $e) { }

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
}