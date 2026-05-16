<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\HendhysStockMovement;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $q = Product::where('status', 'active');

        // Dynamic Join berdasarkan type cabang user
        if ($user->branch->type === 'pusat') {
            $q->leftJoin('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
              ->select('master_products.*', 'hendhys_stock_pusat.quantity as current_stock');
        } else {
            $q->leftJoin('hendhys_stock_branch', function($join) use ($user) {
                $join->on('master_products.id', '=', 'hendhys_stock_branch.product_id')
                     ->where('hendhys_stock_branch.branch_id', '=', $user->branch_id);
            })->select('master_products.*', 'hendhys_stock_branch.quantity as current_stock');
        }

        $q->with(['unit', 'category']);

        if ($search = $request->search) {
            $q->where(function($w) use ($search) {
                $w->where('master_products.name', 'like', "%$search%")
                  ->orWhere('master_products.code', 'like', "%$search%");
            });
        }

        if ($request->filled('jenis')) {
            $q->where('master_products.jenis', $request->jenis);
        }

        $stocks = $q->orderBy('master_products.name')->paginate(20)->withQueryString();

        return view('hendhys.stock.index', compact('stocks', 'user'));
    }

    public function movements(Request $request)
    {
        $user = auth()->user();
        $q = HendhysStockMovement::with(['product', 'creator']);

        // Filter pergerakan berdasarkan branch
        if ($user->branch->type === 'cabang') {
            $q->where('branch_id', $user->branch_id);
        } else {
            $q->whereNull('branch_id'); // Pusat tidak punya branch_id di movements (null)
        }

        if ($search = $request->search) {
            $q->whereHas('product', fn($p) => $p->where('name', 'like', "%$search%"));
        }

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        $movements = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('hendhys.stock.movements', compact('movements', 'user'));
    }
}
