<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Akun yang dinonaktifkan admin langsung dikeluarkan pada request berikutnya,
// tidak menunggu sampai pengguna logout sendiri
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun kamu dinonaktifkan admin.'], 403);
            }

            return redirect()->route('login')->withErrors(['email' => 'Akun kamu dinonaktifkan admin. Hubungi admin FindIt Campus.']);
        }

        return $next($request);
    }
}
