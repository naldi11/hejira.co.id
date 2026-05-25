<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TransferRequest;
use App\Models\TransferRequestDetail;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferRequestController extends Controller
{
    public function __construct(private NumberGeneratorService $numbers) {}

    public function index(Request $request)
    {
        // Hanya Pusat yang bisa request ke Gudang
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $q = TransferRequest::where('from_entity', 'hendhys')->with(['creator', 'approver']);

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('request_number', 'like', "%$search%");
        }

        $requests = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('hendhys.transfer-requests.index', compact('requests'));
    }

    public function create()
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $products = Product::where('status', 'active')
            ->where('source_type', 'purchased')
            ->visibleInGudang()
            ->orderBy('name')
            ->get();
        $units = Unit::all();

        return view('hendhys.transfer-requests.form', compact('products', 'units'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
        ]);

        // Blokir produk produksi sendiri dari Transfer Request
        $producedNames = Product::whereIn('id', collect($request->items)->pluck('product_id'))
            ->where('source_type', 'produced')
            ->pluck('name');

        if ($producedNames->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'items' => 'Produk berikut adalah produk produksi sendiri dan tidak bisa diminta dari Gudang: '
                           . $producedNames->implode(', '),
            ]);
        }

        try {
            DB::transaction(function () use ($request) {
                $tr = TransferRequest::create([
                    'request_number' => $this->numbers->generateYearly('REQ-HND', 'gudang_transfer_requests', 'request_number'),
                    'from_entity' => 'hendhys',
                    'branch_id' => auth()->user()->branch_id,
                    'date' => $request->date,
                    'status' => 'pending',
                    'notes' => $request->notes,
                    'requested_by' => auth()->id()
                ]);

                foreach ($request->items as $item) {
                    TransferRequestDetail::create([
                        'request_id' => $tr->id,
                        'product_id' => $item['product_id'],
                        'quantity_requested' => $item['quantity'],
                        'unit_id' => $item['unit_id']
                    ]);
                }

                event(new \App\Events\TransferRequestCreated($tr));
            });

            return redirect()->route('hendhys.transfer-requests.index')
                ->with('success', 'Request suplai bahan baku ke Gudang berhasil dikirim.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal membuat request: ' . $e->getMessage());
        }
    }

    public function show(TransferRequest $transferRequest)
    {
        if (auth()->user()->branch->type !== 'pusat' || $transferRequest->from_entity !== 'hendhys') {
            abort(403, 'Akses ditolak.');
        }

        $transferRequest->load(['creator', 'approver', 'details.product', 'details.unit', 'transferOuts']);
        return view('hendhys.transfer-requests.show', compact('transferRequest'));
    }
}
