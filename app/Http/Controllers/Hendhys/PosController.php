<?php

namespace App\Http\Controllers\Hendhys;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hendhys\StorePosTransactionRequest;
use App\Models\HendhysStockBranch;
use App\Models\HendhysStockPusat;
use App\Models\Product;
use App\Models\HendhysTransaction;
use App\Models\HendhysTransactionDetail;
use App\Models\HendhysTransactionPayment;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PosController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService,
        private ActivityLogService $logger
    ) {}

    public function index()
    {
        $user = auth()->user();
        
        $q = Product::where('status', 'active')->visibleInHendhys();

        $isPusat = !$user->branch || $user->branch->type === 'pusat';
        if ($isPusat) {
            $q->leftJoin('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
              ->select('master_products.*', DB::raw('COALESCE(hendhys_stock_pusat.quantity, 0) as current_stock'));
        } else {
            $q->leftJoin('hendhys_stock_branch', function($join) use ($user) {
                $join->on('master_products.id', '=', 'hendhys_stock_branch.product_id')
                     ->where('hendhys_stock_branch.branch_id', '=', $user->branch_id);
            })->select('master_products.*', DB::raw('COALESCE(hendhys_stock_branch.quantity, 0) as current_stock'));
        }

        $products = $q->with(['unit', 'tieredPrices'])->get()
            ->sort(fn($a, $b) =>
                ($b->current_stock > 0 ? 1 : 0) - ($a->current_stock > 0 ? 1 : 0)
                ?: strcmp($a->name, $b->name)
            )
            ->values()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'code'          => $p->code,
                'jenis'         => $p->jenis,
                'price'         => (float) $p->selling_price,
                'unit_id'       => $p->unit_id,
                'unit'          => $p->unit?->abbreviation ?? 'PCS',
                'current_stock' => (int) $p->current_stock,
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
            ->get(['id', 'name', 'type', 'bank_name', 'account_number', 'account_name', 'image'])
            ->map(fn ($pm) => [
                'id'             => $pm->id,
                'name'           => $pm->name,
                'type'           => $pm->type,
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

    /**
     * Hitung ulang harga & total di sisi server.
     *
     * Sebelumnya `subtotal`, `grand_total`, dan `total` per item disimpan persis
     * seperti kiriman klien tanpa diperiksa. Payload yang dimanipulasi bisa
     * mencatat omzet nyaris nol padahal stok tetap terpotong penuh.
     *
     * Di POS Hendhys harga TIDAK bisa diubah kasir (tidak ada input harga di UI),
     * jadi harga DB adalah otoritas. Rumusnya mengikuti getPrice() di
     * resources/js/Pages/Hendhys/Pos/Index.jsx: harga bertingkat dengan min_qty
     * terbesar yang masih <= qty, kalau tidak ada pakai selling_price.
     *
     * @return array{items: array, subtotal: float, grand_total: float}
     */
    private function recalculateTotals(StorePosTransactionRequest $request): array
    {
        $products = Product::with('tieredPrices')
            ->whereIn('id', collect($request->items)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $items    = [];
        $subtotal = 0.0;

        foreach ($request->items as $item) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) {
                throw new \RuntimeException("Produk dengan ID {$item['product_id']} tidak ditemukan.");
            }

            $qty   = (int) $item['quantity'];
            $price = (float) $product->selling_price;

            $tier = $product->tieredPrices
                ->filter(fn ($t) => $qty >= (int) $t->min_qty)
                ->sortByDesc(fn ($t) => (int) $t->min_qty)
                ->first();
            if ($tier) {
                $price = (float) $tier->price;
            }

            $total     = $price * $qty;
            $subtotal += $total;

            $items[] = [
                'product_id'   => (int) $item['product_id'],
                'product_name' => $product->name,
                'unit_id'      => $product->unit_id,
                'quantity'     => $qty,
                'price'        => $price,
                'discount'     => 0,
                'total'        => $total,
            ];
        }

        $discount   = (float) ($request->discount_amount ?? 0);
        $tax        = (float) ($request->tax_amount ?? 0);
        $otherCosts = (float) ($request->other_costs ?? 0);
        $grandTotal = max(0, $subtotal - $discount + $tax + $otherCosts);

        return ['items' => $items, 'subtotal' => $subtotal, 'grand_total' => $grandTotal];
    }

    public function store(StorePosTransactionRequest $request)
    {
        $now = now()->timezone('Asia/Jakarta');
        if ($now->hour >= 0 && $now->hour < 7) {
            return response()->json([
                'success' => false,
                'error' => 'Sistem Kasir Sedang Tutup! Silahkan Lanjutkan Transaksi Pada Pukul 07:00 WIB.'
            ], 400);
        }

        $computed = $this->recalculateTotals($request);

        // Tolak (bukan diam-diam menimpa) bila total kiriman klien menyimpang:
        // kasir harus melihat error, bukan struk dengan angka berbeda dari yang
        // ditampilkan saat transaksi. Toleransi Rp 1 untuk galat pembulatan.
        if (abs($computed['grand_total'] - (float) $request->grand_total) > 1.0) {
            return response()->json([
                'success' => false,
                'error'   => sprintf(
                    'Total transaksi tidak cocok dengan harga master (server: %s, dikirim: %s). Muat ulang halaman POS lalu ulangi.',
                    number_format($computed['grand_total'], 0, ',', '.'),
                    number_format((float) $request->grand_total, 0, ',', '.')
                ),
            ], 422);
        }

        try {
            $transaction = DB::transaction(function () use ($request, $computed) {
                $user = auth()->user();
                $branchId = $user->branch?->type === 'cabang' ? $user->branch_id : null;

                // Validasi ketersediaan stok SEBELUM transaksi ditulis, supaya kasir
                // mendapat pesan yang jelas alih-alih penjualan lolos diam-diam.
                // (StockService tetap menjadi penjaga terakhir dengan lockForUpdate.)
                //
                // Kuantitas dijumlahkan per produk lebih dulu: satu produk bisa muncul
                // di dua baris keranjang, dan mengecek tiap baris sendiri-sendiri akan
                // meloloskan total yang melebihi stok.
                $neededPerProduct = [];
                foreach ($computed['items'] as $item) {
                    $neededPerProduct[$item['product_id']] =
                        ($neededPerProduct[$item['product_id']] ?? 0) + $item['quantity'];
                }

                foreach ($neededPerProduct as $productId => $needed) {
                    $available = $branchId
                        ? (int) (HendhysStockBranch::where('branch_id', $branchId)
                            ->where('product_id', $productId)->value('quantity') ?? 0)
                        : (int) (HendhysStockPusat::where('product_id', $productId)
                            ->value('quantity') ?? 0);

                    if ($needed > $available) {
                        throw new InsufficientStockException(
                            (int) $productId,
                            $needed,
                            $available,
                            $branchId ? 'stok cabang Hendhys' : 'stok pusat Hendhys'
                        );
                    }
                }

                $transaction = HendhysTransaction::create([
                    'transaction_number' => $this->numbers->generateYearly('HTRX', 'hendhys_transactions', 'transaction_number'),
                    'branch_id' => $branchId,
                    'date' => now()->toDateString(),
                    'time' => now()->toTimeString(),
                    'customer_id' => null,
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'customer_type' => $request->customer_type ?? 'Pelanggan Individual',
                    // Nilai uang diambil dari hasil hitung server, bukan kiriman klien.
                    'subtotal' => $computed['subtotal'],
                    'discount_amount' => $request->discount_amount ?? 0,
                    'ppn_type' => $request->ppn_type ?? 'none',
                    'tax_amount' => $request->tax_amount ?? 0,
                    'other_costs' => $request->other_costs ?? 0,
                    'grand_total' => $computed['grand_total'],
                    'status' => 'paid',
                    'notes' => $request->notes,
                    'created_by' => $user->id
                ]);

                foreach ($computed['items'] as $item) {
                    HendhysTransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id'],
                        'price' => $item['price'],
                        'discount_amount' => $item['discount'],
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

                // Resolve payment type and method id
                $paymentType = $request->payment_type ?? 'tunai'; // tunai, transfer, kartu_debit, kartu_kredit
                $pmId = $request->payment_method_id;

                // Map payment_type to legacy payment_method enum (cash, transfer)
                $paymentMethodLegacy = match($paymentType) {
                    'transfer'     => 'transfer',
                    'kartu_debit'  => 'transfer',
                    'kartu_kredit' => 'transfer',
                    default        => 'cash',
                };

                // Jika tidak ada payment_method_id dari DB, coba cari dari master_payment_methods berdasarkan type
                if (!$pmId) {
                    $pm = \App\Models\PaymentMethod::where('is_active', true)
                        ->where('type', $paymentType)
                        ->first();
                    $pmId = $pm?->id;
                }

                HendhysTransactionPayment::create([
                    'transaction_id'    => $transaction->id,
                    'payment_method_id' => $pmId,
                    'payment_method'    => $paymentMethodLegacy,
                    'payment_type'      => $paymentType,
                    'amount'            => $request->amount_paid,
                    'bank_name'         => null,
                    'reference_number'  => $request->reference_number
                ]);


                // Hapus transaksi tertahan HANYA setelah penjualannya tersimpan.
                if ($request->filled('pending_id')) {
                    \App\Models\HendhysPendingTransaction::whereKey($request->pending_id)->delete();
                }

                return $transaction;
            });

            // Penjualan adalah aksi paling perlu diaudit; sebelumnya POS Hendhys
            // sama sekali tidak tercatat di activity log (POS Jihans sudah).
            $this->logger->log(
                'create',
                'hendhys_pos',
                "Transaksi POS {$transaction->transaction_number} sebesar {$transaction->grand_total}",
                $transaction
            );

            return response()->json([
                'success' => true,
                'redirect' => route('hendhys.pos.receipt', $transaction->id)
            ]);

        } catch (InsufficientStockException $e) {
            // Stok kurang = kesalahan input kasir (422), bukan error server.
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Gagal memproses transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function receipt(\Illuminate\Http\Request $request, HendhysTransaction $transaction)
    {
        $user = auth()->user();
        // User tanpa branch diperlakukan sebagai pusat (konsisten dengan index()).
        $isPusat = !$user->branch || $user->branch->type === 'pusat';
        if (!$isPusat && $transaction->branch_id !== $user->branch_id) {
            abort(403);
        }
        if ($isPusat && $transaction->branch_id !== null) {
            abort(403);
        }

        $transaction->load(['details.unit', 'payments.method', 'creator', 'customer', 'branch']);
        $paperSize = $request->input('paper_size', '58');
        return view('hendhys.pos.receipt', compact('transaction', 'paperSize'));
    }

    public function invoice(HendhysTransaction $transaction)
    {
        $transaction->load(['details.unit', 'payments.method', 'creator']);
        return view('hendhys.pos.invoice', compact('transaction'));
    }

}
