<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\TransferOut;
use App\Models\TransferOutPhoto;
use App\Services\ActivityLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

        app(\App\Services\TransferReceiving::class)->authorize($transferOut, $request->user());
        if ($transferOut->to_entity !== $info['scope']) {
            abort(403, 'Anda tidak memiliki akses untuk menerima pengiriman ini.');
        }

        if ($transferOut->status !== 'sent') {
            return redirect()->route($info['transferRoute'] . 'index')
                ->with('info', 'Transfer ini sudah dikonfirmasi sebelumnya.');
        }

        return Inertia::render('Master/Receipt/Receive', [
            'transferOut'   => $transferOut,
            'routePrefix'   => $info['route'],
            'currentScope'  => $info['scope'],
            'info'          => $info,
        ]);
    }

    public function receive(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $document = TransferOut::findOrFail($id);
        abort_unless($document->to_entity === $info['scope'], 403, 'Akses ditolak.');
        $service = app(\App\Services\TransferReceiving::class);
        $service->authorize($document, $request->user());
        $data = $request->validate(\App\Services\TransferReceiving::rules(true));
        $service->receive($document, $data, $request->user());
        return redirect()->route($info['transferRoute'].'index')->with('success', 'Penerimaan dikonfirmasi. Hanya barang baik menambah stok jual; barang rusak dan selisih dicatat pada BAST.');
    }

    public function print(Request $request, $id)
    {
        $info = $this->getScopeInfo($request);
        $transferOut = TransferOut::with(['details.product', 'details.unit', 'branch', 'creator', 'receiver', 'photos'])
            ->findOrFail($id);

        app(\App\Services\TransferReceiving::class)->authorize($transferOut, $request->user());
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
            // 'layout' dihapus: key ini tidak pernah dibaca blade manapun, dan
            // nilainya ('layouts.hendhys') menunjuk file yang sudah tidak ada.
            return [
                'scope'          => 'hendhys',
                'route'          => 'hendhys.',
                'transferRoute'  => 'hendhys.transfer-requests.',
                'printRoute'     => 'hendhys.transfer-requests.print-gudang',
                'receiveRoute'   => 'hendhys.transfer-requests.receive-gudang',
                'entity'         => "Hendhys Brownies",
            ];
        }
        return [
            'scope'          => 'jihans',
            'route'          => 'jihans.',
            'transferRoute'  => 'jihans.transfer-requests.',
            'printRoute'     => 'jihans.transfer-requests.print',
            'receiveRoute'   => 'jihans.transfer-requests.receive',
            'entity'         => "Jihan's Food",
        ];
    }
}
