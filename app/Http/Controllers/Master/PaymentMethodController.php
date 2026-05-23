<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentMethodController extends Controller
{
    use ScopesMasterData;

    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $methods = PaymentMethod::whereIn('entity_scope', [$info['scope'], 'all'])
            ->orderBy('name')
            ->get();

        return view('master.payment-methods.index', [
            'methods'      => $methods,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getScopeInfo($request);
        return view('master.payment-methods.form', [
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name'   => 'nullable|string|max:100',
            'image'          => 'nullable|image|max:2048',
            'is_active'      => 'boolean',
            'entity_scope'   => 'nullable|in:gudang,jihans,hendhys,all',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('payment-methods', 'public');
        }

        $data['entity_scope'] = $request->input('entity_scope', $info['scope']);
        $data['is_active']    = $request->boolean('is_active', true);

        $method = PaymentMethod::create($data);
        $this->logger->log('create', 'master.payment_method', "Tambah metode: {$method->name}", $method);

        return redirect()->route($info['route'] . 'payment-methods.index')
            ->with('success', "Metode pembayaran {$method->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, PaymentMethod $paymentMethod)
    {
        $info = $this->getScopeInfo($request);
        return view('master.payment-methods.form', [
            'method'       => $paymentMethod,
            'layout'       => $info['layout'],
            'routePrefix'  => $info['route'],
            'currentScope' => $info['scope'],
        ]);
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $info = $this->getScopeInfo($request);
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name'   => 'nullable|string|max:100',
            'image'          => 'nullable|image|max:2048',
            'is_active'      => 'boolean',
            'entity_scope'   => 'nullable|in:gudang,jihans,hendhys,all',
        ]);

        if ($request->hasFile('image')) {
            if ($paymentMethod->image) {
                Storage::disk('public')->delete($paymentMethod->image);
            }
            $data['image'] = $request->file('image')->store('payment-methods', 'public');
        }

        $old = $paymentMethod->toArray();
        $data['is_active']    = $request->boolean('is_active', true);
        $data['entity_scope'] = $request->input('entity_scope', $paymentMethod->entity_scope);
        $paymentMethod->update($data);

        $this->logger->log('update', 'master.payment_method', "Update metode: {$paymentMethod->name}", $paymentMethod, $old, $paymentMethod->fresh()->toArray());

        return redirect()->route($info['route'] . 'payment-methods.index')
            ->with('success', "Metode {$paymentMethod->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod)
    {
        $info = $this->getScopeInfo($request);
        if ($paymentMethod->image) {
            Storage::disk('public')->delete($paymentMethod->image);
        }
        $name = $paymentMethod->name;
        $paymentMethod->delete();
        $this->logger->log('delete', 'master.payment_method', "Hapus metode: $name");

        return redirect()->route($info['route'] . 'payment-methods.index')
            ->with('success', "Metode $name berhasil dihapus.");
    }
}
