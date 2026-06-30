<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hendhys\HendhysTransferToBranchResource;
use App\Models\HendhysBranchRequest;
use App\Models\HendhysReturnFromBranch;
use App\Models\HendhysReturnDetail;
use App\Models\HendhysStockPusat;
use App\Models\HendhysTransferToBranch;
use App\Models\HendhysTransferToBranchDetail;
use App\Models\HendhysTransferToBranchPhoto;
use App\Models\Product;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use App\Models\TransferOut;
use App\Http\Resources\Gudang\TransferOutResource;

class TransferToBranchController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stockService
    ) {
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $q = HendhysTransferToBranch::with(['branch', 'branchRequest', 'creator', 'receiver']);

        if ($user->branch->type === 'cabang') {
            $q->where('branch_id', $user->branch_id);
        }

        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('transfer_number', 'like', "%$search%");
        }

        $transfers = $q->orderBy('id', 'desc')->paginate(20)->withQueryString();

        // Fetch direct Gudang transfers sent to this branch/entity
        $gudangQuery = TransferOut::where('to_entity', 'hendhys')
            ->with(['branch', 'creator', 'receiver', 'details.product', 'details.unit']);

        if ($user->branch->type === 'cabang') {
            $gudangQuery->where('branch_id', $user->branch_id);
        }

        if ($status = $request->status) {
            $gudangQuery->where('status', $status);
        }
        if ($search = $request->search) {
            $gudangQuery->where('transfer_number', 'like', "%$search%");
        }

        $gudangTransfers = $gudangQuery->orderBy('id', 'desc')->get();

        return Inertia::render('Hendhys/TransferToBranch/Index', [
            'transfers'       => HendhysTransferToBranchResource::collection($transfers),
            'gudangTransfers' => TransferOutResource::collection($gudangTransfers),
            'filters'         => $request->only('search', 'status'),
            'isPusat'         => $user->branch->type === 'pusat',
        ]);
    }

    public function showGudangTransfer(Request $request, $id)
    {
        $user = auth()->user();
        $transferOut = TransferOut::with(['branch', 'creator', 'receiver', 'details.product', 'details.unit'])
            ->findOrFail($id);

        if ($transferOut->to_entity !== 'hendhys') {
            abort(403, 'Akses ditolak.');
        }

        if ($user->branch->type === 'cabang' && $transferOut->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        return Inertia::render('Hendhys/TransferToBranch/ShowGudang', [
            'transfer' => new TransferOutResource($transferOut),
        ]);
    }

    public function showGudangReceiveForm(Request $request, $id)
    {
        $user = auth()->user();
        $transferOut = TransferOut::with(['branch', 'creator', 'details.product', 'details.unit'])
            ->findOrFail($id);

        if ($transferOut->to_entity !== 'hendhys') {
            abort(403, 'Akses ditolak.');
        }

        if ($user->branch->type !== 'cabang' || $transferOut->branch_id !== $user->branch_id) {
            abort(403, 'Hanya cabang penerima yang dapat melakukan konfirmasi ini.');
        }

        if ($transferOut->status !== 'sent') {
            return redirect()->route('hendhys.gudang-transfers.show', $transferOut->id)
                ->with('error', 'Transfer ini sudah diproses sebelumnya.');
        }

        return Inertia::render('Hendhys/TransferToBranch/ReceiveGudang', [
            'transfer' => new TransferOutResource($transferOut),
        ]);
    }

    public function receiveGudangTransfer(Request $request, $id)
    {
        $user = auth()->user();
        $transferOut = TransferOut::with(['branch', 'creator', 'details.product', 'details.unit'])
            ->findOrFail($id);

        if ($transferOut->to_entity !== 'hendhys') {
            abort(403, 'Akses ditolak.');
        }

        if ($user->branch->type !== 'cabang' || $transferOut->branch_id !== $user->branch_id) {
            abort(403, 'Hanya cabang penerima yang dapat melakukan konfirmasi ini.');
        }

        if ($transferOut->status !== 'sent') {
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
        ]);

        try {
            DB::transaction(function () use ($request, $transferOut, $user) {
                foreach ($transferOut->details as $detail) {
                    $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                    $receivedQty = min($receivedQty, (float) $detail->quantity);
                    $kondisi = $request->kondisi[$detail->id] ?? null;

                    $detail->update([
                        'received_quantity' => $receivedQty,
                        'kondisi'           => $kondisi,
                    ]);

                    if ($receivedQty > 0) {
                        $this->stockService->creditHendhys(
                            $detail->product_id,
                            $detail->unit_id,
                            $receivedQty,
                            $transferOut->branch_id,
                            'transfer_gudang',
                            $transferOut->id,
                            $user->id
                        );
                    }
                }

                $transferOut->update([
                    'status'                    => 'received',
                    'received_by'               => $user->id,
                    'receive_notes'             => $request->receive_notes,
                    'receive_kendala'           => $request->receive_kendala,
                    'receive_received_by_name'  => $request->receive_received_by_name,
                    'receive_pengirim_name'     => $request->receive_pengirim_name,
                    'received_at'               => now(),
                ]);

                // Create ReceiptConfirmation (Unified BAST) automatically
                $receiptConfirmation = \App\Models\ReceiptConfirmation::create([
                    'receiptable_type' => TransferOut::class,
                    'receiptable_id'   => $transferOut->id,
                    'received_by'      => $user->id,
                    'received_at'      => now(),
                    'status'           => 'completed',
                    'general_notes'    => $request->receive_notes,
                ]);

                foreach ($transferOut->details as $detail) {
                    $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                    $receivedQty = min($receivedQty, (float) $detail->quantity);
                    $kondisi = $request->kondisi[$detail->id] ?? 'baik';

                    $receiptConfirmation->details()->create([
                        'product_id'   => $detail->product_id,
                        'expected_qty' => $detail->quantity,
                        'actual_qty'   => $receivedQty,
                        'condition'    => $kondisi,
                        'expired_date' => null,
                        'batch_number' => null,
                        'notes'        => null,
                    ]);
                }

                // Mark Transfer Request as completed if exists
                if ($transferOut->request) {
                    $transferOut->request->update(['status' => 'completed']);
                }

                // AUTO-RETURN: Credit shortfall back to Gudang stock
                $shortfallItems = [];
                foreach ($transferOut->details as $detail) {
                    $sentQty     = (float) $detail->quantity;
                    $receivedQty = (float) $detail->received_quantity;
                    $selisih     = round($sentQty - $receivedQty, 3);

                    if ($selisih > 0.001) {
                        $shortfallItems[] = [
                            'product_id' => $detail->product_id,
                            'unit_id'    => $detail->unit_id,
                            'qty'        => $selisih,
                            'product'    => $detail->product?->name ?? '?',
                        ];
                        // Credit selisih kembali ke stok Gudang
                        $this->stockService->creditGudang(
                            $detail->product_id,
                            $detail->unit_id,
                            $selisih,
                            'return_discrepancy',
                            $transferOut->id,
                            $user->id,
                            "Selisih penerimaan dari {$transferOut->transfer_number}"
                        );
                    }
                }
            });

            $successMsg = 'Penerimaan barang dikonfirmasi. BAST berhasil dibuat.';

            return redirect()->route('hendhys.gudang-transfers.show', $transferOut->id)
                ->with('success', $successMsg);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses penerimaan: ' . $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Hanya Pusat yang dapat melakukan transfer barang ke cabang.');
        }
        if (!auth()->user()->hasAnyRole(['admin_hendhys', 'super_admin_hendhys', 'owner'])) {
            abort(403, 'Akses ditolak.');
        }

        if ($request->has('request_id')) {
            $branchRequest = HendhysBranchRequest::with(['branch', 'details.product', 'details.unit'])
                ->where('status', 'pending')
                ->findOrFail($request->request_id);

            $brData = [
                'id'             => $branchRequest->id,
                'request_number' => $branchRequest->request_number,
                'branch'         => $branchRequest->branch?->name,
                'branch_id'      => $branchRequest->branch_id,
                'details'        => $branchRequest->details->map(fn ($d) => [
                    'id'                 => $d->id,
                    'product_id'         => $d->product_id,
                    'product'            => $d->product?->name ?? '-',
                    'quantity_requested' => (float) $d->quantity_requested,
                    'unit_id'            => $d->unit_id,
                    'unit'               => $d->unit?->abbreviation ?? 'PCS',
                ]),
            ];

            return Inertia::render('Hendhys/TransferToBranch/Create', [
                'branchRequest' => $brData,
            ]);
        }

        // Fitur: Distribusi Manual tanpa request.
        $branches = \App\Models\Branch::where('type', 'cabang')->where('is_active', true)->orderBy('name')->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]);

        $products = Product::where('status', 'active')
            ->visibleInHendhys()
            ->join('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
            ->with('unit')
            ->select('master_products.*', 'hendhys_stock_pusat.quantity as current_stock')
            ->where('hendhys_stock_pusat.quantity', '>', 0)
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'code'          => $p->code,
                'unit_id'       => $p->unit_id,
                'unit'          => $p->unit?->abbreviation ?? 'PCS',
                'current_stock' => (float) $p->current_stock,
            ]);

        return Inertia::render('Hendhys/TransferToBranch/Create', [
            'branches' => $branches,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }
        if (!auth()->user()->hasAnyRole(['admin_hendhys', 'super_admin_hendhys', 'owner'])) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'request_id' => 'nullable|exists:hendhys_branch_requests,id',
            'branch_id' => 'required_without:request_id|exists:master_branches,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_id' => 'required|exists:master_units,id',
            'items.*.detail_id' => 'nullable|exists:hendhys_branch_request_details,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // Determine Branch ID
                $branchId = $request->branch_id;
                $br = null;

                if ($request->filled('request_id')) {
                    $br = HendhysBranchRequest::findOrFail($request->request_id);
                    $branchId = $br->branch_id;
                }

                $transfer = HendhysTransferToBranch::create([
                    'transfer_number' => $this->numbers->generateYearly('TRF-HND', 'hendhys_transfer_to_branch', 'transfer_number'),
                    'request_id' => $br ? $br->id : null,
                    'branch_id' => $branchId,
                    'date' => $request->date,
                    'status' => 'sent',
                    'notes' => $request->notes,
                    'created_by' => auth()->id()
                ]);

                $allZero = true;
                $hasPartial = false;

                foreach ($request->items as $item) {
                    $qtyApproved = (float) $item['quantity'];

                    if ($qtyApproved > 0) {
                        $allZero = false;

                        // 1. Cek stok pusat
                        $stokPusat = HendhysStockPusat::where('product_id', $item['product_id'])->first();
                        if (!$stokPusat || $stokPusat->quantity < $qtyApproved) {
                            throw new \Exception("Stok pusat tidak mencukupi untuk produk ID: {$item['product_id']}");
                        }

                        // 2. Buat detail transfer
                        HendhysTransferToBranchDetail::create([
                            'transfer_id' => $transfer->id,
                            'product_id' => $item['product_id'],
                            'quantity' => $qtyApproved,
                            'unit_id' => $item['unit_id']
                        ]);

                        // 3. Update request detail quantity_approved jika terkait request
                        if ($br && !empty($item['detail_id'])) {
                            DB::table('hendhys_branch_request_details')
                                ->where('id', $item['detail_id'])
                                ->update(['quantity_approved' => $qtyApproved]);

                            $reqDetail = DB::table('hendhys_branch_request_details')->where('id', $item['detail_id'])->first();
                            if ($qtyApproved < $reqDetail->quantity_requested) {
                                $hasPartial = true;
                            }
                        }

                        // 4. Debit dari stok Pusat
                        $this->stockService->debitHendhys(
                            $item['product_id'],
                            $qtyApproved,
                            null, // Pusat
                            'transfer_to_branch',
                            $transfer->id,
                            auth()->id()
                        );
                    }
                }

                if ($allZero) {
                    if ($br) {
                        $br->update(['status' => 'rejected', 'approved_by' => auth()->id()]);
                    }
                    $transfer->delete();
                    return;
                }

                if ($br) {
                    $br->update([
                        'status' => $hasPartial ? 'partial' : 'completed',
                        'approved_by' => auth()->id()
                    ]);
                }
            });

            if ($request->filled('request_id')) {
                return redirect()->route('hendhys.branch-requests.show', $request->request_id)
                    ->with('success', 'Transfer barang ke cabang berhasil dikirim.');
            }

            return redirect()->route('hendhys.transfer-to-branch.index')
                ->with('success', 'Distribusi manual berhasil dibuat dan dikirim ke cabang.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses transfer: ' . $e->getMessage());
        }
    }

    public function show(HendhysTransferToBranch $transferToBranch)
    {
        $user = auth()->user();
        if ($user->branch->type === 'cabang' && $transferToBranch->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $transferToBranch->load(['branch', 'branchRequest', 'creator', 'receiver', 'details.product', 'details.unit', 'photos']);

        return Inertia::render('Hendhys/TransferToBranch/Show', [
            'transfer' => new HendhysTransferToBranchResource($transferToBranch),
        ]);
    }

    public function showReceiveForm(HendhysTransferToBranch $transferToBranch)
    {
        $user = auth()->user();

        if ($user->branch->type !== 'cabang') {
            abort(403, 'Hanya cabang yang dapat mengakses halaman ini.');
        }
        if ($transferToBranch->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }
        if ($transferToBranch->status !== 'sent') {
            return redirect()->route('hendhys.transfer-to-branch.show', $transferToBranch->id)
                ->with('error', 'Transfer ini sudah diproses sebelumnya.');
        }

        $transferToBranch->load(['branch', 'branchRequest', 'creator', 'details.product', 'details.unit']);

        return Inertia::render('Hendhys/TransferToBranch/Receive', [
            'transfer' => new HendhysTransferToBranchResource($transferToBranch),
        ]);
    }

    public function receive(Request $request, HendhysTransferToBranch $transferToBranch)
    {
        $user = auth()->user();

        if ($user->branch->type !== 'cabang' || $transferToBranch->branch_id !== $user->branch_id) {
            abort(403, 'Hanya cabang penerima yang dapat melakukan konfirmasi ini.');
        }
        if ($transferToBranch->status !== 'sent') {
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
            DB::transaction(function () use ($request, $transferToBranch, $user) {
                $transferToBranch->load('details');

                foreach ($transferToBranch->details as $detail) {
                    $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                    $receivedQty = min($receivedQty, (float) $detail->quantity);
                    $kondisi = $request->kondisi[$detail->id] ?? null;

                    $detail->update([
                        'received_quantity' => $receivedQty,
                        'kondisi'           => $kondisi,
                    ]);

                    if ($receivedQty > 0) {
                        $this->stockService->creditHendhys(
                            $detail->product_id,
                            $detail->unit_id,
                            $receivedQty,
                            $transferToBranch->branch_id,
                            'receive_from_pusat',
                            $transferToBranch->id,
                            $user->id
                        );
                    }
                }

                $transferToBranch->update([
                    'status'                    => 'received',
                    'received_by'               => $user->id,
                    'receive_notes'             => $request->receive_notes,
                    'receive_kendala'           => $request->receive_kendala,
                    'receive_received_by_name'  => $request->receive_received_by_name,
                    'receive_pengirim_name'     => $request->receive_pengirim_name,
                ]);

                // AUTO-RETURN: Detect shortfall and create return document to Pusat
                $shortfallDetails = [];
                foreach ($transferToBranch->details as $detail) {
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
                        // Credit selisih kembali ke stok Pusat
                        $this->stockService->creditHendhys(
                            $detail->product_id,
                            $detail->unit_id,
                            $selisih,
                            null, // null = Pusat
                            'return_discrepancy',
                            $transferToBranch->id,
                            $user->id
                        );
                    }
                }

                if (!empty($shortfallDetails)) {
                    $returnRecord = HendhysReturnFromBranch::create([
                        'return_number' => $this->numbers->generateYearly('RET-HND', 'hendhys_returns_from_branch', 'return_number'),
                        'branch_id'     => $transferToBranch->branch_id,
                        'date'          => now()->toDateString(),
                        'status'        => 'received',
                        'notes'         => "Retur otomatis selisih penerimaan dari transfer {$transferToBranch->transfer_number}",
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
                            'notes'      => 'Selisih otomatis dari penerimaan',
                        ]);
                    }
                }

                if ($request->hasFile('photos')) {
                    $dir = 'transfer-branch-receipts/' . $transferToBranch->transfer_number;
                    foreach ($request->file('photos') as $file) {
                        $path = $file->store($dir, 'public');
                        HendhysTransferToBranchPhoto::create([
                            'transfer_id' => $transferToBranch->id,
                            'path'        => $path,
                            'uploaded_by' => $user->id,
                            'created_at'  => now(),
                        ]);
                    }
                }
            });

            return redirect()->route('hendhys.transfer-to-branch.show', $transferToBranch->id)
                ->with('success', 'Penerimaan barang dikonfirmasi. BAST berhasil dibuat.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses penerimaan: ' . $e->getMessage());
        }
    }

    /**
     * Pusat mengkonfirmasi penerimaan atas nama cabang
     * (digunakan ketika cabang belum bisa konfirmasi sendiri)
     */
    public function forceReceive(Request $request, HendhysTransferToBranch $transferToBranch)
    {
        $user = auth()->user();

        if ($user->branch->type !== 'pusat') {
            abort(403, 'Hanya Pusat yang dapat melakukan konfirmasi paksa.');
        }

        if ($transferToBranch->status !== 'sent') {
            return back()->with('error', 'Transfer ini sudah diproses sebelumnya.');
        }

        try {
            DB::transaction(function () use ($transferToBranch, $user) {
                $transferToBranch->load('details');

                foreach ($transferToBranch->details as $detail) {
                    $qty = (float) $detail->quantity;

                    $detail->update([
                        'received_quantity' => $qty,
                        'kondisi'           => 'baik',
                    ]);

                    if ($qty > 0) {
                        $this->stockService->creditHendhys(
                            $detail->product_id,
                            $detail->unit_id,
                            $qty,
                            $transferToBranch->branch_id,
                            'receive_from_pusat',
                            $transferToBranch->id,
                            $user->id
                        );
                    }
                }

                $transferToBranch->update([
                    'status'       => 'received',
                    'received_by'  => $user->id,
                    'receive_notes' => 'Dikonfirmasi oleh Pusat atas nama Cabang.',
                    'receive_received_by_name' => $user->name,
                ]);
            });

            return redirect()->route('hendhys.transfer-to-branch.show', $transferToBranch->id)
                ->with('success', 'Penerimaan dikonfirmasi. Stok cabang berhasil diperbarui.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function printBast(HendhysTransferToBranch $transferToBranch)
    {
        // ⏭️ Print tetap Blade
        $user = auth()->user();
        if ($user->branch->type === 'cabang' && $transferToBranch->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $transferToBranch->load(['branch', 'branchRequest', 'creator', 'receiver', 'details.product', 'details.unit', 'photos']);

        return view('hendhys.transfer-to-branch.print', compact('transferToBranch'));
    }
}
