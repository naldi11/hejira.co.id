<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = Customer::query();

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

        return view('master.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('master.customers.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'type'      => 'required|in:retail,agen',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'address'   => 'nullable|string',
            'notes'     => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $data['code']      = $this->numbers->generate('CST', 'master_customers', 'code');
        $data['is_active'] = $request->boolean('is_active', true);

        $customer = Customer::create($data);
        $this->logger->log('create', 'master.customer', "Tambah customer: {$customer->name}", $customer);

        return redirect()->route('master.customers.index')->with('success', "Customer {$customer->name} berhasil ditambahkan.");
    }

    public function edit(Customer $customer)
    {
        return view('master.customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
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

        return redirect()->route('master.customers.index')->with('success', "Customer {$customer->name} berhasil diperbarui.");
    }

    public function destroy(Customer $customer)
    {
        $name = $customer->name;
        $customer->delete();
        $this->logger->log('delete', 'master.customer', "Hapus customer: $name");

        return redirect()->route('master.customers.index')->with('success', "Customer $name berhasil dihapus.");
    }
}
