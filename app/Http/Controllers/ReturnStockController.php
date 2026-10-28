<?php

namespace App\Http\Controllers;

use App\Services\ReturnQuarantine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReturnStockController extends Controller
{
    public function __construct(private ReturnQuarantine $quarantine) {}
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'condition' => 'nullable|in:damaged,expired,other', 'destination' => 'nullable|in:gudang,hendhys-pusat', 'branch_id' => 'nullable|integer', 'date_from' => 'nullable|date_format:Y-m-d', 'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from']);
        $visible = $this->quarantine->visible($request->user());
        $branches = (clone $visible)->select('origin_branch_id as id', 'origin_name as name')->whereNotNull('origin_branch_id')->distinct()->get();
        $q = (clone $visible)->when($request->filled('condition'), fn ($q) => $q->where('condition', $filters['condition']))
            ->when($request->filled('destination'), fn ($q) => $q->where('destination_key', $filters['destination']))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('origin_branch_id', $filters['branch_id']))
            ->when($request->filled('date_from'), fn ($q) => $q->where('return_date', '>=', $filters['date_from']))
            ->when($request->filled('date_to'), fn ($q) => $q->where('return_date', '<=', $filters['date_to']))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($filters) {
                foreach (['product_name', 'product_code', 'return_number', 'origin_name', 'reason', 'receiving_notes', 'sent_by_name', 'received_by_name'] as $column) $q->orWhere($column, 'like', '%'.$filters['search'].'%');
            }));
        // Do not add quantities across different products or units.
        $balances = (clone $q)->select('product_id', 'unit_id', 'destination_key', 'condition')->selectRaw('MAX(product_name) as product_name, MAX(product_code) as product_code, MAX(unit_name) as unit_name, SUM(quantity) as quantity, COUNT(*) as receipts')
            ->groupBy('product_id', 'unit_id', 'destination_key', 'condition')->orderBy('product_name')->paginate(12, ['*'], 'stock_page')->withQueryString();
        $rows = $q->orderByDesc('id')->paginate(20)->withQueryString();
        $rows->through(fn ($r) => $this->serialize($r));
        $balances->through(fn ($r) => [...(array) $r, 'destination_name' => $this->destinationName($r->destination_key)]);
        return Inertia::render('ReturnStock/Index', ['entries' => $rows, 'balances' => $balances, 'branches' => $branches, 'filters' => $filters]);
    }
    public function show(Request $request, int $entry)
    {
        $row = DB::table('return_quarantine_entries')->where('id', $entry)->first();
        abort_unless($row, 404);
        abort_unless($this->quarantine->visible($request->user())->where('id', $entry)->exists(), 403);
        return Inertia::render('ReturnStock/Show', ['entry' => $this->serialize($row)]);
    }
    private function destinationName(string $key): string
    {
        if (str_starts_with($key, 'izzi-branch-')) return 'Cabang Izzi #'.substr($key, strlen('izzi-branch-'));
        return match ($key) {
            'gudang' => 'Gudang Utama', 'hendhys-pusat' => 'Hendhys Pusat', 'jihans-retail' => 'Jihan’s Retail',
            default => 'Cabang Hendhys #'.str_replace('hendhys-branch-', '', $key),
        };
    }

    private function serialize(object $row): array
    {
        $r = (array) $row;
        foreach (['received_at', 'recorded_at'] as $field) $r[$field] = $row->$field ? Carbon::parse($row->$field)->format('d/m/Y H:i:s').' WIB' : null;
        $r['quantity'] = (float) $row->quantity;
        $r['destination_name'] = $this->destinationName($row->destination_key);
        return $r;
    }
}
