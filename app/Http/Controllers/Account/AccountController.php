<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['required','string','max:100'],
            'phone' => ['nullable','string','max:50'],
        ]);

        $user->fill($validated)->save();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Profile updated']);
        }
        return back()->with('ok','Profile updated');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password'      => ['required','string'],
            'password'              => ['required','string','min:8','confirmed'],
            'password_confirmation' => ['required','string','min:8'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            // AJAX → balikin 422 dgn struktur errors Laravel
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['current_password' => ['Current password is incorrect']],
                ], 422);
            }

            // Non-AJAX fallback
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect'])
                ->with('modal','changePassword');
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        if ($request->wantsJson()) {
            // 204 → sukses tanpa body (pas buat modal + Swal loading)
            return response()->noContent();
        }

        return back()->with('ok','Password updated')->with('modal','');
    }
}
