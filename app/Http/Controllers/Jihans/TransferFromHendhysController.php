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
        if ($transferFromHendhy->branch?->entity !== 'jihans') {
            abort(403, 'Akses ditolak.');
        }

        $transferFromHendhy->load(['creator', 'receiver', 'details.product', 'details.unit', 'photos']);

        return Inertia::render('Jihans/TransferFromHendhys/Show', [
            'transfer' => new HendhysTransferToBranchResource($transferFromHendhy),
        ]);
    }

    public function showReceiveForm(HendhysTransferToBranch $transferFromHendhy)
    {
        $user = auth()->user();

        $transferFromHendhy->load('branch');
        if ($transferFromHendhy->branch?->entity !== 'jihans') {
            abort(403, 'Akses ditolak.');
        }
        // Cek entity saja tidak cukup: kalau ada lebih dari satu cabang ber-entity
        // 'jihans', user cabang A bisa membuka form terima milik cabang B.
        // Padanan Hendhys (TransferToBranchController::showReceiveForm) sudah cek ini.
        if ($user->branch_id && $transferFromHendhy->branch_id !== $user->branch_id) {
            abort(403, 'Transfer ini ditujukan untuk cabang lain.');
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
        abort_unless($transferFromHendhy->branch?->entity === 'jihans', 403, 'Akses ditolak.');
        $service = app(\App\Services\TransferReceiving::class);
        $service->authorize($transferFromHendhy, $request->user());
        $data = $request->validate(\App\Services\TransferReceiving::rules());
        $service->receive($transferFromHendhy, $data, $request->user());
        return redirect()->route('jihans.transfer-from-hendhys.show', $transferFromHendhy->id)->with('success', 'Penerimaan dikonfirmasi. Barang rusak dan selisih dicatat terpisah pada BAST.');
    }

    public function printBast(HendhysTransferToBranch $transferFromHendhy)
    {
        $transferFromHendhy->load('branch');
        if ($transferFromHendhy->branch?->entity !== 'jihans') {
            abort(403, 'Akses ditolak.');
        }

        $transferFromHendhy->load(['creator', 'receiver', 'details.product', 'details.unit', 'photos']);

        // We can reuse the Hendhys print view since it is the exact same structure
        return view('hendhys.transfer-to-branch.print', ['transferToBranch' => $transferFromHendhy]);
    }
}
