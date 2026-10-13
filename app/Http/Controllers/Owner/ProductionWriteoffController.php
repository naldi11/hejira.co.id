<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\ProductionWriteoffs;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductionWriteoffController extends Controller
{
    public function index(Request $request, ProductionWriteoffs $writeoffs)
    {
        $filters = $request->validate([
            'entity' => 'nullable|in:jihans,hendhys', 'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1', 'history_page' => 'nullable|integer|min:1',
        ]);
        return Inertia::render('Owner/ProductionWriteoffs/Index', [
            'filters' => $filters,
            'stocks' => $writeoffs->available($filters)->paginate(12)->withQueryString(),
            'history' => $writeoffs->history($filters)->paginate(10, ['*'], 'history_page')->withQueryString()
                ->through(fn ($row) => ProductionWriteoffs::historyRow($row)),
            'reasons' => ProductionWriteoffs::REASONS,
        ]);
    }

    public function store(Request $request, ProductionWriteoffs $writeoffs)
    {
        if (is_string($request->input('notes'))) $request->merge(['notes' => trim($request->input('notes'))]);
        $data = $request->validate([
            'request_key' => 'required|uuid', 'entity' => 'required|in:jihans,hendhys',
            'product_id' => 'required|integer|exists:master_products,id',
            'lot_id' => 'nullable|integer|min:1', 'expected_remaining' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|in:'.implode(',', array_keys(ProductionWriteoffs::REASONS)),
            'notes' => 'required|string|min:5|max:1000', 'confirmed' => 'accepted',
        ]);
        $writeoffs->create($data, $request->user());
        return back()->with('success', 'Sisa stok berhasil dikurangi dan dicatat dalam riwayat penghapusan.');
    }

    public function bulk(Request $request, ProductionWriteoffs $writeoffs)
    {
        if (is_string($request->input('notes'))) $request->merge(['notes' => trim($request->input('notes'))]);
        $data = $request->validate([
            'request_key' => 'required|uuid', 'items' => 'required|array|min:1|max:50',
            'items.*.entity' => 'required|in:jihans,hendhys', 'items.*.product_id' => 'required|integer|exists:master_products,id',
            'items.*.lot_id' => 'nullable|integer|min:1', 'items.*.expected_remaining' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'reason' => 'required|in:'.implode(',', array_keys(ProductionWriteoffs::REASONS)),
            'notes' => 'required|string|min:5|max:1000', 'confirmed' => 'accepted',
        ]);
        $ids = $writeoffs->createMany($data, $request->user());
        return back()->with('success', count($ids).' pilihan sisa stok berhasil dihapus dan dicatat.');
    }

    public function reverse(Request $request, int $writeoff, ProductionWriteoffs $writeoffs)
    {
        if (is_string($request->input('notes'))) $request->merge(['notes' => trim($request->input('notes'))]);
        $data = $request->validate([
            'request_key' => 'required|uuid', 'notes' => 'required|string|min:5|max:1000', 'confirmed' => 'accepted',
        ]);
        $writeoffs->reverse($writeoff, $data, $request->user());
        return back()->with('success', 'Penghapusan dibatalkan. Stok kembali ke sumber produksi yang sama dan pembatalannya tercatat.');
    }
}
