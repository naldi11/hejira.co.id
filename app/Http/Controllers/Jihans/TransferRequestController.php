<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jihans\StoreTransferRequestRequest;
use App\Http\Resources\Jihans\TransferRequestResource;
use App\Models\Product;
use App\Models\TransferOut;
use App\Models\TransferRequest;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransferRequestController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $requests = TransferRequest::where('from_entity', 'jihans')
            ->with(['creator', 'transferOuts'])
            ->when($request->filled('search'), fn ($q) => $q->where('request_number', 'like', "%{$request->search}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(15)->withQueryString();

        $incoming = TransferOut::where('to_entity', 'jihans')->where('status', 'sent')
            ->with(['creator', 'request'])->get()
            ->map(fn ($do) => [
                'id'              => $do->id,
                'transfer_number' => $do->transfer_number,
                'date'            => $do->date?->format('d/m/Y'),
                'request_number'  => $do->request?->request_number,
                'creator'         => $do->creator?->name ?? 'Gudang',
            ]);

        return Inertia::render('Jihans/TransferRequests/Index', [
            'requests'          => TransferRequestResource::collection($requests),
            'incomingTransfers' => $incoming,
            'filters'           => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Jihans/TransferRequests/Create', [
            'products' => Product::where('status', 'active')->where('source_type', 'purchased')->visibleInGudang()->with('unit')->orderBy('name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'unit_id' => $p->unit_id]),
            'units'    => Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'abbreviation' => $u->abbreviation]),
        ]);
    }

    public function store(StoreTransferRequestRequest $request)
    {
        $data = $request->validated();

        // Block self-produced products from being requested from the warehouse.
        $producedNames = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
            ->where('source_type', 'produced')->pluck('name');

        if ($producedNames->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'items' => 'Produk berikut adalah produk produksi sendiri dan tidak bisa diminta dari Gudang: ' . $producedNames->implode(', '),
            ]);
        }

        DB::transaction(function () use ($data) {
            $tr = TransferRequest::create([
                'request_number' => $this->numbers->generateYearly('REQ-JHS', 'gudang_transfer_requests', 'request_number'),
                'from_entity'    => 'jihans',
                'branch_id'      => null,
                'date'           => $data['date'],
                'status'         => 'pending',
                'notes'          => $data['notes'] ?? null,
                'requested_by'   => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $tr->details()->create([
                    'product_id'         => $item['product_id'],
                    'quantity_requested' => $item['quantity'],
                    'unit_id'            => $item['unit_id'],
                ]);
            }

            event(new \App\Events\TransferRequestCreated($tr));
            $this->logger->log('create', 'jihans.transfer_request', "Request stok Jihan's ke Gudang: {$tr->request_number}", $tr);
        });

        return redirect()->route('jihans.transfer-requests.index')->with('success', 'Request stok berhasil dikirim ke Gudang Utama.');
    }

    public function show(TransferRequest $transferRequest)
    {
        abort_if($transferRequest->from_entity !== 'jihans', 403);

        $transferRequest->load(['details.product', 'details.unit', 'creator', 'approver', 'transferOuts']);

        return Inertia::render('Jihans/TransferRequests/Show', [
            'request' => new TransferRequestResource($transferRequest),
        ]);
    }
}
