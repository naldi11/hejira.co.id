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

        // Get active branches for the user's entity
        $branches = Branch::where('entity', $user->entity)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

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

        $branch = Branch::where('entity', $user->entity)
            ->where('is_active', true)
            ->findOrFail($request->branch_id);

        session(['active_branch_id' => $branch->id]);

        return redirect()->route('dashboard')->with('success', "Cabang aktif diubah ke {$branch->name}.");
    }
}
