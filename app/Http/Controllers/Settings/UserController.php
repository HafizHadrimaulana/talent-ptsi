<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Guard; // ⬅ penting

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $users = User::query()
            ->when($q, function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%$q%")
                      ->orWhere('email', 'like', "%$q%");
                });
            })
            ->with('roles:id,name')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Guard default utk model User (cara aman via Spatie)
        $guard = Guard::getDefaultName(User::class);

        $roles = Role::where('guard_name', $guard)
            ->orderBy('name')
            ->get(['id','name']);

        return view('settings.users.index', compact('users','q','roles'));
    }

    public function store(Request $request)
    {
        $guard = Guard::getDefaultName(User::class);

        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','unique:users,email'],
            'password' => ['required','min:6'],
            'roles'    => ['array'],
            'roles.*'  => ['integer', Rule::exists('roles','id')->where(fn($q)=>$q->where('guard_name',$guard))],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'unit_id'  => auth()->user()->unit_id ?? null,
        ]);

        $roles = Role::where('guard_name',$guard)
            ->whereIn('id', $data['roles'] ?? [])
            ->get();

        $user->syncRoles($roles);

        return back()->with('ok','User created');
    }

    public function update(Request $request, User $user)
    {
        $guard = Guard::getDefaultName(User::class);

        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','min:6'],
            'roles'    => ['array'],
            'roles.*'  => ['integer', Rule::exists('roles','id')->where(fn($q)=>$q->where('guard_name',$guard))],
        ]);

        $payload = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];
        if (!empty($data['password'])) {
            $payload['password'] = bcrypt($data['password']);
        }
        $user->update($payload);

        $roles = Role::where('guard_name',$guard)
            ->whereIn('id', $data['roles'] ?? [])
            ->get();

        $user->syncRoles($roles);

        return back()->with('ok','User updated');
    }

    public function destroy(User $user)
    {
        abort_if(auth()->id() === $user->id, 403, 'Tidak boleh menghapus diri sendiri.');
        $user->delete();
        return back()->with('ok','User deleted');
    }
}
