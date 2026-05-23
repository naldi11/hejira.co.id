<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductionRate;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ProductionRateController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger) {}

    public function edit(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $rate = ProductionRate::where('entity_scope', $info['scope'])->first();

        return view('master.production-rates.edit', [
            'rate'         => $rate,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function update(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'tb_rate'     => 'required|numeric|min:0',
            'ts_rate'     => 'required|numeric|min:0',
            'tk_rate'     => 'required|numeric|min:0',
            'tc_rate'     => 'required|numeric|min:0',
            'kribab_rate' => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $data['updated_by']   = auth()->id();
        $data['entity_scope'] = $info['scope'];

        ProductionRate::updateOrCreate(
            ['entity_scope' => $info['scope']],
            $data
        );

        $this->logger->log('update', 'master.production_rate', "Update tarif produksi {$info['scope']}");

        return redirect()->route($info['route'] . 'production-rates.edit')
            ->with('success', 'Tarif produksi berhasil diperbarui.');
    }
}
