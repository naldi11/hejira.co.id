<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEntity
{
    public function handle(Request $request, Closure $next, string $entity): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->entity, [$entity, 'all'])) {
            abort(403, 'Akses ditolak. Anda tidak memiliki akses ke entitas ini.');
        }

        return $next($request);
    }
}
