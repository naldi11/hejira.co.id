<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansPendingTransaction;
use App\Models\JihansPendingDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendingController extends Controller
{
    public function index(Request $request)
    {
        $q = JihansPendingTransaction::with('creator');

        if ($search = $request->search) {
            $q->where('pending_number', 'like', "%$search%")
              ->orWhere('customer_name', 'like', "%$search%");
        }

        $pendings = $q->orderBy('id', 'desc')->paginate(15);

        return view('jihans.pending.index', compact('pendings'));
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

        $pending = DB::transaction(function () use ($request) {
            $pen = JihansPendingTransaction::create([
                'pending_number' => 'HLD-' . time() . '-' . rand(100, 999),
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
