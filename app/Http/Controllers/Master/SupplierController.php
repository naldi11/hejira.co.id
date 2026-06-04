<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SaveSupplierRequest;
use App\Http\Resources\Master\SupplierResource;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    use ScopesMasterData;

    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);

        $suppliers = $this->getModelClass('Supplier', $info['scope'])::query()
            ->whereIn('entity_scope', [$info['scope'], 'all'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")))
            ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('is_active', $request->status))
            ->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Master/Suppliers/Index', [
            'suppliers'    => SupplierResource::collection($suppliers),
            'filters'      => $request->only('search', 'status'),
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getScopeInfo($request);

        return Inertia::render('Master/Suppliers/Form', [
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function store(SaveSupplierRequest $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validated();

        $data['code']         = $this->numbers->generate('SUP', 'master_suppliers', 'code');
        $data['created_by']   = auth()->id();
        $data['entity_scope'] = $request->input('entity_scope', $info['scope'] === 'gudang' ? 'all' : $info['scope']);
        $data['is_active']    = $request->boolean('is_active', true);

        $supplier = $this->getModelClass('Supplier', $info['scope'])::create($data);
        $this->logger->log('create', 'master.supplier', "Tambah supplier: {$supplier->name}", $supplier);

        return redirect()->route($info['route'].'suppliers.index')->with('success', "Supplier {$supplier->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $supplier = $this->getModelClass('Supplier', $info['scope'])::findOrFail($id);

        return Inertia::render('Master/Suppliers/Form', [
            'supplier'     => new SupplierResource($supplier),
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function update(SaveSupplierRequest $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $supplier = $this->getModelClass('Supplier', $info['scope'])::findOrFail($id);

        $old = $supplier->toArray();
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        if (! $request->filled('entity_scope')) {
            unset($data['entity_scope']);
        }

        $supplier->update($data);
        $this->logger->log('update', 'master.supplier', "Update supplier: {$supplier->name}", $supplier, $old, $supplier->fresh()->toArray());

        return redirect()->route($info['route'].'suppliers.index')->with('success', "Supplier {$supplier->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $supplier = $this->getModelClass('Supplier', $info['scope'])::findOrFail($id);

        $name = $supplier->name;
        $supplier->delete();
        $this->logger->log('delete', 'master.supplier', "Hapus supplier: $name");

        return redirect()->route($info['route'].'suppliers.index')->with('success', "Supplier $name berhasil dihapus.");
    }
}
