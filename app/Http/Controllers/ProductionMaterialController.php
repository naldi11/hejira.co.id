<?php

namespace App\Http\Controllers;

use App\Services\{ProductionLots, ProductionMaterials};
use App\Support\ListPage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductionMaterialController extends Controller
{
    public function __construct(private ProductionMaterials $materials) {}

    private function time(?string $time): ?string
    {
        return $time ? Carbon::parse($time)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB' : null;
    }

    private function header(object $tx): array
    {
        return [...(array) $tx, 'kind_label' => ProductionMaterials::KINDS[$tx->kind],
            'status_label' => $tx->status === 'cancelled' ? 'Dibatalkan' : 'Selesai dicatat',
            'occurred_label' => $this->time($tx->occurred_at), 'recorded_label' => $this->time($tx->created_at), 'cancelled_label' => $this->time($tx->cancelled_at)];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $this->materials->isSuperAdmin($user);
        $branches = $this->materials->branches($user);
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'per_page' => 'nullable|in:20,50,100,all',
            'status' => 'nullable|in:completed,cancelled', 'branch_id' => ['nullable', 'integer', Rule::in($branches->pluck('id')->all())],
            'date_from' => 'nullable|date_format:Y-m-d', 'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])]]);
        $location = $isSuperAdmin
            ? (!empty($filters['branch_id']) ? $this->materials->location($user, (int) $filters['branch_id']) : null)
            : $this->materials->location($user);
        $query = DB::table('production_material_transactions as t')->where('t.kind', 'branch_usage')
            ->when($location, fn ($q) => $q->where('t.branch_id', $location['branch_id']), fn ($q) => $q->whereIn('t.branch_id', $branches->pluck('id')))
            ->whereIn('t.status', ['completed', 'cancelled'])
            ->when(!empty($filters['status']), fn ($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->where('t.date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->where('t.date', '<=', $filters['date_to']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(function ($w) use ($term) {
                    foreach (['number', 'branch_name', 'receiver_name', 'created_by_name', 'reason', 'notes', 'evidence_reference', 'cancel_reason', 'cancelled_by_name'] as $field) $w->orWhere('t.'.$field, 'like', $term);
                    $w->orWhereExists(fn ($sq) => $sq->selectRaw('1')->from('production_material_items as i')->whereColumn('i.transaction_id', 't.id')
                        ->where(fn ($item) => $item->where('product_name', 'like', $term)->orWhere('product_code', 'like', $term)->orWhere('notes', 'like', $term)));
                });
            })->orderByDesc('t.date')->orderByDesc('t.id');
        $transactions = ListPage::paginate($query, $request);
        $items = DB::table('production_material_items')->whereIn('transaction_id', $transactions->pluck('id'))->orderBy('id')->get()->groupBy('transaction_id');
        $transactions->through(fn ($tx) => $this->header($tx) + ['items' => $items->get($tx->id, collect())->values()]);
        return Inertia::render('ProductionMaterials/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'location' => $location,
            'branches' => $branches->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'type' => $b->type])->values(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['q' => 'nullable|string|max:100', 'catalog_page' => 'nullable|integer|min:1', 'branch_id' => 'nullable|integer|min:1']);
        $location = $this->materials->location($user, $this->materials->isSuperAdmin($user) && !empty($data['branch_id']) ? (int) $data['branch_id'] : null);
        $branches = $this->materials->branches($user)->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'type' => $b->type])->values();
        $page = DB::table($location['stock_location'].' as s')->join('master_products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('master_units as u', 'u.id', '=', 'p.unit_id')->leftJoin('master_units as su', 'su.id', '=', 's.unit_id')
            ->when($location['stock_location'] === 'hendhys_stock_branch', fn ($q) => $q->where('s.branch_id', $location['branch_id']))
            ->whereNull('p.deleted_at')->where('p.status', 'active')->where('s.quantity', '>', 0)
            ->where(fn ($q) => $q->whereIn('p.entity_scope', ['hendhys', 'all'])->orWhere('p.visible_hendhys', true))
            ->when(!empty($data['q']), fn ($q) => $q->where(fn ($w) => $w->where('p.name', 'like', '%'.$data['q'].'%')->orWhere('p.code', 'like', '%'.$data['q'].'%')->orWhere('p.barcode', 'like', '%'.$data['q'].'%')))
            ->select('p.id', 'p.name', 'p.code', 'p.unit_id', 's.unit_id as stock_unit_id', 'u.abbreviation as unit', 'su.abbreviation as stock_unit', 's.quantity')
            ->orderBy('p.name')->orderBy('p.id')->simplePaginate(20, ['*'], 'catalog_page');
        $products = $page->getCollection()->map(fn ($p) => [...(array) $p, 'quantity' => (int) round((float) $p->quantity), 'unit_matches' => $p->unit !== null && (int) $p->unit_id === (int) $p->stock_unit_id]);
        $catalogue = ['page' => $page->currentPage(), 'has_more' => $page->hasMorePages()];
        if ($request->query('lookup') === 'products') return response()->json(['products' => $products, 'catalogue' => $catalogue, 'branch_id' => $location['branch_id']]);
        return Inertia::render('ProductionMaterials/Create', ['products' => $products, 'catalogue' => $catalogue, 'location' => $location, 'branches' => $branches,
            'today' => now('Asia/Jakarta')->toDateString(), 'currentTime' => now('Asia/Jakarta')->format('H:i')]);
    }

    public function store(Request $request)
    {
        $this->materials->authorize($request->user());
        foreach (['receiver_name', 'reason', 'notes', 'evidence_reference'] as $key) if (is_string($request->input($key))) $request->merge([$key => trim($request->input($key))]);
        $data = $request->validate([
            'request_key' => 'required|uuid', 'branch_id' => 'required|integer|min:1',
            'kind' => 'prohibited', 'production_id' => 'prohibited', 'sender_name' => 'prohibited',
            'date' => 'required|date_format:Y-m-d|before_or_equal:'.now('Asia/Jakarta')->toDateString(), 'time' => 'required|date_format:H:i',
            'receiver_name' => 'required|string|min:2|max:255', 'reason' => 'required|string|min:5|max:1000',
            'notes' => 'nullable|string|max:2000', 'evidence_reference' => 'nullable|string|max:255',
            'items' => 'required|array|min:1|max:100', 'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('master_products', 'id')->whereNull('deleted_at')->where('status', 'active')],
            'items.*.quantity' => 'required|integer|min:1|max:999999999', 'items.*.lot_id' => 'prohibited', 'items.*.unit_id' => 'prohibited', 'items.*.notes' => 'nullable|string|max:500',
        ]);
        if (Carbon::parse($data['date'].' '.$data['time'], 'Asia/Jakarta')->gt(now())) throw ValidationException::withMessages(['time' => 'Tanggal dan jam pemakaian tidak boleh melewati waktu sekarang (WIB).']);
        $id = $this->materials->record($data, $request->user());
        $cancelled = DB::table('production_material_transactions')->where('id', $id)->value('status') === 'cancelled';
        return redirect()->route('production-materials.show', $id)->with('success', $cancelled
            ? 'Pemakaian ini sudah dibatalkan. Stok tidak dikurangi kembali.'
            : 'Pemakaian untuk produksi dicatat. Stok cabang aktif sudah berkurang.');
    }

    public function show(Request $request, int $transaction)
    {
        $user = $request->user();
        $location = $this->materials->isSuperAdmin($user) ? null : $this->materials->location($user);
        if ($location === null) $this->materials->authorize($user);
        $tx = DB::table('production_material_transactions')->where('id', $transaction)->first(); abort_unless($tx, 404);
        $this->materials->assertTransaction($tx, $location, $user);
        $items = DB::table('production_material_items')->where('transaction_id', $transaction)->orderBy('id')->get();
        $lotSources = $tx->stock_location === 'hendhys_stock_pusat' ? app(ProductionLots::class)->dispatchSources('production_material', $transaction) : [];
        $items = $items->map(fn ($i) => [...(array) $i, 'lots' => $lotSources[$i->id] ?? []]);
        $events = DB::table('production_material_events')->where('transaction_id', $transaction)->orderBy('id')->get()->map(fn ($e) => [...(array) $e, 'at' => $this->time($e->created_at), 'stock_changes' => json_decode($e->stock_changes, true)]);
        return Inertia::render('ProductionMaterials/Show', ['transaction' => $this->header($tx), 'items' => $items, 'events' => $events]);
    }

    public function cancel(Request $request, int $transaction)
    {
        $this->materials->authorize($request->user());
        if (is_string($request->input('reason'))) $request->merge(['reason' => trim($request->input('reason'))]);
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000', 'confirmed' => 'accepted']);
        $this->materials->cancel($transaction, $data, $request->user());
        return back()->with('success', 'Pemakaian dibatalkan. Stok kembali ke cabang asal dan riwayat pembatalan tersimpan.');
    }
}
