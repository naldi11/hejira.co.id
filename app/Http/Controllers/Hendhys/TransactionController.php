<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysTransactionResource;
use App\Models\CashierShift;
use App\Models\HendhysTransaction;
use App\Services\ActivityLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = HendhysTransaction::with(['creator', 'customer'])->orderBy('created_at', 'desc');

        if ($user->branch && $user->branch->type !== 'pusat') {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                \Carbon\Carbon::parse($request->end_date)->endOfDay()
            ]);
        } elseif ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('shift_id')) {
            $shift = \App\Models\CashierShift::find($request->shift_id);
            if ($shift) {
                $query->whereBetween('created_at', [
                    $shift->opened_at,
                    $shift->closed_at ?? now()
                ]);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        return Inertia::render('Hendhys/Transactions/Index', [
            'transactions' => HendhysTransactionResource::collection($transactions),
            'filters'      => $request->only(['search', 'date', 'start_date', 'end_date', 'shift_id']),
        ]);
    }

    /**
     * Reprint a sale from history → the SAME 80mm thermal receipt used after a sale
     * (one template, consistent output). Kept as a Blade print document.
     */
    public function show(\Illuminate\Http\Request $request, $id)
    {
        $transaction = HendhysTransaction::with(['details.product', 'details.unit', 'creator', 'customer', 'branch', 'payments'])->findOrFail($id);
        $paperSize = $request->input('paper_size', '58');

        return view('hendhys.pos.receipt', compact('transaction', 'paperSize'));
    }

    /**
     * Batalkan (void) transaksi penjualan.
     *
     * Sebelumnya tidak ada jalur pembatalan sama sekali: enum status sudah punya
     * 'cancelled' dan seluruh laporan sudah menyaring `status != 'cancelled'`,
     * tetapi tidak ada satu pun kode yang menuliskannya. Akibatnya salah input
     * kasir tidak pernah bisa dikoreksi — stok bisa disesuaikan manual, omzet tidak.
     *
     * Transaksi TIDAK dihapus: statusnya diubah menjadi 'cancelled' supaya nomor
     * transaksi tetap berurutan dan jejak auditnya utuh.
     */
    public function void(Request $request, HendhysTransaction $transaction)
    {
        $user = auth()->user();

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'reason.min'      => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        if ($transaction->status === 'cancelled') {
            return back()->with('error', 'Transaksi ini sudah dibatalkan sebelumnya.');
        }

        // Batasi ke cabang milik user, konsisten dengan index()/receipt().
        $isPusat = !$user->branch || $user->branch->type === 'pusat';
        if ($isPusat ? $transaction->branch_id !== null : $transaction->branch_id !== $user->branch_id) {
            abort(403, 'Anda tidak dapat membatalkan transaksi cabang lain.');
        }

        // Hanya boleh membatalkan transaksi dari shift yang MASIH TERBUKA.
        // Setelah shift ditutup, rekap laci sudah dicetak dan selisih kasnya sudah
        // dihitung — membatalkan transaksi lama akan membuat rekap itu tidak cocok
        // selamanya. Untuk itu gunakan retur, bukan pembatalan.
        $openShift = CashierShift::where('user_id', $transaction->created_by)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if (!$openShift || $transaction->created_at < $openShift->opened_at) {
            return back()->with('error',
                'Transaksi ini berasal dari shift yang sudah ditutup sehingga tidak bisa dibatalkan. '
                . 'Gunakan mekanisme retur agar rekap laci tetap cocok.');
        }

        try {
            DB::transaction(function () use ($transaction, $data, $user) {
                $transaction->load('details');

                // Kembalikan stok setiap baris ke tempat asalnya dipotong.
                foreach ($transaction->details as $detail) {
                    $this->stockService->creditHendhys(
                        $detail->product_id,
                        $detail->unit_id,
                        (int) $detail->quantity,
                        $transaction->branch_id,
                        'adjustment',
                        $transaction->id,
                        $user->id
                    );
                }

                $transaction->update([
                    'status' => 'cancelled',
                    'notes'  => trim(($transaction->notes ? $transaction->notes . ' | ' : '')
                              . 'DIBATALKAN oleh ' . $user->name . ': ' . $data['reason']),
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }

        $this->logger->log(
            'void',
            'hendhys_pos',
            "Pembatalan transaksi {$transaction->transaction_number} sebesar {$transaction->grand_total}. Alasan: {$data['reason']}",
            $transaction
        );

        return back()->with('success', "Transaksi {$transaction->transaction_number} berhasil dibatalkan dan stok dikembalikan.");
    }
}
