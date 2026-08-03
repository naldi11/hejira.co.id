<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Resources\Jihans\TransactionResource;
use App\Models\CashierShift;
use App\Models\JihansTransaction;
use App\Services\ActivityLogService;
use App\Services\InvoiceService;
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
        $query = JihansTransaction::with(['creator', 'customer'])->orderBy('created_at', 'desc');

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
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        return Inertia::render('Jihans/Transactions/Index', [
            'transactions' => TransactionResource::collection($transactions),
            'filters'      => $request->only(['search', 'date', 'start_date', 'end_date', 'shift_id']),
        ]);
    }

    /**
     * The faktur is a standalone HTML print document (preview-first; the user prints
     * it to a dot-matrix LX-310, 9.5"×5.5" 3-ply form). Kept as a Blade view.
     */
    public function show($id)
    {
        $transaction = JihansTransaction::with(['details.product', 'details.unit', 'creator', 'customer', 'payments'])->findOrFail($id);

        return view('jihans.transactions.show', compact('transaction'));
    }

    /** Same faktur as a downloadable PDF (for archive / sending). */
    public function pdf($id, InvoiceService $invoiceService)
    {
        return $invoiceService->generateJihansInvoice(JihansTransaction::findOrFail($id));
    }

    /**
     * Batalkan (void) transaksi penjualan Jihan's.
     *
     * Sama seperti sisi Hendhys: seluruh laporan sudah menyaring
     * `status != 'cancelled'`, tetapi tidak ada kode yang pernah menuliskannya,
     * sehingga salah input kasir tidak bisa dikoreksi. Transaksi tidak dihapus —
     * hanya ditandai batal agar nomor urut dan jejak audit tetap utuh.
     */
    public function void(Request $request, JihansTransaction $transaction)
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

        // Hanya transaksi dari shift yang MASIH TERBUKA yang boleh dibatalkan.
        // Setelah shift ditutup, rekap laci sudah dihitung; membatalkan transaksi
        // lama akan membuat rekap itu tidak pernah cocok lagi.
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

                foreach ($transaction->details as $detail) {
                    $this->stockService->creditJihansRetail(
                        $detail->product_id,
                        $detail->unit_id,
                        (int) $detail->quantity,
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
            'jihans.pos',
            "Pembatalan transaksi {$transaction->transaction_number} sebesar {$transaction->grand_total}. Alasan: {$data['reason']}",
            $transaction
        );

        return back()->with('success', "Transaksi {$transaction->transaction_number} berhasil dibatalkan dan stok dikembalikan.");
    }
}
