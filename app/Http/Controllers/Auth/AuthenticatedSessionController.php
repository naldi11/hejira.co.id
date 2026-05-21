<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $user->update(['last_login_at' => now()]);

        return redirect()->intended($this->redirectRoute($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectRoute(User $user): string
    {
        if ($user->hasRole('owner'))
            return route('owner.dashboard');
        if ($user->hasRole('admin_gudang'))
            return route('gudang.dashboard');
        if ($user->hasRole(['kasir_jihans', 'admin_jihans']))
            return route('jihans.dashboard');
        if ($user->hasRole('kasir_hendhys'))
            return route('hendhys.pos.index');

        return route('login');
    }
}
