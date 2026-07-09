<?php

namespace App\Http\Controllers\Jihans;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysTransferToBranchResource;
use App\Models\HendhysReturnFromBranch;
use App\Models\HendhysReturnDetail;
use App\Models\HendhysTransferToBranch;
use App\Models\HendhysTransferToBranchPhoto;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransferFromHendhysController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {
    }

    public function index(Request $request)
    {
        $q = HendhysTransferToBranch::with(['branch', 'creator', 'receiver'])
            ->whereHas('branch', function ($query) {
                $query->where('entity', 'jihans');
            });

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('transfer_number', 'like', "%$search%");
        }

        $transfers = $q->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Jihans/TransferFromHendhys/Index', [
            'transfers'       => HendhysTransferToBranchResource::collection($transfers),
            'filters'         => $request->only('search', 'status'),
        ]);
    }

    public function show(HendhysTransferToBranch $transferFromHendhy)
    {
        $transferFromHendhy->load('branch');
        if ($transferFromHendhy->branch->entity !== 'jihans') {
            abort(403, 'Akses ditolak.');
        }

        $transferFromHendhy->load(['creator', 'receiver', 'details.product', 'details.unit', 'photos']);

        return Inertia::render('Jihans/TransferFromHendhys/Show', [
            'transfer' => new HendhysTransferToBranchResource($transferFromHendhy),
        ]);
    }

    public function showReceiveForm(HendhysTransferToBranch $transferFromHendhy)
    {
        $transferFromHendhy->load('branch');
        if ($transferFromHendhy->branch->entity !== 'jihans') {
            abort(403, 'Akses ditolak.');
        }
        if ($transferFromHendhy->status !== 'sent') {
            return redirect()->route('jihans.transfer-from-hendhys.show', $transferFromHendhy->id)
                ->with('error', 'Transfer ini sudah diproses sebelumnya.');
        }

        $transferFromHendhy->load(['creator', 'details.product', 'details.unit']);

        return Inertia::render('Jihans/TransferFromHendhys/Receive', [
            'transfer' => new HendhysTransferToBranchResource($transferFromHendhy),
        ]);
    }

    public function receive(Request $request, HendhysTransferToBranch $transferFromHendhy)
    {
        $user = auth()->user();

        $transferFromHendhy->load('branch');
        if ($transferFromHendhy->branch->entity !== 'jihans') {
            abort(403, 'Hanya cabang penerima yang dapat melakukan konfirmasi ini.');
        }
        if ($transferFromHendhy->status !== 'sent') {
            return back()->with('error', 'Transfer ini sudah diproses sebelumnya.');
        }

        $request->validate([
            'received_quantities'      => 'required|array|min:1',
            'received_quantities.*'    => 'required|numeric|min:0',
            'kondisi'                  => 'nullable|array',
            'kondisi.*'                => 'nullable|in:baik,rusak,kurang',
            'receive_notes'            => 'nullable|string|max:2000',
            'receive_kendala'          => 'nullable|string|max:2000',
            'receive_received_by_name' => 'nullable|string|max:255',
            'receive_pengirim_name'    => 'nullable|string|max:255',
            'photos'                   => 'nullable|array|max:10',
            'photos.*'                 => 'image|max:5120',
        ]);

        try {
            DB::transaction(function () use ($request, $transferFromHendhy, $user) {
                $transferFromHendhy->load('details');

                foreach ($transferFromHendhy->details as $detail) {
                    $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                    $receivedQty = min($receivedQty, (float) $detail->quantity);
                    $kondisi = $request->kondisi[$detail->id] ?? null;

                    $detail->update([
                        'received_quantity' => $receivedQty,
                        'kondisi'           => $kondisi,
                    ]);

                    if ($receivedQty > 0) {
                        $this->stockService->creditJihansRetail(
                            $detail->product_id,
                            $detail->unit_id,
                            $receivedQty,
                            'receive_from_hendhys',
                            $transferFromHendhy->id,
                            $user->id
                        );
                    }
                }

                $transferFromHendhy->update([
                    'status'                    => 'received',
                    'received_by'               => $user->id,
                    'receive_notes'             => $request->receive_notes,
                    'receive_kendala'           => $request->receive_kendala,
                    'receive_received_by_name'  => $request->receive_received_by_name,
                    'receive_pengirim_name'     => $request->receive_pengirim_name,
                ]);

                // AUTO-RETURN: Detect shortfall and create return document to Pusat
                $shortfallDetails = [];
                foreach ($transferFromHendhy->details as $detail) {
                    $sentQty     = (float) $detail->quantity;
                    $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                    $receivedQty = min($receivedQty, $sentQty);
                    $selisih     = round($sentQty - $receivedQty, 3);

                    if ($selisih > 0.001) {
                        $shortfallDetails[] = [
                            'product_id' => $detail->product_id,
                            'unit_id'    => $detail->unit_id,
                            'qty'        => $selisih,
                        ];
                        // Credit selisih kembali ke stok Pusat Hendhys
                        $this->stockService->creditHendhys(
                            $detail->product_id,
                            $detail->unit_id,
                            $selisih,
                            null, // null = Pusat Hendhys
                            'return_from_branch',
                            $transferFromHendhy->id,
                            $user->id
                        );
                    }
                }

                if (!empty($shortfallDetails)) {
                    $returnRecord = HendhysReturnFromBranch::create([
                        'return_number' => $this->numbers->generateYearly('RET-HND', 'hendhys_returns_from_branch', 'return_number'),
                        'branch_id'     => $transferFromHendhy->branch_id,
                        'date'          => now()->toDateString(),
                        'status'        => 'received',
                        'notes'         => "Retur otomatis selisih penerimaan dari transfer {$transferFromHendhy->transfer_number}",
                        'created_by'    => $user->id,
                        'received_by'   => $user->id,
                    ]);

                    foreach ($shortfallDetails as $item) {
                        HendhysReturnDetail::create([
                            'return_id'  => $returnRecord->id,
                            'product_id' => $item['product_id'],
                            'unit_id'    => $item['unit_id'],
                            'quantity'   => $item['qty'],
                            'condition'  => 'kurang',
                            'notes'      => 'Selisih otomatis dari penerimaan Jihans',
                        ]);
                    }
                }

                if ($request->hasFile('photos')) {
                    $dir = 'transfer-branch-receipts/' . $transferFromHendhy->transfer_number;
                    foreach ($request->file('photos') as $file) {
                        $path = $file->store($dir, 'public');
                        HendhysTransferToBranchPhoto::create([
                            'transfer_id' => $transferFromHendhy->id,
                            'path'        => $path,
                            'uploaded_by' => $user->id,
                            'created_at'  => now(),
                        ]);
                    }
                }
            });

            return redirect()->route('jihans.transfer-from-hendhys.show', $transferFromHendhy->id)
                ->with('success', 'Penerimaan barang dikonfirmasi. BAST berhasil dibuat.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses penerimaan: ' . $e->getMessage());
        }
    }

    public function printBast(HendhysTransferToBranch $transferFromHendhy)
    {
        $transferFromHendhy->load('branch');
        if ($transferFromHendhy->branch->entity !== 'jihans') {
            abort(403, 'Akses ditolak.');
        }

        $transferFromHendhy->load(['creator', 'receiver', 'details.product', 'details.unit', 'photos']);

        // We can reuse the Hendhys print view since it is the exact same structure
        return view('hendhys.transfer-to-branch.print', ['transferToBranch' => $transferFromHendhy]);
    }
}
