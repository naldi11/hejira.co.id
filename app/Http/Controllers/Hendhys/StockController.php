<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HendhysStockBranch;
use App\Models\HendhysStockMovement;
use App\Models\HendhysStockPusat;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isPusat = $user->branch->type === 'pusat';

        if ($isPusat) {
            // --- PUSAT: stok milik pusat ---
            $q = Product::where('status', 'active')
                ->leftJoin('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
                ->select('master_products.*', 'hendhys_stock_pusat.quantity as current_stock')
                ->with(['unit', 'category']);

            if ($search = $request->search) {
                $q->where(function ($w) use ($search) {
                    $w->where('master_products.name', 'like', "%$search%")
                        ->orWhere('master_products.code', 'like', "%$search%");
                });
            }

            $stocks = $q->orderBy('master_products.name')->paginate(20)->withQueryString();

            // Daftar cabang aktif beserta stok masing-masing
            $branches = Branch::where('is_active', true)
                ->where('type', 'cabang')
                ->orderBy('name')
                ->get();

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
                ->select('master_products.*', 'hendhys_stock_branch.quantity as current_stock', 'hendhys_stock_branch.branch_id')
                ->orderBy('master_products.name')
                ->paginate(20, ['*'], 'branch_page')
                ->withQueryString();

            return view('hendhys.stock.index', compact('stocks', 'user', 'isPusat', 'branches', 'branchStocks', 'selectedBranchId'));
        } else {
            // --- CABANG: hanya stok milik branch sendiri ---
            $q = Product::where('status', 'active')
                ->leftJoin('hendhys_stock_branch', function ($join) use ($user) {
                    $join->on('master_products.id', '=', 'hendhys_stock_branch.product_id')
                        ->where('hendhys_stock_branch.branch_id', '=', $user->branch_id);
                })
                ->select('master_products.*', 'hendhys_stock_branch.quantity as current_stock')
                ->with(['unit', 'category']);

            if ($search = $request->search) {
                $q->where(function ($w) use ($search) {
                    $w->where('master_products.name', 'like', "%$search%")
                        ->orWhere('master_products.code', 'like', "%$search%");
                });
            }

            $stocks = $q->orderBy('master_products.name')->paginate(20)->withQueryString();

            return view('hendhys.stock.index', compact('stocks', 'user', 'isPusat'));
        }
    }

    public function movements(Request $request)
    {
        $user = auth()->user();
        $isPusat = $user->branch->type === 'pusat';
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
        $branches = $isPusat ? Branch::where('is_active', true)->where('type', 'cabang')->orderBy('name')->get() : collect();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('hendhys.stock.movements', compact('movements', 'user', 'isPusat', 'branches', 'products'));
    }
}
