<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProductionStockMonitor
{
    public function stocks(string $entity): array
    {
        $table = $entity === 'hendhys' ? 'hendhys_stock_pusat' : 'jihans_gudang_stock';
        // One narrow query rather than hydrating every product and two unit relations.
        // Keep archived products: physical stock must still appear in the warning.
        $products = Product::withTrashed()->where('source_type', 'produced')->select('id');
        if ($entity === 'jihans') $products->visibleInJihans();
        $query = DB::table($table.' as s')->join('master_products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('master_units as su', 'su.id', '=', 's.unit_id')
            ->leftJoin('master_units as pu', 'pu.id', '=', 'p.unit_id')
            ->whereIn('s.product_id', $products)
            ->select(['s.product_id', 's.quantity', 'p.name as product', 'p.code'])
            ->selectRaw("COALESCE(su.abbreviation, pu.abbreviation, '-') as unit");
        if ($entity === 'jihans') {
            $query->leftJoin('jihans_retail_stock as r', 'r.product_id', '=', 's.product_id')
                ->selectRaw('COALESCE(r.quantity, 0) as retail_quantity');
        }
        return $query->get()->map(fn ($stock) => [
            'product_id' => $stock->product_id,
            'product' => $stock->product,
            'code' => $stock->code,
            'quantity' => (int) $stock->quantity,
            'retail_quantity' => (int) ($stock->retail_quantity ?? 0),
            'unit' => $stock->unit,
        ])->sortBy('product')->values()->all();
    }

    public function overviewForRequest(Request $request): array
    {
        // Only reuse within this HTTP request. A new request sees current stock,
        // including distributions, opname corrections and writeoff cancellations.
        $key = 'hejira.production_stock_monitor';
        if (!$request->attributes->has($key)) $request->attributes->set($key, $this->overview());
        return $request->attributes->get($key);
    }

    public function summaryForRequest(Request $request): array
    {
        return Arr::only($this->overviewForRequest($request), ['alert', 'checked_at', 'deadline', 'production_start', 'overdue_count']);
    }

    public function overview(): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $stocks = collect($this->stocks('hendhys'));
        $knownLots = DB::table('production_lots')->where('entity', 'hendhys')->where('remaining', '>', 0)
            ->whereNotNull('production_id')->get(['product_id', 'remaining', 'production_date', 'production_number', 'production_id']);
        $cutoff = $now->hour >= 17 ? $now->toDateString() : $now->copy()->subDay()->toDateString();
        $overdueLots = $knownLots->filter(fn ($lot) => $lot->production_date
            ? $lot->production_date <= $cutoff : $now->hour >= 17);
        $lotsByProduct = $knownLots->groupBy('product_id');
        $lateByProduct = $overdueLots->groupBy('product_id');
        $activeAlerts = DB::table('production_stock_alerts')->whereNull('resolved_at')
            ->get(['id', 'product_id', 'deadline_date', 'quantity'])
            ->keyBy(fn ($row) => $row->deadline_date.'|'.$row->product_id);
        $previousDeadlines = $activeAlerts->groupBy('product_id')->map(fn ($rows) => $rows->min('deadline_date'));
        $deadlines = collect();
        $alertRows = [];
        foreach ($stocks as $stock) {
            $productLots = $lotsByProduct->get($stock['product_id'], collect());
            $late = $lateByProduct->get($stock['product_id'], collect());
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
        $resolvedIds = $activeAlerts->reject(fn ($row) => $deadlines->has($row->product_id))->pluck('id');
        if ($resolvedIds->isNotEmpty()) DB::table('production_stock_alerts')->whereIn('id', $resolvedIds)
            ->whereNull('resolved_at')->update(['resolved_at' => $now, 'updated_at' => $now]);
        // Reads of an unchanged page should not repeatedly write/lock the same alerts.
        $changedAlerts = array_values(array_filter($alertRows, function ($row) use ($activeAlerts) {
            $existing = $activeAlerts->get($row['deadline_date'].'|'.$row['product_id']);
            return !$existing || (int) $existing->quantity !== (int) $row['quantity'];
        }));
        if ($changedAlerts) DB::table('production_stock_alerts')->upsert($changedAlerts, ['deadline_date', 'product_id'], ['quantity', 'resolved_at', 'updated_at']);
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
