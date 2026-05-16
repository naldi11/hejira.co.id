<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = Supplier::query();

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$search%")
                                   ->orWhere('code', 'like', "%$search%")
                                   ->orWhere('phone', 'like', "%$search%"));
        }

        if ($request->status !== null && $request->status !== '') {
            $q->where('is_active', $request->status);
        }

        $suppliers = $q->orderBy('name')->paginate(15)->withQueryString();

        return view('master.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('master.suppliers.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'contact_person'  => 'nullable|string|max:100',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:100',
            'address'         => 'nullable|string',
            'notes'           => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $data['code']      = $this->numbers->generate('SUP', 'master_suppliers', 'code');
        $data['is_active'] = $request->boolean('is_active', true);

        $supplier = Supplier::create($data);
        $this->logger->log('create', 'master.supplier', "Tambah supplier: {$supplier->name}", $supplier);

        return redirect()->route('master.suppliers.index')->with('success', "Supplier {$supplier->name} berhasil ditambahkan.");
    }

    public function edit(Supplier $supplier)
    {
        return view('master.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
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
        $supplier->update($data);

        $this->logger->log('update', 'master.supplier', "Update supplier: {$supplier->name}", $supplier, $old, $supplier->fresh()->toArray());

        return redirect()->route('master.suppliers.index')->with('success', "Supplier {$supplier->name} berhasil diperbarui.");
    }

    public function destroy(Supplier $supplier)
    {
        $name = $supplier->name;
        $supplier->delete();
        $this->logger->log('delete', 'master.supplier', "Hapus supplier: $name");

        return redirect()->route('master.suppliers.index')->with('success', "Supplier $name berhasil dihapus.");
    }
}
