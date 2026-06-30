<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SaveBranchRequest;
use App\Http\Resources\Master\BranchResource;
use App\Models\Branch;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $branches = Branch::withCount('users')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")))
            ->orderByRaw("CASE type WHEN 'pusat' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return Inertia::render('Master/Branches/Index', [
            'branches' => BranchResource::collection($branches),
            'filters'  => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Branches/Form');
    }

    public function store(SaveBranchRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $branch = Branch::create($data);
        $this->logger->log('create', 'master.branch', "Tambah cabang: {$branch->name}", $branch);

        return redirect()->route('master.branches.index')->with('success', "Cabang {$branch->name} berhasil ditambahkan.");
    }

    public function edit(Branch $branch)
    {
        return Inertia::render('Master/Branches/Form', [
            'branch' => new BranchResource($branch),
        ]);
    }

    public function update(SaveBranchRequest $request, Branch $branch)
    {
        $old = $branch->toArray();
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $branch->update($data);

        $this->logger->log('update', 'master.branch', "Update cabang: {$branch->name}", $branch, $old, $branch->fresh()->toArray());

        return redirect()->route('master.branches.index')->with('success', "Cabang {$branch->name} berhasil diperbarui.");
    }

    public function destroy(Branch $branch)
    {
        $name = $branch->name;
        $blockers = [];

        // Cek semua data terkait yang mencegah penghapusan
        if ($branch->users()->count() > 0) {
            $blockers[] = $branch->users()->count() . ' user';
        }

        // Cek stok Hendhys cabang
        if (\App\Models\HendhysStockBranch::where('branch_id', $branch->id)->exists()) {
            $blockers[] = 'data stok cabang';
        }

        // Cek transaksi Hendhys
        if (\App\Models\HendhysTransaction::where('branch_id', $branch->id)->exists()) {
            $blockers[] = 'riwayat transaksi';
        }

        // Cek transfer ke cabang
        if (\App\Models\HendhysTransferToBranch::where('branch_id', $branch->id)->exists()) {
            $blockers[] = 'riwayat transfer';
        }

        // Cek branch requests
        if (\App\Models\HendhysBranchRequest::where('branch_id', $branch->id)->exists()) {
            $blockers[] = 'data request stok';
        }

        // Cek returns from branch
        if (\App\Models\HendhysReturnFromBranch::where('branch_id', $branch->id)->exists()) {
            $blockers[] = 'data return barang';
        }

        if (!empty($blockers)) {
            return back()->with('error',
                "Cabang \"$name\" tidak bisa dihapus karena masih memiliki: " .
                implode(', ', $blockers) . ". " .
                "Nonaktifkan cabang jika tidak ingin digunakan lagi."
            );
        }

        try {
            $branch->delete();
            $this->logger->log('delete', 'master.branch', "Hapus cabang: $name");
            return redirect()->route('master.branches.index')->with('success', "Cabang $name berhasil dihapus.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error',
                "Cabang \"$name\" tidak bisa dihapus karena masih ada data yang terhubung. " .
                "Nonaktifkan cabang jika tidak ingin digunakan lagi."
            );
        }
    }
}
