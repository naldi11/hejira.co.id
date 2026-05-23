<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\JihansStock;
use App\Models\JihansTransaction;
use App\Models\JihansTransactionDetail;
use App\Models\Product;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // Get products that are available in Jihans Stock
        $products = Product::where('status', 'active')->whereIn('master_products.entity_scope', ['jihans', 'all'])
            ->leftJoin('jihans_stock', 'master_products.id', '=', 'jihans_stock.product_id')
            ->select('master_products.*', DB::raw('COALESCE(jihans_stock.quantity, 0) as current_stock'))
            ->with(['unit', 'category', 'tieredPrices'])
            ->get();

        // Kirim semua pelanggan aktif, filter tipe dilakukan di frontend
        $customers = Customer::where('is_active', true)->whereIn('entity_scope', ['jihans', 'all'])->orderBy('name')->get(['id', 'name', 'type', 'phone']);

        // Tipe unik pelanggan untuk dropdown
        $customerTypes = $customers->pluck('type')->unique()->values()->map(fn($t) => [
            'value' => $t,
            'label' => $t,
        ]);

        // Metode Pembayaran Aktif
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)
            ->whereIn('entity_scope', ['jihans', 'all'])
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name', 'account_number', 'account_name', 'image']);

        return view('jihans.pos.index', compact('products', 'customers', 'customerTypes', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_date'  => 'nullable|date',
            'customer_id'       => 'nullable|exists:master_customers,id',
            'customer_name'     => 'nullable|string|max:150',
            'customer_type'     => 'required|in:Pelanggan Individual,Pelanggan Retail,Pelanggan Agen',
            'ppn_type'          => 'required|in:none,include,exclude',
            'ppn_rate'          => 'required|numeric|min:0',
            'subtotal'          => 'required|numeric|min:0',
            'discount_amount'   => 'required|numeric|min:0',
            'extra_discount'    => 'nullable|numeric|min:0',
            'tax_amount'        => 'required|numeric|min:0',
            'other_costs'       => 'required|numeric|min:0',
            'grand_total'       => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:master_payment_methods,id',
            'amount_paid'       => 'required|numeric|min:0',
            'reference_number'  => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price'     => 'required|numeric|min:0',
            'items.*.discount'  => 'nullable|numeric|min:0',
            'items.*.total'     => 'required|numeric|min:0',
        ]);

        // Cek stok apakah mencukupi
        foreach ($request->items as $item) {
            $stock = JihansStock::where('product_id', $item['product_id'])->value('quantity') ?? 0;
            if ($item['quantity'] > $stock) {
                return response()->json(['error' => "Stok produk tidak mencukupi untuk item dengan ID {$item['product_id']}."], 422);
            }
        }

        $transaction = DB::transaction(function () use ($request) {
            $trx = JihansTransaction::create([
                'transaction_number' => $this->numbers->generateYearly('JHS-INV', 'jihans_transactions', 'transaction_number'),
                'date'               => $request->transaction_date ?? now()->toDateString(),
                'time'               => now()->toTimeString(),
                'customer_id'        => $request->customer_id,
                'customer_name'      => $request->customer_name ?? 'Pelanggan Umum',
                'customer_type'      => $request->customer_type,
                'ppn_type'           => $request->ppn_type,
                'ppn_rate'           => $request->ppn_rate,
                'subtotal'           => $request->subtotal,
                'discount_amount'    => $request->discount_amount + ($request->extra_discount ?? 0),
                'tax_amount'         => $request->tax_amount,
                'other_costs'        => $request->other_costs,
                'grand_total'        => $request->grand_total,
                'status'             => 'paid',
                'notes'              => $request->notes,
                'created_by'         => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $product = Product::with('unit')->find($item['product_id']);
                
                $trx->details()->create([
                    'product_id'       => $item['product_id'],
                    'product_name'     => $product->name,
                    'quantity'         => $item['quantity'],
                    'unit_id'          => $product->unit_id,
                    'price'            => $item['price'],
                    'discount_amount'  => $item['discount'] ?? 0,
                    'total'            => $item['total'],
                ]);

                // Kurangi stok lokal Jihans
                $this->stock->debitJihans(
                    $item['product_id'],
                    $item['quantity'],
                    'pos_sale',
                    $trx->id,
                    auth()->id()
                );
            }

            // Catat Pembayaran
            $trx->payments()->create([
                'payment_method_id' => $request->payment_method_id,
                'payment_method'    => null, // Menghindari enum jika field baru diisi
                'amount'            => $request->amount_paid,
                'reference_number'  => $request->reference_number,
                'bank_name'         => null, // Diambil dari payment_method_id relasi
                'notes'             => null,
            ]);

            $this->logger->log('create', 'jihans.pos', "Transaksi POS Kasir Jihan's: {$trx->transaction_number}", $trx);

            return $trx;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'redirect' => route('jihans.pos.receipt', $transaction->id)
            ]);
        }

        return redirect()->route('jihans.pos.receipt', $transaction->id);
    }

    public function receipt(JihansTransaction $transaction)
    {
        $transaction->load(['details.unit', 'payments.method', 'creator']);
        return view('jihans.pos.receipt', compact('transaction'));
    }

    public function invoice($id)
    {
        $transaction = JihansTransaction::findOrFail($id);
        return $this->invoiceService->generateJihansInvoice($transaction);
    }

}
