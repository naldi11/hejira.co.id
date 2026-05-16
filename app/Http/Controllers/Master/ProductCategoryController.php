<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $q = ProductCategory::withCount('products');

        if ($search = $request->search) {
            $q->where('name', 'like', "%$search%");
        }

        $categories = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('master.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'entity' => 'required|in:gudang,jihans,hendhys,all',
        ]);

        $category = ProductCategory::create($data);
        $this->logger->log('create', 'master.category', "Tambah kategori: {$category->name}", $category);

        return back()->with('success', "Kategori {$category->name} berhasil ditambahkan.");
    }

    public function update(Request $request, ProductCategory $category)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'entity' => 'required|in:gudang,jihans,hendhys,all',
        ]);

        $category->update($data);
        $this->logger->log('update', 'master.category', "Update kategori: {$category->name}", $category);

        return back()->with('success', "Kategori {$category->name} berhasil diperbarui.");
    }

    public function destroy(ProductCategory $category)
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', "Kategori {$category->name} tidak bisa dihapus karena masih digunakan produk.");
        }

        $name = $category->name;
        $category->delete();
        $this->logger->log('delete', 'master.category', "Hapus kategori: $name");

        return back()->with('success', "Kategori $name berhasil dihapus.");
    }
}
