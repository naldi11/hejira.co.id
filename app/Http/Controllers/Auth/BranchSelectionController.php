<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchSelectionController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // Safe check if database migration hasn't been run on production yet
        if (\Illuminate\Support\Facades\Schema::hasColumn('master_branches', 'entity')) {
            $branches = Branch::where('entity', $user->entity)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'type']);
        } else {
            $allBranches = Branch::where('is_active', true)->orderBy('name')->get();
            $branches = $allBranches->filter(function ($b) use ($user) {
                $code = strtoupper(trim($b->code ?? ''));
                if ($user->entity === 'hendhys') {
                    return str_starts_with($code, 'HB') || str_starts_with($code, 'HND');
                } elseif ($user->entity === 'jihans') {
                    return str_starts_with($code, 'JF') || str_starts_with($code, 'IZ');
                } elseif ($user->entity === 'gudang') {
                    return str_starts_with($code, 'GD') || str_starts_with($code, 'GU');
                }
                return false;
            })->values();
        }

        return Inertia::render('Auth/SelectBranch', [
            'branches' => $branches,
            'current_branch_id' => session('active_branch_id') ?: $user->branch_id,
        ]);
    }

    public function select(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'branch_id' => 'required|exists:master_branches,id',
        ]);

        if (\Illuminate\Support\Facades\Schema::hasColumn('master_branches', 'entity')) {
            $branch = Branch::where('entity', $user->entity)
                ->where('is_active', true)
                ->findOrFail($request->branch_id);
        } else {
            $branch = Branch::where('is_active', true)->findOrFail($request->branch_id);
            $code = strtoupper(trim($branch->code ?? ''));
            $allowed = false;
            if ($user->entity === 'hendhys') {
                $allowed = str_starts_with($code, 'HB') || str_starts_with($code, 'HND');
            } elseif ($user->entity === 'jihans') {
                $allowed = str_starts_with($code, 'JF') || str_starts_with($code, 'IZ');
            } elseif ($user->entity === 'gudang') {
                $allowed = str_starts_with($code, 'GD') || str_starts_with($code, 'GU');
            }
            if (!$allowed) {
                abort(403, "Cabang tidak sesuai dengan entitas bisnis.");
            }
        }

        session(['active_branch_id' => $branch->id]);

        return redirect()->route('dashboard')->with('success', "Cabang aktif diubah ke {$branch->name}.");
    }
}
