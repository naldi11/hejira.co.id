<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hendhys\StorePosTransactionRequest;
use App\Models\Product;
use App\Models\HendhysTransaction;
use App\Models\HendhysTransactionDetail;
use App\Models\HendhysTransactionPayment;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PosController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {}

    public function index()
    {
        $user = auth()->user();
        
        $q = Product::where('status', 'active')->visibleInHendhys();

        if ($user->branch->type === 'pusat') {
            $q->leftJoin('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
              ->select('master_products.*', DB::raw('COALESCE(hendhys_stock_pusat.quantity, 0) as current_stock'));
        } else {
            $q->leftJoin('hendhys_stock_branch', function($join) use ($user) {
                $join->on('master_products.id', '=', 'hendhys_stock_branch.product_id')
                     ->where('hendhys_stock_branch.branch_id', '=', $user->branch_id);
            })->select('master_products.*', DB::raw('COALESCE(hendhys_stock_branch.quantity, 0) as current_stock'));
        }

        $products = $q->with(['unit', 'tieredPrices'])->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'code'          => $p->code,
                'jenis'         => $p->jenis,
                'price'         => (float) $p->selling_price,
                'unit_id'       => $p->unit_id,
                'unit'          => $p->unit?->abbreviation ?? 'PCS',
                'current_stock' => (float) $p->current_stock,
                'photo'         => $p->image ? \Illuminate\Support\Facades\Storage::url($p->image) : null,
                'tiered_prices' => $p->tieredPrices->map(fn ($tp) => [
                    'min_qty' => (int) $tp->min_qty,
                    'price'   => (float) $tp->price,
                ]),
            ]);

        // Metode Pembayaran Aktif
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)
            ->whereIn('entity_scope', ['hendhys', 'all'])
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name', 'account_number', 'account_name', 'image'])
            ->map(fn ($pm) => [
                'id'             => $pm->id,
                'name'           => $pm->name,
                'bank_name'      => $pm->bank_name,
                'account_number' => $pm->account_number,
                'account_name'   => $pm->account_name,
                'image'          => $pm->image,
            ]);

        return Inertia::render('Hendhys/Pos/Index', [
            'products'       => $products,
            'paymentMethods' => $paymentMethods,
        ]);
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

        // Search only in master_customers
        $customers = \App\Models\Customer::where('is_active', true)
            ->where('visible_hendhys', true)
            ->where('name', 'like', '%' . $q . '%')
            ->select('name as customer_name', 'phone as customer_phone', 'type as customer_type')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    public function store(StorePosTransactionRequest $request)
    {
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
                    'ppn_type' => $request->ppn_type ?? 'none',
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

                $pmId = $request->payment_method_id;
                $paymentMethod = 'cash';

                if (!$pmId) {
                    $defaultPM = \App\Models\PaymentMethod::where('is_active', true)
                        ->where(function($q) {
                            $q->where('name', 'like', '%tunai%')
                              ->orWhere('name', 'like', '%cash%');
                        })->first();
                    if (!$defaultPM) {
                        $defaultPM = \App\Models\PaymentMethod::where('is_active', true)->first();
                    }
                    $pmId = $defaultPM?->id;
                } else {
                    $pm = \App\Models\PaymentMethod::find($pmId);
                    $paymentMethod = $pm?->type ?? 'cash';
                }

                HendhysTransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'payment_method_id' => $pmId,
                    'payment_method' => $paymentMethod,
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

        $transaction->load(['details.unit', 'payments.method', 'creator', 'customer']);
        return view('hendhys.pos.receipt', compact('transaction'));
    }

    public function invoice(HendhysTransaction $transaction)
    {
        $transaction->load(['details.unit', 'payments.method', 'creator']);
        return view('hendhys.pos.invoice', compact('transaction'));
    }

}
