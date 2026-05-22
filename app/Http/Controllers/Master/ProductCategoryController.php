<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger)
    {
    }

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $q = $this->getModelClass('ProductCategory', $info['scope'])::withCount('products')->whereIn('entity_scope', [$info['scope'], 'all']);

        if ($search = $request->search) {
            $q->where('name', 'like', "%$search%");
        }

        $categories = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('master.categories.index', [
            'categories' => $categories,
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

        $data['entity_scope'] = $request->input('entity_scope', $info['scope'] === 'gudang' ? 'all' : $info['scope']);

        $category = $this->getModelClass('ProductCategory', $info['scope'])::create($data);
        $this->logger->log('create', 'master.category', "Tambah kategori: {$category->name}", $category);

        return back()->with('success', "Kategori {$category->name} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $category = $this->getModelClass('ProductCategory', $info['scope'])::findOrFail($id);


        $data = $request->validate([
            'name' => 'required|string|max:100',

        ]);

        $category->update($data);
        $this->logger->log('update', 'master.category', "Update kategori: {$category->name}", $category);

        return back()->with('success', "Kategori {$category->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $category = $this->getModelClass('ProductCategory', $info['scope'])::findOrFail($id);


        if ($category->products()->count() > 0) {
            return back()->with('error', "Kategori {$category->name} tidak bisa dihapus karena masih digunakan produk.");
        }

        $name = $category->name;
        $category->delete();
        $this->logger->log('delete', 'master.category', "Hapus kategori: $name");

        return back()->with('success', "Kategori $name berhasil dihapus.");
    }
}
