<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $q = Unit::withCount('products');

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$search%")
                                   ->orWhere('abbreviation', 'like', "%$search%"));
        }

        $units = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('master.units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:50',
            'abbreviation' => 'required|string|max:10',
        ]);

        $unit = Unit::create($data);
        $this->logger->log('create', 'master.unit', "Tambah satuan: {$unit->name}", $unit);

        return back()->with('success', "Satuan {$unit->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:50',
            'abbreviation' => 'required|string|max:10',
        ]);

        $unit->update($data);
        $this->logger->log('update', 'master.unit', "Update satuan: {$unit->name}", $unit);

        return back()->with('success', "Satuan {$unit->name} berhasil diperbarui.");
    }

    public function destroy(Unit $unit)
    {
        if ($unit->products()->count() > 0) {
            return back()->with('error', "Satuan {$unit->name} tidak bisa dihapus karena masih digunakan produk.");
        }

        $name = $unit->name;
        $unit->delete();
        $this->logger->log('delete', 'master.unit', "Hapus satuan: $name");

        return back()->with('success', "Satuan $name berhasil dihapus.");
    }
}
