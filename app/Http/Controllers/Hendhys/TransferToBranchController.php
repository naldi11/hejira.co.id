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
    ) {}

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

        $branchRequest = null;
        if ($request->has('request_id')) {
            $branchRequest = HendhysBranchRequest::with(['branch', 'details.product', 'details.unit'])
                ->where('status', 'pending')
                ->findOrFail($request->request_id);
        }

        return view('hendhys.transfer-to-branch.form', compact('branchRequest'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->branch->type !== 'pusat') {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'request_id' => 'required|exists:hendhys_branch_requests,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_id' => 'required|exists:master_units,id',
            'items.*.detail_id' => 'required|exists:hendhys_branch_request_details,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $br = HendhysBranchRequest::findOrFail($request->request_id);
                
                $transfer = HendhysTransferToBranch::create([
                    'transfer_number' => $this->numbers->generateYearly('TRF-HND', 'hendhys_transfer_to_branch', 'transfer_number'),
                    'request_id' => $br->id,
                    'branch_id' => $br->branch_id,
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

                        // 3. Update request detail quantity_approved
                        DB::table('hendhys_branch_request_details')
                            ->where('id', $item['detail_id'])
                            ->update(['quantity_approved' => $qtyApproved]);
                            
                        // 4. Debit dari stok Pusat (Credit ke cabang nanti saat receive)
                        $this->stockService->debitHendhys(
                            $item['product_id'],
                            $qtyApproved,
                            null, // Pusat
                            'transfer_to_branch',
                            $transfer->id,
                            auth()->id()
                        );
                    }

                    $reqDetail = DB::table('hendhys_branch_request_details')->where('id', $item['detail_id'])->first();
                    if ($qtyApproved < $reqDetail->quantity_requested) {
                        $hasPartial = true;
                    }
                }

                if ($allZero) {
                    $br->update(['status' => 'rejected', 'approved_by' => auth()->id()]);
                    // Delete the dummy transfer
                    $transfer->delete();
                    return;
                }

                $br->update([
                    'status' => $hasPartial ? 'partial' : 'completed',
                    'approved_by' => auth()->id()
                ]);
            });

            return redirect()->route('hendhys.branch-requests.show', $request->request_id)
                ->with('success', 'Transfer barang ke cabang berhasil dikirim. Menunggu penerimaan oleh cabang.');

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

    public function receive(Request $request, HendhysTransferToBranch $transferToBranch)
    {
        $user = auth()->user();
        if ($user->branch->type !== 'cabang' || $transferToBranch->branch_id !== $user->branch_id) {
            abort(403, 'Hanya cabang penerima yang dapat melakukan konfirmasi ini.');
        }

        if ($transferToBranch->status !== 'sent') {
            return back()->with('error', 'Transfer ini sudah diproses sebelumnya.');
        }

        try {
            DB::transaction(function () use ($transferToBranch, $user) {
                $transferToBranch->update([
                    'status' => 'received',
                    'received_by' => $user->id
                ]);

                // Credit stok cabang
                foreach ($transferToBranch->details as $detail) {
                    $this->stockService->creditHendhys(
                        $detail->product_id,
                        $detail->unit_id,
                        $detail->quantity,
                        $transferToBranch->branch_id,
                        'receive_from_pusat',
                        $transferToBranch->id,
                        $user->id
                    );
                }
            });

            return back()->with('success', 'Penerimaan barang berhasil dikonfirmasi. Stok cabang telah bertambah.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menerima barang: ' . $e->getMessage());
        }
    }
}
