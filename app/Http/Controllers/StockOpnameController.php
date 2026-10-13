<?php

namespace App\Http\Controllers;

use App\Services\StockOpname;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StockOpnameController extends Controller
{
    public function __construct(private StockOpname $opname) {}

    public function index(Request $request)
    {
        $locations = collect($this->opname->locations())->filter(fn ($l) => $this->opname->allowed($request->user(), $l))->values();
        $sessions = $this->opname->visibleSessions($request->user())
            ->orderByDesc('id')->paginate(15)->withQueryString();
        $sessions->through(fn ($s) => $this->opname->serialize($s));
        return Inertia::render('StockOpname/Index', [
            'locations' => $locations, 'sessions' => $sessions, 'isOwner' => $request->user()->hasRole('owner'),
        ]);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'request_key' => ['required', 'uuid'], 'location_key' => ['required', 'string', 'max:80'],
            'expires_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'notes' => ['required', 'string', 'min:5', 'max:1000'], 'confirmed' => ['accepted'],
        ]);
        $request->merge(['notes' => trim($data['notes'])]);
        $request->validate(['notes' => ['required', 'string', 'min:5']]);
        $id = $this->opname->open($data, $request->user());
        return redirect()->route('stock-opname.show', $id)->with('success', 'Sesi stock opname dibuka. Hanya lokasi yang dipilih mendapat akses.');
    }

    public function history(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100', 'location_key' => 'nullable|string|max:80',
            'counted_by' => 'nullable|integer', 'session_id' => 'nullable|integer', 'product_id' => 'nullable|integer',
            'difference' => 'nullable|in:increase,decrease,same', 'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('date_from'), 'after_or_equal:date_from')],
        ]);
        $base = DB::table('stock_opname_counts as c')->join('stock_opname_sessions as s', 's.id', '=', 'c.session_id')
            ->whereIn('s.id', $this->opname->visibleSessions($request->user())->select('id'));
        $actors = (clone $base)->select('c.counted_by as id', 'c.counted_by_name as name')->distinct()->orderBy('name')->get()->unique('id')->values();
        $locations = $this->opname->visibleSessions($request->user())->orderByDesc('id')->get(['location_key as key', 'location_name as name'])->unique('key')->values();
        $query = (clone $base)->leftJoin('master_products as p', 'p.id', '=', 'c.product_id')
            ->when($request->filled('location_key'), fn ($q) => $q->where('s.location_key', $filters['location_key']))
            ->when($request->filled('counted_by'), fn ($q) => $q->where('c.counted_by', $filters['counted_by']))
            ->when($request->filled('session_id'), fn ($q) => $q->where('c.session_id', $filters['session_id']))
            ->when($request->filled('product_id'), fn ($q) => $q->where('c.product_id', $filters['product_id']))
            ->when($request->filled('difference'), fn ($q) => $q->where('c.difference', ['increase' => '>', 'decrease' => '<', 'same' => '='][$filters['difference']], 0))
            ->when($request->filled('date_from'), fn ($q) => $q->where('c.created_at', '>=', \Carbon\Carbon::parse($filters['date_from'], 'Asia/Jakarta')->startOfDay()))
            ->when($request->filled('date_to'), fn ($q) => $q->where('c.created_at', '<', \Carbon\Carbon::parse($filters['date_to'], 'Asia/Jakarta')->addDay()->startOfDay()))
            ->when($request->filled('search'), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(function ($w) use ($term, $filters) {
                    foreach (['c.product_name', 'c.product_code', 'c.counted_by_name', 'c.notes', 's.location_name', 's.opened_by_name'] as $column) $w->orWhere($column, 'like', $term);
                    // For older rows, this is explicitly shown as the current code, not a historical snapshot.
                    $w->orWhere(fn ($old) => $old->whereNull('c.product_code')->where('p.code', 'like', $term));
                    if (preg_match('/^SO-0*(\d+)$/i', trim($filters['search']), $match)) $w->orWhere('c.id', (int) $match[1]);
                });
            });
        $summary = (clone $query)->selectRaw('COUNT(*) AS total, SUM(CASE WHEN c.difference < 0 THEN 1 ELSE 0 END) AS decreased, SUM(CASE WHEN c.difference > 0 THEN 1 ELSE 0 END) AS increased, SUM(CASE WHEN c.difference = 0 THEN 1 ELSE 0 END) AS unchanged')->first();
        $counts = $query->select('c.*', 's.location_name', 's.opened_by_name', 'p.code as current_product_code')
            ->orderByDesc('c.created_at')->orderByDesc('c.id')->paginate(20)->withQueryString();
        $counts->through(fn ($c) => $this->opname->serializeCount($c));
        return Inertia::render('StockOpname/History', ['counts' => $counts, 'filters' => $filters, 'actors' => $actors, 'locations' => $locations,
            'summary' => collect((array) $summary)->map(fn ($n) => (int) $n),
        ]);
    }

    public function result(Request $request, int $count)
    {
        $c = DB::table('stock_opname_counts')->where('id', $count)->first();
        abort_unless($c, 404);
        $s = DB::table('stock_opname_sessions')->where('id', $c->session_id)->first();
        abort_unless($s && $this->opname->allowed($request->user(), $s), 403);
        [$table, $movementTable, $branchId, $lotEntity] = $this->opname->table($s);
        $current = DB::table($table.' as stock')->join('master_units as u', 'u.id', '=', 'stock.unit_id')
            ->where('stock.product_id', $c->product_id)->when($branchId, fn ($q) => $q->where('stock.branch_id', $branchId))
            ->first(['stock.quantity', 'u.abbreviation as unit']);
        if ($current) $current->quantity = (int) round((float) $current->quantity);
        $movementQuery = DB::table($movementTable)->where('source', 'adjustment')->where('reference_id', $c->id)
            ->where('product_id', $c->product_id)->where('created_by', $c->counted_by)
            ->where('quantity_before', $c->quantity_before)->where('quantity_after', $c->quantity_after)
            ->where('notes', "Stock opname sesi #{$s->id}, hasil #{$c->id} — {$s->location_name}: ".$c->notes);
        if ($movementTable === 'hendhys_stock_movements') $branchId ? $movementQuery->where('branch_id', $branchId) : $movementQuery->whereNull('branch_id');
        $direct = $c->stock_movement_table === $movementTable && $c->stock_movement_id !== null;
        if ($c->stock_movement_id !== null || $c->stock_movement_table !== null) $movementQuery->where('id', $direct ? $c->stock_movement_id : -1);
        $matches = $movementQuery->limit(2)->get();
        $movement = $matches->count() === 1 ? $matches->first() : null;
        if ($movement) $movement = ['id' => $movement->id, 'type' => $movement->type, 'quantity' => (int) $movement->quantity,
            'created_at' => \Carbon\Carbon::parse($movement->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB',
            'verified_by' => $direct ? 'Tautan langsung hasil SO' : 'Jejak dokumen SO lama yang cocok', 'notes' => $movement->notes];
        $lots = $lotEntity ? DB::table('production_lot_outflows as o')->join('production_lots as l', 'l.id', '=', 'o.lot_id')
            ->where('o.source', 'stock_opname')->where('o.reference_id', $count)->where('l.entity', $lotEntity)->where('l.product_id', $c->product_id)
            ->orderBy('o.id')->get(['l.id as lot_id', 'l.production_number', 'l.production_date', 'o.quantity'])->map(fn ($l) => [...(array) $l, 'quantity' => (int) $l->quantity]) : collect();
        $currentCode = DB::table('master_products')->where('id', $c->product_id)->value('code');
        return Inertia::render('StockOpname/Result', [
            'count' => [...$this->opname->serializeCount($c), 'current_product_code' => $currentCode],
            'session' => $this->opname->serialize($s), 'currentStock' => $current, 'checkedAt' => now('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB',
            'movement' => $movement, 'lotEffects' => $lots, 'centralStock' => $lotEntity !== null,
        ]);
    }

    public function close(Request $request, int $session)
    {
        $request->validate(['confirmed' => ['accepted']]);
        $this->opname->close($session, $request->user());
        return back()->with('success', 'Sesi ditutup. Hasil opname dan riwayat tetap tersimpan.');
    }

    public function reopen(Request $request, int $session)
    {
        $data = $request->validate([
            'request_key' => ['required', 'uuid'], 'expires_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'notes' => ['required', 'string', 'min:5', 'max:1000'], 'confirmed' => ['accepted'],
        ]);
        $this->opname->reopen($session, $data, $request->user());
        return redirect()->route('stock-opname.show', $session)->with('success', 'Sesi SO dibuka kembali. Nomor sesi dan hasil sebelumnya tetap tersimpan.');
    }

    public function show(Request $request, int $session)
    {
        $s = DB::table('stock_opname_sessions')->where('id', $session)->first();
        abort_unless($s, 404);
        abort_unless($this->opname->allowed($request->user(), $s), 403);
        [$table, , $branchId] = $this->opname->table($s);
        $products = DB::table($table.' as s')->join('master_products as p', 'p.id', '=', 's.product_id')
            ->join('master_units as u', 'u.id', '=', 's.unit_id')
            ->whereNull('p.deleted_at')->where('p.status', 'active')
            ->when($branchId, fn ($q) => $q->where('s.branch_id', $branchId))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('p.name', 'like', '%'.$request->string('search').'%')->orWhere('p.code', 'like', '%'.$request->string('search').'%')))
            ->select('p.id', 'p.name', 'p.code', 's.quantity', 'u.abbreviation as unit')
            ->orderBy('p.name')->paginate(20)->withQueryString();
        $products->through(fn ($p) => array_merge((array) $p, ['quantity' => (int) round((float) $p->quantity)]));
        $countQuery = DB::table('stock_opname_counts')->where('session_id', $session);
        $countTotal = (clone $countQuery)->count();
        $counts = (clone $countQuery)->orderByDesc('id')->limit(5)->get()->map(fn ($c) => $this->opname->serializeCount($c));
        $productCounts = (clone $countQuery)->whereIn('product_id', $products->getCollection()->pluck('id'))
            ->get()->map(fn ($c) => $this->opname->serializeCount($c));
        $events = DB::table('stock_opname_session_events')->where('session_id', $session)->orderByDesc('id')
            ->paginate(20, ['*'], 'access_page')->withQueryString();
        $location = collect($this->opname->locations())->firstWhere('key', $s->location_key);
        return Inertia::render('StockOpname/Show', [
            'session' => $this->opname->serialize($s), 'products' => $products, 'counts' => $counts,
            'countTotal' => $countTotal, 'productCounts' => $productCounts,
            'isOwner' => $request->user()->hasRole('owner'), 'description' => $location['description'] ?? 'Lokasi sudah tidak aktif.',
            'canCount' => !$request->user()->hasRole('owner') && $this->opname->active($s) && $location !== null && $this->opname->allowed($request->user(), $location),
            'filters' => ['search' => $request->input('search', '')],
            'eventsPagination' => \App\Support\ListPage::metadata($events),
            'events' => $events->getCollection()->map(function ($event) {
                $row = (array) $event;
                foreach (['created_at', 'previous_expires_at', 'expires_at', 'previous_closed_at'] as $field) $row[$field] = $event->$field ? \Carbon\Carbon::parse($event->$field)->format('d/m/Y H:i').' WIB' : null;
                return $row;
            }),
        ]);
    }

    public function count(Request $request, int $session)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('master_products', 'id')->whereNull('deleted_at')->where('status', 'active')],
            'expected_quantity' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:0', 'max:999999999'],
            'notes' => ['required', 'string', 'min:5', 'max:1000'], 'confirmed' => ['accepted'],
        ]);
        $request->merge(['notes' => trim($data['notes'])]);
        $request->validate(['notes' => ['required', 'string', 'min:5']]);
        $this->opname->count($session, $data, $request->user());
        return back()->with('success', 'Hasil hitung fisik disimpan dan saldo stok disesuaikan.');
    }
}
