<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $q = Brand::withCount('products');

        if ($search = $request->search) {
            $q->where('name', 'like', "%$search%");
        }

        $brands = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('master.brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);

        $brand = Brand::create($data);
        $this->logger->log('create', 'master.brand', "Tambah brand: {$brand->name}", $brand);

        return back()->with('success', "Brand {$brand->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);

        $brand->update($data);
        $this->logger->log('update', 'master.brand', "Update brand: {$brand->name}", $brand);

        return back()->with('success', "Brand {$brand->name} berhasil diperbarui.");
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->count() > 0) {
            return back()->with('error', "Brand {$brand->name} tidak bisa dihapus karena masih digunakan produk.");
        }

        $name = $brand->name;
        $brand->delete();
        $this->logger->log('delete', 'master.brand', "Hapus brand: $name");

        return back()->with('success', "Brand $name berhasil dihapus.");
    }
}
