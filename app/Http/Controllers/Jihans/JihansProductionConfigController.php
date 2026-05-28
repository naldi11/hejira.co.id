<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\JihansProductionConfig;
use App\Models\Product;
use Illuminate\Http\Request;

class JihansProductionConfigController extends Controller
{
    public function edit()
    {
        $config = JihansProductionConfig::current()->load([
            'tbProduct', 'tsProduct', 'tkProduct', 'tcProduct', 'kribabProduct',
        ]);

        $products = Product::visibleInJihans()
            ->where('source_type', 'produced')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('jihans.production-config', compact('config', 'products'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'tb_product_id'     => 'nullable|exists:master_products,id',
            'ts_product_id'     => 'nullable|exists:master_products,id',
            'tk_product_id'     => 'nullable|exists:master_products,id',
            'tc_product_id'     => 'nullable|exists:master_products,id',
            'kribab_product_id' => 'nullable|exists:master_products,id',
        ]);

        $config = JihansProductionConfig::current();
        $config->update([
            'tb_product_id'     => $request->tb_product_id ?: null,
            'ts_product_id'     => $request->ts_product_id ?: null,
            'tk_product_id'     => $request->tk_product_id ?: null,
            'tc_product_id'     => $request->tc_product_id ?: null,
            'kribab_product_id' => $request->kribab_product_id ?: null,
            'updated_by'        => auth()->id(),
        ]);

        return back()->with('success', 'Konfigurasi produksi berhasil disimpan.');
    }
}
