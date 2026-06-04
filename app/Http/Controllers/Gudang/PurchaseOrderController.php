<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\SavePurchaseOrderRequest;
use App\Http\Resources\Gudang\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $orders = PurchaseOrder::with('supplier')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('po_number', 'like', "%{$request->search}%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$request->search}%"))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return Inertia::render('Gudang/PurchaseOrders/Index', [
            'orders'  => PurchaseOrderResource::collection($orders),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Gudang/PurchaseOrders/Form', $this->formOptions());
    }

    public function store(SavePurchaseOrderRequest $request)
    {
        $data = $request->validated();
        $newPo = null;

        DB::transaction(function () use ($data, &$newPo) {
            $total = collect($data['items'])->sum(fn ($i) => (int) $i['quantity'] * (float) $i['price']);

            $newPo = PurchaseOrder::create([
                'po_number'     => $this->numbers->generateYearly('GDG-PO', 'gudang_purchase_orders', 'po_number'),
                'supplier_id'   => $data['supplier_id'],
                'date'          => $data['date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status'        => 'draft',
                'total_amount'  => $total,
                'notes'         => $data['notes'] ?? null,
                'created_by'    => auth()->id(),
            ]);

            $this->syncDetails($newPo, $data['items']);

            $this->logger->log('create', 'gudang.po', "Buat PO: {$newPo->po_number}", $newPo);
        });

        return redirect()
            ->route('gudang.receiving.create', ['po_id' => $newPo->id])
            ->with('success', "PO {$newPo->po_number} berhasil dibuat. Silakan input penerimaan barang.");
    }

    public function show(PurchaseOrder $po)
    {
        $po->load(['supplier', 'details.product', 'details.unit', 'creator', 'receivings.details.product']);

        return Inertia::render('Gudang/PurchaseOrders/Show', [
            'po' => new PurchaseOrderResource($po),
        ]);
    }

    public function edit(PurchaseOrder $po)
    {
        abort_if(! $po->isEditable(), 403, 'PO sudah tidak bisa diedit.');

        $po->load('details');

        return Inertia::render('Gudang/PurchaseOrders/Form', [
            ...$this->formOptions(),
            'po' => new PurchaseOrderResource($po),
        ]);
    }

    public function update(SavePurchaseOrderRequest $request, PurchaseOrder $po)
    {
        abort_if(! $po->isEditable(), 403, 'PO sudah tidak bisa diedit.');

        $data = $request->validated();

        DB::transaction(function () use ($data, $po) {
            $total = collect($data['items'])->sum(fn ($i) => (int) $i['quantity'] * (float) $i['price']);

            $po->update([
                'supplier_id'   => $data['supplier_id'],
                'date'          => $data['date'],
                'expected_date' => $data['expected_date'] ?? null,
                'total_amount'  => $total,
                'notes'         => $data['notes'] ?? null,
                'updated_by'    => auth()->id(),
            ]);

            $po->details()->delete();
            $this->syncDetails($po, $data['items']);

            $this->logger->log('update', 'gudang.po', "Update PO: {$po->po_number}", $po);
        });

        return redirect()->route('gudang.po.show', $po)->with('success', 'PO berhasil diperbarui.');
    }

    public function cancel(PurchaseOrder $po)
    {
        abort_if(! in_array($po->status, ['draft', 'sent']), 403, 'PO tidak bisa dibatalkan.');

        $po->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);
        $this->logger->log('update', 'gudang.po', "PO dibatalkan: {$po->po_number}", $po);

        return back()->with('success', "PO {$po->po_number} dibatalkan.");
    }

    public function print(PurchaseOrder $po)
    {
        $po->load(['supplier', 'details.product', 'details.unit', 'creator']);

        return view('gudang.purchase-orders.print', compact('po'));
    }

    /** JSON endpoint consumed by the goods-receiving form. */
    public function json(PurchaseOrder $po)
    {
        $po->load(['supplier', 'details.product.unit', 'details.unit']);

        return response()->json([
            'id'          => $po->id,
            'po_number'   => $po->po_number,
            'supplier_id' => $po->supplier_id,
            'supplier'    => ['id' => $po->supplier->id, 'name' => $po->supplier->name],
            'details'     => $po->details->map(fn ($d) => [
                'product_id'        => $d->product_id,
                'product_name'      => $d->product->name,
                'quantity_ordered'  => (int) $d->quantity_ordered,
                'quantity_received' => (int) $d->quantity_received,
                'unit_id'           => $d->unit_id,
                'unit'              => $d->unit ? ['id' => $d->unit->id, 'abbreviation' => $d->unit->abbreviation, 'name' => $d->unit->name] : null,
                'price'             => (float) $d->price,
            ]),
        ]);
    }

    /** Shared option payload for the create/edit form. */
    private function formOptions(): array
    {
        return [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'products'  => Product::where('status', 'active')->visibleInGudang()->with('unit')->orderBy('name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'unit_id' => $p->unit_id, 'hpp' => (float) $p->hpp]),
            'units'     => Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ];
    }

    /** Re-create the PO's line items from the submitted payload. */
    private function syncDetails(PurchaseOrder $po, array $items): void
    {
        foreach ($items as $item) {
            $po->details()->create([
                'product_id'        => $item['product_id'],
                'quantity_ordered'  => (int) $item['quantity'],
                'quantity_received' => 0,
                'unit_id'           => $item['unit_id'],
                'price'             => $item['price'],
                'total'             => (int) $item['quantity'] * (float) $item['price'],
                'notes'             => $item['notes'] ?? null,
            ]);
        }
    }
}
