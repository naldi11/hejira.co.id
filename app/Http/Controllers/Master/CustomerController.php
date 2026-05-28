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
        $q->whereIn('entity_scope', [$info['scope'], 'all']);

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
            'type'      => 'required|in:Pelanggan Individual,Pelanggan Retail,Pelanggan Agen',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'province'  => 'nullable|string|max:100',
            'city'      => 'nullable|string|max:100',
            'district'  => 'nullable|string|max:100',
            'address'   => 'nullable|string',
            'notes'     => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $tableName = 'master_customers';
        $data['code']      = $this->numbers->generate('CST', $tableName, 'code');
        $data['created_by'] = auth()->id();
        $data['entity_scope']    = $request->input('entity_scope', $info['scope'] === 'gudang' ? 'all' : $info['scope']);
        $data['visible_gudang']  = $request->boolean('visible_gudang',  in_array($info['scope'], ['gudang']));
        $data['visible_jihans']  = $request->boolean('visible_jihans',  in_array($info['scope'], ['gudang','jihans']));
        $data['visible_hendhys'] = $request->boolean('visible_hendhys', in_array($info['scope'], ['gudang','hendhys']));
        $data['is_active']       = $request->boolean('is_active', true);
        

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
            'name'         => 'required|string|max:150',
            'type'         => 'required|in:Pelanggan Individual,Pelanggan Retail,Pelanggan Agen',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:100',
            'province'     => 'nullable|string|max:100',
            'city'         => 'nullable|string|max:100',
            'district'     => 'nullable|string|max:100',
            'address'      => 'nullable|string',
            'notes'        => 'nullable|string',
            'is_active'    => 'boolean',
            'entity_scope' => 'nullable|in:all,gudang,jihans,hendhys',
        ]);

        $old = $customer->toArray();
        $data['is_active']       = $request->boolean('is_active', true);
        $data['entity_scope']    = $request->input('entity_scope', $customer->entity_scope);
        $data['visible_gudang']  = $request->boolean('visible_gudang');
        $data['visible_jihans']  = $request->boolean('visible_jihans');
        $data['visible_hendhys'] = $request->boolean('visible_hendhys');
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

    public function downloadTemplate()
    {
        return response()->download(public_path('Daftar Pelanggan.xls'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $info = $this->getScopeInfo($request);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\Master\CustomersImport, $request->file('file'));
            $this->logger->log('import', 'master.customer', "Import customer via Excel");
            return redirect()->route($info['route'] . 'customers.index')->with('success', 'Customer berhasil di-import dan diperbarui dari file Excel.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }
}
