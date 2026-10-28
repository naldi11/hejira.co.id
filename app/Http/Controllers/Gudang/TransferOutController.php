<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StoreTransferOutRequest;
use App\Http\Resources\Gudang\TransferOutResource;
use App\Models\Branch;
use App\Models\JihansGudangStock;
use App\Models\Product;
use App\Models\TransferOut;
use App\Models\TransferRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransferOutController extends Controller
{
    public function index(Request $request)
    {
        $transfers = TransferOut::with(['request', 'branch', 'creator'])
            ->when($request->filled('search'), fn ($q) => $q->where('transfer_number', 'like', "%{$request->search}%"))
            ->when($request->filled('to_entity'), fn ($q) => $q->where('to_entity', $request->to_entity))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return Inertia::render('Gudang/TransferOut/Index', [
            'transfers' => TransferOutResource::collection($transfers),
            'filters'   => $request->only('search', 'to_entity'),
        ]);
    }

    public function create(Request $request)
    {
        $products = Product::where('status', 'active')
            ->visibleInGudang()
            ->with('unit')
            ->leftJoin('jihans_gudang_stock', 'master_products.id', '=', 'jihans_gudang_stock.product_id')
            ->select('master_products.*', 'jihans_gudang_stock.quantity as current_stock')
            ->orderBy('master_products.name')
            ->get()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'stock' => (int) ($p->current_stock ?? 0),
                'unit_id'   => $p->unit_id,
                'unit_name' => $p->unit?->abbreviation ?? 'PCS',
                'hpp'   => (float) $p->hpp,
            ]);

        $branches = Branch::where('is_active', true)->where('entity', 'hendhys')
            ->orderByRaw("CASE WHEN type = 'pusat' THEN 0 ELSE 1 END")
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'type' => $b->type]);

        $transferRequest = null;
        if ($request->filled('request_id')) {
            $tr = TransferRequest::with('details.product', 'details.unit', 'branch')
                ->whereIn('status', ['approved', 'partial'])
                ->findOrFail($request->request_id);

            $stocks = JihansGudangStock::whereIn('product_id', $tr->details->pluck('product_id'))->pluck('quantity', 'product_id');

            $transferRequest = [
                'id'             => $tr->id,
                'request_number' => $tr->request_number,
                'from_entity'    => $tr->from_entity,
                'branch_id'      => $tr->branch_id,
                'branch'         => $tr->branch?->name,
                'items'          => $tr->details
                    ->filter(fn ($d) => $d->quantity_approved > $d->quantity_sent)
                    ->map(fn ($d) => [
                        'product_id'        => $d->product_id,
                        'product_name'      => $d->product?->name,
                        'stock'             => (int) ($stocks[$d->product_id] ?? 0),
                        'quantity_approved' => (int) $d->quantity_approved,
                        'quantity_sent'     => (int) $d->quantity_sent,
                        'quantity'          => (int) max(0, $d->quantity_approved - $d->quantity_sent),
                        'unit_id'           => $d->unit_id,
                        'unit_name'         => $d->unit?->abbreviation,
                        'hpp_price'         => (float) $d->product?->hpp,
                    ])->values(),
            ];
        }

        $izziBranches = Branch::where('entity', 'izzi')->where('type', 'cabang')->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Gudang/TransferOut/Create', [
            'products'        => $products,
            'branches'        => $branches,
            'transferRequest' => $transferRequest,
            'productionLots' => app(\App\Services\ProductionLots::class)->choices('jihans'),
            'izziBranches' => $izziBranches,
        ]);
    }

    public function store(StoreTransferOutRequest $request)
    {
        app(\App\Services\WarehouseDispatch::class)->send($request->validated(), $request->user());

        return redirect()->route('gudang.transfer-out.index')->with('success', 'Transfer keluar berhasil diproses.');
    }

    public function show(TransferOut $transferOut)
    {
        $transferOut->load(['request', 'branch', 'creator', 'details.product', 'details.unit']);

        return Inertia::render('Gudang/TransferOut/Show', [
            'transfer' => new TransferOutResource($transferOut),
        ]);
    }

    /** Surat jalan yang menyertai barang — dokumen cetak, bukan halaman Inertia. */
    public function print(TransferOut $transferOut)
    {
        $transferOut->load(['request', 'branch', 'creator', 'details.product', 'details.unit']);

        return view('gudang.transfer-out.print', compact('transferOut'));
    }
}
