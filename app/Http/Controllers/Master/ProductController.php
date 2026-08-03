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
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

        $q = $modelClass::with(['category', 'unit', 'brand', 'tieredPrices']);

        // Terapkan scope visibilitas berdasarkan entitas login
        if ($info['scope'] === 'hendhys') {
            $q->visibleInHendhys();
        } elseif ($info['scope'] === 'jihans') {
            $q->visibleInJihans();
        } else {
            $q->visibleInGudang();
        }

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

        return Inertia::render('Master/Products/Index', [
            // If there's a ProductResource, use it. Otherwise, fallback to the paginator object.
            'products' => class_exists(\App\Http\Resources\Master\ProductResource::class) ? \App\Http\Resources\Master\ProductResource::collection($products) : $products,
            'filters'  => $request->only('search', 'status', 'visibility'),
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

        return Inertia::render('Master/Products/Form', [
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
            'category_id' => "required|string",
            'unit_id' => "required|string",
            'brand_id' => "nullable|string",
            'rack' => 'nullable|string|max:20',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_min' => 'required|integer|min:0',
            'ppn_type' => 'required|in:none,include,exclude',
            'ppn_rate' => 'required|numeric|min:0|max:100',
            'product_type'       => 'required|in:INV,NON',
            'source_type'        => 'required|in:produced,purchased',
            'status'             => 'required|in:active,discontinued',
            'visible_gudang'     => 'boolean',
            'visible_jihans'     => 'boolean',
            'visible_hendhys'    => 'boolean',
            'notes'              => 'nullable|string',
            'image'              => 'nullable|image|max:2048',
            'image_url'          => 'nullable|string',
            'tiered_prices'      => 'nullable|array',
            'tiered_prices.*.min_qty' => 'required_with:tiered_prices|numeric|min:1',
            'tiered_prices.*.price'   => 'required_with:tiered_prices|numeric|min:0',
        ], [
            'barcode.unique' => 'Gagal menyimpan produk: Barcode sudah terdaftar dan digunakan oleh produk lain. Silakan periksa kembali barcode yang dimasukkan.',
        ]);

        $data = $this->resolveRelations($data, $info['scope']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->filled('image_url')) {
            if ($stored = $this->storeImageFromUrl($request->input('image_url'))) {
                $data['image'] = $stored;
            }
        }

        $data['created_by']      = auth()->id();
        $data['visible_gudang']  = $request->boolean('visible_gudang');
        $data['visible_jihans']  = $request->boolean('visible_jihans');
        $data['visible_hendhys'] = $request->boolean('visible_hendhys');
        // Simpan entity_scope dari entitas aktif untuk kompatibilitas
        $data['entity_scope']    = $info['scope'] === 'gudang' ? 'all' : $info['scope'];

        // Generate kode di dalam transaksi supaya lockForUpdate pada generator
        // benar-benar menahan baris sampai produk tersimpan.
        $product = DB::transaction(function () use ($data, $tableName, $info) {
            $data['code'] = $this->numbers->generate('PRD', $tableName, 'code');

            return $this->getModelClass('Product', $info['scope'])::create($data);
        });

        if ($request->has('tiered_prices')) {
            $this->saveTieredPrices($product, $request->tiered_prices);
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

        return Inertia::render('Master/Products/Form', [
            'product' => class_exists(\App\Http\Resources\Master\ProductResource::class) ? new \App\Http\Resources\Master\ProductResource($product) : $product,
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
            'category_id' => "required|string",
            'unit_id' => "required|string",
            'brand_id' => "nullable|string",
            'rack' => 'nullable|string|max:20',
            'hpp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_min' => 'required|integer|min:0',
            'ppn_type' => 'required|in:none,include,exclude',
            'ppn_rate' => 'required|numeric|min:0|max:100',
            'product_type'    => 'required|in:INV,NON',
            'source_type'     => 'required|in:produced,purchased',
            'status'          => 'required|in:active,discontinued',
            'visible_gudang'  => 'boolean',
            'visible_jihans'  => 'boolean',
            'visible_hendhys' => 'boolean',
            'notes'           => 'nullable|string',
            'image'           => 'nullable|image|max:2048',
            'image_url'       => 'nullable|string',
            'tiered_prices'   => 'nullable|array',
            'tiered_prices.*.min_qty' => 'required_with:tiered_prices|numeric|min:1',
            'tiered_prices.*.price'   => 'required_with:tiered_prices|numeric|min:0',
        ], [
            'barcode.unique' => 'Gagal memperbarui produk: Barcode sudah terdaftar dan digunakan oleh produk lain. Silakan periksa kembali barcode yang dimasukkan.',
        ]);

        $data = $this->resolveRelations($data, $info['scope']);

        if ($request->hasFile('image')) {
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->filled('image_url')) {
            // Unduh dulu, baru hapus gambar lama — supaya gambar lama tidak hilang
            // kalau URL baru ternyata ditolak/gagal diambil.
            if ($stored = $this->storeImageFromUrl($request->input('image_url'))) {
                if ($product->image) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
                }
                $data['image'] = $stored;
            }
        } elseif ($request->boolean('clear_image')) {
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $data['image'] = null;
        }

        $data['visible_gudang']  = $request->boolean('visible_gudang');
        $data['visible_jihans']  = $request->boolean('visible_jihans');
        $data['visible_hendhys'] = $request->boolean('visible_hendhys');

        $old = $product->toArray();

        $product->update($data);
        
        if ($request->has('tiered_prices')) {
            $this->saveTieredPrices($product, $request->tiered_prices);
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

    /**
     * Simpan gambar produk dari sebuah URL (data: URI hasil cropper, atau URL http/https).
     *
     * Dipakai bersama oleh store() dan update() — sebelumnya blok ini disalin dua kali
     * dan keduanya mengambil URL apa pun yang dikirim klien tanpa pemeriksaan, sehingga
     * server bisa dipaksa menembak alamat internal (SSRF).
     *
     * @return string|null Path relatif di disk 'public', atau null bila ditolak/gagal.
     */
    private function storeImageFromUrl(string $url): ?string
    {
        try {
            // 1. data: URI — di-encode di browser, tidak menyentuh jaringan sama sekali.
            if (str_starts_with($url, 'data:')) {
                if (!preg_match('#^data:image/(png|jpe?g|webp|gif);base64,#i', $url, $m)) {
                    return null;
                }
                $contents = base64_decode(substr($url, strpos($url, ',') + 1), true);
                if ($contents === false || $contents === '') {
                    return null;
                }
                $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);

                return $this->putProductImage($contents, $ext);
            }

            // 2. URL remote — hanya http/https ke alamat IP publik.
            if (!$this->isPublicHttpUrl($url)) {
                \Illuminate\Support\Facades\Log::warning('Image URL ditolak (bukan alamat publik): ' . $url);

                return null;
            }

            $response = \Illuminate\Support\Facades\Http::timeout(10)
                // Redirect dimatikan: host publik yang diizinkan tetap bisa
                // meneruskan (302) ke alamat internal kalau ini dibiarkan.
                ->withOptions(['allow_redirects' => false])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $contentType = (string) $response->header('Content-Type');
            if (!str_starts_with($contentType, 'image/')) {
                \Illuminate\Support\Facades\Log::warning("Image URL ditolak (Content-Type {$contentType}): " . $url);

                return null;
            }

            $ext = match (true) {
                str_contains($contentType, 'png')  => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif')  => 'gif',
                default                            => 'jpg',
            };

            return $this->putProductImage($response->body(), $ext);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to download image: ' . $e->getMessage());

            return null;
        }
    }

    private function putProductImage(string $contents, string $ext): string
    {
        $filename = 'products/' . uniqid() . '.' . $ext;
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $contents);

        return $filename;
    }

    /**
     * True hanya jika URL memakai skema http/https DAN setiap IP yang di-resolve
     * berada di luar rentang privat/reserved (10/8, 192.168/16, 127/8, 169.254/16
     * termasuk endpoint metadata cloud, ::1, fc00::/7, dst).
     */
    private function isPublicHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'] ?? '';
        if ($host === '') {
            return false;
        }

        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
                if (!empty($record['ip']))   { $ips[] = $record['ip']; }
                if (!empty($record['ipv6'])) { $ips[] = $record['ipv6']; }
            }
        }

        if (empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private function resolveRelations(array $data, string $scope): array
    {
        if (isset($data['category_id']) && !is_numeric($data['category_id'])) {
            $cat = $this->getModelClass('ProductCategory', $scope)::firstOrCreate(
                ['name' => $data['category_id']],
                ['entity_scope' => $scope === 'gudang' ? 'all' : $scope]
            );
            $data['category_id'] = $cat->id;
        }

        if (isset($data['unit_id']) && !is_numeric($data['unit_id'])) {
            $unit = $this->getModelClass('Unit', $scope)::firstOrCreate(
                ['name' => $data['unit_id']],
                ['abbreviation' => substr($data['unit_id'], 0, 3), 'entity_scope' => $scope === 'gudang' ? 'all' : $scope]
            );
            $data['unit_id'] = $unit->id;
        }

        if (!empty($data['brand_id']) && !is_numeric($data['brand_id'])) {
            $brand = $this->getModelClass('Brand', $scope)::firstOrCreate(
                ['name' => $data['brand_id']],
                ['entity_scope' => $scope === 'gudang' ? 'all' : $scope]
            );
            $data['brand_id'] = $brand->id;
        }

        return $data;
    }

    private function saveTieredPrices(Product $product, ?array $tieredPrices): void
    {
        $product->tieredPrices()->delete();
        if (is_array($tieredPrices)) {
            foreach ($tieredPrices as $tier) {
                if (!empty($tier['min_qty']) && !empty($tier['price'])) {
                    $product->tieredPrices()->create([
                        'min_qty' => $tier['min_qty'],
                        'price'   => $tier['price']
                    ]);
                }
            }
        }
    }
}
