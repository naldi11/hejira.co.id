@extends('layouts.hendhys')
@section('title', 'Konfirmasi Penerimaan Barang')
@section('page-title', 'Konfirmasi Terima: ' . $transferToBranch->transfer_number)

@section('content')
<div class="mt-4 max-w-4xl space-y-4">

    <div class="flex items-center gap-2">
        <a href="{{ route('hendhys.transfer-to-branch.show', $transferToBranch->id) }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('hendhys.transfer-to-branch.receive', $transferToBranch->id) }}"
          method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        {{-- Header Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Informasi Pengiriman dari Pusat</p>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div><p class="text-gray-400 text-xs">No. Transfer</p><p class="font-bold text-gray-800">{{ $transferToBranch->transfer_number }}</p></div>
                <div><p class="text-gray-400 text-xs">Tanggal Kirim</p><p class="font-medium text-gray-700">{{ $transferToBranch->date->format('d M Y') }}</p></div>
                <div><p class="text-gray-400 text-xs">Dikirim Oleh</p><p class="font-medium text-gray-700">{{ $transferToBranch->creator->name }}</p></div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 font-semibold text-sm text-gray-700">Daftar Barang — Isi Qty & Kondisi yang Diterima</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Produk</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500">Qty Dikirim</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500 w-32">Qty Diterima</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 w-20">Satuan</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500 w-32">Kondisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($transferToBranch->details as $detail)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $detail->product->name }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ (int) $detail->quantity }}</td>
                            <td class="px-4 py-3">
                                <input type="number" name="received_quantities[{{ $detail->id }}]"
                                       value="{{ old('received_quantities.' . $detail->id, (int) $detail->quantity) }}"
                                       min="0" max="{{ (int) $detail->quantity }}" step="1" required
                                       class="w-full text-center border border-gray-200 rounded-lg px-2 py-1.5 text-sm font-bold text-gray-800 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ $detail->unit->abbreviation ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <select name="kondisi[{{ $detail->id }}]"
                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                    <option value="">-</option>
                                    <option value="baik">Baik</option>
                                    <option value="rusak">Rusak</option>
                                    <option value="kurang">Kurang</option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BAST Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Informasi Bukti Serah Terima</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Penerima (Cabang)</label>
                    <input type="text" name="receive_received_by_name" value="{{ old('receive_received_by_name') }}"
                           placeholder="Nama petugas cabang penerima..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pengirim (Pusat)</label>
                    <input type="text" name="receive_pengirim_name" value="{{ old('receive_pengirim_name') }}"
                           placeholder="Nama petugas pusat pengirim..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan / No. Surat Jalan</label>
                    <input type="text" name="receive_notes" value="{{ old('receive_notes') }}"
                           placeholder="Nomor surat jalan atau catatan..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kendala / Catatan Masalah</label>
                    <input type="text" name="receive_kendala" value="{{ old('receive_kendala') }}"
                           placeholder="Isi jika ada kendala (rusak, kurang, dll)..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Photos --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5"
             x-data="{ previews: [], addFiles(evt) {
                 const newFiles = Array.from(evt.target.files);
                 newFiles.forEach(f => {
                     if (this.previews.length < 10) {
                         this.previews.push({ src: URL.createObjectURL(f), name: f.name });
                     }
                 });
                 evt.target.value = '';
             }, remove(i) { this.previews.splice(i,1); } }">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Foto Bukti Penerimaan (Maks. 10)</p>
                <span class="text-xs text-gray-400" x-text="previews.length + '/10 foto'"></span>
            </div>
            <div class="grid grid-cols-4 gap-3 mb-3" x-show="previews.length > 0">
                <template x-for="(item, i) in previews" :key="i">
                    <div class="relative group">
                        <img :src="item.src" class="w-full h-24 object-cover rounded-lg border border-gray-200">
                        <button type="button" @click="remove(i)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
                    </div>
                </template>
            </div>
            <label x-show="previews.length < 10"
                   class="flex items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-xl py-4 cursor-pointer hover:border-indigo-300 hover:bg-indigo-50 transition-all text-sm text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Foto
                <input type="file" name="photos[]" accept="image/*" multiple class="hidden" @change="addFiles($event)">
            </label>
            <p class="text-xs text-gray-400 mt-1.5">JPG, PNG, WEBP — maks. 5 MB per foto</p>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    onclick="return confirm('Konfirmasi penerimaan barang? Stok cabang akan bertambah dan BAST akan dibuat. Tindakan ini tidak dapat diubah.')"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Konfirmasi & Buat BAST
            </button>
            <a href="{{ route('hendhys.transfer-to-branch.show', $transferToBranch->id) }}"
               class="border border-gray-300 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50">Batal</a>
        </div>
    </form>
</div>
@endsection
