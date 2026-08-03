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
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PosController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stock,
        private ActivityLogService $logger
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
    /**
     * Verifikasi konsistensi aritmetika nilai uang yang dikirim POS.
     *
     * Berbeda dengan Hendhys, kasir Jihan's MEMANG boleh mengubah harga per baris
     * di keranjang (ada input harga di UI), jadi harga tidak bisa dipaksa dari
     * master. Yang tetap wajib benar adalah hitungannya sendiri — tanpa ini,
     * payload yang dimanipulasi bisa mencatat omzet nyaris nol padahal stok
     * tetap terpotong penuh.
     *
     * Rumus mengikuti totals() di resources/js/Pages/Jihans/Pos/Index.jsx:
     *   total_baris = qty * harga - diskon_baris
     *   subtotal    = Σ total_baris
     *   setelahDisk = subtotal - extra_discount
     *   pajak       = ppn_type 'exclude' ? setelahDisk * (ppn_rate/100) : 0
     *   grand_total = max(0, setelahDisk + pajak) + other_costs
     *
     * @return string|null Pesan error, atau null bila konsisten.
     */
    private function assertTotalsConsistent(array $data): ?string
    {
        $tolerance = 1.0; // Rp 1, untuk galat pembulatan floating point di browser
        $subtotal  = 0.0;

        foreach ($data['items'] as $i => $item) {
            $expected = ((int) $item['quantity']) * (float) $item['price'] - (float) ($item['discount'] ?? 0);
            if (abs($expected - (float) $item['total']) > $tolerance) {
                return sprintf(
                    'Total baris ke-%d tidak konsisten (server: %s, dikirim: %s).',
                    $i + 1,
                    number_format($expected, 0, ',', '.'),
                    number_format((float) $item['total'], 0, ',', '.')
                );
            }
            $subtotal += $expected;
        }

        if (abs($subtotal - (float) $data['subtotal']) > $tolerance) {
            return sprintf(
                'Subtotal tidak konsisten (server: %s, dikirim: %s).',
                number_format($subtotal, 0, ',', '.'),
                number_format((float) $data['subtotal'], 0, ',', '.')
            );
        }

        $afterDiscount = $subtotal - (float) ($data['extra_discount'] ?? 0);
        $tax = ($data['ppn_type'] ?? 'none') === 'exclude'
            ? $afterDiscount * ((float) ($data['ppn_rate'] ?? 0) / 100)
            : 0.0;

        if (abs($tax - (float) ($data['tax_amount'] ?? 0)) > $tolerance) {
            return sprintf(
                'Nilai PPN tidak konsisten (server: %s, dikirim: %s).',
                number_format($tax, 0, ',', '.'),
                number_format((float) ($data['tax_amount'] ?? 0), 0, ',', '.')
            );
        }

        $grandTotal = max(0, $afterDiscount + $tax) + (float) ($data['other_costs'] ?? 0);
        if (abs($grandTotal - (float) $data['grand_total']) > $tolerance) {
            return sprintf(
                'Grand total tidak konsisten (server: %s, dikirim: %s).',
                number_format($grandTotal, 0, ',', '.'),
                number_format((float) $data['grand_total'], 0, ',', '.')
            );
        }

        return null;
    }

    public function store(StorePosTransactionRequest $request)
    {
        $now = now()->timezone('Asia/Jakarta');
        if ($now->hour >= 0 && $now->hour < 7) {
            return response()->json(['error' => 'Sistem Kasir Sedang Tutup! Silahkan Lanjutkan Transaksi Pada Pukul 07:00 WIB.'], 422);
        }

        $data = $request->validated();

        if ($error = $this->assertTotalsConsistent($data)) {
            return response()->json(['error' => $error], 422);
        }

        // Jumlahkan per produk: satu produk bisa muncul di dua baris keranjang,
        // dan mengecek tiap baris sendiri-sendiri meloloskan total melebihi stok.
        $neededPerProduct = [];
        foreach ($data['items'] as $item) {
            $neededPerProduct[$item['product_id']] =
                ($neededPerProduct[$item['product_id']] ?? 0) + (int) $item['quantity'];
        }

        foreach ($neededPerProduct as $productId => $needed) {
            $available = (int) round((float) (JihansRetailStock::where('product_id', $productId)->value('quantity') ?? 0));
            if ($needed > $available) {
                return response()->json(['error' => "Stok produk tidak mencukupi untuk item dengan ID {$productId}."], 422);
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

            // Hapus transaksi tertahan HANYA setelah penjualannya benar-benar
            // tersimpan, di dalam transaksi DB yang sama. Sebelumnya frontend
            // menghapusnya lebih dulu, sehingga keranjang bisa hilang permanen
            // bila kasir menyegarkan halaman sebelum checkout.
            if (!empty($data['pending_id'])) {
                \App\Models\JihansPendingTransaction::whereKey($data['pending_id'])->delete();
            }

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

        if ($transaction->status === 'cancelled') {
            return response()->json(['error' => 'Transaksi yang sudah dibatalkan tidak dapat diubah.'], 422);
        }

        // Hanya transaksi dari shift yang MASIH TERBUKA yang boleh diedit.
        //
        // Rekap laci dihitung dari transaksi dalam rentang shift pada saat shift
        // ditutup. Mengedit transaksi milik shift yang sudah tutup akan mengubah
        // omzet dan stok, tetapi TIDAK memperbarui rekap yang sudah tercetak —
        // sehingga angka laporan dan angka transaksi berbeda selamanya.
        $openShift = \App\Models\CashierShift::where('user_id', $transaction->created_by)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (!$openShift || $transaction->created_at < $openShift->opened_at) {
            return response()->json([
                'error' => 'Transaksi ini berasal dari shift yang sudah ditutup sehingga tidak bisa diubah. '
                         . 'Gunakan pembatalan transaksi atau retur agar rekap laci tetap cocok.',
            ], 422);
        }

        // Sama seperti store(): nilai uang wajib konsisten secara aritmetika.
        if ($error = $this->assertTotalsConsistent($data)) {
            return response()->json(['error' => $error], 422);
        }

        // Jumlahkan kebutuhan per produk (satu produk bisa muncul di dua baris),
        // lalu tambahkan kembali qty yang sudah tercatat di transaksi ini karena
        // stoknya akan dikembalikan dulu sebelum dipotong ulang.
        $neededPerProduct = [];
        foreach ($data['items'] as $item) {
            $neededPerProduct[$item['product_id']] =
                ($neededPerProduct[$item['product_id']] ?? 0) + (int) $item['quantity'];
        }

        foreach ($neededPerProduct as $productId => $needed) {
            $available = (int) round((float) (JihansRetailStock::where('product_id', $productId)->value('quantity') ?? 0));
            $available += (int) $transaction->details()->where('product_id', $productId)->sum('quantity');

            if ($needed > $available) {
                return response()->json(['error' => "Stok produk tidak mencukupi untuk item dengan ID {$productId}."], 422);
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
