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

        if ($user && $user->hasAnyRole(['kasir_hendhys', 'admin_hendhys', 'super_admin_hendhys', 'kasir_jihans', 'admin_jihans', 'super_admin_jihans'])) {
            $activeBranchId = session('active_branch_id');

            if ($activeBranchId) {
                $user->branch_id = $activeBranchId;
                $user->unsetRelation('branch');
                $branch = \App\Models\Branch::find($activeBranchId);
                if ($branch) {
                    $user->setRelation('branch', $branch);
                } else {
                    session()->forget('active_branch_id');
                    $activeBranchId = null;
                }
            }

            if (!$activeBranchId && $user->branch_id) {
                session(['active_branch_id' => $user->branch_id]);
                $user->unsetRelation('branch');
                $branch = \App\Models\Branch::find($user->branch_id);
                if ($branch) {
                    $user->setRelation('branch', $branch);
                }
                $activeBranchId = $user->branch_id;
            }

            if (!$activeBranchId && !$request->routeIs('select-branch') && !$request->routeIs('select-branch.post')) {
                return redirect()->route('select-branch');
            }
        }

        return $next($request);
    }

}
