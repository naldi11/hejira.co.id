<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\GudangStock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\ReceivingPhoto;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReceivingController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $q = Receiving::with(['supplier', 'po', 'creator']);

        if ($search = $request->search) {
            $q->where(fn ($w) => $w->where('grn_number', 'like', "%$search%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%$search%")));
        }

        if ($request->filled('date_from')) $q->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))   $q->whereDate('date', '<=', $request->date_to);
        if ($request->filled('status'))    $q->where('status', $request->status);

        $receivings = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('gudang.receivings.index', compact('receivings'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('status', 'active')
            ->visibleInGudang()
            ->with('unit')
            ->orderBy('name')
            ->get();
        $units = Unit::orderBy('name')->get();
        $po    = null;

        if ($request->filled('po_id')) {
            $po = PurchaseOrder::with('details.product', 'details.unit', 'supplier')
                ->findOrFail($request->po_id);
        }

        return view('gudang.receivings.form', compact('suppliers', 'products', 'units', 'po'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'          => 'required|exists:master_suppliers,id',
            'date'                 => 'required|date',
            'po_id'                => 'nullable|exists:gudang_purchase_orders,id',
            'notes'                => 'nullable|string',
            'received_by_name'     => 'nullable|string|max:100',
            'supplier_rep_name'    => 'nullable|string|max:100',
            'kendala'              => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:master_products,id',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.unit_id'      => 'required|exists:master_units,id',
            'items.*.hpp_price'    => 'required|numeric|min:0',
            'items.*.kondisi'      => 'nullable|in:baik,rusak,kurang',
        ]);

        DB::transaction(function () use ($request) {
            $receiving = Receiving::create([
                'grn_number'        => $this->numbers->generateYearly('GDG-GRN', 'gudang_receivings', 'grn_number'),
                'po_id'             => $request->po_id,
                'supplier_id'       => $request->supplier_id,
                'date'              => $request->date,
                'notes'             => $request->notes,
                'status'            => 'open',
                'received_by_name'  => $request->received_by_name,
                'supplier_rep_name' => $request->supplier_rep_name,
                'kendala'           => $request->kendala,
                'created_by'        => auth()->id(),
            ]);

            // Build map of PO detail quantities for quantity_ordered
            $poDetailMap = [];
            if ($request->po_id) {
                $po = PurchaseOrder::with('details')->find($request->po_id);
                if ($po) {
                    foreach ($po->details as $d) {
                        $poDetailMap[$d->product_id] = (float) $d->quantity_ordered;
                    }
                }
            }

            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $receiving->details()->create([
                    'product_id'       => $item['product_id'],
                    'quantity_ordered'  => $poDetailMap[$item['product_id']] ?? null,
                    'quantity'         => $qty,
                    'unit_id'          => $item['unit_id'],
                    'hpp_price'        => $item['hpp_price'],
                    'total'            => $qty * (float) $item['hpp_price'],
                    'notes'            => $item['notes'] ?? null,
                    'kondisi'          => $item['kondisi'] ?? null,
                ]);

                $this->stock->creditGudang(
                    $item['product_id'],
                    $item['unit_id'],
                    $qty,
                    'purchase_receiving',
                    $receiving->id,
                    auth()->id()
                );

                $this->stock->updateProductHpp($item['product_id'], $item['hpp_price']);
            }

            if ($request->po_id) {
                $this->updatePoReceived($request->po_id, $request->items);
            }

            $this->logger->log('create', 'gudang.receiving', "Buat GRN: {$receiving->grn_number}", $receiving);
        });

        return redirect()->route('gudang.receiving.index')->with('success', 'Penerimaan barang berhasil dicatat.');
    }

    public function show(Receiving $receiving)
    {
        $receiving->load(['supplier', 'po', 'creator', 'details.product', 'details.unit', 'photos', 'closedBy']);

        return view('gudang.receivings.show', compact('receiving'));
    }

    public function update(Request $request, Receiving $receiving)
    {
        abort_if($receiving->isClosed(), 403, 'GRN sudah ditutup dan tidak dapat diedit.');

        $request->validate([
            'received_by_name'      => 'nullable|string|max:100',
            'supplier_rep_name'     => 'nullable|string|max:100',
            'kendala'               => 'nullable|string',
            'notes'                 => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.detail_id'     => 'required|exists:gudang_receiving_details,id',
            'items.*.quantity'      => 'required|numeric|min:0',
            'items.*.hpp_price'     => 'required|numeric|min:0',
            'items.*.kondisi'       => 'nullable|in:baik,rusak,kurang',
            'items.*.notes'         => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $receiving) {
            foreach ($request->items as $item) {
                $detail = $receiving->details()->findOrFail($item['detail_id']);
                $oldQty = (float) $detail->quantity;
                $newQty = (float) $item['quantity'];
                $delta  = $newQty - $oldQty;

                if ($delta > 0) {
                    $this->stock->creditGudang(
                        $detail->product_id,
                        $detail->unit_id,
                        $delta,
                        'receiving_edit',
                        $receiving->id,
                        auth()->id()
                    );
                } elseif ($delta < 0) {
                    $absDelta = abs($delta);
                    $stock = GudangStock::where('product_id', $detail->product_id)->first();
                    if (!$stock || $stock->quantity < $absDelta) {
                        throw new \Exception("Stok tidak mencukupi untuk koreksi produk: {$detail->product->name}");
                    }
                    $this->stock->debitGudang(
                        $detail->product_id,
                        $absDelta,
                        'receiving_edit',
                        $receiving->id,
                        auth()->id()
                    );
                }

                $newHpp = (float) $item['hpp_price'];
                $detail->update([
                    'quantity'  => $newQty,
                    'hpp_price' => $newHpp,
                    'total'     => $newQty * $newHpp,
                    'kondisi'   => $item['kondisi'] ?? null,
                    'notes'     => $item['notes'] ?? null,
                ]);
            }

            $receiving->update([
                'notes'             => $request->notes,
                'received_by_name'  => $request->received_by_name,
                'supplier_rep_name' => $request->supplier_rep_name,
                'kendala'           => $request->kendala,
            ]);

            $this->logger->log('update', 'gudang.receiving', "Edit GRN: {$receiving->grn_number}", $receiving);
        });

        return back()->with('success', 'GRN berhasil diperbarui.');
    }

    public function close(Receiving $receiving)
    {
        abort_if($receiving->isClosed(), 403, 'GRN sudah ditutup.');

        if (empty($receiving->received_by_name)) {
            return back()->withErrors(['close' => 'Nama penerima wajib diisi sebelum menutup GRN.']);
        }

        if (empty($receiving->supplier_rep_name)) {
            return back()->withErrors(['close' => 'Nama perwakilan supplier wajib diisi sebelum menutup GRN.']);
        }

        $receiving->update([
            'status'    => 'closed',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        $this->logger->log('close', 'gudang.receiving', "GRN ditutup: {$receiving->grn_number}", $receiving);

        return back()->with('success', "GRN {$receiving->grn_number} berhasil diselesaikan dan dikunci.");
    }

    public function print(Receiving $receiving)
    {
        $receiving->load(['supplier', 'po', 'creator', 'details.product', 'details.unit', 'photos', 'closedBy']);

        return view('gudang.receivings.print', compact('receiving'));
    }

    public function uploadPhoto(Request $request, Receiving $receiving)
    {
        abort_if($receiving->isClosed(), 403, 'GRN sudah ditutup.');

        $request->validate([
            'photos'   => 'required|array',
            'photos.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'caption'  => 'nullable|string|max:200',
        ]);

        $currentCount = $receiving->photos()->count();
        $newCount     = count($request->file('photos'));

        if ($currentCount + $newCount > 10) {
            return back()->withErrors(['photos' => "Maksimal 10 foto per GRN. Sudah ada {$currentCount} foto."]);
        }

        foreach ($request->file('photos') as $file) {
            $path = $file->store("receivings/{$receiving->grn_number}", 'public');
            ReceivingPhoto::create([
                'receiving_id' => $receiving->id,
                'path'         => $path,
                'caption'      => $request->caption,
                'uploaded_by'  => auth()->id(),
                'created_at'   => now(),
            ]);
        }

        return back()->with('success', $newCount . ' foto berhasil diunggah.');
    }

    public function deletePhoto(Receiving $receiving, ReceivingPhoto $photo)
    {
        abort_if($receiving->isClosed(), 403, 'GRN sudah ditutup.');
        abort_if($photo->receiving_id !== $receiving->id, 403, 'Foto tidak ditemukan.');

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('success', 'Foto berhasil dihapus.');
    }

    private function updatePoReceived(int $poId, array $items): void
    {
        $po = PurchaseOrder::with('details')->find($poId);
        if (!$po) return;

        foreach ($items as $item) {
            $detail = $po->details->where('product_id', $item['product_id'])->first();
            if ($detail) {
                $detail->increment('quantity_received', (float) $item['quantity']);
            }
        }

        $po->refresh();
        $allReceived = $po->details->every(fn ($d) => $d->quantity_received >= $d->quantity_ordered);
        $anyReceived = $po->details->some(fn ($d) => $d->quantity_received > 0);

        $po->update([
            'status'     => $allReceived ? 'received' : ($anyReceived ? 'partial' : $po->status),
            'updated_by' => auth()->id(),
        ]);
    }
}
