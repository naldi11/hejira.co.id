<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\CashierShift;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveShift
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Check active shift for kasir
        if ($user && ($user->hasRole('kasir_hendhys') || $user->hasRole('kasir_jihans'))) {
            $activeShift = CashierShift::where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            if (!$activeShift) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'Laci kasir belum dibuka. Anda harus membuka shift terlebih dahulu.',
                        'shift_required' => true,
                    ], 403);
                }
                
                $laciRoute = ($user->entity === 'jihans') ? 'jihans.reports.laci' : 'hendhys.reports.laci';
                return redirect()->route($laciRoute)->with('error', 'Akses POS Dibatasi! Silakan lakukan pembukaan shift dengan menginput saldo modal awal terlebih dahulu.');
            }
        }

        return $next($request);
    }
}
