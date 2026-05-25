<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TransferRequest;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferRequestController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {
    }

    public function index(Request $request)
    {
        $q = TransferRequest::where('from_entity', 'jihans')->with(['approver', 'creator']);

        if ($search = $request->search) {
            $q->where('request_number', 'like', "%$search%");
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $requests = $q->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('jihans.transfer-requests.index', compact('requests'));
    }

    public function create()
    {
        $products = Product::where('status', 'active')
            ->where('source_type', 'purchased')
            ->visibleInGudang()
            ->with('unit')
            ->orderBy('name')
            ->get();

        $units = Unit::all();

        return view('jihans.transfer-requests.form', compact('products', 'units'));
    }

    public function store(Request $request)
    {
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

        DB::transaction(function () use ($request) {
            $transferRequest = TransferRequest::create([
                'request_number' => $this->numbers->generateYearly('REQ-JHS', 'gudang_transfer_requests', 'request_number'),
                'from_entity' => 'jihans',
                'branch_id' => null, // Jihans doesn't use branch
                'date' => $request->date,
                'status' => 'pending',
                'notes' => $request->notes,
                'requested_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $transferRequest->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                ]);
            }

            event(new \App\Events\TransferRequestCreated($transferRequest));
            $this->logger->log('create', 'jihans.transfer_request', "Request stok Jihan's ke Gudang: {$transferRequest->request_number}", $transferRequest);
        });

        return redirect()->route('jihans.transfer-requests.index')->with('success', 'Request stok berhasil dikirim ke Gudang Utama.');
    }

    public function show(TransferRequest $transferRequest)
    {
        abort_if($transferRequest->from_entity !== 'jihans', 403);

        $transferRequest->load(['details.product', 'details.unit', 'approver', 'transferOuts']);

        return view('jihans.transfer-requests.show', compact('transferRequest'));
    }
}
