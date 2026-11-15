<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    // Proses login memakai Auth::attempt (Modul 13)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (! Auth::user()->is_active) {
                Auth::logout();

                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Akun kamu dinonaktifkan admin. Hubungi admin FindIt Campus.']);
            }

            $request->session()->regenerate();
            $home = Auth::user()->isAdmin() ? route('admin.dashboard') : route('map');

            return redirect()->intended($home)->with('success', 'Selamat datang, '.Auth::user()->short_name.'!');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'Email atau kata sandi salah.']);
    }

    public function showRegister()
    {
        return view('auth.register', ['domains' => config('findit.email_domains')]);
    }

    public function register(Request $request)
    {
        $domains = config('findit.email_domains');

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:80',
            'nim' => 'required|digits_between:6,20|unique:users,nim',
            'email' => [
                'required', 'email', 'max:120', 'unique:users,email',
                function ($attribute, $value, $fail) use ($domains) {
                    if ($domains && ! Str::endsWith(Str::lower($value), array_map(fn ($d) => '@'.$d, $domains))) {
                        $fail('Gunakan email kampus ('.implode(' atau ', array_map(fn ($d) => '@'.$d, $domains)).').');
                    }
                },
            ],
            'password' => 'required|min:8|confirmed',
        ]);

        $validated['email'] = Str::lower($validated['email']);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('map')->with('success', 'Akun berhasil dibuat. Selamat datang di FindIt Campus!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu sudah keluar.');
    }
}
