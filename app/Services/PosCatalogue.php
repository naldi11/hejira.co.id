<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosCatalogue
{
    public function query(string $entity, $user)
    {
        $q = Product::where('master_products.status', 'active');
        if ($entity === 'jihans') {
            $q->visibleInJihans()->leftJoin('jihans_retail_stock as stock', 'stock.product_id', '=', 'master_products.id');
        } elseif (!$user->branch || $user->branch->type === 'pusat') {
            $q->visibleInHendhys()->leftJoin('hendhys_stock_pusat as stock', 'stock.product_id', '=', 'master_products.id');
        } else {
            $q->visibleInHendhys()->leftJoin('hendhys_stock_branch as stock', fn ($join) => $join
                ->on('stock.product_id', '=', 'master_products.id')->where('stock.branch_id', $user->branch_id));
        }
        return $q->select('master_products.*', DB::raw('COALESCE(stock.quantity, 0) as current_stock'))
            ->with($entity === 'jihans' ? ['unit', 'tieredPrices', 'category'] : ['unit', 'tieredPrices']);
    }

    public function page(string $entity, Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:255', 'catalog_page' => 'nullable|integer|min:1',
            'product_ids' => 'nullable|array|max:100', 'product_ids.*' => 'required|integer|min:1']);
        $q = $this->query($entity, $request->user());
        if ($request->query('lookup') === 'products' && isset($data['product_ids'])) return $q->whereIn('master_products.id', $data['product_ids'])->get();
        $search = trim($data['q'] ?? '');
        if ($search !== '') {
            $q->where(fn ($w) => $w->where('master_products.name', 'like', "%$search%")
                ->orWhere('master_products.code', 'like', "%$search%")
                ->orWhere('master_products.barcode', 'like', "%$search%"))
                ->orderByRaw('CASE WHEN master_products.barcode = ? OR master_products.code = ? THEN 0 ELSE 1 END', [$search, $search]);
        }
        if ($entity === 'hendhys') $q->orderByRaw('CASE WHEN COALESCE(stock.quantity, 0) > 0 THEN 0 ELSE 1 END');
        return $q->orderBy('master_products.name')->orderBy('master_products.id')->simplePaginate(20, ['*'], 'catalog_page');
    }

    public function metadata($page): array
    {
        return ['page' => $page->currentPage(), 'has_more' => $page->hasMorePages()];
    }
}
