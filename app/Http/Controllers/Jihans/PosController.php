<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jihans\StorePosTransactionRequest;
use App\Http\Resources\Jihans\PosProductResource;
use App\Models\Customer;
use App\Models\JihansRetailStock;
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
            ->leftJoin('jihans_retail_stock', 'master_products.id', '=', 'jihans_retail_stock.product_id')
            ->select('master_products.*', DB::raw('COALESCE(jihans_retail_stock.quantity, 0) as current_stock'))
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
            $available = JihansRetailStock::where('product_id', $item['product_id'])->value('quantity') ?? 0;
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

                $this->stock->debitJihansRetail($item['product_id'], $item['quantity'], 'pos_sale', $trx->id, auth()->id());
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

    public function receipt(\Illuminate\Http\Request $request, JihansTransaction $transaction)
    {
        $transaction->load(['details.unit', 'payments.method', 'creator', 'customer']);
        $paperSize = $request->input('paper_size', '58');

        return view('jihans.pos.receipt', compact('transaction', 'paperSize'));
    }

    public function edit(JihansTransaction $transaction)
    {
        $transaction->load(['details.product', 'details.unit', 'payments']);
        
        $products = Product::where('status', 'active')
            ->visibleInJihans()
            ->leftJoin('jihans_retail_stock', 'master_products.id', '=', 'jihans_retail_stock.product_id')
            ->select('master_products.*', DB::raw('COALESCE(jihans_retail_stock.quantity, 0) as current_stock'))
            ->with(['unit', 'tieredPrices', 'category'])
            ->orderBy('master_products.name')
            ->get();

        $extraDiscount = $transaction->discount_amount - $transaction->details->sum('discount_amount');

        return Inertia::render('Jihans/Pos/Index', [
            'products'  => PosProductResource::collection($products)->resolve(),
            'customers' => Customer::where('is_active', true)->whereIn('entity_scope', ['jihans', 'all'])->orderBy('name')
                ->get(['id', 'name', 'type', 'phone'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'phone' => $c->phone]),
            'editTransaction' => [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'customer_id' => $transaction->customer_id,
                'customer_name' => $transaction->customer_name,
                'customer_type' => $transaction->customer_type,
                'date' => $transaction->date,
                'notes' => $transaction->notes,
                'ppn_type' => $transaction->ppn_type,
                'extra_discount' => $extraDiscount > 0 ? $extraDiscount : 0,
                'shipping_fee' => $transaction->other_costs,
                'amount_paid' => $transaction->payments->sum('amount'),
                'items' => $transaction->details->map(fn($d) => [
                    'product_id' => $d->product_id,
                    'product_name' => $d->product_name,
                    'product_code' => $d->product->code ?? '',
                    'quantity' => (int) $d->quantity,
                    'price' => (float) $d->price,
                    'discount' => (float) $d->discount_amount,
                    'unit_name' => $d->unit->abbreviation ?? 'PCS',
                    'is_custom_price' => true,
                ])
            ]
        ]);
    }

    public function update(StorePosTransactionRequest $request, JihansTransaction $transaction)
    {
        $data = $request->validated();

        foreach ($data['items'] as $item) {
            $available = JihansRetailStock::where('product_id', $item['product_id'])->value('quantity') ?? 0;
            $currentDetail = $transaction->details()->where('product_id', $item['product_id'])->first();
            if ($currentDetail) {
                $available += $currentDetail->quantity;
            }
            
            if ($item['quantity'] > $available) {
                return response()->json(['error' => "Stok produk tidak mencukupi untuk item dengan ID {$item['product_id']}."], 422);
            }
        }

        DB::transaction(function () use ($data, $transaction) {
            foreach ($transaction->details as $detail) {
                $this->stock->creditJihansRetail($detail->product_id, $detail->unit_id, $detail->quantity, 'adjustment', $transaction->id, auth()->id());
            }

            $transaction->details()->delete();

            $baseNotes = str_replace(preg_replace('/.*(\(Direvisi pada .*\)).*/', '$1', $transaction->notes ?? ''), '', $transaction->notes ?? '');
            $notes = trim($baseNotes) . " (Direvisi pada " . now()->format('d M Y H:i') . ")";
            
            $transaction->update([
                'date'               => $data['transaction_date'] ?? $transaction->date,
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
                'notes'              => trim($notes),
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);

                $transaction->details()->create([
                    'product_id'      => $item['product_id'],
                    'product_name'    => $product->name,
                    'quantity'        => $item['quantity'],
                    'unit_id'         => $product->unit_id,
                    'price'           => $item['price'],
                    'discount_amount' => $item['discount'] ?? 0,
                    'total'           => $item['total'],
                ]);

                $this->stock->debitJihansRetail($item['product_id'], $item['quantity'], 'pos_sale', $transaction->id, auth()->id());
            }

            $transaction->payments()->delete();
            $transaction->payments()->create([
                'payment_method_id' => null,
                'payment_method'    => 'cash',
                'amount'            => $data['amount_paid'],
                'reference_number'  => $data['reference_number'] ?? null,
                'bank_name'         => null,
                'notes'             => null,
            ]);

            $this->logger->log('update', 'jihans.pos', "Edit Transaksi POS Kasir Jihan's: {$transaction->transaction_number}", $transaction);
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
}
