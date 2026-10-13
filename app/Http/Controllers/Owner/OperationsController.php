<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Gudang\PurchaseOrderResource;
use App\Models\HendhysProduction;
use App\Models\JihansProductionSession;
use App\Models\PurchaseOrder;
use App\Services\ProductionSnapshot;
use App\Services\ProductionDistribution;
use App\Services\ProductionStockMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperationsController extends Controller
{
    public function purchaseOrder(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'creator', 'details.product', 'details.unit', 'receivings.details']);
        return Inertia::render('Owner/PurchaseOrder', ['po' => new PurchaseOrderResource($purchaseOrder)]);
    }

    public function index(Request $request, ProductionStockMonitor $monitor)
    {
        $filters = $request->validate([
            'entity' => 'nullable|in:jihans,hendhys', 'type' => 'nullable|in:prediksi,aktual',
            'date' => 'nullable|date_format:Y-m-d', 'page' => 'nullable|integer|min:1',
        ]);
        $query = function (string $entity, string $table, string $number) use ($filters) {
            $query = DB::table($table)->select('id', 'date', 'type', 'notes', 'created_at')
                ->selectRaw("{$number} as number, '{$entity}' as entity");
            if (!empty($filters['entity']) && $filters['entity'] !== $entity) $query->whereRaw('1 = 0');
            if (!empty($filters['type'])) $query->where('type', $filters['type']);
            if (!empty($filters['date'])) $query->whereDate('date', $filters['date']);
            return $query;
        };
        $union = $query('hendhys', 'hendhys_productions', 'production_number')
            ->unionAll($query('jihans', 'jihans_production_sessions', 'session_number'));
        $records = DB::query()->fromSub($union, 'productions')
            ->orderByDesc('date')->orderByDesc('created_at')->orderByDesc('id')->orderBy('entity')->paginate(20)->withQueryString();

        return Inertia::render('Owner/Production/Index', [
            'records' => $records, 'filters' => $filters,
            'production_monitor' => fn () => $monitor->overview(),
            'jihans_stocks' => fn () => $monitor->stocks('jihans'),
        ]);
    }

    public function production(Request $request, string $entity, int $id, ProductionStockMonitor $monitor, ProductionDistribution $distribution)
    {
        abort_unless(in_array($entity, ['hendhys', 'jihans'], true), 404);
        $model = $entity === 'hendhys' ? HendhysProduction::class : JihansProductionSession::class;
        $production = $model::with(['details.product.unit', 'creator'])->findOrFail($id);
        $filters = $request->validate([
            'distribution_date' => 'nullable|date_format:Y-m-d',
            'distribution_page' => 'nullable|integer|min:1',
            'writeoff_page' => 'nullable|integer|min:1',
        ]);
        $distributionDate = $filters['distribution_date'] ?? null;
        $snapshot = ProductionSnapshot::capture($production, $entity);
        return Inertia::render('Owner/Production/Show', [
            'distribution_date' => $distributionDate,
            'batch_stock' => fn () => app(\App\Services\ProductionLots::class)->summary($production, $entity),
            'writeoffs' => fn () => app(\App\Services\ProductionWriteoffs::class)->history(['entity' => $entity, 'production_id' => $id])->paginate(10, ['*'], 'writeoff_page')->withQueryString()
                ->through(fn ($row) => \App\Services\ProductionWriteoffs::historyRow($row)),
            'distributions' => fn () => $distribution->history($production, $entity, $distributionDate),
            'production' => [...$snapshot, 'id' => $production->id, 'entity' => $entity,
                'number' => $production->production_number ?? $production->session_number,
                'type' => $production->type, 'prediction' => $production->prediction_snapshot,
            ],
            'production_monitor' => fn () => $monitor->overview(),
            'jihans_stocks' => fn () => $monitor->stocks('jihans'),
        ]);
    }
}
