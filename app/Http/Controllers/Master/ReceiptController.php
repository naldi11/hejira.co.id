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
            'quantity_bagus'           => 'required|array|min:1',
            'quantity_bagus.*'         => 'required|numeric|min:0',
            'quantity_rusak'           => 'required|array|min:1',
            'quantity_rusak.*'         => 'required|numeric|min:0',
            'batch_number'             => 'nullable|array',
            'expired_date'             => 'nullable|array',
            'expired_date.*'           => 'nullable|date',
            'receive_notes'            => 'nullable|string|max:2000',
            'receive_kendala'          => 'nullable|string|max:2000',
            'receive_received_by_name' => 'nullable|string|max:255',
            'receive_pengirim_name'    => 'nullable|string|max:255',
            'photos'                   => 'nullable|array|max:10',
            'photos.*'                 => 'image|max:5120',
        ]);

        // Validate sum of good + damaged equals shipped quantity
        foreach ($transferOut->details as $detail) {
            $qtyBagus = (float) ($request->quantity_bagus[$detail->id] ?? 0);
            $qtyRusak = (float) ($request->quantity_rusak[$detail->id] ?? 0);
            $qtySent  = (float) $detail->quantity;
            if (abs(($qtyBagus + $qtyRusak) - $qtySent) > 0.001) {
                return back()->withInput()->withErrors([
                    'received_quantities' => "Jumlah Bagus + Rusak untuk produk {$detail->product->name} harus sama dengan Qty Kirim ({$qtySent})."
                ]);
            }
        }

        try {
            DB::transaction(function () use ($request, $transferOut, $info) {
                $receiptConfirmation = \App\Models\ReceiptConfirmation::create([
                    'receiptable_type' => TransferOut::class,
                    'receiptable_id'   => $transferOut->id,
                    'received_by'      => auth()->id(),
                    'received_at'      => now(),
                    'status'           => 'completed',
                    'general_notes'    => $request->receive_notes . ($request->receive_kendala ? " | Kendala: " . $request->receive_kendala : ""),
                ]);

                foreach ($transferOut->details as $detail) {
                    $qtyBagus = (float) ($request->quantity_bagus[$detail->id] ?? 0);
                    $qtyRusak = (float) ($request->quantity_rusak[$detail->id] ?? 0);
                    $qtySent  = (float) $detail->quantity;

                    // Update legacy detail: received_quantity = good items, kondisi = 'baik'
                    // (Only good items go to stock)
                    $legacyKondisi = 'baik';
                    if ($qtyBagus <= 0 && $qtyRusak > 0) {
                        $legacyKondisi = 'rusak';
                    } elseif ($qtyBagus + $qtyRusak < $qtySent) {
                        $legacyKondisi = 'kurang';
                    }

                    $detail->update([
                        'received_quantity' => $qtyBagus,
                        'kondisi'           => $legacyKondisi,
                    ]);

                    // Insert to Unified BAST details
                    // 1. Good items
                    if ($qtyBagus > 0) {
                        $receiptConfirmation->details()->create([
                            'product_id'   => $detail->product_id,
                            'expected_qty' => $qtySent,
                            'actual_qty'   => $qtyBagus,
                            'condition'    => 'baik',
                            'expired_date' => $request->expired_date[$detail->id] ?? null,
                            'batch_number' => $request->batch_number[$detail->id] ?? null,
                            'notes'        => null,
                        ]);
                    }

                    // 2. Damaged items
                    if ($qtyRusak > 0) {
                        $receiptConfirmation->details()->create([
                            'product_id'   => $detail->product_id,
                            'expected_qty' => $qtySent,
                            'actual_qty'   => $qtyRusak,
                            'condition'    => 'rusak',
                            'expired_date' => $request->expired_date[$detail->id] ?? null,
                            'batch_number' => $request->batch_number[$detail->id] ?? null,
                            'notes'        => 'Barang rusak saat pengiriman',
                        ]);
                    }

                    // 3. Missing items
                    $qtyKurang = $qtySent - ($qtyBagus + $qtyRusak);
                    if ($qtyKurang > 0.001) {
                        $receiptConfirmation->details()->create([
                            'product_id'   => $detail->product_id,
                            'expected_qty' => $qtySent,
                            'actual_qty'   => $qtyKurang,
                            'condition'    => 'kurang',
                            'expired_date' => null,
                            'batch_number' => null,
                            'notes'        => 'Kurang/Hilang saat pengiriman',
                        ]);
                    }
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
                        // Legacy photo
                        TransferOutPhoto::create([
                            'transfer_id' => $transferOut->id,
                            'path'        => $path,
                            'uploaded_by' => auth()->id(),
                            'created_at'  => now(),
                        ]);
                        // Unified BAST photo
                        $receiptConfirmation->photos()->create([
                            'photo_path' => $path,
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
