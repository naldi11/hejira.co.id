<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivingController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = Receiving::with(['supplier', 'po', 'creator']);

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('grn_number', 'like', "%$search%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%$search%")));
        }

        if ($request->filled('date_from')) $q->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $q->whereDate('date', '<=', $request->date_to);

        $receivings = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('gudang.receivings.index', compact('receivings'));
    }

    public function create(Request $request)
    {
        $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('status', 'active')->whereIn('entity_scope', ['gudang', 'all'])->with('unit')->orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        $po         = null;

        if ($request->filled('po_id')) {
            $po = PurchaseOrder::with('details.product', 'details.unit', 'supplier')
                ->findOrFail($request->po_id);
        }

        return view('gudang.receivings.form', compact('suppliers', 'products', 'units', 'po'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'        => 'required|exists:master_suppliers,id',
            'date'               => 'required|date',
            'po_id'              => 'nullable|exists:gudang_purchase_orders,id',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_id'    => 'required|exists:master_units,id',
            'items.*.hpp_price'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $receiving = Receiving::create([
                'grn_number'  => $this->numbers->generateYearly('GDG-GRN', 'gudang_receivings', 'grn_number'),
                'po_id'       => $request->po_id,
                'supplier_id' => $request->supplier_id,
                'date'        => $request->date,
                'notes'       => $request->notes,
                'created_by'  => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $receiving->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_id'    => $item['unit_id'],
                    'hpp_price'  => $item['hpp_price'],
                    'total'      => $item['quantity'] * $item['hpp_price'],
                    'notes'      => $item['notes'] ?? null,
                ]);

                // Update gudang stock
                $this->stock->creditGudang(
                    $item['product_id'],
                    $item['unit_id'],
                    $item['quantity'],
                    'purchase_receiving',
                    $receiving->id,
                    auth()->id()
                );

                // Update HPP product (last price)
                $this->stock->updateProductHpp($item['product_id'], $item['hpp_price']);
            }

            // Update PO status & quantity_received
            if ($request->po_id) {
                $this->updatePoReceived($request->po_id, $request->items);
            }

            $this->logger->log('create', 'gudang.receiving', "Buat GRN: {$receiving->grn_number}", $receiving);
        });

        return redirect()->route('gudang.receiving.index')->with('success', 'Penerimaan barang berhasil dicatat.');
    }

    public function show(Receiving $receiving)
    {
        $receiving->load(['supplier', 'po', 'creator', 'details.product', 'details.unit']);

        return view('gudang.receivings.show', compact('receiving'));
    }

    private function updatePoReceived(int $poId, array $items): void
    {
        $po = PurchaseOrder::with('details')->find($poId);
        if (!$po) return;

        foreach ($items as $item) {
            $detail = $po->details->where('product_id', $item['product_id'])->first();
            if ($detail) {
                $detail->increment('quantity_received', $item['quantity']);
            }
        }

        $po->refresh();
        $allReceived = $po->details->every(
            fn ($d) => $d->quantity_received >= $d->quantity_ordered
        );
        $anyReceived = $po->details->some(fn ($d) => $d->quantity_received > 0);

        $po->update([
            'status'     => $allReceived ? 'received' : ($anyReceived ? 'partial' : $po->status),
            'updated_by' => auth()->id(),
        ]);
    }
}
