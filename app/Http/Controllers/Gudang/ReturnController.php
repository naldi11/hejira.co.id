<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\GudangReturn;
use App\Models\GudangReturnDetail;
use App\Services\ActivityLogService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function __construct(
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = GudangReturn::with(['branch', 'creator', 'receiver']);

        if ($entity = $request->entity) {
            $q->where('from_entity', $entity);
        }
        if ($status = $request->status) {
            $q->where('status', $status);
        }
        if ($search = $request->search) {
            $q->where('return_number', 'like', "%$search%");
        }

        $returns = $q->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('gudang.returns.index', compact('returns'));
    }

    public function show(GudangReturn $return)
    {
        $return->load(['branch', 'creator', 'receiver', 'details.product.unit', 'details.unit']);
        return view('gudang.returns.show', compact('return'));
    }

    public function receive(Request $request, GudangReturn $return)
    {
        if ($return->status !== 'sent') {
            return back()->with('error', 'Retur ini sudah diterima sebelumnya.');
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.condition' => 'required|string|max:100',
        ]);

        try {
            DB::transaction(function () use ($request, $return) {
                $user = auth()->user();

                foreach ($request->items as $detailId => $itemData) {
                    $detail = GudangReturnDetail::findOrFail($detailId);
                    
                    if ($itemData['received_quantity'] > $detail->quantity) {
                        throw new \Exception("Jumlah yang diterima tidak boleh melebihi jumlah yang dikirim ({$detail->quantity}).");
                    }

                    $detail->update([
                        'received_quantity' => $itemData['received_quantity'],
                        'condition' => $itemData['condition']
                    ]);

                    // Tambahkan stok ke Gudang Utama (creditGudang)
                    if ($itemData['received_quantity'] > 0) {
                        $this->stock->creditGudang(
                            $detail->product_id,
                            $detail->unit_id,
                            $itemData['received_quantity'],
                            'return_receiving',
                            $return->id,
                            $user->id,
                            "Penerimaan retur dari " . ($return->from_entity === 'hendhys' ? 'Hendhys' : 'Jihans')
                        );
                    }
                }

                $return->update([
                    'status' => 'received',
                    'received_by' => $user->id,
                    'received_at' => now()
                ]);

                $this->logger->log('receive', 'gudang.return', "Menerima retur barang: {$return->return_number}", $return);
            });

            return redirect()->route('gudang.returns.index')->with('success', 'Retur barang berhasil diterima dan dimasukkan kembali ke stok Gudang Utama.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses penerimaan retur: ' . $e->getMessage());
        }
    }
}
