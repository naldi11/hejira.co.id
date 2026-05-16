<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $q = Branch::withCount('users');

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$search%")
                                   ->orWhere('code', 'like', "%$search%"));
        }

        $branches = $q->orderByRaw("FIELD(type,'pusat','cabang')")->orderBy('name')->paginate(20)->withQueryString();

        return view('master.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('master.branches.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'      => 'required|string|max:20|unique:master_branches,code',
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:pusat,cabang',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $branch = Branch::create($data);
        $this->logger->log('create', 'master.branch', "Tambah cabang: {$branch->name}", $branch);

        return redirect()->route('master.branches.index')->with('success', "Cabang {$branch->name} berhasil ditambahkan.");
    }

    public function edit(Branch $branch)
    {
        return view('master.branches.form', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'code'      => 'required|string|max:20|unique:master_branches,code,' . $branch->id,
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:pusat,cabang',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $old = $branch->toArray();
        $data['is_active'] = $request->boolean('is_active', true);
        $branch->update($data);

        $this->logger->log('update', 'master.branch', "Update cabang: {$branch->name}", $branch, $old, $branch->fresh()->toArray());

        return redirect()->route('master.branches.index')->with('success', "Cabang {$branch->name} berhasil diperbarui.");
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->count() > 0) {
            return back()->with('error', "Cabang {$branch->name} tidak bisa dihapus karena masih memiliki user aktif.");
        }

        $name = $branch->name;
        $branch->delete();
        $this->logger->log('delete', 'master.branch', "Hapus cabang: $name");

        return redirect()->route('master.branches.index')->with('success', "Cabang $name berhasil dihapus.");
    }
}
