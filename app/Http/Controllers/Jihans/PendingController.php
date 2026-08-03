<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Resources\Jihans\PendingResource;
use App\Models\JihansPendingTransaction;
use App\Models\Product;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PendingController extends Controller
{
    public function __construct(private NumberGeneratorService $numbers) {}

    public function index(Request $request)
    {
        $pendings = JihansPendingTransaction::with('creator')->withCount('details')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('pending_number', 'like', "%{$request->search}%")
                ->orWhere('customer_name', 'like', "%{$request->search}%")))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return Inertia::render('Jihans/Pending/Index', [
            'pendings' => PendingResource::collection($pendings),
            'filters'  => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'       => 'nullable|exists:master_customers,id',
            'customer_name'     => 'nullable|string|max:150',
            'customer_type'     => 'nullable|string',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price'     => 'required|numeric|min:0',
            'items.*.discount'  => 'nullable|numeric|min:0',
            'items.*.total'     => 'required|numeric|min:0',
        ]);

        try {
            $pending = DB::transaction(function () use ($request) {
                $pen = JihansPendingTransaction::create([
                    // Pakai generator bernomor urut + lockForUpdate. Skema lama
                    // ('HLD-'.time().'-'.rand()) bisa tabrakan pada unique index
                    // jika dua kasir menahan keranjang di detik yang sama.
                    'pending_number' => $this->numbers->generateYearly('JPND', 'jihans_pending_transactions', 'pending_number'),
                    'date'           => now()->toDateString(),
                    'customer_id'    => $request->customer_id,
                    'customer_name'  => $request->customer_name ?? 'Pelanggan Umum',
                    'customer_type'  => $request->customer_type ?? 'Pelanggan Retail',
                    'notes'          => $request->notes,
                    'created_by'     => auth()->id(),
                ]);

                foreach ($request->items as $item) {
                    $product = Product::with('unit')->find($item['product_id']);

                    $pen->details()->create([
                        'product_id'       => $item['product_id'],
                        'product_name'     => $product->name,
                        'quantity'         => $item['quantity'],
                        'unit_id'          => $product->unit_id,
                        'price'            => $item['price'],
                        'discount_percent' => 0, // Simplified for pending
                        'total'            => $item['total'],
                    ]);
                }

                return $pen;
            });
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Gagal menahan transaksi: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal menahan transaksi: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil di-hold (pending).',
                'pending_id' => $pending->id
            ]);
        }

        return redirect()->route('jihans.pos.index')->with('success', 'Transaksi berhasil di-hold.');
    }

    public function show(JihansPendingTransaction $pending)
    {
        $pending->load('details.product.unit', 'creator');
        return response()->json($pending);
    }

    public function destroy(JihansPendingTransaction $pending)
    {
        $pending->delete();
        
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pending transaksi dihapus.']);
        }
        
        return back()->with('success', 'Pending transaksi berhasil dihapus.');
    }
}
