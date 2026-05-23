<?php

namespace App\Http\Controllers\Hendhys;

use App\Http\Controllers\Controller;
use App\Models\HendhysBranchRequest;
use App\Models\HendhysStockPusat;
use App\Models\HendhysTransferToBranch;
use App\Models\HendhysTransferToBranchDetail;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $transfers = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('hendhys.transfer-to-branch.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Hanya Pusat yang dapat melakukan transfer barang ke cabang.');
        }

        if ($request->has('request_id')) {
            $branchRequest = HendhysBranchRequest::with(['branch', 'details.product', 'details.unit'])
                ->where('status', 'pending')
                ->findOrFail($request->request_id);
            return view('hendhys.transfer-to-branch.form', compact('branchRequest'));
        }

        // Fitur baru: Distribusi Manual tanpa request.
        $branches = \App\Models\Branch::where('type', 'cabang')->where('is_active', true)->orderBy('name')->get();
        // Hanya ambil produk yang ada stoknya di pusat
        $products = \App\Models\Hendhys\Product::where('status', 'active')
            ->join('hendhys_stock_pusat', 'master_products.id', '=', 'hendhys_stock_pusat.product_id')
            ->with('unit')
            ->select('master_products.*', 'hendhys_stock_pusat.quantity as current_stock')
            ->where('hendhys_stock_pusat.quantity', '>', 0)
            ->get();

        return view('hendhys.transfer-to-branch.manual-form', compact('branches', 'products'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->branch->type !== 'pusat') {
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

        $transferToBranch->load(['branch', 'branchRequest', 'creator', 'receiver', 'details.product', 'details.unit']);
        return view('hendhys.transfer-to-branch.show', compact('transferToBranch'));
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
        return view('hendhys.transfer-to-branch.receive', compact('transferToBranch'));
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
            'received_quantities'   => 'required|array|min:1',
            'received_quantities.*' => 'required|integer|min:1',
            'receive_notes'         => 'nullable|string|max:1000',
            'receive_photo'         => 'nullable|image|max:5120',
        ]);

        try {
            $photoPath = null;
            if ($request->hasFile('receive_photo')) {
                $photoPath = $request->file('receive_photo')->store('transfer-receipts', 'public');
            }

            DB::transaction(function () use ($request, $transferToBranch, $user, $photoPath) {
                $transferToBranch->load('details');

                foreach ($transferToBranch->details as $detail) {
                    $receivedQty = (float) ($request->received_quantities[$detail->id] ?? 0);
                    $receivedQty = min($receivedQty, (float) $detail->quantity);

                    $detail->update(['received_quantity' => $receivedQty]);

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
                    'status'        => 'received',
                    'received_by'   => $user->id,
                    'receive_notes' => $request->receive_notes,
                    'receive_photo' => $photoPath,
                ]);
            });

            return redirect()->route('hendhys.transfer-to-branch.show', $transferToBranch->id)
                ->with('success', 'Penerimaan barang berhasil dikonfirmasi. Stok cabang telah bertambah sesuai qty diterima.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses penerimaan: ' . $e->getMessage());
        }
    }
}
