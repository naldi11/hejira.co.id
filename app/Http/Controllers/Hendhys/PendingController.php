<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\HendhysPendingDetail;
use App\Models\HendhysPendingTransaction;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendingController extends Controller
{
    public function __construct(private NumberGeneratorService $numbers) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $q = HendhysPendingTransaction::with(['creator', 'customer']);

        if ($user->branch->type === 'cabang') {
            $q->where('branch_id', $user->branch_id);
        } else {
            $q->whereNull('branch_id');
        }

        if ($search = $request->search) {
            $q->where(function($w) use ($search) {
                $w->where('pending_number', 'like', "%$search%")
                  ->orWhere('customer_name', 'like', "%$search%");
            });
        }

        $pendings = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('hendhys.pending.index', compact('pendings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_type' => 'required|in:retail,agen',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $user = auth()->user();
                $branchId = $user->branch->type === 'cabang' ? $user->branch_id : null;

                $pending = HendhysPendingTransaction::create([
                    'pending_number' => $this->numbers->generateYearly('HPND', 'hendhys_pending_transactions', 'pending_number'),
                    'branch_id' => $branchId,
                    'date' => now()->toDateString(),
                    'customer_id' => $request->customer_id,
                    'customer_name' => $request->customer_name,
                    'customer_type' => $request->customer_type,
                    'notes' => $request->notes,
                    'created_by' => $user->id
                ]);

                foreach ($request->items as $item) {
                    HendhysPendingDetail::create([
                        'pending_id' => $pending->id,
                        'product_id' => $item['product_id'],
                        'product_name' => \App\Models\Product::find($item['product_id'])->name,
                        'quantity' => $item['quantity'],
                        'unit_id' => \App\Models\Product::find($item['product_id'])->unit_id,
                        'price' => $item['price'],
                        'discount_amount' => $item['discount'] ?? 0,
                        'total' => $item['total']
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil di-hold (pending).'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal hold transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(HendhysPendingTransaction $pending)
    {
        $user = auth()->user();
        if ($user->branch->type === 'cabang' && $pending->branch_id !== $user->branch_id) {
            abort(403);
        }
        if ($user->branch->type === 'pusat' && $pending->branch_id !== null) {
            abort(403);
        }

        $pending->load(['details.unit', 'creator']);
        return response()->json($pending);
    }

    public function destroy(HendhysPendingTransaction $pending)
    {
        $user = auth()->user();
        if ($user->branch->type === 'cabang' && $pending->branch_id !== $user->branch_id) {
            abort(403);
        }
        if ($user->branch->type === 'pusat' && $pending->branch_id !== null) {
            abort(403);
        }

        $pending->delete(); // Details are cascade deleted if configured, otherwise handled by DB constraints or we can manually delete
        DB::table('hendhys_pending_details')->where('pending_id', $pending->id)->delete();

        return redirect()->route('hendhys.pending.index')
            ->with('success', 'Transaksi pending berhasil dihapus.');
    }
}
