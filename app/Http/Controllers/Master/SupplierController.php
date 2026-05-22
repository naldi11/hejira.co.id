<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;

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
        $q = $this->getModelClass('Supplier', $info['scope'])::query()->whereIn('entity_scope', [$info['scope'], 'all']);

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$search%")
                                   ->orWhere('code', 'like', "%$search%")
                                   ->orWhere('phone', 'like', "%$search%"));
        }

        if ($request->status !== null && $request->status !== '') {
            $q->where('is_active', $request->status);
        }

        $suppliers = $q->orderBy('name')->paginate(15)->withQueryString();

        return view('master.suppliers.index', [
            'suppliers' => $suppliers,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getScopeInfo($request);
        return view('master.suppliers.form', [
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'contact_person'  => 'nullable|string|max:100',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:100',
            'address'         => 'nullable|string',
            'notes'           => 'nullable|string',
            'is_active'       => 'boolean',
            
        ]);

        $tableName = 'master_suppliers';
        $data['code']      = $this->numbers->generate('SUP', $tableName, 'code');
        $data['created_by'] = auth()->id();
        $data['entity_scope'] = $request->input('entity_scope', $info['scope'] === 'gudang' ? 'all' : $info['scope']);
        $data['is_active'] = $request->boolean('is_active', true);
        

        $supplier = $this->getModelClass('Supplier', $info['scope'])::create($data);
        $this->logger->log('create', 'master.supplier', "Tambah supplier: {$supplier->name}", $supplier);

        return redirect()->route($info['route'].'suppliers.index')->with('success', "Supplier {$supplier->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $supplier = $this->getModelClass('Supplier', $info['scope'])::findOrFail($id);
        

        return view('master.suppliers.form', [
            'supplier' => $supplier,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function update(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $supplier = $this->getModelClass('Supplier', $info['scope'])::findOrFail($id);
        

        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'contact_person'  => 'nullable|string|max:100',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:100',
            'address'         => 'nullable|string',
            'notes'           => 'nullable|string',
            'is_active'       => 'boolean',
            
        ]);

        $old = $supplier->toArray();
        $data['is_active'] = $request->boolean('is_active', true);
        if($request->filled('entity_scope')) 
        
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
