<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
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

class TransferOutController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = TransferOut::with(['request', 'branch', 'creator']);

        if ($search = $request->search) {
            $q->where('transfer_number', 'like', "%$search%");
        }

        if ($request->filled('to_entity')) $q->where('to_entity', $request->to_entity);

        $transfers = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('gudang.transfer-out.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        $products  = Product::where('status', 'active')->whereIn('master_products.entity_scope', ['gudang', 'all'])
            ->with(['unit'])
            ->leftJoin('gudang_stock', 'master_products.id', '=', 'gudang_stock.product_id')
            ->select('master_products.*', 'gudang_stock.quantity as current_stock')
            ->orderBy('master_products.name')
            ->get();

        $units    = Unit::orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderByRaw("FIELD(type,'pusat','cabang')")->get();

        $transferRequest = null;
        if ($request->filled('request_id')) {
            $transferRequest = TransferRequest::with('details.product', 'details.unit', 'branch')
                ->whereIn('status', ['approved', 'partial'])
                ->findOrFail($request->request_id);
        }

        return view('gudang.transfer-out.form', compact('products', 'units', 'branches', 'transferRequest'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'to_entity'          => 'required|in:jihans,hendhys',
            'branch_id'          => 'nullable|required_if:to_entity,hendhys|exists:master_branches,id',
            'date'               => 'required|date',
            'request_id'         => 'nullable|exists:gudang_transfer_requests,id',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_id'    => 'required|exists:master_units,id',
            'items.*.hpp_price'  => 'required|numeric|min:0',
        ]);

        // Validate stock sufficiency
        foreach ($request->items as $item) {
            $stock = GudangStock::where('product_id', $item['product_id'])->value('quantity') ?? 0;
            $product = Product::find($item['product_id']);

            if ($item['quantity'] > $stock) {
                $availableStock = (int) $stock;
                return back()->withErrors([
                    'items' => "Stok {$product->name} tidak mencukupi. Tersedia: {$availableStock}",
                ])->withInput();
            }
        }

        DB::transaction(function () use ($request) {
            $transfer = TransferOut::create([
                'transfer_number' => $this->numbers->generateYearly('GDG-TRF', 'gudang_transfer_out', 'transfer_number'),
                'request_id'      => $request->request_id,
                'to_entity'       => $request->to_entity,
                'branch_id'       => $request->to_entity === 'hendhys' ? $request->branch_id : null,
                'date'            => $request->date,
                'notes'           => $request->notes,
                'created_by'      => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $transfer->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_id'    => $item['unit_id'],
                    'hpp_price'  => $item['hpp_price'],
                    'total'      => $item['quantity'] * $item['hpp_price'],
                ]);
            }

            // Process stock movements
            $transfer->load('details');
            $this->stock->processTransferOut($transfer);

            // Mark linked request as completed if all items sent
            if ($request->request_id) {
                $tr = TransferRequest::find($request->request_id);
                if ($tr && in_array($tr->status, ['approved', 'partial'])) {
                    $tr->update(['status' => 'completed']);
                }
            }

            $this->logger->log('create', 'gudang.transfer_out',
                "Transfer keluar: {$transfer->transfer_number} ke {$transfer->to_entity}", $transfer);
        });

        return redirect()->route('gudang.transfer-out.index')->with('success', 'Transfer keluar berhasil diproses.');
    }

    public function show(TransferOut $transferOut)
    {
        $transferOut->load(['request', 'branch', 'creator', 'details.product', 'details.unit']);

        return view('gudang.transfer-out.show', compact('transferOut'));
    }
}
