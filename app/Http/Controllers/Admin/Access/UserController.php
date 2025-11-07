<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $users = User::query()
            ->when($q, function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->with('roles:id,name')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $guard = Guard::getDefaultName(User::class);

        $roles = Role::where('guard_name', $guard)
            // ->where(function($q){
            //     /** @var \App\Models\User|null $me */
            //     $me = Auth::user();
            //     $q->whereNull('unit_id')->orWhere('unit_id', $me?->unit_id);
            // })
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.users.index', compact('users','q','roles'));
    }

    public function store(Request $request)
    {
        $guard = Guard::getDefaultName(User::class);

        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','unique:users,email'],
            'password' => ['required','min:6'],
            'roles'    => ['nullable','array'],
            'roles.*'  => ['integer', Rule::exists('roles','id')->where(fn($q)=>$q->where('guard_name',$guard))],
        ]);

        /** @var \App\Models\User|null $me */
        $me = Auth::user();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'unit_id'  => $me?->unit_id, // aman buat analyzer
        ]);

        $roleModels = empty($data['roles'])
            ? collect()
            : Role::where('guard_name',$guard)->whereIn('id', $data['roles'])->get();

        $user->syncRoles($roleModels);

        return back()->with('ok','User created');
    }

    public function update(Request $request, User $user)
    {
        $guard = Guard::getDefaultName(User::class);

        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','min:6'],
            'roles'    => ['nullable','array'],
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

        $roleModels = empty($data['roles'])
            ? collect()
            : Role::where('guard_name',$guard)->whereIn('id', $data['roles'])->get();

        $user->syncRoles($roleModels);

        return back()->with('ok','User updated');
    }

    public function destroy(User $user)
    {
        $meId = Auth::id();
        abort_if($meId === $user->id, 403, 'Tidak boleh menghapus diri sendiri.');
        $user->delete();
        return back()->with('ok','User deleted');
    }

    public function roleOptions(Request $request): JsonResponse
    {
        $guard = Guard::getDefaultName(User::class);

        $roles = Role::where('guard_name', $guard)
            // ->where(function($q){
            //     /** @var \App\Models\User|null $me */
            //     $me = Auth::user();
            //     $q->whereNull('unit_id')->orWhere('unit_id', $me?->unit_id);
            // })
            ->orderBy('name')
            ->get(['id','name']);

        $assigned = collect();

        if ($request->filled('user_id')) {
            $u = User::with('roles:id')->find($request->integer('user_id'));
            if ($u) {
                $assigned = $u->roles->pluck('id')->values();
            }
        }

        return response()->json([
            'roles'    => $roles,
            'assigned' => $assigned,
        ]);
    }
}
