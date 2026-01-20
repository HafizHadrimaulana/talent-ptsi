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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik'  => ['required', 'string', 'min:16', 'max:16', 'unique:persons,nik_hash'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ], [
            
            'email.unique' => 'Email tersebut sudah digunakan. Silakan gunakan email lain atau Login.',
            'nik.unique' => 'NIK sudah terdaftar di sistem kami.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'required' => 'Kolom :attribute wajib diisi.'
        ]);

        $pelamarUnitId = 100; 

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'unit_id' => $pelamarUnitId,
        ]);

        $role = Role::firstOrCreate(
            [
                'name' => 'Pelamar', 
                'guard_name' => 'web', 
                'unit_id' => $pelamarUnitId 
            ]
        );

        if ($role->permissions->isEmpty()) {
            $role->syncPermissions([
                'applicant.data.view', 
                'recruitment.external.view',
                'recruitment.external.apply'
            ]);
        }

        DB::table('model_has_roles')->insert([
            'role_id'    => $role->id,
            'model_type' => get_class($user),
            'model_id'   => $user->id,
            'unit_id'    => $pelamarUnitId
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $person = Person::create([
            'full_name' => $user->name,
            'email'     => $user->email,
            'nik_hash'  => $request->nik, 
            'nik_last4' => substr($request->nik, -4),
            'nik'       => null,
        ]);
        
        $user->person_id = $person->id;
        $user->save();

        event(new Registered($user));

        Auth::login($user);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($pelamarUnitId);

        return redirect()->route('recruitment.applicant-data.index');
    }
}