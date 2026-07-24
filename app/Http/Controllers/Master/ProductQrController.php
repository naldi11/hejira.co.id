<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Master\ScopesMasterData;

class ProductQrController extends Controller
{
    use ScopesMasterData;

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $modelClass = $this->getModelClass('Product', $info['scope']);

        $q = $modelClass::with(['category', 'unit', 'brand']);

        // Terapkan scope visibilitas berdasarkan entitas login
        if ($info['scope'] === 'hendhys') {
            $q->visibleInHendhys();
        } elseif ($info['scope'] === 'jihans') {
            $q->visibleInJihans();
        } else {
            $q->visibleInGudang();
        }

        if ($search = $request->search) {
            $q->where(fn($w) => $w->where('name', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")
                ->orWhere('barcode', 'like', "%$search%"));
        }

        if ($request->filled('visibility')) {
            $q->where('visible_' . $request->visibility, true);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 50);
        $products = $q->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('Master/Products/QrPrint', [
            'products' => $products,
            'filters'  => $request->only('search', 'status', 'visibility', 'per_page'),
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }
}
