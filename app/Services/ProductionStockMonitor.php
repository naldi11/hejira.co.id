<?php

namespace App\Services;

use App\Models\HendhysStockPusat;
use App\Models\JihansGudangStock;
use App\Models\JihansRetailStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionStockMonitor
{
    public function stocks(string $entity): array
    {
        $model = $entity === 'hendhys' ? HendhysStockPusat::class : JihansGudangStock::class;
        $stocks = $model::with(['product.unit', 'unit'])
            ->whereHas('product', function ($query) use ($entity) {
                $query->withTrashed()->where('source_type', 'produced');
                // Pusat Hendhys adalah lokasi fisik; jangan sembunyikan sisanya karena label unit produk.
                if ($entity === 'jihans') $query->visibleInJihans();
            })
            ->get();
        $retail = $entity === 'jihans'
            ? JihansRetailStock::whereIn('product_id', $stocks->pluck('product_id'))->pluck('quantity', 'product_id') : collect();

        return $stocks->map(fn ($stock) => [
            'product_id' => $stock->product_id,
            'product' => $stock->product?->name ?? 'Produk tidak tersedia',
            'code' => $stock->product?->code,
            'quantity' => (int) $stock->quantity,
            'retail_quantity' => (int) ($retail[$stock->product_id] ?? 0),
            'unit' => $stock->unit?->abbreviation ?? $stock->product?->unit?->abbreviation ?? '-',
        ])->sortBy('product')->values()->all();
    }

    public function overview(): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $stocks = collect($this->stocks('hendhys'));
        $knownLots = DB::table('production_lots')->where('entity', 'hendhys')->whereNotNull('production_id')->get();
        $overdueLots = $knownLots->filter(fn ($lot) => $lot->remaining > 0
            && $now->gte(Carbon::parse($lot->production_date.' 17:00', 'Asia/Jakarta')));
        $previousDeadlines = DB::table('production_stock_alerts')->whereNull('resolved_at')
            ->select('product_id')->selectRaw('MIN(deadline_date) as deadline_date')
            ->groupBy('product_id')->pluck('deadline_date', 'product_id');
        $deadlines = collect();
        $alertRows = [];
        foreach ($stocks as $stock) {
            $productLots = $knownLots->where('product_id', $stock['product_id']);
            $late = $overdueLots->where('product_id', $stock['product_id']);
            $unknown = max(0, $stock['quantity'] - $productLots->sum('remaining'));
            $unknownIsLate = $unknown > 0 && ($now->hour >= 17 || isset($previousDeadlines[$stock['product_id']]));
            $lateQuantity = $late->sum('remaining') + ($unknownIsLate ? $unknown : 0);
            if ($lateQuantity <= 0) continue;
            $date = $late->min('production_date') ?? ($previousDeadlines[$stock['product_id']] ?? $now->toDateString());
            if ($unknownIsLate && isset($previousDeadlines[$stock['product_id']])) $date = min($date, $previousDeadlines[$stock['product_id']]);
            $deadlines[$stock['product_id']] = $date;
            $alertRows[] = ['deadline_date' => $date, 'product_id' => $stock['product_id'], 'quantity' => $lateQuantity,
                'resolved_at' => null, 'created_at' => $now, 'updated_at' => $now];
        }
        // Today's new lot must not inherit yesterday's resolved production warning.
        DB::table('production_stock_alerts')->whereNull('resolved_at')->whereNotIn('product_id', $deadlines->keys()->all())
            ->update(['resolved_at' => $now, 'updated_at' => $now]);
        if ($alertRows) DB::table('production_stock_alerts')->upsert($alertRows, ['deadline_date', 'product_id'], ['quantity', 'resolved_at', 'updated_at']);
        $stocks = $stocks->map(fn ($stock) => [...$stock,
            'overdue' => isset($deadlines[$stock['product_id']]) && $stock['quantity'] > 0,
            'deadline_date' => $deadlines[$stock['product_id']] ?? $now->toDateString(),
        ])->all();
        $overdue = collect($stocks)->where('overdue', true)->values();

        return [
            'checked_at' => $now->format('d M Y H:i').' WIB',
            'deadline' => '17.00 WIB', 'production_start' => '08.00 WIB',
            'overdue_lots' => $overdueLots->map(fn ($lot) => ['number' => $lot->production_number, 'quantity' => (int) $lot->remaining, 'production_id' => $lot->production_id, 'product_id' => $lot->product_id, 'date' => $lot->production_date])->values()->all(),
            'stocks' => $stocks, 'overdue_count' => $overdue->count(),
            'alert' => $overdue->isEmpty() ? null : [
                'id' => 'production_stock_'.$overdue->min('deadline_date'),
                'title' => 'Stok produksi Hendhys belum habis',
                'message' => $overdue->count().' produk masih tersisa di pusat setelah batas 17.00 WIB. Segera distribusikan ke cabang.',
                'path' => route('owner.production.index'),
                'type' => 'danger', 'icon' => 'warning', 'time' => 'Batas '.$overdue->min('deadline_date').' 17.00 WIB',
            ],
        ];
    }
}
