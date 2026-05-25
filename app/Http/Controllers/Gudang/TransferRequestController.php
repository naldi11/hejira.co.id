<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\TransferRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferRequestController extends Controller
{
    public function __construct(private ActivityLogService $logger) {}

    public function index(Request $request)
    {
        $q = TransferRequest::with(['branch', 'requester']);

        if ($request->filled('status'))      $q->where('status', $request->status);
        if ($request->filled('from_entity')) $q->where('from_entity', $request->from_entity);

        if ($search = $request->search) {
            $q->where('request_number', 'like', "%$search%");
        }

        $requests = $q->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $counts = [
            'pending'   => TransferRequest::where('status', 'pending')->count(),
            'approved'  => TransferRequest::where('status', 'approved')->count(),
            'completed' => TransferRequest::where('status', 'completed')->count(),
        ];

        return view('gudang.transfer-requests.index', compact('requests', 'counts'));
    }

    public function show(TransferRequest $transferRequest)
    {
        $transferRequest->load(['branch', 'requester', 'approver', 'details.product', 'details.unit', 'transferOuts']);

        return view('gudang.transfer-requests.show', compact('transferRequest'));
    }

    public function approve(Request $request, TransferRequest $transferRequest)
    {
        abort_if($transferRequest->status !== 'pending', 403, 'Request ini sudah diproses.');

        $request->validate([
            'items'                    => 'required|array',
            'items.*.id'               => 'required|exists:gudang_transfer_request_details,id',
            'items.*.quantity_approved' => 'required|numeric|min:0.001',
            'notes'                    => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $transferRequest) {
            $allFulfilled = true;

            foreach ($request->items as $item) {
                $detail = $transferRequest->details->find($item['id']);
                if (!$detail) continue;

                // VALIDASI BACKEND: Qty disetujui tidak boleh melebihi qty diminta!
                if ((float)$item['quantity_approved'] > (float)$detail->quantity_requested) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Kuantitas disetujui untuk produk '" . ($detail->product->name ?? 'Barang') . "' tidak boleh melebihi jumlah permintaan (" . floatval($detail->quantity_requested) . ")."
                    ]);
                }

                $detail->update(['quantity_approved' => $item['quantity_approved']]);

                if ((float)$item['quantity_approved'] < (float)$detail->quantity_requested) {
                    $allFulfilled = false;
                }
            }

            $transferRequest->update([
                'status'      => $allFulfilled ? 'approved' : 'partial',
                'notes'       => $request->notes ?? $transferRequest->notes,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->logger->log('approve', 'gudang.transfer_request',
                "Approve request: {$transferRequest->request_number}", $transferRequest);
                
            event(new \App\Events\TransferRequestStatusChanged($transferRequest));
        });

        return back()->with('success', "Request {$transferRequest->request_number} berhasil di-approve.");
    }

    public function reject(Request $request, TransferRequest $transferRequest)
    {
        abort_if($transferRequest->status !== 'pending', 403, 'Request ini sudah diproses.');

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $transferRequest->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        $this->logger->log('reject', 'gudang.transfer_request',
            "Reject request: {$transferRequest->request_number}", $transferRequest);

        return back()->with('success', "Request {$transferRequest->request_number} ditolak.");
    }
}
