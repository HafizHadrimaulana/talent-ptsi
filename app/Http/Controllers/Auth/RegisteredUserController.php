<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; 
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role; 

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 1. Buat User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'unit_id' => null, // Pastikan unit_id null untuk pelamar
        ]);

        // 2. Assign Role "Pelamar" (PASTI)
        $role = Role::where('name', 'Pelamar')->where('guard_name', 'web')->first();

        // Jika role tidak ditemukan, buat baru (safety)
        if (!$role) {
            $role = Role::create(['name' => 'Pelamar', 'guard_name' => 'web']);
        }

        // INSERT MANUAL KE TABEL PIVOT
        // Kita gunakan DB::table langsung untuk menghindari error Spatie (Team ID check)
        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => 'App\Models\User', // Sesuaikan namespace User Anda
            'model_id' => $user->id,
            'unit_id' => 0 // Paksa 0 atau NULL (tergantung struktur DB Anda)
        ]);

        event(new Registered($user));

        // 3. Login
        Auth::login($user);

        // 4. Redirect ke Dashboard Pelamar (applicant-data)
        return redirect()->route('recruitment.applicant-data.index')
            ->with('ok', 'Akun berhasil dibuat! Silakan lengkapi biodata Anda.');
    }
}