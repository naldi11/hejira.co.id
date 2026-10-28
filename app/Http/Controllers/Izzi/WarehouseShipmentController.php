<?php

namespace App\Http\Controllers\Izzi;

use App\Http\Controllers\Controller;
use App\Http\Resources\Gudang\TransferOutResource;
use App\Models\{Branch, TransferOut};
use App\Services\Izzi\Access;
use App\Services\TransferReceiving;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseShipmentController extends Controller
{
    public function index(Request $request, Access $access)
    {
        $filters = $request->validate(['branch_id' => 'nullable|integer|min:1', 'search' => 'nullable|string|max:100', 'status' => 'nullable|in:sent,received']);
        $branch = $access->branch($request->user(), $request->filled('branch_id') ? $request->integer('branch_id') : null, true);
        $rows = TransferOut::where('to_entity', 'izzi')->where('branch_id', $branch->id)->with(['branch', 'creator'])
            ->when($request->filled('search'), fn ($q) => $q->where('transfer_number', 'like', '%'.$filters['search'].'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return Inertia::render('Izzi/WarehouseShipments/Index', [
            'transfers' => TransferOutResource::collection($rows), 'filters' => $filters,
            'branch' => $branch->only(['id', 'name', 'is_active']),
            'branches' => $request->user()->hasRole('super_admin') ? Branch::where('entity', 'izzi')->orderBy('name')->get(['id', 'name', 'is_active']) : [],
        ]);
    }

    private function document(Request $request, int $shipment): TransferOut
    {
        $document = TransferOut::with(['branch', 'creator', 'details.product', 'details.unit', 'receiver', 'receiptConfirmation.details', 'photos'])->findOrFail($shipment);
        abort_unless($document->to_entity === 'izzi', 403);
        app(TransferReceiving::class)->authorize($document, $request->user());

        return $document;
    }

    public function show(Request $request, int $shipment)
    {
        $document = $this->document($request, $shipment);

        return Inertia::render('Izzi/WarehouseShipments/Show', [
            'transfer' => new TransferOutResource($document),
            'branchId' => $document->branch_id,
            'canReceive' => $document->status === 'sent' && $document->branch?->is_active,
            'receipt' => $document->receiptConfirmation ? [
                'received_at' => $document->received_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB',
                'receiver' => $document->receive_received_by_name, 'notes' => $document->receive_notes,
                'items' => $document->receiptConfirmation->details->map->only(['product_id', 'actual_qty', 'condition']),
                'photos' => $document->photos->map(fn ($photo) => ['url' => $photo->url()]),
            ] : null,
        ]);
    }

    public function print(Request $request, int $shipment)
    {
        $transferOut = $this->document($request, $shipment);

        return view('gudang.transfer-out.print', ['transferOut' => $transferOut,
            'backUrl' => route('izzi.warehouse-shipments.show', $transferOut->id)]);
    }
}
