<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysStockMovementResource;
use App\Http\Resources\Hendhys\HendhysStockResource;
use App\Models\Branch;
use App\Models\HendhysStockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isPusat = !$user->branch || $user->branch->type === 'pusat';
        $lowStockOnly = $request->low_stock === '1';

        if ($isPusat) {
            // --- PUSAT: stok milik pusat ---
            $q = Product::where('status', 'active')
                ->where(function ($w) {
                    $w->visibleInHendhys()
                      ->orWhereExists(function ($sq) {
                          $sq->from('hendhys_stock_pusat')
                             ->whereColumn('hendhys_stock_pusat.product_id', 'master_products.id');
                      });
                })
                ->leftJoin('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
                ->leftJoin('jihans_gudang_stock', 'master_products.id', '=', 'jihans_gudang_stock.product_id')
                ->select('master_products.*', 'hendhys_stock_pusat.quantity as current_stock', 'hendhys_stock_pusat.quantity_return as return_stock', 'jihans_gudang_stock.quantity as parent_stock')
                ->with(['unit', 'category']);

            if ($search = $request->search) {
                $q->where(function ($w) use ($search) {
                    $w->where('master_products.name', 'like', "%$search%")
                        ->orWhere('master_products.code', 'like', "%$search%");
                });
            }

            $stocks = $q->when($lowStockOnly, fn ($q) => $q
                    ->whereRaw('COALESCE(hendhys_stock_pusat.quantity, 0) < master_products.stock_min')
                    ->where('master_products.stock_min', '>', 0))
                ->when($lowStockOnly, function ($q) {
                    $q->orderBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN COALESCE(hendhys_stock_pusat.quantity, 0) = 0 THEN 1 ELSE 0 END"), 'asc')
                      ->orderBy('hendhys_stock_pusat.quantity', 'desc');
                })
                ->orderBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN COALESCE(hendhys_stock_pusat.quantity, 0) > 0 THEN 0 ELSE 1 END"), 'asc')
                ->orderBy('master_products.name')
                ->paginate(20)->withQueryString();

            // Daftar cabang aktif beserta stok masing-masing
            $branches = Branch::where('is_active', true)
                ->where('type', 'cabang')
                ->orderBy('name')
                ->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]);

            // Stok per cabang yang difilter
            $selectedBranchId = $request->branch_id;
            $branchStocksQuery = Product::where('master_products.status', 'active')
                ->join('hendhys_stock_branch', 'master_products.id', '=', 'hendhys_stock_branch.product_id')
                ->with(['unit']);

            if ($selectedBranchId) {
                $branchStocksQuery->where('hendhys_stock_branch.branch_id', $selectedBranchId);
            }

            if ($search = $request->search) {
                $branchStocksQuery->where(function ($w) use ($search) {
                    $w->where('master_products.name', 'like', "%$search%")
                        ->orWhere('master_products.code', 'like', "%$search%");
                });
            }

            $branchStocks = $branchStocksQuery
                ->select('master_products.*', 'hendhys_stock_branch.quantity as current_stock', 'hendhys_stock_branch.quantity_return as return_stock', 'hendhys_stock_branch.branch_id')
                ->when($lowStockOnly, fn ($q) => $q
                    ->whereRaw('COALESCE(hendhys_stock_branch.quantity, 0) < master_products.stock_min')
                    ->where('master_products.stock_min', '>', 0))
                ->when($lowStockOnly, function ($q) {
                    $q->orderBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN COALESCE(hendhys_stock_branch.quantity, 0) = 0 THEN 1 ELSE 0 END"), 'asc')
                      ->orderBy('hendhys_stock_branch.quantity', 'desc');
                })
                ->orderBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN COALESCE(hendhys_stock_branch.quantity, 0) > 0 THEN 0 ELSE 1 END"), 'asc')
                ->orderBy('master_products.name')
                ->paginate(20, ['*'], 'branch_page')
                ->withQueryString();

            return Inertia::render('Hendhys/Stock/Index', [
                'stocks'           => HendhysStockResource::collection($stocks),
                'branches'         => $branches,
                'branchStocks'     => HendhysStockResource::collection($branchStocks),
                'selectedBranchId' => $selectedBranchId,
                'isPusat'          => true,
                'filters'          => $request->only('search', 'branch_id', 'low_stock'),
            ]);
        } else {
            // --- CABANG: hanya stok milik branch sendiri ---
            $q = Product::where('status', 'active')
                ->leftJoin('hendhys_stock_branch', function ($join) use ($user) {
                    $join->on('master_products.id', '=', 'hendhys_stock_branch.product_id')
                        ->where('hendhys_stock_branch.branch_id', '=', $user->branch_id);
                })
                ->leftJoin('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
                ->select('master_products.*', 'hendhys_stock_branch.quantity as current_stock', 'hendhys_stock_branch.quantity_return as return_stock', 'hendhys_stock_pusat.quantity as parent_stock')
                ->with(['unit', 'category']);

            if ($search = $request->search) {
                $q->where(function ($w) use ($search) {
                    $w->where('master_products.name', 'like', "%$search%")
                        ->orWhere('master_products.code', 'like', "%$search%");
                });
            }

            $stocks = $q->when($lowStockOnly, fn ($q) => $q
                    ->whereRaw('COALESCE(hendhys_stock_branch.quantity, 0) < master_products.stock_min')
                    ->where('master_products.stock_min', '>', 0))
                ->when($lowStockOnly, function ($q) {
                    $q->orderBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN COALESCE(hendhys_stock_branch.quantity, 0) = 0 THEN 1 ELSE 0 END"), 'asc')
                      ->orderBy('hendhys_stock_branch.quantity', 'desc');
                })
                ->orderBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN COALESCE(hendhys_stock_branch.quantity, 0) > 0 THEN 0 ELSE 1 END"), 'asc')
                ->orderBy('master_products.name')
                ->paginate(20)->withQueryString();

            return Inertia::render('Hendhys/Stock/Index', [
                'stocks'  => HendhysStockResource::collection($stocks),
                'isPusat' => false,
                'filters' => $request->only('search', 'low_stock'),
            ]);
        }
    }

    public function movements(Request $request)
    {
        $user = auth()->user();
        $isPusat = !$user->branch || $user->branch->type === 'pusat';
        $q = HendhysStockMovement::with(['product', 'creator']);

        // Filter pergerakan berdasarkan branch
        if (!$isPusat) {
            $q->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            if ($request->branch_id === 'pusat') {
                $q->whereNull('branch_id');
            } else {
                $q->where('branch_id', $request->branch_id);
            }
        }

        if ($search = $request->search) {
            $q->whereHas('product', fn($p) => $p->where('name', 'like', "%$search%"));
        }

        if ($request->filled('product_id')) {
            $q->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $branches = $isPusat ? Branch::where('is_active', true)->where('type', 'cabang')->orderBy('name')->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]) : collect();
        $products = Product::where('status', 'active')->orderBy('name')->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]);

        return Inertia::render('Hendhys/Stock/Movements', [
            'movements' => HendhysStockMovementResource::collection($movements),
            'branches'  => $branches,
            'products'  => $products,
            'isPusat'   => $isPusat,
            'filters'   => $request->only('search', 'branch_id', 'product_id', 'type', 'date_from', 'date_to'),
        ]);
    }
}
