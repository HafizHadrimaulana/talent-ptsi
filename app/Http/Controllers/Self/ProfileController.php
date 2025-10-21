<?php

namespace App\Http\Controllers\Self;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required','string'],
            'new_password'     => ['required','string','min:8','confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.'])->with('pw_modal', true);
        }

        $user->forceFill([
            'password' => Hash::make($data['new_password']),
        ])->save();

        return back()->with('pw_changed', true);
    }
}
