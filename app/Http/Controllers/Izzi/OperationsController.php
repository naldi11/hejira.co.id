<?php

namespace App\Http\Controllers\Izzi;

use App\Http\Controllers\Controller;
use App\Models\{Branch, CashierShift, User};
use App\Services\Izzi\{Access, Inventory, ProductCatalogue, Quantity, Sales};
use App\Services\StockOpname;
use App\Support\ListPage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OperationsController extends Controller
{
    public function __construct(private Access $access, private Sales $salesService, private Inventory $inventory) {}

    private function context(Request $request): array
    {
        $request->validate(['branch_id' => 'nullable|integer|min:1']);
        $branch = $this->access->branch($request->user(), $request->filled('branch_id') ? (int) $request->branch_id : null, $request->isMethod('get'));
        $global = $request->user()->hasRole('owner') || $request->user()->hasRole('super_admin');
        return ['branch' => $branch->only(['id','name','is_active']), 'branches' => $global ? Branch::where('entity', 'izzi')->orderBy('name')->get(['id', 'name', 'is_active']) : [],
            'canManage' => !$request->user()->hasRole('owner') && $request->user()->hasAnyRole(['admin_izzi','super_admin'])];
    }

    private function time($value): string { return Carbon::parse($value)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB'; }

    public function dashboard(Request $request)
    {
        if (!Branch::where('entity', 'izzi')->exists()) {
            if (!$request->user()->hasRole('owner')) $this->access->manage($request->user());
            return Inertia::render('Izzi/Dashboard', ['branch' => null, 'branches' => [], 'canManage' => !$request->user()->hasRole('owner'), 'stats' => [], 'recent' => []]);
        }
        $context = $this->context($request); $id = $context['branch']['id'];
        $sales = DB::table('izzi_sales')->where('branch_id', $id)->where('created_at', '>=', now('Asia/Jakarta')->startOfDay());
        $cancelled = DB::table('izzi_cancellations as c')->join('izzi_sales as s', 's.id', '=', 'c.sale_id')->where('s.branch_id', $id);
        return Inertia::render('Izzi/Dashboard', $context + ['stats' => ['revenue_today' => (clone $sales)->sum('net_total'), 'sales_today' => (clone $sales)->count(),
            'cancelled_total' => $cancelled->sum('c.refund_amount'), 'active_menus' => DB::table('izzi_menus')->where('is_active', true)->count()],
            'recent' => DB::table('izzi_sales')->where('branch_id', $id)->orderByDesc('id')->limit(5)->get()->map(fn ($s) => [...(array) $s, 'time' => $this->time($s->created_at)])]);
    }

    private function stocks(int $branchId, ?string $search = null, bool $allProducts = false)
    {
        return app(ProductCatalogue::class)->query($search)
            ->leftJoin('izzi_stock as s', fn ($j) => $j->on('s.product_id', '=', 'p.id')->where('s.branch_id', $branchId))
            ->when(!$allProducts, fn ($q) => $q->where(fn ($w) => $w->whereNotNull('i.product_id')->orWhereNotNull('s.id')))
            ->addSelect(DB::raw('COALESCE(s.quantity, 0) as quantity'));
    }

    public function stock(Request $request)
    {
        $context = $this->context($request); $request->validate(['search' => 'nullable|string|max:100']);
        $rows = ListPage::paginate($this->stocks($context['branch']['id'], $request->search), $request);
        $rows->through(fn ($s) => [...(array) $s, 'quantity' => Quantity::display($s->quantity), 'purchase_factor' => Quantity::display($s->purchase_factor)]);
        return Inertia::render('Izzi/Stock', $context + ['stocks' => $rows, 'filters' => $request->only('search', 'per_page'),
            'opnameAccess' => app(StockOpname::class)->accessForStock($request->user(), 'branch-'.$context['branch']['id'])]);
    }

    public function movements(Request $request)
    {
        $context = $this->context($request);
        $request->validate(['search' => 'nullable|string|max:100']);
        $rows = ListPage::paginate(DB::table('izzi_stock_movements as m')->join('master_products as p', 'p.id', '=', 'm.product_id')->join('master_units as u', 'u.id', '=', 'm.unit_id')
            ->where('m.branch_id', $context['branch']['id'])->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('p.name', 'like', '%'.$request->search.'%')->orWhere('m.notes', 'like', '%'.$request->search.'%')->orWhere('m.actor_name', 'like', '%'.$request->search.'%')))
            ->select('m.*', 'p.name', 'p.code', 'u.abbreviation as unit')->orderByDesc('m.id'), $request);
        $rows->through(fn ($m) => [...(array) $m, 'quantity' => Quantity::display($m->quantity), 'before' => Quantity::display($m->quantity_before), 'after' => Quantity::display($m->quantity_after), 'time' => $this->time($m->created_at)]);
        return Inertia::render('Izzi/Movements', $context + ['movements' => $rows, 'filters' => $request->only('search', 'per_page')]);
    }

    public function receiveForm(Request $request)
    {
        $this->access->manage($request->user()); $context = $this->context($request);
        $page = $this->stocks($context['branch']['id'], null, true)->where('p.status', 'active')->simplePaginate(20);
        return Inertia::render('Izzi/Receive', $context + ['catalogue' => ['page' => 1, 'has_more' => $page->hasMorePages()], 'ingredients' => $page->getCollection()->map(fn ($s) => [...(array) $s, 'purchase_factor' => Quantity::display($s->purchase_factor)]),
            'today' => now('Asia/Jakarta')->toDateString(), 'currentTime' => now('Asia/Jakarta')->format('H:i')]);
    }

    public function receive(Request $request)
    {
        $this->access->manage($request->user());
        $data = $request->validate(['branch_id' => 'required|integer|min:1', 'request_key' => 'required|uuid', 'date' => 'required|date_format:Y-m-d|before_or_equal:'.now('Asia/Jakarta')->toDateString(),
            'time' => 'required|date_format:H:i', 'origin' => 'required|string|min:2|max:255', 'notes' => 'required|string|min:5|max:1000', 'evidence_reference' => 'nullable|string|max:255',
            'items' => 'required|array|min:1|max:100', 'items.*.product_id' => 'required|integer|distinct|exists:master_products,id', 'items.*.quantity' => 'required', 'items.*.mode' => 'required|in:base,purchase']);
        if (Carbon::parse($data['date'].' '.$data['time'], 'Asia/Jakarta')->gt(now())) throw ValidationException::withMessages(['time' => 'Waktu penerimaan tidak boleh di masa depan.']);
        $this->inventory->receipt($data, $request->user());
        return redirect()->route('izzi.receipts', ['branch_id' => $data['branch_id']])->with('success', 'Penerimaan tersimpan dan stok bahan bertambah.');
    }

    public function receipts(Request $request)
    {
        $context = $this->context($request); $request->validate(['search' => 'nullable|string|max:100']);
        $rows = ListPage::paginate(DB::table('izzi_receipts')->where('branch_id', $context['branch']['id'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', '%'.$request->search.'%')->orWhere('origin', 'like', '%'.$request->search.'%')->orWhere('notes', 'like', '%'.$request->search.'%')->orWhere('actor_name', 'like', '%'.$request->search.'%')))->orderByDesc('id'), $request);
        $rows->through(fn ($r) => [...(array) $r, 'items' => json_decode($r->items, true), 'time' => $this->time($r->occurred_at), 'recorded' => $this->time($r->created_at)]);
        return Inertia::render('Izzi/Receipts', $context + ['receipts' => $rows, 'filters' => $request->only('search', 'per_page')]);
    }

    public function pos(Request $request)
    {
        $this->access->cashier($request->user()); $context = $this->context($request);
        $shift = CashierShift::where('user_id', $request->user()->id)->where('branch_id', $context['branch']['id'])->where('entity', 'izzi')->where('status', 'open')->first();
        if (!$shift) return redirect()->route('izzi.shifts')->with('error', 'Buka laci kasir cabang aktif terlebih dahulu.');
        $request->validate(['q' => 'nullable|string|max:100', 'catalog_page' => 'nullable|integer|min:1']);
        $page = DB::table('izzi_menus')->where('is_active', true)->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('code', 'like', '%'.$request->q.'%')))->orderBy('name')->simplePaginate(20, ['*'], 'catalog_page');
        $ids = $page->getCollection()->flatMap(fn ($m) => array_column(json_decode($m->recipe, true), 'product_id'))->unique();
        $stocks = $this->stocks($context['branch']['id'])->whereIn('p.id', $ids)->get()->keyBy('id');
        $products = $page->getCollection()->map(function ($menu) use ($stocks) {
            $available = 1000000;
            foreach (json_decode($menu->recipe, true) as $ingredient) {
                $stock = $stocks->get($ingredient['product_id']);
                $available = min($available, $stock && $stock->status === 'active' ? intdiv((int) $stock->quantity, $ingredient['quantity']) : 0);
            }
            return ['id' => $menu->id, 'name' => $menu->name, 'code' => $menu->code, 'category' => $menu->category, 'price' => $menu->price, 'available' => $available];
        });
        $catalogue = ['page' => $page->currentPage(), 'has_more' => $page->hasMorePages()];
        if ($request->query('lookup') === 'products') return response()->json(['products' => $products, 'catalogue' => $catalogue, 'branch_id' => $context['branch']['id']]);
        return Inertia::render('Izzi/Pos', $context + ['products' => $products, 'catalogue' => $catalogue]);
    }

    public function checkout(Request $request)
    {
        $this->access->cashier($request->user());
        $data = $request->validate(['checkout_key' => 'required|uuid', 'branch_id' => 'required|integer|min:1', 'customer_name' => 'nullable|string|max:150',
            'payment_type' => 'required|in:cash,transfer', 'amount_paid' => 'required|integer|min:0|max:1000000000000', 'payment_reference' => 'nullable|string|max:255',
            'items' => 'required|array|min:1|max:100', 'items.*.menu_id' => 'required|integer|distinct|exists:izzi_menus,id', 'items.*.quantity' => 'required|integer|min:1|max:1000', 'items.*.price' => 'required|integer|min:0|max:1000000000']);
        $sale = $this->salesService->record($data, $request->user());
        return redirect()->route('izzi.sales.show', ['sale' => $sale->id, 'branch_id' => $sale->branch_id])->with('success', 'Penjualan tersimpan; seluruh bahan sudah dikurangi sesuai resep.');
    }

    public function sales(Request $request)
    {
        $context = $this->context($request); $request->validate(['search' => 'nullable|string|max:100', 'date_from' => 'nullable|date_format:Y-m-d', 'date_to' => 'nullable|date_format:Y-m-d']);
        $query = DB::table('izzi_sales')->where('branch_id', $context['branch']['id'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', '%'.$request->search.'%')->orWhere('customer_name', 'like', '%'.$request->search.'%')->orWhere('actor_name', 'like', '%'.$request->search.'%')))
            ->when($request->filled('date_from'), fn ($q) => $q->where('created_at', '>=', $request->date_from.' 00:00:00'))
            ->when($request->filled('date_to'), fn ($q) => $q->where('created_at', '<', Carbon::parse($request->date_to)->addDay()->startOfDay()));
        $summary = (clone $query)->selectRaw('COUNT(*) as transactions, SUM(total) as original_total, SUM(net_total) as net_total')->first();
        $rows = ListPage::paginate($query->orderByDesc('id'), $request); $rows->through(fn ($s) => [...(array) $s, 'time' => $this->time($s->created_at)]);
        return Inertia::render('Izzi/Sales', $context + ['sales' => $rows, 'summary' => $summary, 'filters' => $request->only('search','date_from','date_to','per_page')]);
    }

    public function show(Request $request, int $sale)
    {
        $context = $this->context($request); $row = DB::table('izzi_sales')->where('id', $sale)->first(); abort_unless($row, 404); abort_unless((int) $row->branch_id === (int) $context['branch']['id'], 403);
        $lines = DB::table('izzi_sale_lines')->where('sale_id', $sale)->get();
        $ingredients = DB::table('izzi_sale_ingredients')->whereIn('line_id', $lines->pluck('id'))->get()->groupBy('line_id');
        $lines = $lines->map(fn ($l) => [...(array) $l, 'ingredients' => $ingredients->get($l->id, collect())->map(fn ($i) => [...(array) $i, 'per_menu' => Quantity::display($i->per_menu), 'quantity' => Quantity::display($i->quantity), 'restored_quantity' => Quantity::display($i->restored_quantity), 'wasted_quantity' => Quantity::display($i->wasted_quantity)])]);
        $events = DB::table('izzi_cancellations')->where('sale_id', $sale)->orderBy('id')->get()->map(fn ($c) => [...(array) $c, 'items' => json_decode($c->items, true), 'time' => $this->time($c->created_at)]);
        if ($request->routeIs('izzi.sales.receipt')) return view('izzi.receipt', ['sale' => $row, 'lines' => $lines, 'time' => $this->time($row->created_at)]);
        return Inertia::render('Izzi/Sale', $context + ['sale' => [...(array) $row, 'time' => $this->time($row->created_at)], 'lines' => $lines, 'cancellations' => $events,
            'canCancel' => $context['canManage'] && CashierShift::whereKey($row->shift_id)->where('status','open')->exists()]);
    }

    public function cancel(Request $request, int $sale)
    {
        $this->access->manage($request->user());
        $data = $request->validate(['request_key' => 'required|uuid', 'branch_id' => 'required|integer|min:1', 'reason' => 'required|string|min:5|max:1000', 'confirmed' => 'accepted',
            'items' => 'required|array|min:1|max:100', 'items.*.line_id' => 'required|integer|distinct', 'items.*.unused' => 'required|integer|min:0|max:1000', 'items.*.wasted' => 'required|integer|min:0|max:1000']);
        $this->salesService->cancel($sale, $data, $request->user());
        return back()->with('success', 'Pembatalan dicatat. Bahan yang belum dipakai dikembalikan; bahan makanan terbuang tidak dipotong lagi.');
    }

    public function shifts(Request $request)
    {
        $context = $this->context($request); $user = $request->user();
        $request->validate(['search' => 'nullable|string|max:100']);
        $query = CashierShift::where('entity', 'izzi')->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('note', 'like', '%'.$request->search.'%')->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->search.'%'))))->where('branch_id', $context['branch']['id'])->when($user->hasRole('kasir_izzi'), fn ($q) => $q->where('user_id', $user->id));
        $rows = ListPage::paginate($query->with('user:id,name')->orderByDesc('id'), $request);
        $active = $user->hasRole('kasir_izzi') ? CashierShift::with('branch:id,name')->where('user_id', $user->id)->where('entity','izzi')->where('status','open')->first() : null;
        return Inertia::render('Izzi/Shifts', $context + ['shifts' => $rows, 'active' => $active, 'filters' => $request->only('search','per_page'), 'cash_sales' => $active ? $this->salesService->shiftSummary($active)['tunai'] : 0]);
    }

    public function openShift(Request $request)
    {
        $this->access->cashier($request->user()); $branch = $this->access->branch($request->user()); $data = $request->validate(['starting_cash' => 'required|integer|min:0|max:1000000000']);
        DB::transaction(function () use ($request, $branch, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->first();
            if (CashierShift::where('user_id', $request->user()->id)->where('status','open')->exists()) throw ValidationException::withMessages(['starting_cash' => 'Masih ada laci aktif. Tutup terlebih dahulu sebelum pindah cabang atau membuka laci baru.']);
            $shift = CashierShift::create(['user_id' => $request->user()->id, 'branch_id' => $branch->id, 'entity' => 'izzi', 'status' => 'open', 'opened_at' => now(), 'starting_cash' => $data['starting_cash']]);
            app(\App\Services\ActivityLogService::class)->log('create', 'izzi_shift', 'Buka laci #'.$shift->id.' · '.$branch->name, $shift);
        });
        return back()->with('success', 'Laci Izzi Kebab dibuka.');
    }

    public function closeShift(Request $request)
    {
        $this->access->cashier($request->user());
        $data = $request->validate(['actual_cash' => 'required|integer|min:0|max:1000000000000', 'note' => 'nullable|string|max:1000']);
        DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->first();
            $shift = CashierShift::where('user_id', $request->user()->id)->where('entity','izzi')->where('status','open')->lockForUpdate()->first();
            if (!$shift) throw ValidationException::withMessages(['actual_cash' => 'Laci aktif tidak ditemukan.']);
            $expected = $shift->starting_cash + $this->salesService->shiftSummary($shift)['tunai'];
            $shift->update(['status' => 'closed', 'closed_at' => now(), 'expected_cash' => $expected, 'actual_cash' => $data['actual_cash'], 'discrepancy' => $data['actual_cash'] - $expected, 'note' => $data['note'] ?? null]);
            app(\App\Services\ActivityLogService::class)->log('update', 'izzi_shift', 'Tutup laci #'.$shift->id, $shift, null, ['expected_cash' => $expected, 'actual_cash' => $data['actual_cash']]);
        });
        return back()->with('success', 'Laci ditutup dan rekap uang tersimpan.');
    }
}
