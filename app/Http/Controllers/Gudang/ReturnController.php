<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\ReceiveReturnRequest;
use App\Http\Resources\Gudang\GudangReturnResource;
use App\Models\GudangReturn;
use App\Models\GudangReturnDetail;
use App\Services\ActivityLogService;
use App\Services\StockService;
use App\Services\ReturnQuarantine;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReturnController extends Controller
{
    public function __construct(
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $returns = GudangReturn::with(['branch', 'creator', 'receiver'])
            ->withCount('details')
            ->when($request->filled('entity'), fn ($q) => $q->where('from_entity', $request->entity))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('return_number', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Gudang/Returns/Index', [
            'returns' => GudangReturnResource::collection($returns),
            'filters' => $request->only('search', 'entity', 'status'),
        ]);
    }

    public function show(GudangReturn $return)
    {
        $return->load(['branch', 'creator', 'receiver', 'details.product.unit', 'details.unit']);

        return Inertia::render('Gudang/Returns/Show', [
            'return' => new GudangReturnResource($return),
        ]);
    }

    public function receive(ReceiveReturnRequest $request, GudangReturn $return)
    {
        if ($return->status !== 'sent') {
            return back()->with('error', 'Retur ini sudah diterima sebelumnya.');
        }

        try {
            DB::transaction(function () use ($request, $return) {
                $return = GudangReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();
                if ($return->status !== 'sent') return;
                $userId = auth()->id();
                $details = $return->details()->lockForUpdate()->get()->keyBy('id');
                $ids = array_map('intval', array_keys($request->validated('items'))); sort($ids);
                $expected = $details->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
                if ($ids !== $expected) throw ValidationException::withMessages(['items' => 'Daftar barang harus tepat sesuai retur ini. Muat ulang halaman.']);

                foreach ($request->validated('items') as $detailId => $itemData) {
                    $detail = $details->get((int) $detailId);
                    $sentCondition = $detail->sent_condition ?? $detail->condition;
                    if (ReturnQuarantine::condition($sentCondition) !== 'good' && $itemData['condition'] === 'Bagus (Siap Jual)') throw ValidationException::withMessages(["items.{$detailId}.condition" => 'Barang rusak, kedaluwarsa, atau kondisi belum jelas wajib masuk Stok Rusak / Retur, bukan stok siap jual.']);

                    if ($itemData['received_quantity'] > $detail->quantity) {
                        throw new \Exception("Jumlah yang diterima tidak boleh melebihi jumlah yang dikirim ({$detail->quantity}).");
                    }

                    $detail->update([
                        'received_quantity' => $itemData['received_quantity'],
                        'condition'         => $itemData['condition'],
                        'receiving_notes' => $itemData['receiving_notes'] ?? null,
                    ]);

                    if ($itemData['received_quantity'] > 0 && $itemData['condition'] === 'Bagus (Siap Jual)') {
                        $this->stock->creditJihansGudang(
                            $detail->product_id,
                            $detail->unit_id,
                            (int) $itemData['received_quantity'],
                            'return_receiving',
                            $return->id,
                            $userId,
                            'Penerimaan retur (Bagus) dari ' . ($return->from_entity === 'hendhys' ? 'Hendhys' : 'Jihans'),
                        );
                    } elseif ($itemData['received_quantity'] > 0) {
                        // Kadaluwarsa / Rusak — log agar muncul di Kartu Stok,
                        // tapi TIDAK menambah qty siap jual
                        $condLabel = $itemData['condition'] === 'Kadaluwarsa' ? 'Kadaluwarsa' : 'Rusak (Defect)';
                        $this->stock->logJihansGudangReturnMovement(
                            $detail->product_id,
                            (int) $itemData['received_quantity'],
                            'return_receiving',
                            $return->id,
                            $userId,
                            "Penerimaan retur ({$condLabel}) dari " . ($return->from_entity === 'hendhys' ? 'Hendhys' : 'Jihans') . ' — tidak masuk stok siap jual',
                        );
                    }
                }

                $return->update([
                    'status'      => 'received',
                    'received_by' => $userId,
                    'received_at' => now(),
                ]);

                app(ReturnQuarantine::class)->record('gudang', $return->fresh());

                $this->logger->log('receive', 'gudang.return', "Menerima retur barang: {$return->return_number}", $return);
            });

            return redirect()->route('gudang.returns.index')
                ->with('success', 'Retur diterima. Barang rusak / kedaluwarsa masuk stok terpisah yang tidak dapat dijual atau dikirim.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses penerimaan retur: ' . $e->getMessage());
        }
    }
}
