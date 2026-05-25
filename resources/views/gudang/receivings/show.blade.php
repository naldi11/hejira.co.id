@extends('layouts.gudang')
@section('title', 'Detail GRN '.$receiving->grn_number)
@section('page-title', 'Penerimaan Barang — '.$receiving->grn_number)

@section('content')
@php
    $statusClass = $receiving->isOpen()
        ? 'bg-yellow-100 text-yellow-700 border border-yellow-300'
        : 'bg-green-100 text-green-700 border border-green-300';
    $statusLabel = $receiving->isOpen() ? 'TERBUKA' : 'SELESAI';
    $kondisiLabel = ['baik' => 'Baik', 'rusak' => 'Rusak', 'kurang' => 'Kurang'];
    $kondisiClass  = ['baik' => 'bg-green-100 text-green-700', 'rusak' => 'bg-red-100 text-red-700', 'kurang' => 'bg-yellow-100 text-yellow-700'];
@endphp

<div class="mt-4 max-w-5xl space-y-4"
     x-data="{ editMode: false }">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->has('close'))
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">{{ $errors->first('close') }}</div>
    @endif

    {{-- Header Actions --}}
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('gudang.receiving.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>

        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>

        @if($receiving->isOpen())
            <button @click="editMode = !editMode"
                    :class="editMode ? 'bg-gray-600 hover:bg-gray-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                    class="text-white text-sm px-3 py-1.5 rounded-lg font-medium transition-colors"
                    x-text="editMode ? 'Batal Edit' : 'Edit GRN'">
            </button>
        @endif

        <a href="{{ route('gudang.receiving.print', $receiving) }}" target="_blank"
           class="bg-gray-800 hover:bg-gray-900 text-white text-sm px-3 py-1.5 rounded-lg font-medium flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak BAST
        </a>

        @if($receiving->isOpen())
        <form method="POST" action="{{ route('gudang.receiving.close', $receiving) }}" class="inline"
              onsubmit="return confirm('Tutup GRN ini? Setelah ditutup tidak dapat diedit lagi.')">
            @csrf
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm px-3 py-1.5 rounded-lg font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Selesaikan GRN
            </button>
        </form>
        @else
        <span class="text-xs text-gray-400">Ditutup: {{ $receiving->closed_at?->format('d M Y H:i') }} oleh {{ $receiving->closedBy?->name }}</span>
        @endif
    </div>

    {{-- ===== VIEW MODE ===== --}}
    <div x-show="!editMode">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Info Supplier --}}
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Informasi Supplier</h3>
                <div class="space-y-2 text-sm">
                    <div><p class="text-xs text-gray-400">Supplier</p><p class="font-medium text-gray-800">{{ $receiving->supplier->name }}</p></div>
                    <div><p class="text-xs text-gray-400">Perwakilan Supplier</p><p class="text-gray-800">{{ $receiving->supplier_rep_name ?: '-' }}</p></div>
                </div>
            </div>
            {{-- Info Penerimaan --}}
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Informasi Penerimaan</h3>
                <div class="space-y-2 text-sm">
                    <div><p class="text-xs text-gray-400">No. GRN</p><p class="font-bold text-gray-800">{{ $receiving->grn_number }}</p></div>
                    <div><p class="text-xs text-gray-400">Tanggal</p><p class="text-gray-800">{{ $receiving->date->format('d M Y') }}</p></div>
                    <div><p class="text-xs text-gray-400">Referensi PO</p>
                        <p class="text-gray-800">
                            @if($receiving->po)
                                <a href="{{ route('gudang.po.show', $receiving->po) }}" class="text-indigo-600 hover:underline">{{ $receiving->po->po_number }}</a>
                            @else Penerimaan Langsung @endif
                        </p>
                    </div>
                    <div><p class="text-xs text-gray-400">Catatan / No. Surat Jalan</p><p class="text-gray-800">{{ $receiving->notes ?: '-' }}</p></div>
                    <div><p class="text-xs text-gray-400">Diterima Oleh (Gudang)</p><p class="text-gray-800">{{ $receiving->received_by_name ?: '-' }}</p></div>
                </div>
            </div>
        </div>

        @if($receiving->kendala)
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm">
            <p class="text-xs font-semibold text-orange-500 uppercase tracking-wider mb-1">Kendala / Catatan Masalah</p>
            <p class="text-orange-800">{{ $receiving->kendala }}</p>
        </div>
        @endif

        {{-- Items Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-700">Item Produk Diterima</h3>
                @php $grandTotal = $receiving->details->sum('total'); @endphp
                <span class="text-sm font-bold text-gray-900">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 text-xs">
                        <tr>
                            <th class="px-4 py-3 font-medium">Produk</th>
                            @if($receiving->details->whereNotNull('quantity_ordered')->isNotEmpty())
                            <th class="px-4 py-3 font-medium text-center">Qty PO</th>
                            @endif
                            <th class="px-4 py-3 font-medium text-center">Qty Terima</th>
                            <th class="px-4 py-3 font-medium text-center">Satuan</th>
                            <th class="px-4 py-3 font-medium text-center">Kondisi</th>
                            <th class="px-4 py-3 font-medium text-right">Harga Beli</th>
                            <th class="px-4 py-3 font-medium text-right">Total</th>
                            <th class="px-4 py-3 font-medium">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($receiving->details as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $item->product->name }}</td>
                            @if($receiving->details->whereNotNull('quantity_ordered')->isNotEmpty())
                            <td class="px-4 py-3 text-center text-gray-400 text-xs">{{ $item->quantity_ordered ? number_format($item->quantity_ordered, 0) : '-' }}</td>
                            @endif
                            <td class="px-4 py-3 text-center font-semibold">{{ floatval($item->quantity) }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $item->unit->abbreviation ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($item->kondisi)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $kondisiClass[$item->kondisi] ?? '' }}">
                                    {{ $kondisiLabel[$item->kondisi] ?? $item->kondisi }}
                                </span>
                                @else <span class="text-gray-300">—</span> @endif
                            </td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($item->hpp_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->notes ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== EDIT MODE ===== --}}
    <div x-show="editMode" x-cloak>
        <form method="POST" action="{{ route('gudang.receiving.update', $receiving) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-white p-5 rounded-xl border border-indigo-200 shadow-sm space-y-3">
                    <h3 class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Edit Header</h3>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Catatan / No. Surat Jalan</label>
                        <input type="text" name="notes" value="{{ old('notes', $receiving->notes) }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Nama Penerima Gudang</label>
                        <input type="text" name="received_by_name" value="{{ old('received_by_name', $receiving->received_by_name) }}"
                               placeholder="Wajib diisi sebelum Selesaikan GRN"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Perwakilan Supplier</label>
                        <input type="text" name="supplier_rep_name" value="{{ old('supplier_rep_name', $receiving->supplier_rep_name) }}"
                               placeholder="Wajib diisi sebelum Selesaikan GRN"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Kendala / Catatan Masalah</label>
                        <textarea name="kendala" rows="2" placeholder="Isi jika ada kendala..."
                                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('kendala', $receiving->kendala) }}</textarea>
                    </div>
                </div>
                <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-100 text-sm text-indigo-700 space-y-2">
                    <p class="font-semibold">Catatan Edit GRN:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Stok gudang otomatis disesuaikan berdasarkan selisih qty</li>
                        <li>Qty tidak boleh dikurangi melebihi stok yang tersedia</li>
                        <li>Selesaikan GRN membutuhkan Nama Penerima & Perwakilan Supplier</li>
                    </ul>
                </div>
            </div>

            {{-- Edit Items --}}
            <div class="bg-white rounded-xl border border-indigo-200 shadow-sm overflow-hidden mb-4">
                <div class="p-4 border-b border-gray-100 bg-indigo-50">
                    <h3 class="text-sm font-semibold text-indigo-700">Edit Item Diterima</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Produk</th>
                                @if($receiving->details->whereNotNull('quantity_ordered')->isNotEmpty())
                                <th class="px-4 py-3 text-center font-medium">Qty PO</th>
                                @endif
                                <th class="px-4 py-3 text-center font-medium">Qty Diterima</th>
                                <th class="px-4 py-3 text-center font-medium">Satuan</th>
                                <th class="px-4 py-3 text-center font-medium">Kondisi</th>
                                <th class="px-4 py-3 text-right font-medium">Harga Beli</th>
                                <th class="px-4 py-3 text-left font-medium">Catatan Item</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($receiving->details as $i => $item)
                            <tr>
                                <input type="hidden" name="items[{{ $i }}][detail_id]" value="{{ $item->id }}">
                                <td class="px-4 py-2 font-medium text-gray-800">{{ $item->product->name }}</td>
                                @if($receiving->details->whereNotNull('quantity_ordered')->isNotEmpty())
                                <td class="px-4 py-2 text-center text-gray-400 text-xs">{{ $item->quantity_ordered ? number_format($item->quantity_ordered, 0) : '-' }}</td>
                                @endif
                                <td class="px-4 py-2">
                                    <input type="number" name="items[{{ $i }}][quantity]" value="{{ floatval($item->quantity) }}"
                                           min="0" step="0.001" required
                                           class="w-24 border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-center focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                </td>
                                <td class="px-4 py-2 text-center text-gray-500 text-xs">{{ $item->unit->abbreviation ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <select name="items[{{ $i }}][kondisi]"
                                            class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                        <option value="">-</option>
                                        <option value="baik"   {{ $item->kondisi === 'baik'   ? 'selected' : '' }}>Baik</option>
                                        <option value="rusak"  {{ $item->kondisi === 'rusak'  ? 'selected' : '' }}>Rusak</option>
                                        <option value="kurang" {{ $item->kondisi === 'kurang' ? 'selected' : '' }}>Kurang</option>
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" name="items[{{ $i }}][hpp_price]" value="{{ floatval($item->hpp_price) }}"
                                           min="0" step="0.01" required
                                           class="w-32 border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-right focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" name="items[{{ $i }}][notes]" value="{{ $item->notes }}"
                                           placeholder="Catatan..."
                                           class="w-40 border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
                    Simpan Perubahan
                </button>
                <button type="button" @click="editMode = false" class="border border-gray-300 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
            </div>
        </form>
    </div>

    {{-- ===== FOTO BUKTI ===== --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-sm font-semibold text-gray-700">Foto Bukti Penerimaan</h3>
            <span class="text-xs text-gray-400">{{ $receiving->photos->count() }} / 10 foto</span>
        </div>

        @if($receiving->photos->isNotEmpty())
        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach($receiving->photos as $photo)
            <div class="relative group">
                <img src="{{ $photo->url() }}" alt="{{ $photo->caption }}"
                     class="w-full h-28 object-cover rounded-lg border border-gray-200 cursor-pointer"
                     onclick="window.open(this.src, '_blank')">
                @if($photo->caption)
                <p class="text-xs text-gray-500 mt-1 truncate">{{ $photo->caption }}</p>
                @endif
                @if($receiving->isOpen())
                <form method="POST" action="{{ route('gudang.receiving.photos.destroy', [$receiving, $photo]) }}"
                      onsubmit="return confirm('Hapus foto ini?')"
                      class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-700">✕</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="p-6 text-center text-gray-400 text-sm">Belum ada foto bukti.</div>
        @endif

        @if($receiving->isOpen() && $receiving->photos->count() < 10)
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            <form method="POST" action="{{ route('gudang.receiving.photos.store', $receiving) }}"
                  enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Upload Foto (maks. {{ 10 - $receiving->photos->count() }} foto lagi)</label>
                    <input type="file" name="photos[]" accept="image/*" multiple required
                           class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Keterangan (opsional)</label>
                    <input type="text" name="caption" placeholder="Contoh: Kondisi kardus..."
                           class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-1.5 rounded-lg font-medium">
                    Upload Foto
                </button>
            </form>
            @error('photos')
            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>
        @endif
    </div>

</div>
@endsection
