<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\TransferOut;
use App\Models\TransferOutPhoto;
use App\Services\ActivityLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function __construct(
        private ActivityLogService $logger,
        private StockService $stock
    ) {}

    public function showReceiveForm(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $transferOut = TransferOut::with(['details.product', 'details.unit', 'branch', 'creator'])->findOrFail($id);

        if ($transferOut->to_entity !== $info['scope']) {
            abort(403, 'Anda tidak memiliki akses untuk menerima pengiriman ini.');
        }

        if ($transferOut->status !== 'sent') {
            return redirect()->route($info['transferRoute'] . 'index')
                ->with('info', 'Transfer ini sudah dikonfirmasi sebelumnya.');
        }

        return view('master.receipt.receive', [
            'transferOut'   => $transferOut,
            'layout'        => $info['layout'],
            'routePrefix'   => $info['route'],
            'currentScope'  => $info['scope'],
            'info'          => $info,
        ]);
    }

    public function receive(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $transferOut = TransferOut::with('details')->findOrFail($id);

        if ($transferOut->to_entity !== $info['scope']) {
            abort(403, 'Akses ditolak.');
        }
        if ($transferOut->status !== 'sent') {
            return back()->with('error', 'Transfer ini sudah dikonfirmasi sebelumnya.');
        }

        $request->validate([
            'received_quantities'    => 'required|array|min:1',
            'received_quantities.*'  => 'required|numeric|min:0',
            'kondisi'                => 'nullable|array',
            'kondisi.*'              => 'nullable|in:baik,rusak,kurang',
            'receive_notes'          => 'nullable|string|max:2000',
            'receive_kendala'        => 'nullable|string|max:2000',
            'receive_received_by_name' => 'nullable|string|max:255',
            'receive_pengirim_name'  => 'nullable|string|max:255',
            'photos'                 => 'nullable|array|max:10',
            'photos.*'               => 'image|max:5120',
        ]);

        try {
            DB::transaction(function () use ($request, $transferOut, $info) {
                foreach ($transferOut->details as $detail) {
                    $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                    $receivedQty = min($receivedQty, (float) $detail->quantity);
                    $kondisi = $request->kondisi[$detail->id] ?? null;

                    $detail->update([
                        'received_quantity' => $receivedQty,
                        'kondisi'           => $kondisi,
                    ]);
                }

                $this->stock->processTransferReceive($transferOut, auth()->id());

                $transferOut->update([
                    'status'                    => 'received',
                    'received_by'               => auth()->id(),
                    'receive_notes'             => $request->receive_notes,
                    'receive_kendala'           => $request->receive_kendala,
                    'receive_received_by_name'  => $request->receive_received_by_name,
                    'receive_pengirim_name'     => $request->receive_pengirim_name,
                    'received_at'               => now(),
                ]);

                if ($request->hasFile('photos')) {
                    $dir = 'transfer-receipts/' . $transferOut->transfer_number;
                    foreach ($request->file('photos') as $file) {
                        $path = $file->store($dir, 'public');
                        TransferOutPhoto::create([
                            'transfer_id' => $transferOut->id,
                            'path'        => $path,
                            'uploaded_by' => auth()->id(),
                            'created_at'  => now(),
                        ]);
                    }
                }

                if ($transferOut->request) {
                    $transferOut->request->update(['status' => 'completed']);
                }

                $this->logger->log('receive', "master.receipt.{$info['scope']}", "Konfirmasi BAST: {$transferOut->transfer_number}", $transferOut);
            });

            return redirect()->route($info['transferRoute'] . 'index')
                ->with('success', 'Penerimaan barang dikonfirmasi. BAST berhasil dibuat.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses penerimaan: ' . $e->getMessage());
        }
    }

    public function print(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $transferOut = TransferOut::with(['details.product', 'details.unit', 'branch', 'creator', 'receiver', 'photos'])
            ->findOrFail($id);

        if ($transferOut->to_entity !== $info['scope']) {
            abort(403, 'Akses ditolak.');
        }

        return view('master.receipt.print', [
            'transferOut'  => $transferOut,
            'currentScope' => $info['scope'],
            'info'         => $info,
        ]);
    }

    protected function getScopeInfo(Request $request): array
    {
        $prefix = $request->route()->getPrefix() ?? '';
        if (str_contains($prefix, 'hendhys')) {
            return [
                'scope'          => 'hendhys',
                'layout'         => 'layouts.hendhys',
                'route'          => 'hendhys.',
                'transferRoute'  => 'hendhys.transfer-requests.',
                'printRoute'     => 'hendhys.transfer-requests.print-gudang',
                'receiveRoute'   => 'hendhys.transfer-requests.receive-gudang',
                'entity'         => "Hendhys Brownies",
            ];
        }
        return [
            'scope'          => 'jihans',
            'layout'         => 'layouts.jihans',
            'route'          => 'jihans.',
            'transferRoute'  => 'jihans.transfer-requests.',
            'printRoute'     => 'jihans.transfer-requests.print',
            'receiveRoute'   => 'jihans.transfer-requests.receive',
            'entity'         => "Jihan's Food",
        ];
    }
}
