<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger)
    {
    }

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $q = $this->getModelClass('Unit', $info['scope'])::withCount('products')->whereIn('entity_scope', [$info['scope'], 'all']);

        if ($search = $request->search) {
            $q->where(fn($w) => $w->where('name', 'like', "%$search%")
                ->orWhere('abbreviation', 'like', "%$search%"));
        }

        $units = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('master.units.index', [
            'units' => $units,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'abbreviation' => 'required|string|max:10',
        ]);

        $data['entity_scope'] = $request->input('entity_scope', $info['scope'] === 'gudang' ? 'all' : $info['scope']);

        $unit = $this->getModelClass('Unit', $info['scope'])::create($data);
        $this->logger->log('create', 'master.unit', "Tambah satuan: {$unit->name}", $unit);

        return back()->with('success', "Satuan {$unit->name} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $unit = $this->getModelClass('Unit', $info['scope'])::findOrFail($id);


        $data = $request->validate([
            'name' => 'required|string|max:50',
            'abbreviation' => 'required|string|max:10',

        ]);

        $unit->update($data);
        $this->logger->log('update', 'master.unit', "Update satuan: {$unit->name}", $unit);

        return back()->with('success', "Satuan {$unit->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $unit = $this->getModelClass('Unit', $info['scope'])::findOrFail($id);


        if ($unit->products()->count() > 0) {
            return back()->with('error', "Satuan {$unit->name} tidak bisa dihapus karena masih digunakan produk.");
        }

        $name = $unit->name;
        $unit->delete();
        $this->logger->log('delete', 'master.unit', "Hapus satuan: $name");

        return back()->with('success', "Satuan $name berhasil dihapus.");
    }
}
