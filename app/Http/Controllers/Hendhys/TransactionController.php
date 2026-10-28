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
use Illuminate\Support\Facades\Gate;
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
            $query->where('created_at', '>=', \App\Support\DateFilter::start($request->date))
                ->where('created_at', '<', \App\Support\DateFilter::nextDay($request->date));
        }

        if ($request->filled('shift_id')) {
            $shift = \App\Models\CashierShift::find($request->shift_id);
            if ($shift) {
                abort_unless($shift->entity === 'hendhys', 403);
                \Illuminate\Support\Facades\Gate::authorize('view', $shift);
                $shift->scopeTransactions($query, 'hendhys_transactions');
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
        Gate::authorize('view', $transaction);
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

        $isPusat = !$user->branch || $user->branch->type === 'pusat';
        if ($isPusat ? $transaction->branch_id !== null : (int) $transaction->branch_id !== (int) $user->branch_id) {
            abort(403, 'Anda tidak dapat membatalkan transaksi cabang lain.');
        }

        try {
            DB::transaction(function () use ($transaction, $data, $user) {
                $transaction = app(\App\Services\SaleShiftGuard::class)->sale($transaction, 'hendhys');
                $transaction->load(['details' => fn ($q) => $q->orderBy('product_id')]);

                // Kembalikan stok setiap baris ke tempat asalnya dipotong.
                foreach ($transaction->details as $detail) {
                    $this->stockService->creditHendhys(
                        $detail->product_id,
                        $detail->unit_id,
                        (int) $detail->quantity,
                        $transaction->branch_id,
                        'pos_void',
                        $transaction->id,
                        $user->id
                    );
                }

                $transaction->update([
                    'status' => 'cancelled',
                    'notes'  => trim(($transaction->notes ? $transaction->notes . ' | ' : '')
                              . 'DIBATALKAN oleh ' . $user->name . ': ' . $data['reason']),
                ]);
                $this->logger->log(
                    'void',
                    'hendhys_pos',
                    "Pembatalan transaksi {$transaction->transaction_number} sebesar {$transaction->grand_total}. Alasan: {$data['reason']}",
                    $transaction
                );
            }, 3);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }


        return back()->with('success', "Transaksi {$transaction->transaction_number} berhasil dibatalkan dan stok dikembalikan.");
    }
}
