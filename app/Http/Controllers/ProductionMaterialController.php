<?php

namespace App\Http\Controllers;

use App\Models\HendhysProduction;
use App\Services\{ProductionLots, ProductionMaterials};
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
        $this->materials->authorize($request->user());
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'kind' => ['nullable', Rule::in(array_keys(ProductionMaterials::KINDS))],
            'status' => 'nullable|in:completed,cancelled', 'date_from' => 'nullable|date_format:Y-m-d', 'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])]]);
        $query = DB::table('production_material_transactions as t')->whereIn('t.status', ['completed', 'cancelled'])
            ->when(!empty($filters['kind']), fn ($q) => $q->where('t.kind', $filters['kind']))
            ->when(!empty($filters['status']), fn ($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->where('t.date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->where('t.date', '<=', $filters['date_to']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(function ($w) use ($term) {
                    foreach (['number', 'production_number', 'sender_name', 'receiver_name', 'created_by_name', 'reason', 'notes', 'evidence_reference', 'cancel_reason', 'cancelled_by_name'] as $field) $w->orWhere('t.'.$field, 'like', $term);
                    $w->orWhereExists(fn ($sq) => $sq->selectRaw('1')->from('production_material_items as i')->whereColumn('i.transaction_id', 't.id')
                        ->where(fn ($item) => $item->where('product_name', 'like', $term)->orWhere('product_code', 'like', $term)->orWhere('notes', 'like', $term)));
                });
            })->orderByDesc('t.date')->orderByDesc('t.id');
        $transactions = $query->paginate(15)->withQueryString();
        $items = DB::table('production_material_items')->whereIn('transaction_id', $transactions->pluck('id'))->orderBy('id')->get()->groupBy('transaction_id');
        $transactions->through(fn ($tx) => $this->header($tx) + ['items' => $items->get($tx->id, collect())->values()]);
        return Inertia::render('ProductionMaterials/Index', ['transactions' => $transactions, 'filters' => $filters, 'kinds' => ProductionMaterials::KINDS]);
    }

    public function create(Request $request)
    {
        $this->materials->authorize($request->user());
        $stocks = [];
        foreach (['warehouse_transfer' => 'jihans_gudang_stock', 'production_usage' => 'hendhys_stock_pusat'] as $kind => $table) {
            $stocks[$kind] = DB::table($table.' as s')->join('master_products as p', 'p.id', '=', 's.product_id')
                ->leftJoin('master_units as u', 'u.id', '=', 'p.unit_id')->leftJoin('master_units as su', 'su.id', '=', 's.unit_id')
                ->whereNull('p.deleted_at')->where('p.status', 'active')->where('s.quantity', '>', 0)->orderBy('p.name')
                ->get(['p.id', 'p.name', 'p.code', 'p.unit_id', 's.unit_id as stock_unit_id', 'u.abbreviation as unit', 'su.abbreviation as stock_unit', 's.quantity'])
                ->map(fn ($p) => [...(array) $p, 'quantity' => (int) round((float) $p->quantity), 'unit_matches' => $p->unit !== null && (int) $p->unit_id === (int) $p->stock_unit_id]);
        }
        return Inertia::render('ProductionMaterials/Create', ['stocks' => $stocks, 'kinds' => ProductionMaterials::KINDS,
            'productions' => HendhysProduction::orderByDesc('date')->orderByDesc('id')->get(['id', 'production_number', 'type', 'date'])->map(fn ($p) => ['id' => $p->id, 'number' => $p->production_number, 'type' => $p->type, 'date' => $p->date->format('d/m/Y')]),
            'lots' => ['warehouse_transfer' => app(ProductionLots::class)->choices('jihans'), 'production_usage' => app(ProductionLots::class)->choices('hendhys')],
            'today' => now('Asia/Jakarta')->toDateString(), 'currentTime' => now('Asia/Jakarta')->format('H:i')]);
    }

    public function store(Request $request)
    {
        $this->materials->authorize($request->user());
        foreach (['sender_name', 'receiver_name', 'reason', 'notes', 'evidence_reference'] as $key) if (is_string($request->input($key))) $request->merge([$key => trim($request->input($key))]);
        $data = $request->validate([
            'request_key' => 'required|uuid', 'kind' => ['required', Rule::in(array_keys(ProductionMaterials::KINDS))],
            'date' => 'required|date_format:Y-m-d|before_or_equal:'.now('Asia/Jakarta')->toDateString(), 'time' => 'required|date_format:H:i',
            'production_id' => 'nullable|required_if:kind,production_usage|integer|exists:hendhys_productions,id',
            'sender_name' => 'required|string|min:2|max:255', 'receiver_name' => 'required|string|min:2|max:255',
            'reason' => 'required|string|min:5|max:1000', 'notes' => 'nullable|string|max:2000', 'evidence_reference' => 'nullable|string|max:255',
            'items' => 'required|array|min:1|max:100', 'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('master_products', 'id')->whereNull('deleted_at')->where('status', 'active')],
            'items.*.quantity' => 'required|integer|min:1|max:999999999', 'items.*.lot_id' => 'nullable|integer|min:1|exists:production_lots,id', 'items.*.notes' => 'nullable|string|max:500', 'confirmed' => 'accepted',
        ]);
        if (Carbon::parse($data['date'].' '.$data['time'], 'Asia/Jakarta')->gt(now())) throw ValidationException::withMessages(['time' => 'Tanggal dan jam kejadian tidak boleh melewati waktu sekarang (WIB).']);
        $id = $this->materials->record($data, $request->user());
        $cancelled = DB::table('production_material_transactions')->where('id', $id)->value('status') === 'cancelled';
        return redirect()->route('production-materials.show', $id)->with('success', $cancelled
            ? 'Transaksi ini sudah dibatalkan. Stok tidak dipindahkan kembali.'
            : 'Barang untuk produksi berhasil dicatat beserta perubahan stok.');
    }

    public function show(Request $request, int $transaction)
    {
        $this->materials->authorize($request->user());
        $tx = DB::table('production_material_transactions')->where('id', $transaction)->first(); abort_unless($tx, 404);
        $items = DB::table('production_material_items')->where('transaction_id', $transaction)->orderBy('id')->get();
        $lotSources = app(ProductionLots::class)->dispatchSources('production_material', $transaction);
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
        return back()->with('success', 'Transaksi dibatalkan. Stok dipulihkan dan jejak pembatalan tersimpan.');
    }
}
