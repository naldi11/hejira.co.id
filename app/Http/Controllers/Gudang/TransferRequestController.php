<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\ApproveTransferRequest;
use App\Http\Requests\Gudang\RejectTransferRequest;
use App\Http\Resources\Gudang\TransferRequestResource;
use App\Models\JihansGudangStock;
use App\Models\TransferRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TransferRequestController extends Controller
{
    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $requests = TransferRequest::with(['branch', 'requester'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from_entity'), fn ($q) => $q->where('from_entity', $request->from_entity))
            ->when($request->filled('search'), fn ($q) => $q->where('request_number', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Gudang/TransferRequests/Index', [
            'requests' => TransferRequestResource::collection($requests),
            'counts'   => [
                'pending'   => TransferRequest::where('status', 'pending')->count(),
                'approved'  => TransferRequest::where('status', 'approved')->count(),
                'completed' => TransferRequest::where('status', 'completed')->count(),
            ],
            'filters'  => $request->only('search', 'status', 'from_entity'),
        ]);
    }

    public function show(TransferRequest $transferRequest)
    {
        $transferRequest->load(['branch', 'requester', 'approver', 'details.product', 'details.unit']);

        // Batch the warehouse-stock lookup (one query instead of one-per-row).
        $stocks = JihansGudangStock::whereIn('product_id', $transferRequest->details->pluck('product_id'))
            ->pluck('quantity', 'product_id');
        $transferRequest->details->each(function ($detail) use ($stocks) {
            $detail->warehouse_stock = (float) ($stocks[$detail->product_id] ?? 0);
        });

        return Inertia::render('Gudang/TransferRequests/Show', [
            'request' => new TransferRequestResource($transferRequest),
        ]);
    }

    public function approve(ApproveTransferRequest $request, TransferRequest $transferRequest)
    {
        abort_if($transferRequest->status !== 'pending', 403, 'Request ini sudah diproses.');

        $data = $request->validated();

        DB::transaction(function () use ($data, $transferRequest) {
            $allFulfilled = true;

            foreach ($data['items'] as $item) {
                $detail = $transferRequest->details->find($item['id']);
                if (! $detail) {
                    continue;
                }

                // Business rule: approved qty cannot exceed requested qty.
                if ((float) $item['quantity_approved'] > (float) $detail->quantity_requested) {
                    throw ValidationException::withMessages([
                        'items' => "Kuantitas disetujui untuk produk '" . ($detail->product->name ?? 'Barang') . "' tidak boleh melebihi jumlah permintaan (" . floatval($detail->quantity_requested) . ").",
                    ]);
                }

                $detail->update(['quantity_approved' => $item['quantity_approved']]);

                if ((float) $item['quantity_approved'] < (float) $detail->quantity_requested) {
                    $allFulfilled = false;
                }
            }

            $transferRequest->update([
                'status'      => $allFulfilled ? 'approved' : 'partial',
                'notes'       => $data['notes'] ?? $transferRequest->notes,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->logger->log('approve', 'gudang.transfer_request', "Approve request: {$transferRequest->request_number}", $transferRequest);

            event(new \App\Events\TransferRequestStatusChanged($transferRequest));
        });

        return back()->with('success', "Request {$transferRequest->request_number} berhasil di-approve.");
    }

    public function reject(RejectTransferRequest $request, TransferRequest $transferRequest)
    {
        abort_if($transferRequest->status !== 'pending', 403, 'Request ini sudah diproses.');

        $transferRequest->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->validated('rejection_reason'),
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        $this->logger->log('reject', 'gudang.transfer_request', "Reject request: {$transferRequest->request_number}", $transferRequest);

        return back()->with('success', "Request {$transferRequest->request_number} ditolak.");
    }
}
