<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\HendhysTransaction;
use App\Models\HendhysTransactionDetail;
use App\Models\HendhysTransactionPayment;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {}

    public function index()
    {
        $user = auth()->user();
        
        $q = Product::where('status', 'active')->whereIn('master_products.entity_scope', ['hendhys', 'all']);

        if ($user->branch->type === 'pusat') {
            $q->leftJoin('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
              ->select('master_products.*', DB::raw('COALESCE(hendhys_stock_pusat.quantity, 0) as current_stock'));
        } else {
            $q->leftJoin('hendhys_stock_branch', function($join) use ($user) {
                $join->on('master_products.id', '=', 'hendhys_stock_branch.product_id')
                     ->where('hendhys_stock_branch.branch_id', '=', $user->branch_id);
            })->select('master_products.*', DB::raw('COALESCE(hendhys_stock_branch.quantity, 0) as current_stock'));
        }

        $products = $q->with(['unit', 'tieredPrices'])->get();

        // Metode Pembayaran Aktif
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)
            ->whereIn('entity_scope', ['hendhys', 'all'])
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name', 'account_number', 'account_name', 'image']);

        return view('hendhys.pos.index', compact('products', 'paymentMethods'));
    }

    public function checkout()
    {
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)
            ->whereIn('entity_scope', ['hendhys', 'all'])
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name', 'account_number', 'account_name', 'image']);

        return view('hendhys.pos.checkout', compact('paymentMethods'));
    }

    public function heldStock()
    {
        $user = auth()->user();
        $query = \App\Models\HendhysPendingTransaction::with('details');

        if ($user->branch->type === 'cabang') {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        $heldQty = [];
        foreach ($query->get() as $pending) {
            foreach ($pending->details as $detail) {
                $pid = $detail->product_id;
                $heldQty[$pid] = ($heldQty[$pid] ?? 0) + (int) $detail->quantity;
            }
        }

        return response()->json($heldQty);
    }

    public function customerSearch(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $user = auth()->user();

        $query = \App\Models\HendhysTransaction::query()
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->where('customer_name', 'like', '%' . $q . '%');

        if ($user->branch->type === 'cabang') {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        $results = $query
            ->select('customer_name', 'customer_phone')
            ->distinct()
            ->orderBy('customer_name')
            ->limit(8)
            ->get();

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_type' => 'required|in:Pelanggan Individual,Pelanggan Retail,Pelanggan Agen',
            'customer_phone' => 'nullable|string|max:20',
            'payment_method_id' => 'required|exists:master_payment_methods,id',
            'amount_paid' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $transactionId = DB::transaction(function () use ($request) {
                $user = auth()->user();
                $branchId = $user->branch->type === 'cabang' ? $user->branch_id : null;

                $transaction = HendhysTransaction::create([
                    'transaction_number' => $this->numbers->generateYearly('HTRX', 'hendhys_transactions', 'transaction_number'),
                    'branch_id' => $branchId,
                    'date' => now()->toDateString(),
                    'time' => now()->toTimeString(),
                    'customer_id' => null,
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'customer_type' => $request->customer_type ?? 'Pelanggan Individual',
                    'subtotal' => $request->subtotal,
                    'discount_amount' => $request->discount_amount ?? 0,
                    'ppn_type' => $request->ppn_type,
                    'tax_amount' => $request->tax_amount ?? 0,
                    'other_costs' => $request->other_costs ?? 0,
                    'grand_total' => $request->grand_total,
                    'status' => 'paid',
                    'notes' => $request->notes,
                    'created_by' => $user->id
                ]);

                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    
                    HendhysTransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'unit_id' => $product->unit_id,
                        'price' => $item['price'],
                        'discount_amount' => $item['discount'] ?? 0,
                        'total' => $item['total']
                    ]);

                    // Potong stok
                    $this->stockService->debitHendhys(
                        $item['product_id'],
                        $item['quantity'],
                        $branchId,
                        'pos_sale',
                        $transaction->id,
                        $user->id
                    );
                }

                HendhysTransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_method_id' => $request->payment_method_id,
                    'payment_method' => null,
                    'amount' => $request->amount_paid,
                    'bank_name' => null,
                    'reference_number' => $request->reference_number
                ]);

                return $transaction->id;
            });

            return response()->json([
                'success' => true,
                'redirect' => route('hendhys.pos.receipt', $transactionId)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gagal memproses transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function receipt(HendhysTransaction $transaction)
    {
        $user = auth()->user();
        if ($user->branch->type === 'cabang' && $transaction->branch_id !== $user->branch_id) {
            abort(403);
        }
        if ($user->branch->type === 'pusat' && $transaction->branch_id !== null) {
            abort(403);
        }

        $transaction->load(['details.unit', 'payments.method', 'creator']);
        return view('hendhys.pos.receipt', compact('transaction'));
    }

    public function invoice($id)
    {
        $transaction = HendhysTransaction::findOrFail($id);
        return $this->invoiceService->generateHendhysInvoice($transaction);
    }

}
