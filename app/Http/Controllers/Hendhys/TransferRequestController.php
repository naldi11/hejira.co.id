<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysTransferRequestResource;
use App\Models\Product;
use App\Models\TransferRequest;
use App\Models\TransferRequestDetail;
use App\Models\Unit;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransferRequestController extends Controller
{
    public function __construct(private NumberGeneratorService $numbers) {}

    public function index(Request $request)
    {
        // Hanya Pusat yang bisa request ke Gudang
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $q = TransferRequest::where('from_entity', 'hendhys')->with(['creator', 'approver', 'transferOuts']);

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('request_number', 'like', "%$search%");
        }

        $requests = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Load incoming transfers from Gudang with status 'sent'
        $incoming = \App\Models\TransferOut::where('to_entity', 'hendhys')
            ->where('branch_id', auth()->user()->branch_id)
            ->where('status', 'sent')
            ->with(['creator', 'request'])
            ->get()
            ->map(fn ($do) => [
                'id'              => $do->id,
                'transfer_number' => $do->transfer_number,
                'date'            => $do->date?->format('d/m/Y'),
                'request_number'  => $do->request?->request_number,
                'creator'         => $do->creator?->name ?? 'Gudang',
            ]);

        return Inertia::render('Hendhys/TransferRequests/Index', [
            'requests'          => HendhysTransferRequestResource::collection($requests),
            'incomingTransfers' => $incoming,
            'filters'           => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $products = Product::where('status', 'active')
            ->where('source_type', 'purchased')
            ->visibleInGudang()
            ->with('unit')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'unit_id' => $p->unit_id]);

        $units = Unit::orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'abbreviation' => $u->abbreviation]);

        return Inertia::render('Hendhys/TransferRequests/Create', [
            'products' => $products,
            'units'    => $units,
        ]);
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

        return Inertia::render('Hendhys/TransferRequests/Show', [
            'request' => new HendhysTransferRequestResource($transferRequest),
        ]);
    }
}
