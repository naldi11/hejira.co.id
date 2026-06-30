<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jihans\StorePosTransactionRequest;
use App\Http\Resources\Jihans\PosProductResource;
use App\Models\Customer;
use App\Models\JihansStock;
use App\Models\JihansTransaction;
use App\Models\Product;
use App\Services\ActivityLogService;
use App\Services\InvoiceService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PosController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stock,
        private ActivityLogService $logger,
        private InvoiceService $invoiceService
    ) {}

    public function index()
    {
        $products = Product::where('status', 'active')
            ->visibleInJihans()
            ->leftJoin('gudang_stock', 'master_products.id', '=', 'gudang_stock.product_id')
            ->select('master_products.*', DB::raw('COALESCE(gudang_stock.quantity, 0) as current_stock'))
            ->with(['unit', 'tieredPrices', 'category'])
            ->orderBy('master_products.name')
            ->get();

        return Inertia::render('Jihans/Pos/Index', [
            'products'  => PosProductResource::collection($products)->resolve(),
            'customers' => Customer::where('is_active', true)->whereIn('entity_scope', ['jihans', 'all'])->orderBy('name')
                ->get(['id', 'name', 'type', 'phone'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'phone' => $c->phone]),
        ]);
    }

    /**
     * Persist a sale. Called by the React POS via axios (JSON), so it returns JSON
     * (not an Inertia response) and redirects the client to the printable receipt.
     */
    public function store(StorePosTransactionRequest $request)
    {
        $data = $request->validated();

        foreach ($data['items'] as $item) {
            $available = \App\Models\GudangStock::where('product_id', $item['product_id'])->value('quantity') ?? 0;
            if ($item['quantity'] > $available) {
                return response()->json(['error' => "Stok produk tidak mencukupi untuk item dengan ID {$item['product_id']}."], 422);
            }
        }

        $transaction = DB::transaction(function () use ($data) {
            $trx = JihansTransaction::create([
                'transaction_number' => $this->numbers->generateYearly('JHS-INV', 'jihans_transactions', 'transaction_number'),
                'date'               => $data['transaction_date'] ?? now()->toDateString(),
                'time'               => now()->toTimeString(),
                'customer_id'        => $data['customer_id'] ?? null,
                'customer_name'      => $data['customer_name'] ?? 'Pelanggan Umum',
                'customer_type'      => $data['customer_type'] ?? 'Pelanggan Retail',
                'ppn_type'           => $data['ppn_type'],
                'ppn_rate'           => $data['ppn_rate'],
                'subtotal'           => $data['subtotal'],
                'discount_amount'    => $data['discount_amount'] + ($data['extra_discount'] ?? 0),
                'tax_amount'         => $data['tax_amount'],
                'other_costs'        => $data['other_costs'],
                'grand_total'        => $data['grand_total'],
                'status'             => 'paid',
                'notes'              => $data['notes'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);

                $trx->details()->create([
                    'product_id'      => $item['product_id'],
                    'product_name'    => $product->name,
                    'quantity'        => $item['quantity'],
                    'unit_id'         => $product->unit_id,
                    'price'           => $item['price'],
                    'discount_amount' => $item['discount'] ?? 0,
                    'total'           => $item['total'],
                ]);

                $this->stock->debitJihans($item['product_id'], $item['quantity'], 'pos_sale', $trx->id, auth()->id());
            }

            $trx->payments()->create([
                'payment_method_id' => null,
                'payment_method'    => 'cash',
                'amount'            => $data['amount_paid'],
                'reference_number'  => $data['reference_number'] ?? null,
                'bank_name'         => null,
                'notes'             => null,
            ]);

            $this->logger->log('create', 'jihans.pos', "Transaksi POS Kasir Jihan's: {$trx->transaction_number}", $trx);

            return $trx;
        });

        if (request()->wantsJson()) {
            return response()->json([
                'success'        => true,
                'transaction_id' => $transaction->id,
                'redirect'       => route('jihans.pos.receipt', $transaction->id),
            ]);
        }

        return redirect()->route('jihans.pos.receipt', $transaction->id);
    }

    /** Printable receipt — kept as a Blade document. */
    public function receipt(JihansTransaction $transaction)
    {
        $transaction->load(['details.unit', 'payments.method', 'creator', 'customer']);

        return view('jihans.pos.receipt', compact('transaction'));
    }

    public function invoice($id)
    {
        return $this->invoiceService->generateJihansInvoice(JihansTransaction::findOrFail($id));
    }
}
