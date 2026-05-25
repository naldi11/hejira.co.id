<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = PurchaseOrder::with('supplier');

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('po_number', 'like', "%$search%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%$search%")));
        }

        if ($request->filled('status')) $q->where('status', $request->status);

        $orders = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('gudang.purchase-orders.index', compact('orders'));
    }

    public function create()
    {
        $po        = new PurchaseOrder();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('status', 'active')
            ->visibleInGudang()
            ->with('unit')
            ->orderBy('name')
            ->get();
        $units     = Unit::orderBy('name')->get();

        return view('gudang.purchase-orders.form', compact('po', 'suppliers', 'products', 'units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'          => 'required|exists:master_suppliers,id',
            'date'                 => 'required|date',
            'expected_date'        => 'nullable|date|after_or_equal:date',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:master_products,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_id'      => 'required|exists:master_units,id',
            'items.*.price'        => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $total = collect($request->items)->sum(fn ($i) => (int)$i['quantity'] * (float)$i['price']);

            $po = PurchaseOrder::create([
                'po_number'    => $this->numbers->generateYearly('GDG-PO', 'gudang_purchase_orders', 'po_number'),
                'supplier_id'  => $request->supplier_id,
                'date'         => $request->date,
                'expected_date'=> $request->expected_date,
                'status'       => 'draft',
                'total_amount' => $total,
                'notes'        => $request->notes,
                'created_by'   => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $po->details()->create([
                    'product_id'        => $item['product_id'],
                    'quantity_ordered'  => (int)$item['quantity'],
                    'quantity_received' => 0,
                    'unit_id'           => $item['unit_id'],
                    'price'             => $item['price'],
                    'total'             => (int)$item['quantity'] * (float)$item['price'],
                    'notes'             => $item['notes'] ?? null,
                ]);
            }

            $this->logger->log('create', 'gudang.po', "Buat PO: {$po->po_number}", $po);
        });

        return redirect()->route('gudang.po.index')->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(PurchaseOrder $po)
    {
        $po->load(['supplier', 'details.product', 'details.unit', 'creator', 'receivings.details.product']);

        return view('gudang.purchase-orders.show', compact('po'));
    }

    public function edit(PurchaseOrder $po)
    {
        abort_if(!$po->isEditable(), 403, 'PO sudah tidak bisa diedit.');

        $po->load('details');
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('status', 'active')->whereIn('entity_scope', ['gudang', 'all'])->with('unit')->orderBy('name')->get();
        $units     = Unit::orderBy('name')->get();

        return view('gudang.purchase-orders.form', compact('po', 'suppliers', 'products', 'units'));
    }

    public function update(Request $request, PurchaseOrder $po)
    {
        abort_if(!$po->isEditable(), 403, 'PO sudah tidak bisa diedit.');

        $request->validate([
            'supplier_id'          => 'required|exists:master_suppliers,id',
            'date'                 => 'required|date',
            'expected_date'        => 'nullable|date|after_or_equal:date',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:master_products,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_id'      => 'required|exists:master_units,id',
            'items.*.price'        => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $po) {
            $total = collect($request->items)->sum(fn ($i) => (int)$i['quantity'] * (float)$i['price']);

            $po->update([
                'supplier_id'   => $request->supplier_id,
                'date'          => $request->date,
                'expected_date' => $request->expected_date,
                'total_amount'  => $total,
                'notes'         => $request->notes,
                'updated_by'    => auth()->id(),
            ]);

            $po->details()->delete();

            foreach ($request->items as $item) {
                $po->details()->create([
                    'product_id'        => $item['product_id'],
                    'quantity_ordered'  => (int)$item['quantity'],
                    'quantity_received' => 0,
                    'unit_id'           => $item['unit_id'],
                    'price'             => $item['price'],
                    'total'             => (int)$item['quantity'] * (float)$item['price'],
                    'notes'             => $item['notes'] ?? null,
                ]);
            }

            $this->logger->log('update', 'gudang.po', "Update PO: {$po->po_number}", $po);
        });

        return redirect()->route('gudang.po.show', $po)->with('success', 'PO berhasil diperbarui.');
    }

    public function send(PurchaseOrder $po)
    {
        abort_if($po->status !== 'draft', 403);

        $po->update(['status' => 'sent', 'updated_by' => auth()->id()]);
        $this->logger->log('update', 'gudang.po', "PO dikirim ke supplier: {$po->po_number}", $po);

        return back()->with('success', "PO {$po->po_number} ditandai sebagai Terkirim.");
    }

    public function cancel(PurchaseOrder $po)
    {
        abort_if(!in_array($po->status, ['draft', 'sent']), 403, 'PO tidak bisa dibatalkan.');

        $po->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);
        $this->logger->log('update', 'gudang.po', "PO dibatalkan: {$po->po_number}", $po);

        return back()->with('success', "PO {$po->po_number} dibatalkan.");
    }

    public function print(PurchaseOrder $po)
    {
        $po->load(['supplier', 'details.product', 'details.unit', 'creator']);

        return view('gudang.purchase-orders.print', compact('po'));
    }
}
