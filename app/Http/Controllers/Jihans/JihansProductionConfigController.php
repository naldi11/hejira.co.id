<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jihans\UpdateProductionConfigRequest;
use App\Models\JihansProductionConfig;
use App\Models\Product;
use Inertia\Inertia;

class JihansProductionConfigController extends Controller
{
    public function edit()
    {
        $config = JihansProductionConfig::current();

        return Inertia::render('Jihans/ProductionConfig', [
            'config' => [
                'tb_product_id'     => $config->tb_product_id,
                'ts_product_id'     => $config->ts_product_id,
                'tk_product_id'     => $config->tk_product_id,
                'tc_product_id'     => $config->tc_product_id,
                'kribab_product_id' => $config->kribab_product_id,
            ],
            'products' => Product::visibleInJihans()->where('source_type', 'produced')->where('status', 'active')->orderBy('name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code]),
        ]);
    }

    public function update(UpdateProductionConfigRequest $request)
    {
        $data = $request->validated();

        JihansProductionConfig::current()->update([
            'tb_product_id'     => $data['tb_product_id'] ?: null,
            'ts_product_id'     => $data['ts_product_id'] ?: null,
            'tk_product_id'     => $data['tk_product_id'] ?: null,
            'tc_product_id'     => $data['tc_product_id'] ?: null,
            'kribab_product_id' => $data['kribab_product_id'] ?: null,
            'updated_by'        => auth()->id(),
        ]);

        return back()->with('success', 'Konfigurasi produksi berhasil disimpan.');
    }
}
