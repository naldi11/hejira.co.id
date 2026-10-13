<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->header('X-Inertia')) {
                return \Inertia\Inertia::location(route('login'));
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun Anda sudah dinonaktifkan.'], 401);
            }

            return redirect()->route('login')->withErrors(['email' => 'Akun Anda sudah dinonaktifkan.']);
        }

        return $next($request);
    }
}
