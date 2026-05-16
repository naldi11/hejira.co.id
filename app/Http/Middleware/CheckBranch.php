<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('kasir_hendhys') && !$user->branch_id) {
            abort(403, 'Akun Anda belum memiliki cabang yang ditetapkan. Hubungi Admin Gudang.');
        }

        return $next($request);
    }
}
