<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = Product::with(['category', 'unit', 'brand']);

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$search%")
                                   ->orWhere('code', 'like', "%$search%")
                                   ->orWhere('barcode', 'like', "%$search%"));
        }

        if ($request->filled('jenis'))        $q->where('jenis', $request->jenis);
        if ($request->filled('entity_scope')) $q->where('entity_scope', $request->entity_scope);
        if ($request->filled('status'))       $q->where('status', $request->status);

        $products = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('master.products.index', compact('products'));
    }

    public function create()
    {
        $categories = ProductCategory::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();

        return view('master.products.form', compact('categories', 'units', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'barcode'       => 'nullable|string|max:50|unique:master_products,barcode',
            'category_id'   => 'required|exists:master_product_categories,id',
            'unit_id'       => 'required|exists:master_units,id',
            'brand_id'      => 'nullable|exists:master_brands,id',
            'rack'          => 'nullable|string|max:20',
            'jenis'         => 'required|in:frozen,tortilla,bakery,bahan_baku,aksesoris,minuman,snack,selai,property,lainnya',
            'hpp'           => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_min'     => 'required|integer|min:0',
            'ppn_type'      => 'required|in:none,include,exclude',
            'ppn_rate'      => 'required|numeric|min:0|max:100',
            'product_type'  => 'required|in:INV,NON',
            'entity_scope'  => 'required|in:gudang,jihans,hendhys,all',
            'status'        => 'required|in:active,discontinued',
            'notes'         => 'nullable|string',
        ]);

        $data['code']       = $this->numbers->generate('PRD', 'master_products', 'code');
        $data['created_by'] = auth()->id();

        $product = Product::create($data);
        $this->logger->log('create', 'master.product', "Tambah produk: {$product->name}", $product);

        return redirect()->route('master.products.index')->with('success', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();

        return view('master.products.form', compact('product', 'categories', 'units', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'barcode'       => 'nullable|string|max:50|unique:master_products,barcode,' . $product->id,
            'category_id'   => 'required|exists:master_product_categories,id',
            'unit_id'       => 'required|exists:master_units,id',
            'brand_id'      => 'nullable|exists:master_brands,id',
            'rack'          => 'nullable|string|max:20',
            'jenis'         => 'required|in:frozen,tortilla,bakery,bahan_baku,aksesoris,minuman,snack,selai,property,lainnya',
            'hpp'           => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_min'     => 'required|integer|min:0',
            'ppn_type'      => 'required|in:none,include,exclude',
            'ppn_rate'      => 'required|numeric|min:0|max:100',
            'product_type'  => 'required|in:INV,NON',
            'entity_scope'  => 'required|in:gudang,jihans,hendhys,all',
            'status'        => 'required|in:active,discontinued',
            'notes'         => 'nullable|string',
        ]);

        $old = $product->toArray();
        $product->update($data);

        $this->logger->log('update', 'master.product', "Update produk: {$product->name}", $product, $old, $product->fresh()->toArray());

        return redirect()->route('master.products.index')->with('success', "Produk {$product->name} berhasil diperbarui.");
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        $product->delete();
        $this->logger->log('delete', 'master.product', "Hapus produk: $name");

        return redirect()->route('master.products.index')->with('success', "Produk $name berhasil dihapus.");
    }
}
