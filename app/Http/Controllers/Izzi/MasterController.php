<?php

namespace App\Http\Controllers\Izzi;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ActivityLogService;
use App\Services\Izzi\{Access, ProductCatalogue, Quantity};
use App\Support\ListPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};
use Inertia\Inertia;

class MasterController extends Controller
{
    public function __construct(private Access $access, private ActivityLogService $logger) {}

    public function ingredientQuery(?string $search = null)
    {
        return app(ProductCatalogue::class)->query($search);
    }

    public function ingredients(Request $request)
    {
        $this->access->manage($request->user());
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'search' => 'nullable|string|max:100', 'catalog_page' => 'nullable|integer|min:1']);
        $query = $this->ingredientQuery($filters['q'] ?? $filters['search'] ?? null);
        if ($request->query('lookup') === 'products') {
            $page = $query->where('p.status', 'active')->simplePaginate(20, ['*'], 'catalog_page');
            return response()->json(['products' => $page->getCollection()->map(fn ($p) => [...(array) $p, 'purchase_factor' => Quantity::display($p->purchase_factor)]), 'catalogue' => ['page' => $page->currentPage(), 'has_more' => $page->hasMorePages()]]);
        }
        $rows = ListPage::paginate($query, $request);
        $rows->through(fn ($p) => [...(array) $p, 'purchase_factor' => Quantity::display($p->purchase_factor)]);
        return Inertia::render('Izzi/Ingredients', ['ingredients' => $rows, 'filters' => $request->only('search', 'per_page')]);
    }

    public function saveIngredient(Request $request, ?int $product = null)
    {
        $this->access->manage($request->user());
        $data = $request->validate([
            'product_id' => [Rule::requiredIf($product === null), 'integer', Rule::exists('master_products', 'id')->whereNull('deleted_at')],
            'name' => 'prohibited', 'code' => 'prohibited', 'unit' => 'prohibited', 'unit_id' => 'prohibited', 'is_active' => 'prohibited',
            'purchase_unit' => 'required|string|max:40', 'purchase_factor' => 'required',
        ]);
        if ($product !== null && isset($data['product_id']) && $product !== (int) $data['product_id']) {
            throw ValidationException::withMessages(['product_id' => 'Produk harus sama dengan produk yang sedang disesuaikan.']);
        }
        $product = $product ?? (int) $data['product_id'];
        $factor = Quantity::parse($data['purchase_factor'], 'purchase_factor');
        DB::transaction(function () use ($data, $factor, $product, $request) {
            $p = Product::with('unit')->whereKey($product)->where('status', 'active')->lockForUpdate()->firstOrFail();
            $old = DB::table('izzi_ingredients')->where('product_id', $product)->lockForUpdate()->first();
            DB::table('izzi_ingredients')->updateOrInsert(['product_id' => $p->id], ['purchase_unit' => trim($data['purchase_unit']), 'purchase_factor' => $factor, 'updated_at' => now(), ...(!$old ? ['created_at' => now()] : [])]);
            $this->logger->log($old ? 'update' : 'create', 'izzi_ingredient', $p->name.' · '.$p->unit->abbreviation, $p, $old ? (array) $old : null, ['purchase_unit' => trim($data['purchase_unit']), 'purchase_factor' => $data['purchase_factor']]);
        });
        return redirect()->route('izzi.ingredients')->with('success', 'Penyesuaian kemasan Izzi tersimpan. Produk dan satuan tetap mengikuti master.');
    }

    public function menus(Request $request)
    {
        $this->access->manage($request->user());
        $request->validate(['search' => 'nullable|string|max:100']);
        $rows = ListPage::paginate(DB::table('izzi_menus')->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->search.'%')->orWhere('code', 'like', '%'.$request->search.'%')))->orderBy('name'), $request);
        $rows->through(fn ($m) => [...(array) $m, 'ingredient_count' => count(json_decode($m->recipe, true))]);
        return Inertia::render('Izzi/Menus', ['menus' => $rows, 'filters' => $request->only('search', 'per_page')]);
    }

    public function menuForm(Request $request, ?int $menu = null)
    {
        $this->access->manage($request->user());
        $row = $menu ? DB::table('izzi_menus')->where('id', $menu)->first() : null; abort_if($menu && !$row, 404);
        $recipe = $row ? json_decode($row->recipe, true) : [];
        $ingredients = $this->ingredientQuery()->where('p.status', 'active')->simplePaginate(20);
        $selected = $this->ingredientQuery()->whereIn('p.id', array_column($recipe, 'product_id'))->get();
        return Inertia::render('Izzi/MenuForm', ['menu' => $row ? [...(array) $row, 'recipe' => array_map(fn ($i) => [...$i, 'quantity' => Quantity::display($i['quantity'])], $recipe)] : null,
            'ingredients' => $ingredients->items(), 'selectedIngredients' => $selected, 'catalogue' => ['page' => 1, 'has_more' => $ingredients->hasMorePages()], 'versions' => $row ? DB::table('izzi_recipe_versions')->where('menu_id', $menu)->orderByDesc('version')->limit(10)->get(['version', 'menu_name', 'price', 'actor_name', 'created_at', 'recipe'])->map(fn ($v) => [...(array) $v, 'recipe' => collect(json_decode($v->recipe, true))->map(fn ($r) => [...$r, 'quantity' => Quantity::display($r['quantity'])])]) : []]);
    }

    public function saveMenu(Request $request, ?int $menu = null)
    {
        $this->access->manage($request->user());
        $data = $request->validate(['code' => ['required', 'string', 'max:40', Rule::unique('izzi_menus', 'code')->ignore($menu)], 'name' => 'required|string|max:200',
            'category' => 'required|string|max:80', 'price' => 'required|integer|min:0|max:1000000000', 'is_active' => 'required|boolean',
            'recipe' => 'required|array|min:1|max:100', 'recipe.*.product_id' => 'required|integer|distinct|exists:master_products,id', 'recipe.*.quantity' => 'required']);
        $recipe = collect($data['recipe'])->sortBy('product_id')->map(fn ($i) => ['product_id' => (int) $i['product_id'], 'quantity' => Quantity::parse($i['quantity'], 'recipe')])->values()->all();
        $id = DB::transaction(function () use ($data, $recipe, $menu, $request) {
            $old = $menu ? DB::table('izzi_menus')->where('id', $menu)->lockForUpdate()->first() : null; abort_if($menu && !$old, 404);
            if (DB::table('master_products')->whereIn('id', array_column($recipe, 'product_id'))->whereNull('deleted_at')->where('status', 'active')->count() !== count($recipe)) throw ValidationException::withMessages(['recipe' => 'Resep hanya boleh memakai bahan aktif.']);
            $version = $old ? (int) $old->recipe_version + 1 : 1;
            $row = ['code' => trim($data['code']), 'name' => trim($data['name']), 'category' => trim($data['category']), 'price' => $data['price'],
                'is_active' => $data['is_active'], 'recipe_version' => $version, 'recipe' => json_encode($recipe, JSON_THROW_ON_ERROR), 'updated_by' => $request->user()->id, 'updated_at' => now()];
            $id = $old ? $old->id : DB::table('izzi_menus')->insertGetId($row + ['created_at' => now()]);
            if ($old) DB::table('izzi_menus')->where('id', $id)->update($row);
            $products = Product::with('unit')->whereIn('id', array_column($recipe, 'product_id'))->get()->keyBy('id');
            DB::table('izzi_recipe_versions')->insert(['menu_id' => $id, 'version' => $version, 'menu_name' => $row['name'], 'price' => $row['price'],
                'recipe' => json_encode(collect($recipe)->map(function ($r) use ($products) { $p = $products[$r['product_id']]; return $r + ['product_name' => $p->name, 'unit_name' => $p->unit->abbreviation]; })->all(), JSON_THROW_ON_ERROR), 'created_by' => $request->user()->id, 'actor_name' => $request->user()->name, 'created_at' => now()]);
            $this->logger->log($old ? 'update' : 'create', 'izzi_recipe', $row['name'].' · Resep versi '.$version, null, $old ? (array) $old : null, $row);
            return $id;
        });
        return redirect()->route('izzi.menus.edit', $id)->with('success', 'Menu tersimpan. Resep baru berlaku untuk penjualan berikutnya.');
    }
}
