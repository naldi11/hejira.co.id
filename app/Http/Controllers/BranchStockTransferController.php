<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\BranchStockTransfers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BranchStockTransferController extends Controller
{
    public function __construct(private BranchStockTransfers $transfers) {}

    public function index(Request $request)
    {
        $isHistory = $request->routeIs('branch-transfers.history');
        $today = now('Asia/Jakarta')->toDateString();
        if (!$isHistory && !$request->hasAny(['date_from', 'date_to'])) $request->merge(['date_from' => $today, 'date_to' => $today]);
        $filters = $request->validate([
            'search' => 'nullable|string|max:100', 'status' => 'nullable|in:sent,partial,received,cancelled',
            'from_branch_id' => 'nullable|integer', 'to_branch_id' => 'nullable|integer',
            'date_from' => 'nullable|date_format:Y-m-d', 'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('date_from'), 'after_or_equal:date_from')],
        ]);
        $query = $this->transfers->visibleQuery($request->user())->select('branch_stock_transfers.*')
            ->addSelect(['latest_receiver' => DB::table('branch_stock_transfer_receipts')->select('received_by_name')->whereColumn('transfer_id', 'branch_stock_transfers.id')->orderByDesc('id')->limit(1)])
            ->when($request->filled('search'), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(function ($w) use ($term) {
                    foreach (['number', 'from_branch_name', 'to_branch_name', 'sent_by_name', 'physical_sender_name', 'reason', 'notes', 'courier_name', 'cancel_reason', 'cancelled_by_name'] as $column) {
                        $w->orWhere('branch_stock_transfers.'.$column, 'like', $term);
                    }
                    $w->orWhereExists(fn ($s) => $s->selectRaw('1')->from('branch_stock_transfer_items as i')
                        ->whereColumn('i.transfer_id', 'branch_stock_transfers.id')
                        ->where(fn ($i) => $i->where('i.product_name', 'like', $term)->orWhere('i.product_code', 'like', $term)->orWhere('i.notes', 'like', $term)->orWhere('i.to_product_name', 'like', $term)->orWhere('i.to_product_code', 'like', $term)))
                        ->orWhereExists(fn ($s) => $s->selectRaw('1')->from('branch_stock_transfer_receipts as r')
                            ->whereColumn('r.transfer_id', 'branch_stock_transfers.id')
                            ->where(fn ($r) => $r->where('r.physical_receiver_name', 'like', $term)->orWhere('r.received_by_name', 'like', $term)->orWhere('r.notes', 'like', $term)));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $filters['status']))
            ->when($request->filled('from_branch_id'), fn ($q) => $q->where('from_branch_id', $filters['from_branch_id']))
            ->when($request->filled('to_branch_id'), fn ($q) => $q->where('to_branch_id', $filters['to_branch_id']))
            ->when($request->filled('date_from'), fn ($q) => $q->where('date', '>=', $filters['date_from']))
            ->when($request->filled('date_to'), fn ($q) => $q->where('date', '<=', $filters['date_to']))
            ->orderByDesc('date')->orderByDesc('id');
        $query = \App\Support\ListPage::paginate($query, $request);
        $filters['per_page'] = $request->input('per_page', '20');
        $items = DB::table('branch_stock_transfer_items')->whereIn('transfer_id', $query->pluck('id'))->orderBy('product_name')->get()->groupBy('transfer_id');
        $receivers = DB::table('branch_stock_transfer_receipts')->whereIn('transfer_id', $query->pluck('id'))->orderByDesc('id')->get()->groupBy('transfer_id');
        $query->through(fn ($t) => [...$this->transfers->serialize($t),
            'items' => $items->get($t->id, collect())->map(fn ($i) => ['name' => $i->product_name, 'code' => $i->product_code, 'quantity' => $this->transfers->display($i->quantity, $i->quantity_scale), 'unit' => $i->unit_name, 'to_name' => $i->to_product_name ?? $i->product_name, 'to_quantity' => $this->transfers->display($i->destination_quantity ?? $i->quantity, $i->to_quantity_scale), 'to_unit' => $i->to_unit_name ?? $i->unit_name])->values(),
            'receiver_names' => $receivers->get($t->id, collect())->pluck('physical_receiver_name')->unique()->values(),
        ]);
        return Inertia::render('BranchTransfers/Index', [
            'transfers' => $query, 'filters' => $filters,
            'branches' => $this->transfers->branches()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'entity' => $b->entity]),
            'canCreate' => $this->transfers->sourceBranches($request->user())->isNotEmpty(),
            'isHistory' => $isHistory, 'today' => $today,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate(['from_branch_id' => 'nullable|integer']);
        $sources = $this->transfers->sourceBranches($request->user());
        abort_if($sources->isEmpty(), 403, 'Pilih cabang aktif yang dapat Anda kelola.');
        $from = $request->filled('from_branch_id') ? $sources->firstWhere('id', (int) $request->from_branch_id) : $sources->first();
        abort_unless($from, 403);
        $request->validate(['to_branch_id' => 'nullable|integer']);
        $destinations = $this->transfers->branches()->where('id', '!=', $from->id)->values();
        $to = $request->filled('to_branch_id') ? $destinations->firstWhere('id', $request->integer('to_branch_id')) : null;
        abort_if($request->filled('to_branch_id') && !$to, 422);
        $source = $this->sourceProducts($from)->simplePaginate(20);
        $destination = $to ? $this->destinationProducts($to)->simplePaginate(20) : null;
        return Inertia::render('BranchTransfers/Create', [
            'sources' => $sources->map(fn ($b) => $b->only(['id','name','entity'])),
            'destinations' => $destinations->map(fn ($b) => $b->only(['id','name','entity'])),
            'from' => $from->only(['id','name','entity']), 'to' => $to?->only(['id','name','entity']),
            'products' => $source->getCollection()->map(fn ($p) => $this->sourceProduct($p, $from)),
            'catalogue' => ['page' => 1, 'has_more' => $source->hasMorePages()],
            'destinationProducts' => $destination?->items() ?? [],
            'destinationCatalogue' => ['page' => 1, 'has_more' => $destination?->hasMorePages() ?? false],
            'today' => now('Asia/Jakarta')->toDateString(), 'currentTime' => now('Asia/Jakarta')->format('H:i'),
        ]);
    }
    private function sourceProducts(Branch $branch, ?string $search = null)
    {
        $table = $this->transfers->stockTable($branch->entity);
        return DB::table($table.' as s')->join('master_products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('master_units as u', 'u.id', '=', 'p.unit_id')
            ->whereNull('p.deleted_at')->where('p.status', 'active')->where('s.quantity', '>', 0)
            ->when($this->transfers->hasBranchStock($branch->entity), fn ($q) => $q->where('s.branch_id', $branch->id))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('p.name', 'like', '%'.$search.'%')->orWhere('p.code', 'like', '%'.$search.'%')))
            ->orderBy('p.name')->orderBy('p.id')->select('p.id','p.name','p.code','p.unit_id','u.abbreviation as unit','s.quantity');
    }
    private function sourceProduct(object $p, Branch $b): array
    {
        return [...(array) $p, 'quantity' => $b->entity === 'izzi' ? $this->transfers->display($p->quantity, 1000) : (int) round((float) $p->quantity),
            'stock_unit_id' => $p->unit_id, 'stock_unit' => $p->unit, 'unit_matches' => $p->unit !== null];
    }
    private function destinationProducts(Branch $b, ?string $search = null)
    {
        return DB::table('master_products as p')->join('master_units as u', 'u.id', '=', 'p.unit_id')
            ->whereNull('p.deleted_at')->where('p.status', 'active')
            ->when($b->entity === 'izzi', fn ($q) => $q->whereExists(fn ($s) => $s->selectRaw('1')->from('izzi_ingredients as i')->whereColumn('i.product_id','p.id')))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('p.name','like','%'.$search.'%')->orWhere('p.code','like','%'.$search.'%')))
            ->orderBy('p.name')->orderBy('p.id')->select('p.id','p.name','p.code','p.unit_id','u.abbreviation as unit');
    }
    public function sourceCatalogue(Request $request, Branch $branch)
    {
        abort_unless($this->transfers->canOperate($request->user(), $branch), 403);
        $request->validate(['q'=>'nullable|string|max:100','catalog_page'=>'nullable|integer|min:1']);
        $page = $this->sourceProducts($branch, $request->q)->simplePaginate(20, ['*'], 'catalog_page');
        return response()->json(['products'=>$page->getCollection()->map(fn ($p) => $this->sourceProduct($p, $branch)), 'branch_id'=>$branch->id,
            'catalogue'=>['page'=>$page->currentPage(),'has_more'=>$page->hasMorePages()]]);
    }
    public function destinationCatalogue(Request $request, Branch $branch)
    {
        abort_unless($this->transfers->sourceBranches($request->user())->isNotEmpty() && $this->transfers->branches()->contains('id', $branch->id), 403);
        $request->validate(['q'=>'nullable|string|max:100','catalog_page'=>'nullable|integer|min:1']);
        $page = $this->destinationProducts($branch, $request->q)->simplePaginate(20, ['*'], 'catalog_page');
        return response()->json(['products'=>$page->items(),'branch_id'=>$branch->id,'catalogue'=>['page'=>$page->currentPage(),'has_more'=>$page->hasMorePages()]]);
    }

    public function store(Request $request)
    {
        $request->validate(['from_branch_id' => 'required|integer|exists:master_branches,id']);
        $from = Branch::findOrFail($request->integer('from_branch_id'));
        abort_unless($this->transfers->canOperate($request->user(), $from), 403);
        foreach (['reason', 'notes', 'courier_name', 'physical_sender_name', 'physical_receiver_name'] as $key) if (is_string($request->input($key))) $request->merge([$key => trim($request->input($key))]);
        $data = $request->validate([
            'request_key' => 'required|uuid', 'from_branch_id' => 'required|integer|exists:master_branches,id',
            'to_branch_id' => 'required|integer|exists:master_branches,id|different:from_branch_id',
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'occurred_time' => 'required|date_format:H:i',
            'physical_sender_name' => 'required|string|min:2|max:255',
            'physical_receiver_name' => 'required|string|min:2|max:255',
            'reason' => 'required|string|min:5|max:1000', 'notes' => 'nullable|string|max:2000', 'courier_name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('master_products', 'id')->whereNull('deleted_at')->where('status', 'active')],
            'items.*.quantity' => 'required|numeric|min:0.001|max:999999999',
            'items.*.to_product_id' => ['nullable','integer', Rule::exists('master_products','id')->whereNull('deleted_at')->where('status','active')],
            'items.*.conversion_from' => 'nullable|numeric|min:0.001|max:999999999', 'items.*.conversion_to' => 'nullable|numeric|min:0.001|max:999999999', 'items.*.notes' => 'nullable|string|max:500', 'confirmed' => 'accepted',
        ]);
        if (\Carbon\Carbon::parse($data['date'].' '.$data['occurred_time'], 'Asia/Jakarta')->gt(now())) {
            throw \Illuminate\Validation\ValidationException::withMessages(['occurred_time' => 'Waktu mutasi tidak boleh melewati waktu sekarang dalam WIB.']);
        }
        $id = $this->transfers->recordReceived($data, $request->user());
        return redirect()->route('branch-transfers.show', $id)->with('success', 'Mutasi selesai dicatat. Stok asal berkurang dan stok cabang penerima bertambah sesuai rincian.');
    }

    public function show(Request $request, int $transfer)
    {
        $t = $this->transfers->findVisible($transfer, $request->user());
        $rawDetails = DB::table('branch_stock_transfer_items')->where('transfer_id', $transfer)->orderBy('product_id')->get();
        $details = $rawDetails->map(fn ($d) => [...$this->transfers->serializeItem($d), 'remaining' => $t->status === 'cancelled' ? 0 : $this->transfers->display($d->quantity - $d->received_quantity, $d->quantity_scale)]);
        $receipts = DB::table('branch_stock_transfer_receipts')->where('transfer_id', $transfer)->orderByDesc('id')->get();
        $receiptItems = DB::table('branch_stock_transfer_receipt_items as ri')->join('branch_stock_transfer_items as i', 'i.id', '=', 'ri.transfer_item_id')
            ->whereIn('ri.receipt_id', $receipts->pluck('id'))->get(['ri.receipt_id', 'ri.quantity','ri.destination_quantity','i.quantity_scale','i.to_quantity_scale','i.product_name','i.unit_name','i.to_product_name','i.to_unit_name'])->groupBy('receipt_id');
        $receipts = $receipts->map(fn ($r) => [...(array) $r,
            'received_at' => \Carbon\Carbon::parse($r->received_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB',
            'items' => $receiptItems->get($r->id, collect())->map(fn ($i) => [...(array) $i, 'quantity' => $this->transfers->display($i->quantity, $i->quantity_scale), 'destination_quantity' => $this->transfers->display($i->destination_quantity ?? $i->quantity, $i->to_quantity_scale), 'to_product_name' => $i->to_product_name ?? $i->product_name, 'to_unit_name' => $i->to_unit_name ?? $i->unit_name])->values(),
        ]);
        $from = Branch::find($t->from_branch_id); $to = Branch::find($t->to_branch_id);
        $movements = collect();
        $tables = array_unique([$this->transfers->movementTable($t->from_entity), $this->transfers->movementTable($t->to_entity)]);
        foreach ($tables as $table) {
            $movements = $movements->concat(DB::table($table.' as m')->where('branch_transfer_id', $transfer)->orderBy('m.id')->get()->map(function ($m) use ($table, $t, $rawDetails) {
                $item = $m->branch_transfer_item_id ? $rawDetails->firstWhere('id', $m->branch_transfer_item_id) : $rawDetails->firstWhere('product_id', $m->product_id);
                $origin = $m->branch_transfer_side ? $m->branch_transfer_side === 'from' : (in_array($table, ['jihans_retail_stock_movements', 'jihans_gudang_stock_movements']) ? $t->from_entity === ($table === 'jihans_gudang_stock_movements' ? 'gudang' : 'jihans') : (int) $m->branch_id === (int) $t->from_branch_id);
                $scale = $table === 'izzi_stock_movements' ? 1000 : 1;
                return ['id' => $table.'-'.$m->id, 'location' => $origin ? $t->from_branch_name : $t->to_branch_name,
                    'product' => $origin ? $item?->product_name : ($item?->to_product_name ?? $item?->product_name),
                    'unit' => $origin ? $item?->unit_name : ($item?->to_unit_name ?? $item?->unit_name),
                    'before' => $this->transfers->display($m->quantity_before, $scale), 'after' => $this->transfers->display($m->quantity_after, $scale),
                    'delta' => $this->transfers->display($m->type === 'out' ? -$m->quantity : $m->quantity, $scale),
                    'notes' => $m->notes, 'sort_at' => \Carbon\Carbon::parse($m->created_at)->timestamp,
                    'at' => \Carbon\Carbon::parse($m->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB'];
            }));
        }
        return Inertia::render('BranchTransfers/Show', [
            'transfer' => $this->transfers->serialize($t), 'items' => $details, 'receipts' => $receipts,
            'stockChanges' => $movements->sortBy('sort_at')->values(),
            'canReceive' => $to && $to->entity === $t->to_entity && $this->transfers->canOperate($request->user(), $to) && in_array($t->status, ['sent', 'partial'], true),
            'canCancel' => $from && $from->entity === $t->from_entity && $this->transfers->canOperate($request->user(), $from) && $t->status === 'sent',
        ]);
    }

    public function receive(Request $request, int $transfer)
    {
        foreach (['physical_receiver_name', 'notes'] as $key) if (is_string($request->input($key))) $request->merge([$key => trim($request->input($key))]);
        $data = $request->validate([
            'request_key' => 'required|uuid', 'physical_receiver_name' => 'required|string|min:2|max:255', 'notes' => 'required|string|min:5|max:2000',
            'items' => 'required|array|min:1|max:100', 'items.*.id' => 'required|integer|distinct',
            'items.*.expected_received_quantity' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0|max:999999999', 'confirmed' => 'accepted',
        ]);
        $this->transfers->receive($transfer, $data, $request->user());
        return back()->with('success', 'Penerimaan dicatat. Hanya jumlah yang diterima ditambahkan ke stok tujuan.');
    }

    public function cancel(Request $request, int $transfer)
    {
        if (is_string($request->input('reason'))) $request->merge(['reason' => trim($request->input('reason'))]);
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000', 'confirmed' => 'accepted']);
        $this->transfers->cancel($transfer, $data['reason'], $request->user());
        return back()->with('success', 'Mutasi dibatalkan. Stok kembali ke saldo asal dan riwayat tetap tersimpan.');
    }
}
