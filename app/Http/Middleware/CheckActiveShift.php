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
        
        // Time validation (00:01 to 06:59)
        $now = now()->timezone('Asia/Jakarta');
        $currentTime = $now->format('H:i');
        if ($currentTime >= '00:01' && $currentTime <= '06:59') {
            if (($request->wantsJson() || $request->ajax()) && !$request->hasHeader('X-Inertia')) {
                return response()->json([
                    'error' => 'Sistem kasir tutup. Silakan lanjutkan pada pukul 07:00 WIB.',
                    'shift_required' => true, // Flag for frontend to redirect if needed
                ], 403);
            }
            
            $laciRoute = ($user && $user->entity === 'jihans') ? 'jihans.reports.laci' : 'hendhys.reports.laci';
            return redirect()->route($laciRoute)->with('error', 'Sistem kasir sudah tutup! Silakan lanjutkan pada pukul 07:00 WIB.');
        }
        
        // Check active shift for kasir
        if ($user && ($user->hasRole('kasir_hendhys') || $user->hasRole('kasir_jihans'))) {
            $activeShift = CashierShift::where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            if (!$activeShift) {
                if (($request->wantsJson() || $request->ajax()) && !$request->hasHeader('X-Inertia')) {
                    return response()->json([
                        'error' => 'Laci kasir belum dibuka. Anda harus membuka shift terlebih dahulu.',
                        'shift_required' => true,
                    ], 403);
                }
                
                $laciRoute = ($user->entity === 'jihans') ? 'jihans.reports.laci' : 'hendhys.reports.laci';
                return redirect()->route($laciRoute)->with('error', 'Akses POS Dibatasi! Silakan lakukan pembukaan shift dengan menginput saldo modal awal terlebih dahulu.');
            }
            
            // Branch mismatch validation
            if ($activeShift->branch_id !== $user->branch_id) {
                if (($request->wantsJson() || $request->ajax()) && !$request->hasHeader('X-Inertia')) {
                    return response()->json([
                        'error' => 'Cabang Anda saat ini (' . ($user->branch->name ?? 'Pusat') . ') tidak sesuai dengan cabang shift aktif. Harap tutup shift sebelumnya terlebih dahulu.',
                        'shift_required' => true,
                    ], 403);
                }
                
                $laciRoute = ($user->entity === 'jihans') ? 'jihans.reports.laci' : 'hendhys.reports.laci';
                return redirect()->route($laciRoute)->with('error', 'Cabang Anda saat ini (' . ($user->branch->name ?? 'Pusat') . ') tidak sesuai dengan cabang shift aktif. Harap tutup shift sebelumnya terlebih dahulu.');
            }
        }

        return $next($request);
    }
}
