<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Person;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik'  => ['required', 'string', 'max:16', 'unique:persons,nik'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // ID Unit Khusus Pelamar
        $pelamarUnitId = 100; 

        // 2. Buat User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'unit_id' => $pelamarUnitId,
        ]);

        // =====================================================================
        // PERBAIKAN: Buat Role "Pelamar" SPESIFIK untuk Unit 100
        // =====================================================================
        // Kita cari atau buat role yang unit_id-nya 100. 
        // Jangan pakai yang NULL (Global) agar tidak conflict.
        $role = Role::firstOrCreate(
            [
                'name' => 'Pelamar', 
                'guard_name' => 'web', 
                'unit_id' => $pelamarUnitId // <--- PENTING: Role ini milik Unit 100
            ]
        );

        // Pastikan Role Unit 100 ini punya permission
        if ($role->permissions->isEmpty()) {
            $role->syncPermissions([
                'applicant.data.view', 
                'careers.view',
                'recruitment.external.view',
                'recruitment.external.apply'
            ]);
        }

        // 3. Assign Role ke User (Manual Insert untuk Pivot)
        DB::table('model_has_roles')->insert([
            'role_id'    => $role->id,
            'model_type' => get_class($user),
            'model_id'   => $user->id,
            'unit_id'    => $pelamarUnitId // <--- PENTING: Pivot juga Unit 100
        ]);

        // Reset Cache Permission
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 4. Buat Data Person
        $person = Person::create([
            'full_name' => $user->name,
            'email'     => $user->email,
            'nik'       => $request->nik,
        ]);
        
        $user->person_id = $person->id;
        $user->save();

        event(new Registered($user));

        // 5. Login & Set Context
        Auth::login($user);

        // Set context team id ke 100 agar Spatie membaca Role Unit 100
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($pelamarUnitId);

        return redirect()->route('recruitment.applicant-data.index');
    }
}