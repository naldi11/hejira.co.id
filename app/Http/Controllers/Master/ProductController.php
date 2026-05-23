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
    use ScopesMasterData;

    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {
    }

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $modelClass = $this->getModelClass('Product', $info['scope']);

        $q = $modelClass::with(['category', 'unit', 'brand', 'tieredPrices'])
            ->where('visible_' . $info['scope'], true);

        if ($search = $request->search) {
            $q->where(fn($w) => $w->where('name', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")
                ->orWhere('barcode', 'like', "%$search%"));
        }

        if ($request->filled('visibility')) {
            $q->where('visible_' . $request->visibility, true);
        }
        if ($request->filled('status'))
            $q->where('status', $request->status);

        $products = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('master.products.index', [
            'products' => $products,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $categories = $this->getModelClass('ProductCategory', $info['scope'])::orderBy('name')->get();
        $units = $this->getModelClass('Unit', $info['scope'])::orderBy('name')->get();
        $brands = $this->getModelClass('Brand', $info['scope'])::orderBy('name')->get();

        return view('master.products.form', [
            'categories' => $categories,
            'units' => $units,
            'brands' => $brands,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $tableName = 'master_products';

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'barcode' => "nullable|string|max:50|unique:{$tableName},barcode",
            'category_id' => "required|exists:master_product_categories,id",
            'unit_id' => "required|exists:master_units,id",
            'brand_id' => "nullable|exists:master_brands,id",
            'rack' => 'nullable|string|max:20',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_min' => 'required|integer|min:0',
            'ppn_type' => 'required|in:none,include,exclude',
            'ppn_rate' => 'required|numeric|min:0|max:100',
            'product_type'       => 'required|in:INV,NON',
            'status'             => 'required|in:active,discontinued',
            'visible_gudang'     => 'boolean',
            'visible_jihans'     => 'boolean',
            'visible_hendhys'    => 'boolean',
            'notes'              => 'nullable|string',
            'image'              => 'nullable|image|max:2048',
            'tiered_prices'      => 'nullable|array',
            'tiered_prices.*.min_qty' => 'required_with:tiered_prices|numeric|min:1',
            'tiered_prices.*.price'   => 'required_with:tiered_prices|numeric|min:0',
        ], [
            'barcode.unique' => 'Gagal menyimpan produk: Barcode sudah terdaftar dan digunakan oleh produk lain. Silakan periksa kembali barcode yang dimasukkan.',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['code']            = $this->numbers->generate('PRD', $tableName, 'code');
        $data['created_by']      = auth()->id();
        $data['visible_gudang']  = $request->boolean('visible_gudang');
        $data['visible_jihans']  = $request->boolean('visible_jihans');
        $data['visible_hendhys'] = $request->boolean('visible_hendhys');
        // Simpan entity_scope dari entitas aktif untuk kompatibilitas
        $data['entity_scope']    = $info['scope'] === 'gudang' ? 'all' : $info['scope'];

        
        $product = $this->getModelClass('Product', $info['scope'])::create($data);
        
        if ($request->has('tiered_prices') && is_array($request->tiered_prices)) {
            foreach ($request->tiered_prices as $tier) {
                if (!empty($tier['min_qty']) && !empty($tier['price'])) {
                    $product->tieredPrices()->create([
                        'min_qty' => $tier['min_qty'],
                        'price' => $tier['price']
                    ]);
                }
            }
        }
        
        $this->logger->log('create', 'master.product', "Tambah produk: {$product->name}", $product);


        return redirect()->route($info['route'] . 'products.index')->with('success', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $product = $this->getModelClass('Product', $info['scope'])::with('tieredPrices')->findOrFail($id);

        $categories = $this->getModelClass('ProductCategory', $info['scope'])::orderBy('name')->get();
        $units = $this->getModelClass('Unit', $info['scope'])::orderBy('name')->get();
        $brands = $this->getModelClass('Brand', $info['scope'])::orderBy('name')->get();

        return view('master.products.form', [
            'product' => $product,
            'categories' => $categories,
            'units' => $units,
            'brands' => $brands,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function update(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $product = $this->getModelClass('Product', $info['scope'])::with('tieredPrices')->findOrFail($id);
        $tableName = 'master_products';

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'barcode' => "nullable|string|max:50|unique:{$tableName},barcode," . $product->id,
            'category_id' => "required|exists:master_product_categories,id",
            'unit_id' => "required|exists:master_units,id",
            'brand_id' => "nullable|exists:master_brands,id",
            'rack' => 'nullable|string|max:20',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_min' => 'required|integer|min:0',
            'ppn_type' => 'required|in:none,include,exclude',
            'ppn_rate' => 'required|numeric|min:0|max:100',
            'product_type'    => 'required|in:INV,NON',
            'status'          => 'required|in:active,discontinued',
            'visible_gudang'  => 'boolean',
            'visible_jihans'  => 'boolean',
            'visible_hendhys' => 'boolean',
            'notes'           => 'nullable|string',
            'image'           => 'nullable|image|max:2048',
            'tiered_prices'   => 'nullable|array',
            'tiered_prices.*.min_qty' => 'required_with:tiered_prices|numeric|min:1',
            'tiered_prices.*.price'   => 'required_with:tiered_prices|numeric|min:0',
        ], [
            'barcode.unique' => 'Gagal memperbarui produk: Barcode sudah terdaftar dan digunakan oleh produk lain. Silakan periksa kembali barcode yang dimasukkan.',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['visible_gudang']  = $request->boolean('visible_gudang');
        $data['visible_jihans']  = $request->boolean('visible_jihans');
        $data['visible_hendhys'] = $request->boolean('visible_hendhys');

        $old = $product->toArray();

        $product->update($data);
        
        $product->tieredPrices()->delete();
        if ($request->has('tiered_prices') && is_array($request->tiered_prices)) {
            foreach ($request->tiered_prices as $tier) {
                if (!empty($tier['min_qty']) && !empty($tier['price'])) {
                    $product->tieredPrices()->create([
                        'min_qty' => $tier['min_qty'],
                        'price' => $tier['price']
                    ]);
                }
            }
        }


        $this->logger->log('update', 'master.product', "Update produk: {$product->name}", $product, $old, $product->fresh()->toArray());

        return redirect()->route($info['route'] . 'products.index')->with('success', "Produk {$product->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $product = $this->getModelClass('Product', $info['scope'])::with('tieredPrices')->findOrFail($id);

        $name = $product->name;
        $product->delete();
        $this->logger->log('delete', 'master.product', "Hapus produk: $name");

        return redirect()->route($info['route'] . 'products.index')->with('success', "Produk $name berhasil dihapus.");
    }

    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Master\ProductsTemplateExport, "Produk_Template.xlsx");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $info = $this->getScopeInfo($request);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\Master\ProductsImport, $request->file('file'));
            $this->logger->log('import', 'master.product', "Import produk via Excel");
            return redirect()->route($info['route'] . 'products.index')->with('success', 'Produk berhasil di-import dan diperbarui dari file Excel.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }

}
