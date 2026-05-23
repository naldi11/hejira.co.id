<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger)
    {
    }

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $q = $this->getModelClass('Brand', $info['scope'])::withCount('products')->where('visible_' . $info['scope'], true);

        if ($search = $request->search) {
            $q->where('name', 'like', "%$search%");
        }

        $brands = $q->orderBy('name')->get();

        return view('master.brands.index', [
            'brands' => $brands,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $data['entity_scope']    = $request->input('entity_scope', $info['scope'] === 'gudang' ? 'all' : $info['scope']);
        $data['visible_gudang']  = $request->boolean('visible_gudang',  $info['scope'] === 'gudang');
        $data['visible_jihans']  = $request->boolean('visible_jihans',  in_array($info['scope'], ['gudang', 'jihans']));
        $data['visible_hendhys'] = $request->boolean('visible_hendhys', in_array($info['scope'], ['gudang', 'hendhys']));

        $brand = $this->getModelClass('Brand', $info['scope'])::create($data);
        $this->logger->log('create', 'master.brand', "Tambah brand: {$brand->name}", $brand);

        return back()->with('success', "Brand {$brand->name} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $brand = $this->getModelClass('Brand', $info['scope'])::findOrFail($id);


        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'entity_scope' => 'nullable|in:all,gudang,jihans,hendhys',
        ]);

        if ($request->filled('entity_scope')) {
            $data['entity_scope'] = $request->entity_scope;
        }
        $data['visible_gudang']  = $request->boolean('visible_gudang');
        $data['visible_jihans']  = $request->boolean('visible_jihans');
        $data['visible_hendhys'] = $request->boolean('visible_hendhys');

        $brand->update($data);
        $this->logger->log('update', 'master.brand', "Update brand: {$brand->name}", $brand);

        return back()->with('success', "Brand {$brand->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $brand = $this->getModelClass('Brand', $info['scope'])::findOrFail($id);


        if ($brand->products()->count() > 0) {
            return back()->with('error', "Brand {$brand->name} tidak bisa dihapus karena masih digunakan produk.");
        }

        $name = $brand->name;
        $brand->delete();
        $this->logger->log('delete', 'master.brand', "Hapus brand: $name");

        return back()->with('success', "Brand $name berhasil dihapus.");
    }
}
