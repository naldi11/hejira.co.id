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

        $isAdmin = $user->hasRole('admin_hendhys') || $user->hasRole('super_admin');

        if (!$isAdmin) {
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
            ->with(['branch', 'creator', 'receiver']);

        if (!$isAdmin) {
            $gudangQuery->where('branch_id', $user->branch_id);
        }

        if ($status = $request->status) {
            $gudangQuery->where('status', $status);
        }
        if ($search = $request->search) {
            $gudangQuery->where('transfer_number', 'like', "%$search%");
        }

        $gudangTransfers = $gudangQuery->orderBy('id', 'desc')->paginate(20, ['*'], 'gudang_page')->withQueryString()->appends(['tab' => 'gudang']);

        return Inertia::render('Hendhys/TransferToBranch/Index', [
            'transfers'       => HendhysTransferToBranchResource::collection($transfers),
            'gudangTransfers' => TransferOutResource::collection($gudangTransfers->getCollection()),
            'gudangPagination' => \App\Support\ListPage::metadata($gudangTransfers),
            'filters'         => $request->only('search', 'status', 'tab'),
            'isPusat'         => !$user->branch || $user->branch->type === 'pusat',
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

        if ($user->branch?->type === 'cabang' && $transferOut->branch_id !== $user->branch_id) {
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

        if ($user->branch?->type !== 'cabang' || $transferOut->branch_id !== $user->branch_id) {
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
        $document = TransferOut::findOrFail($id);
        abort_unless($document->to_entity === 'hendhys', 403, 'Akses ditolak.');
        $service = app(\App\Services\TransferReceiving::class);
        $service->authorize($document, $request->user());
        $data = $request->validate(\App\Services\TransferReceiving::rules());
        $service->receive($document, $data, $request->user());
        return redirect()->route('hendhys.gudang-transfers.show', $id)->with('success', 'Penerimaan dikonfirmasi. Barang rusak dipisahkan dan selisih tidak menambah stok asal.');
    }

    public function create(Request $request)
    {
        if (auth()->user()->branch && auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Hanya Pusat yang dapat melakukan transfer barang ke cabang.');
        }
        if (!auth()->user()->hasAnyRole(['admin_hendhys', 'super_admin', 'owner'])) {
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
                    'quantity_requested' => (int) $d->quantity_requested,
                    'unit_id'            => $d->unit_id,
                    'unit'               => $d->unit?->abbreviation ?? 'PCS',
                ]),
            ];

            return Inertia::render('Hendhys/TransferToBranch/Create', [
                'branchRequest' => $brData,
                'productionLots' => app(\App\Services\ProductionLots::class)->choices('hendhys'),
            ]);
        }

        // Fitur: Distribusi Manual tanpa request.
        $branches = \App\Models\Branch::where('type', 'cabang')
            ->whereIn('entity', ['hendhys', 'jihans'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
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
                'current_stock' => (int) $p->current_stock,
            ]);

        return Inertia::render('Hendhys/TransferToBranch/Create', [
            'branches' => $branches,
            'products' => $products,
            'productionLots' => app(\App\Services\ProductionLots::class)->choices('hendhys'),
        ]);
    }

    public function store(Request $request)
    {
        if (auth()->user()->branch && auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }
        if (!auth()->user()->hasAnyRole(['admin_hendhys', 'super_admin', 'owner'])) {
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
            'items.*.production_lot_id' => 'nullable|integer|exists:production_lots,id',
            'items.*.detail_id' => 'nullable|exists:hendhys_branch_request_details,id',
        ]);

        foreach ($request->items as $item) {
            if ((int) Product::findOrFail($item['product_id'])->unit_id !== (int) $item['unit_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Satuan harus sesuai dengan produk yang dipilih.']);
            }
        }

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
                    $qtyApproved = (int) $item['quantity'];

                    if ($qtyApproved > 0) {
                        $allZero = false;

                        // 1. Cek stok pusat
                        $stokPusat = HendhysStockPusat::where('product_id', $item['product_id'])->first();
                        if (!$stokPusat || $stokPusat->quantity < $qtyApproved) {
                            throw new \Exception("Stok pusat tidak mencukupi untuk produk ID: {$item['product_id']}");
                        }

                        // 2. Buat detail transfer
                        $transferDetail = HendhysTransferToBranchDetail::create([
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
                            auth()->id(),
                            $item['production_lot_id'] ?? null,
                            $transferDetail->id
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
        if ($user->branch?->type === 'cabang' && $transferToBranch->branch_id !== $user->branch_id) {
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

        if ($user->branch?->type !== 'cabang') {
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
        $service = app(\App\Services\TransferReceiving::class);
        $service->authorize($transferToBranch, $request->user());
        $data = $request->validate(\App\Services\TransferReceiving::rules());
        $service->receive($transferToBranch, $data, $request->user());
        return redirect()->route('hendhys.transfer-to-branch.show', $transferToBranch->id)->with('success', 'Penerimaan dikonfirmasi. Barang rusak dan selisih dicatat terpisah pada BAST.');
    }

    public function forceReceive(Request $request, HendhysTransferToBranch $transferToBranch)
    {
        app(\App\Services\TransferReceiving::class)->receive($transferToBranch, [], $request->user(), true);
        return redirect()->route('hendhys.transfer-to-branch.show', $transferToBranch->id)->with('success', 'Penerimaan dikonfirmasi oleh Pusat dan BAST tersimpan.');
    }

    public function printBast(HendhysTransferToBranch $transferToBranch)
    {
        // ⏭️ Print tetap Blade
        $user = auth()->user();
        if ($user->branch?->type === 'cabang' && $transferToBranch->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $transferToBranch->load(['branch', 'branchRequest', 'creator', 'receiver', 'details.product', 'details.unit', 'photos']);

        return view('hendhys.transfer-to-branch.print', compact('transferToBranch'));
    }
}
