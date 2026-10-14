<?php

namespace App\Http\Controllers\Izzi;

use App\Http\Controllers\Controller;
use App\Models\{Product, ProductCategory, Unit};
use App\Services\ActivityLogService;
use App\Services\Izzi\{Access, Quantity};
use App\Support\ListPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};
use Inertia\Inertia;

class MasterController extends Controller
{
    public const UNITS = ['gram', 'ml', 'pcs', 'bungkus', 'sachet', 'porsi', 'potong', 'sdm', 'set'];
    public function __construct(private Access $access, private ActivityLogService $logger) {}

    public function ingredientQuery(?string $search = null)
    {
        return DB::table('izzi_ingredients as i')->join('master_products as p', 'p.id', '=', 'i.product_id')->join('master_units as u', 'u.id', '=', 'p.unit_id')
            ->whereNull('p.deleted_at')->when($search, fn ($q) => $q->where(fn ($w) => $w->where('p.name', 'like', '%'.$search.'%')->orWhere('p.code', 'like', '%'.$search.'%')))
            ->select('p.id', 'p.name', 'p.code', 'p.status', 'u.abbreviation as unit', 'i.purchase_unit', 'i.purchase_factor')->orderBy('p.name')->orderBy('p.id');
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
        return Inertia::render('Izzi/Ingredients', ['ingredients' => $rows, 'filters' => $request->only('search', 'per_page'), 'units' => self::UNITS]);
    }

    public function saveIngredient(Request $request, ?int $product = null)
    {
        $this->access->manage($request->user());
        $data = $request->validate(['name' => 'required|string|max:200', 'code' => ['required', 'string', 'max:20', Rule::unique('master_products', 'code')->ignore($product)],
            'unit' => ['required', Rule::in(self::UNITS)], 'purchase_unit' => 'required|string|max:40', 'purchase_factor' => 'required', 'is_active' => 'required|boolean']);
        $factor = Quantity::parse($data['purchase_factor'], 'purchase_factor');
        DB::transaction(function () use ($data, $factor, $product, $request) {
            $unit = Unit::firstOrCreate(['name' => 'Izzi '.$data['unit'], 'entity_scope' => 'izzi'], ['abbreviation' => $data['unit']]);
            $category = ProductCategory::firstOrCreate(['name' => 'Bahan Izzi Kebab', 'entity_scope' => 'all']);
            $old = $product ? DB::table('izzi_ingredients')->where('product_id', $product)->lockForUpdate()->first() : null;
            abort_if($product && !$old, 404);
            $p = $product ? Product::whereKey($product)->lockForUpdate()->firstOrFail() : new Product();
            if ($old && (int) $p->unit_id !== (int) $unit->id) throw ValidationException::withMessages(['unit' => 'Satuan dasar bahan sudah ditetapkan. Buat bahan baru bila satuan dasarnya berbeda agar resep dan riwayat tetap benar.']);
            $p->fill(['name' => trim($data['name']), 'code' => trim($data['code']), 'unit_id' => $unit->id, 'category_id' => $category->id,
                'entity_scope' => 'izzi', 'source_type' => 'purchased', 'product_type' => 'INV', 'status' => $data['is_active'] ? 'active' : 'discontinued', 'created_by' => $old ? $p->created_by : $request->user()->id]);
            $p->save();
            DB::table('izzi_ingredients')->updateOrInsert(['product_id' => $p->id], ['purchase_unit' => trim($data['purchase_unit']), 'purchase_factor' => $factor, 'updated_at' => now(), ...(!$old ? ['created_at' => now()] : [])]);
            $this->logger->log($old ? 'update' : 'create', 'izzi_ingredient', $p->name.' · '.$data['unit'], $p, $old ? (array) $old : null, ['purchase_factor' => $data['purchase_factor']]);
        });
        return redirect()->route('izzi.ingredients')->with('success', 'Bahan dan konversi kemasan tersimpan.');
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
            'recipe' => 'required|array|min:1|max:100', 'recipe.*.product_id' => 'required|integer|distinct|exists:izzi_ingredients,product_id', 'recipe.*.quantity' => 'required']);
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
