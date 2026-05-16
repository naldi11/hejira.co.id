<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\HendhysTransaction;
use App\Models\HendhysTransactionDetail;
use App\Models\HendhysTransactionPayment;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
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
        
        $q = Product::where('status', 'active');

        if ($user->branch->type === 'pusat') {
            $q->join('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
              ->select('master_products.*', 'hendhys_stock_pusat.quantity as current_stock')
              ->where('hendhys_stock_pusat.quantity', '>', 0);
        } else {
            $q->join('hendhys_stock_branch', function($join) use ($user) {
                $join->on('master_products.id', '=', 'hendhys_stock_branch.product_id')
                     ->where('hendhys_stock_branch.branch_id', '=', $user->branch_id);
            })->select('master_products.*', 'hendhys_stock_branch.quantity as current_stock')
              ->where('hendhys_stock_branch.quantity', '>', 0);
        }

        $products = $q->with('unit')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('hendhys.pos.index', compact('products', 'customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_type' => 'required|in:retail,agen',
            'payment_method' => 'required|in:cash,transfer',
            'amount_paid' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
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
                    'customer_id' => $request->customer_id,
                    'customer_name' => $request->customer_name,
                    'customer_type' => $request->customer_type,
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
                        'pos_transaction',
                        $transaction->id,
                        $user->id
                    );
                }

                HendhysTransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_method' => $request->payment_method,
                    'amount' => $request->amount_paid,
                    'bank_name' => $request->bank_name,
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

        $transaction->load(['details.unit', 'payments', 'creator']);
        return view('hendhys.pos.receipt', compact('transaction'));
    }
}
