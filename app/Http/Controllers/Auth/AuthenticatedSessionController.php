<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        // Validasi: form pakai "login" (email atau employee_id) + password
        $request->validate([
            'login'    => ['required','string'],
            'password' => ['required','string'],
        ]);

        $login    = $request->input('login');
        $password = $request->input('password');

        // Deteksi: kalau valid email → pakai kolom "email", selain itu → "employee_id"
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'employee_id';

        $credentials = [
            $field     => $login,
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'login' => __('These credentials do not match our records.'),
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
