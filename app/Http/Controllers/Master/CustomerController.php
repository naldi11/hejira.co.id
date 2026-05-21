<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ScopesMasterData;

    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $q = $this->getModelClass('Customer', $info['scope'])::query();

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$search%")
                                   ->orWhere('code', 'like', "%$search%")
                                   ->orWhere('phone', 'like', "%$search%"));
        }

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        if ($request->status !== null && $request->status !== '') {
            $q->where('is_active', $request->status);
        }

        $customers = $q->orderBy('name')->paginate(15)->withQueryString();

        return view('master.customers.index', [
            'customers' => $customers,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getScopeInfo($request);
        return view('master.customers.form', [
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'type'      => 'required|in:retail,agen',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'address'   => 'nullable|string',
            'notes'     => 'nullable|string',
            'is_active' => 'boolean',
            
        ]);

        $tableName = strtolower($info['scope']) . '_customers';
        $data['code']      = $this->numbers->generate('CST', $tableName, 'code');
        $data['created_by'] = auth()->id();
        $data['is_active'] = $request->boolean('is_active', true);
        

        $customer = $this->getModelClass('Customer', $info['scope'])::create($data);
        $this->logger->log('create', 'master.customer', "Tambah customer: {$customer->name}", $customer);

        return redirect()->route($info['route'].'customers.index')->with('success', "Customer {$customer->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $customer = $this->getModelClass('Customer', $info['scope'])::findOrFail($id);
        

        return view('master.customers.form', [
            'customer' => $customer,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function update(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $customer = $this->getModelClass('Customer', $info['scope'])::findOrFail($id);
        

        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'type'      => 'required|in:retail,agen',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'address'   => 'nullable|string',
            'notes'     => 'nullable|string',
            'is_active' => 'boolean',
            
        ]);

        $old = $customer->toArray();
        $data['is_active'] = $request->boolean('is_active', true);
        $customer->update($data);

        $this->logger->log('update', 'master.customer', "Update customer: {$customer->name}", $customer, $old, $customer->fresh()->toArray());

        return redirect()->route($info['route'].'customers.index')->with('success', "Customer {$customer->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $customer = $this->getModelClass('Customer', $info['scope'])::findOrFail($id);
        

        $name = $customer->name;
        $customer->delete();
        $this->logger->log('delete', 'master.customer', "Hapus customer: $name");

        return redirect()->route($info['route'].'customers.index')->with('success', "Customer $name berhasil dihapus.");
    }
}
