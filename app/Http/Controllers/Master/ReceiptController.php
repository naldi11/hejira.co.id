<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\TransferOut;
use App\Services\ActivityLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    use ScopesMasterData;

    public function __construct(
        private StockService $stockService,
        private ActivityLogService $logger
    ) {}

    public function showReceiveForm(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $transferOut = TransferOut::with(['details.product', 'details.unit', 'branch'])->findOrFail($id);

        // Security check: Only the target entity can receive
        if ($transferOut->to_entity !== $info['scope']) {
            abort(403, 'Anda tidak memiliki akses untuk menerima pengiriman ini.');
        }

        if ($transferOut->status !== 'sent') {
            return redirect()->route($info['route'] . 'transfer-requests.index')
                ->with('error', 'Pengiriman ini sudah diproses sebelumnya.');
        }

        return view('master.receipt.receive', [
            'transferOut' => $transferOut,
            'layout' => $info['layout'],
            'routePrefix' => $info['route'],
            'currentScope' => $info['scope']
        ]);
    }

    public function receive(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $transferOut = TransferOut::findOrFail($id);

        if ($transferOut->to_entity !== $info['scope']) {
            abort(403, 'Akses ditolak.');
        }
        if ($transferOut->status !== 'sent') {
            return back()->with('error', 'Pengiriman ini sudah diproses sebelumnya.');
        }

        $request->validate([
            'received_quantities'   => 'required|array|min:1',
            'received_quantities.*' => 'required|integer|min:1',
            'receive_notes'         => 'nullable|string|max:1000',
            'receive_photo'         => 'nullable|image|max:5120',
        ]);

        try {
            $photoPath = null;
            if ($request->hasFile('receive_photo')) {
                $photoPath = $request->file('receive_photo')->store('gudang-receipts', 'public');
            }

            DB::transaction(function () use ($request, $transferOut, $info, $photoPath) {
                $transferOut->load('details');

                foreach ($transferOut->details as $detail) {
                    $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                    $receivedQty = min($receivedQty, (float) $detail->quantity);

                    $detail->update(['received_quantity' => $receivedQty]);

                    if ($receivedQty > 0) {
                        // Credit to Jihans or Hendhys Pusat
                        if ($info['scope'] === 'jihans') {
                            $this->stockService->creditJihans(
                                $detail->product_id,
                                $detail->unit_id,
                                $receivedQty,
                                'receive_from_gudang',
                                $transferOut->id,
                                auth()->id()
                            );
                        } elseif ($info['scope'] === 'hendhys') {
                            $this->stockService->creditHendhys(
                                $detail->product_id,
                                $detail->unit_id,
                                $receivedQty,
                                null, // Pusat
                                'receive_from_gudang',
                                $transferOut->id,
                                auth()->id()
                            );
                        }
                    }
                }

                $transferOut->update([
                    'status'        => 'received',
                    'received_by'   => auth()->id(),
                    'receive_notes' => $request->receive_notes,
                    'receive_photo' => $photoPath,
                ]);

                // Update original request status if exists
                if ($transferOut->request) {
                    $transferOut->request->update(['status' => 'completed']);
                }

                $this->logger->log('receive', "master.receipt.{$info['scope']}", "Terima barang dari Gudang: {$transferOut->transfer_number}", $transferOut);
            });

            return redirect()->route($info['route'] . 'transfer-requests.index')
                ->with('success', 'Penerimaan barang berhasil dikonfirmasi. Stok telah bertambah sesuai qty diterima.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses penerimaan: ' . $e->getMessage());
        }
    }
}
