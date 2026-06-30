<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StoreTransferOutRequest;
use App\Http\Resources\Gudang\TransferOutResource;
use App\Models\Branch;
use App\Models\GudangStock;
use App\Models\Product;
use App\Models\TransferOut;
use App\Models\TransferRequest;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransferOutController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

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
            ->leftJoin('gudang_stock', 'master_products.id', '=', 'gudang_stock.product_id')
            ->select('master_products.*', 'gudang_stock.quantity as current_stock')
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

        $branches = Branch::where('is_active', true)
            ->orderByRaw("FIELD(type,'pusat','cabang')")
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'type' => $b->type]);

        $transferRequest = null;
        if ($request->filled('request_id')) {
            $tr = TransferRequest::with('details.product', 'details.unit', 'branch')
                ->whereIn('status', ['approved', 'partial'])
                ->findOrFail($request->request_id);

            $stocks = GudangStock::whereIn('product_id', $tr->details->pluck('product_id'))->pluck('quantity', 'product_id');

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
                        'quantity_approved' => (float) $d->quantity_approved,
                        'quantity_sent'     => (float) $d->quantity_sent,
                        'quantity'          => (float) max(0, $d->quantity_approved - $d->quantity_sent),
                        'unit_id'           => $d->unit_id,
                        'unit_name'         => $d->unit?->abbreviation,
                        'hpp_price'         => (float) $d->product?->hpp,
                    ])->values(),
            ];
        }

        return Inertia::render('Gudang/TransferOut/Create', [
            'products'        => $products,
            'branches'        => $branches,
            'transferRequest' => $transferRequest,
        ]);
    }

    public function store(StoreTransferOutRequest $request)
    {
        $data = $request->validated();

        // Stock sufficiency check.
        foreach ($data['items'] as $item) {
            $stock = GudangStock::where('product_id', $item['product_id'])->value('quantity') ?? 0;
            if ($item['quantity'] > $stock) {
                $product = Product::find($item['product_id']);
                return back()->withInput()->withErrors([
                    'items' => "Stok {$product->name} tidak mencukupi. Tersedia: " . (int) $stock,
                ]);
            }
        }

        // Block self-produced products from being transferred out of the warehouse.
        $producedNames = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
            ->where('source_type', 'produced')->pluck('name');

        if ($producedNames->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'items' => 'Produk berikut adalah produk produksi sendiri dan tidak bisa dikirim dari Gudang: ' . $producedNames->implode(', '),
            ]);
        }

        DB::transaction(function () use ($data) {
            $transfer = TransferOut::create([
                'transfer_number' => $this->numbers->generateYearly('GDG-TRF', 'gudang_transfer_out', 'transfer_number'),
                'request_id'      => $data['request_id'] ?? null,
                'to_entity'       => $data['to_entity'],
                'branch_id'       => $data['to_entity'] === 'hendhys' ? $data['branch_id'] : null,
                'date'            => $data['date'],
                'notes'           => $data['notes'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $transfer->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_id'    => $item['unit_id'],
                    'hpp_price'  => $item['hpp_price'],
                    'total'      => $item['quantity'] * $item['hpp_price'],
                ]);
            }

            $transfer->load('details');
            $this->stock->processTransferOut($transfer);

            // AUTO-RECEIVE FOR HENDHYS BRANCHES (Pusat & Cabang)
            if ($transfer->to_entity === 'hendhys') {
                $transfer->update([
                    'status'                    => 'received',
                    'received_by'               => auth()->id(),
                    'receive_notes'             => 'Diterima otomatis langsung ke cabang.',
                    'receive_received_by_name'  => 'Sistem Gudang',
                    'receive_pengirim_name'     => auth()->user()->name,
                    'received_at'               => now(),
                ]);

                foreach ($transfer->details as $detail) {
                    $detail->received_quantity = $detail->quantity;
                    $detail->kondisi = 'baik';
                    $detail->save();
                }

                // Process receipt (credits branch stock and creates HendhysStockIn)
                $this->stock->processTransferReceive($transfer, auth()->id());

                // Create ReceiptConfirmation (Unified BAST) automatically
                $receiptConfirmation = \App\Models\ReceiptConfirmation::create([
                    'receiptable_type' => TransferOut::class,
                    'receiptable_id'   => $transfer->id,
                    'received_by'      => auth()->id(),
                    'received_at'      => now(),
                    'status'           => 'completed',
                    'general_notes'    => 'Diterima otomatis langsung ke cabang.',
                ]);

                foreach ($transfer->details as $detail) {
                    $receiptConfirmation->details()->create([
                        'product_id'   => $detail->product_id,
                        'expected_qty' => $detail->quantity,
                        'actual_qty'   => $detail->quantity,
                        'condition'    => 'baik',
                        'expired_date' => null,
                        'batch_number' => null,
                        'notes'        => null,
                    ]);
                }

                // Mark Transfer Request as completed if exists
                if ($transfer->request) {
                    $transfer->request->update(['status' => 'completed']);
                }
            }

            $this->logger->log('create', 'gudang.transfer_out', "Transfer keluar: {$transfer->transfer_number} ke {$transfer->to_entity} (Auto-Received)", $transfer);
        });

        return redirect()->route('gudang.transfer-out.index')->with('success', 'Transfer keluar berhasil diproses.');
    }

    public function show(TransferOut $transferOut)
    {
        $transferOut->load(['request', 'branch', 'creator', 'details.product', 'details.unit']);

        return Inertia::render('Gudang/TransferOut/Show', [
            'transfer' => new TransferOutResource($transferOut),
        ]);
    }
}
