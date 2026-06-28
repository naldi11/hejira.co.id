<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StoreReceivingRequest;
use App\Http\Requests\Gudang\UpdateReceivingRequest;
use App\Http\Requests\Gudang\UploadReceivingPhotoRequest;
use App\Http\Resources\Gudang\ReceivingResource;
use App\Models\GudangStock;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\ReceivingPhoto;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReceivingController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numbers,
        private StockService $stock,
        private ActivityLogService $logger
    ) {}

    public function index(Request $request)
    {
        $receivings = Receiving::with(['supplier', 'po', 'creator'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('grn_number', 'like', "%{$request->search}%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$request->search}%"))))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', $request->date_to))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return Inertia::render('Gudang/Receivings/Index', [
            'receivings' => ReceivingResource::collection($receivings),
            'filters'    => $request->only('search', 'date_from', 'date_to'),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Gudang/Receivings/Create', [
            'suppliers'      => Supplier::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'products'       => Product::where('status', 'active')->visibleInGudang()->with('unit')->orderBy('name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'unit_id' => $p->unit_id, 'unit_name' => $p->unit?->abbreviation ?? 'PCS', 'hpp' => (float) $p->hpp]),
            'purchaseOrders' => PurchaseOrder::with('supplier', 'details.product', 'details.unit')
                ->whereIn('status', ['draft', 'sent', 'partial'])->orderByDesc('date')->get()
                ->map(fn ($po) => [
                    'id'          => $po->id,
                    'po_number'   => $po->po_number,
                    'supplier_id' => $po->supplier_id,
                    'supplier'    => $po->supplier?->name,
                    'details'     => $po->details->map(fn ($d) => [
                        'product_id'        => $d->product_id,
                        'product_name'      => $d->product?->name,
                        'quantity_ordered'  => (float) $d->quantity_ordered,
                        'quantity_received' => (float) $d->quantity_received,
                        'unit_id'           => $d->unit_id,
                        'unit_name'         => $d->unit?->abbreviation ?? 'PCS',
                        'price'             => (float) $d->price,
                    ])->values(),
                ]),
            'selectedPoId'   => $request->filled('po_id') ? (int) $request->po_id : null,
        ]);
    }

    public function store(StoreReceivingRequest $request)
    {
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

            $receiptConfirmation = \App\Models\ReceiptConfirmation::create([
                'receiptable_type' => Receiving::class,
                'receiptable_id'   => $receiving->id,
                'received_by'      => auth()->id(),
                'received_at'      => now(),
                'status'           => 'completed',
                'general_notes'    => $request->notes,
            ]);

            $poDetailMap = [];
            if ($request->po_id) {
                $po = PurchaseOrder::with('details')->find($request->po_id);
                foreach ($po?->details ?? [] as $d) {
                    $poDetailMap[$d->product_id] = (float) $d->quantity_ordered;
                }
            }

            foreach ($request->items as $item) {
                $qtyBagus = (float) $item['quantity_bagus'];
                $qtyRusak = (float) $item['quantity_rusak'];

                if ($qtyBagus <= 0 && $qtyRusak <= 0) {
                    continue;
                }

                if ($qtyBagus > 0) {
                    $receiving->details()->create([
                        'product_id'       => $item['product_id'],
                        'quantity_ordered' => $poDetailMap[$item['product_id']] ?? null,
                        'quantity'         => $qtyBagus,
                        'unit_id'          => $item['unit_id'],
                        'hpp_price'        => $item['hpp_price'],
                        'total'            => $qtyBagus * (float) $item['hpp_price'],
                        'notes'            => $item['notes'] ?? null,
                        'kondisi'          => 'baik',
                    ]);

                    $receiptConfirmation->details()->create([
                        'product_id'   => $item['product_id'],
                        'expected_qty' => $poDetailMap[$item['product_id']] ?? $qtyBagus,
                        'actual_qty'   => $qtyBagus,
                        'condition'    => 'baik',
                        'expired_date' => $item['expired_date'] ?? null,
                        'batch_number' => $item['batch_number'] ?? null,
                        'notes'        => $item['notes'] ?? null,
                    ]);

                    $this->stock->creditGudang($item['product_id'], $item['unit_id'], $qtyBagus, 'purchase_receiving', $receiving->id, auth()->id());
                }

                if ($qtyRusak > 0) {
                    $notesRusak = trim(($item['notes'] ?? '') . ' (Rusak)');
                    $receiving->details()->create([
                        'product_id'       => $item['product_id'],
                        'quantity_ordered' => $poDetailMap[$item['product_id']] ?? null,
                        'quantity'         => $qtyRusak,
                        'unit_id'          => $item['unit_id'],
                        'hpp_price'        => $item['hpp_price'],
                        'total'            => $qtyRusak * (float) $item['hpp_price'],
                        'notes'            => $notesRusak,
                        'kondisi'          => 'rusak',
                    ]);

                    $receiptConfirmation->details()->create([
                        'product_id'   => $item['product_id'],
                        'expected_qty' => $poDetailMap[$item['product_id']] ?? $qtyRusak,
                        'actual_qty'   => $qtyRusak,
                        'condition'    => 'rusak',
                        'expired_date' => $item['expired_date'] ?? null,
                        'batch_number' => $item['batch_number'] ?? null,
                        'notes'        => $notesRusak,
                    ]);
                }

                $this->stock->updateProductHpp($item['product_id'], $item['hpp_price']);
            }

            if ($request->po_id) {
                $this->updatePoReceived($request->po_id, $request->items);
            }

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $file->store("receivings/{$receiving->grn_number}", 'public');
                    ReceivingPhoto::create([
                        'receiving_id' => $receiving->id,
                        'path'         => $path,
                        'uploaded_by'  => auth()->id(),
                        'created_at'   => now(),
                    ]);
                    $receiptConfirmation->photos()->create(['photo_path' => $path]);
                }
            }

            if ($request->filled('photo_urls')) {
                foreach ($request->input('photo_urls') as $url) {
                    try {
                        $contents = file_get_contents($url);
                        $name = basename(parse_url($url, PHP_URL_PATH));
                        if (empty($name) || !str_contains($name, '.')) {
                            $name = 'image_' . time() . '_' . rand(100, 999) . '.jpg';
                        }
                        $path = "receivings/{$receiving->grn_number}/" . time() . '_' . $name;
                        Storage::disk('public')->put($path, $contents);

                        ReceivingPhoto::create([
                            'receiving_id' => $receiving->id,
                            'path'         => $path,
                            'uploaded_by'  => auth()->id(),
                            'created_at'   => now(),
                        ]);
                        $receiptConfirmation->photos()->create(['photo_path' => $path]);
                    } catch (\Exception $e) {
                        // Skip download failure quietly or log
                    }
                }
            }

            $this->logger->log('create', 'gudang.receiving', "Buat GRN & BAST: {$receiving->grn_number}", $receiving);
        });

        return redirect()->route('gudang.receiving.index')->with('success', 'Penerimaan barang & BAST berhasil dicatat.');
    }

    public function show(Receiving $receiving)
    {
        $receiving->load(['supplier', 'po', 'creator', 'details.product', 'details.unit', 'photos', 'closedBy']);

        return Inertia::render('Gudang/Receivings/Show', [
            'receiving' => new ReceivingResource($receiving),
        ]);
    }

    public function update(UpdateReceivingRequest $request, Receiving $receiving)
    {
        abort_if($receiving->isClosed(), 403, 'GRN sudah ditutup dan tidak dapat diedit.');

        $data = $request->validated();

        DB::transaction(function () use ($data, $receiving) {
            foreach ($data['items'] as $item) {
                $detail = $receiving->details()->findOrFail($item['detail_id']);
                $delta  = (float) $item['quantity'] - (float) $detail->quantity;

                if ($delta > 0) {
                    $this->stock->creditGudang($detail->product_id, $detail->unit_id, $delta, 'receiving_edit', $receiving->id, auth()->id());
                } elseif ($delta < 0) {
                    $absDelta = abs($delta);
                    $stock = GudangStock::where('product_id', $detail->product_id)->first();
                    if (! $stock || $stock->quantity < $absDelta) {
                        throw new \Exception("Stok tidak mencukupi untuk koreksi produk: {$detail->product->name}");
                    }
                    $this->stock->debitGudang($detail->product_id, $absDelta, 'receiving_edit', $receiving->id, auth()->id());
                }

                $newHpp = (float) $item['hpp_price'];
                $detail->update([
                    'quantity'  => (float) $item['quantity'],
                    'hpp_price' => $newHpp,
                    'total'     => (float) $item['quantity'] * $newHpp,
                    'kondisi'   => $item['kondisi'] ?? null,
                    'notes'     => $item['notes'] ?? null,
                ]);
            }

            $receiving->update([
                'notes'             => $data['notes'] ?? null,
                'received_by_name'  => $data['received_by_name'] ?? null,
                'supplier_rep_name' => $data['supplier_rep_name'] ?? null,
                'kendala'           => $data['kendala'] ?? null,
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

        $receiving->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => auth()->id()]);
        $this->logger->log('close', 'gudang.receiving', "GRN ditutup: {$receiving->grn_number}", $receiving);

        return back()->with('success', "GRN {$receiving->grn_number} berhasil diselesaikan dan dikunci.");
    }

    public function print(Receiving $receiving)
    {
        $receiving->load(['supplier', 'po', 'creator', 'details.product', 'details.unit', 'photos', 'closedBy']);

        return view('gudang.receivings.print', compact('receiving'));
    }

    public function uploadPhoto(UploadReceivingPhotoRequest $request, Receiving $receiving)
    {
        abort_if($receiving->isClosed(), 403, 'GRN sudah ditutup.');

        $photoFiles = $request->file('photos') ?? [];
        $photoUrls = $request->input('photo_urls') ?? [];
        $newCount = count($photoFiles) + count($photoUrls);

        if ($receiving->photos()->count() + $newCount > 10) {
            return back()->withErrors(['photos' => "Maksimal 10 foto per GRN. Sudah ada {$receiving->photos()->count()} foto."]);
        }

        foreach ($photoFiles as $file) {
            $path = $file->store("receivings/{$receiving->grn_number}", 'public');
            ReceivingPhoto::create([
                'receiving_id' => $receiving->id,
                'path'         => $path,
                'caption'      => $request->caption,
                'uploaded_by'  => auth()->id(),
                'created_at'   => now(),
            ]);
        }

        foreach ($photoUrls as $url) {
            try {
                $contents = file_get_contents($url);
                $name = basename(parse_url($url, PHP_URL_PATH));
                if (empty($name) || !str_contains($name, '.')) {
                    $name = 'image_' . time() . '_' . rand(100, 999) . '.jpg';
                }
                $path = "receivings/{$receiving->grn_number}/" . time() . '_' . $name;
                Storage::disk('public')->put($path, $contents);

                ReceivingPhoto::create([
                    'receiving_id' => $receiving->id,
                    'path'         => $path,
                    'caption'      => $request->caption,
                    'uploaded_by'  => auth()->id(),
                    'created_at'   => now(),
                ]);
            } catch (\Exception $e) {
                // Skip silently or handle
            }
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
        if (! $po) {
            return;
        }

        foreach ($items as $item) {
            // Hanya barang BAGUS yang dihitung sebagai diterima ke stok.
            // Barang rusak tidak masuk stok, sehingga tidak dihitung ke quantity_received PO.
            $totalReceived = (float) ($item['quantity_bagus'] ?? 0);
            if ($totalReceived <= 0) {
                continue;
            }
            $po->details->where('product_id', $item['product_id'])->first()?->increment('quantity_received', $totalReceived);
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
