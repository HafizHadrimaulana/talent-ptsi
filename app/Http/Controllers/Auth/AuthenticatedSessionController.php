<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

public function store(Request $request)
{
    $request->validate([
        'email' => ['required','string'], // bisa email atau employee_id
        'password' => ['required','string'],
    ]);

    $login = $request->input('email');
    $password = $request->input('password');

    // deteksi input: jika format email => pakai email, else coba employee_id
    $credentials = filter_var($login, FILTER_VALIDATE_EMAIL)
        ? ['email' => $login, 'password' => $password]
        : ['employee_id' => $login, 'password' => $password];

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    throw ValidationException::withMessages([
        'email' => __('These credentials do not match our records.'),
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
