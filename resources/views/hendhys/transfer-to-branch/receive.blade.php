@extends('layouts.hendhys')
@section('title', 'Konfirmasi Penerimaan Barang')
@section('page-title', 'Konfirmasi Penerimaan: ' . $transferToBranch->transfer_number)

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full space-y-md">

    {{-- Header --}}
    <div class="flex items-center gap-sm">
        <a href="{{ route('hendhys.transfer-to-branch.show', $transferToBranch->id) }}"
            class="flex items-center justify-center w-9 h-9 rounded-full bg-surface-container border border-outline-variant text-on-surface-variant hover:bg-surface-container-high transition-colors active:scale-95">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <div>
            <h2 class="font-headline-sm text-headline-sm font-bold text-on-surface">Konfirmasi Penerimaan Barang</h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $transferToBranch->transfer_number }} · Dari Pusat Hendhys</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-error-container border border-error/30 text-on-error-container rounded-xl p-md flex items-start gap-sm">
        <span class="material-symbols-outlined text-error shrink-0 mt-0.5">error</span>
        <div>
            <p class="font-label-lg text-label-lg font-bold mb-xs">Ada kesalahan:</p>
            <ul class="list-disc list-inside space-y-xs font-body-sm text-body-sm">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('hendhys.transfer-to-branch.receive', $transferToBranch->id) }}"
          method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Info Pengiriman --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden mb-md">
            <div class="px-md py-sm border-b border-outline-variant bg-surface-container-low flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[20px]">local_shipping</span>
                <h3 class="font-label-lg text-label-lg font-bold text-on-surface">Informasi Pengiriman</h3>
            </div>
            <div class="p-md grid grid-cols-2 md:grid-cols-3 gap-md">
                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">No. Transfer</p>
                    <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ $transferToBranch->transfer_number }}</p>
                </div>
                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Tanggal Kirim</p>
                    <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ \Carbon\Carbon::parse($transferToBranch->date)->translatedFormat('d F Y') }}</p>
                </div>
                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Dikirim Oleh</p>
                    <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ $transferToBranch->creator->name }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel Qty Diterima --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden mb-md">
            <div class="px-md py-sm border-b border-outline-variant bg-surface-container-low flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[20px]">inventory_2</span>
                <h3 class="font-label-lg text-label-lg font-bold text-on-surface">Daftar Barang — Isi Qty yang Diterima</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Produk</th>
                            <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-right">Qty Dikirim</th>
                            <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-right w-48">Qty Diterima <span class="text-error">*</span></th>
                            <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider w-24">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach($transferToBranch->details as $detail)
                        <tr class="hover:bg-surface-container/40 transition-colors">
                            <td class="px-md py-sm">
                                <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ $detail->product->name }}</p>
                            </td>
                            <td class="px-md py-sm text-right">
                                <span class="font-label-lg text-label-lg text-on-surface-variant">{{ number_format((float)$detail->quantity, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-md py-sm">
                                <input type="number"
                                    name="received_quantities[{{ $detail->id }}]"
                                    value="{{ old('received_quantities.' . $detail->id, (float)$detail->quantity) }}"
                                    min="0"
                                    max="{{ (float)$detail->quantity }}"
                                    step="0.001"
                                    required
                                    class="w-full text-right text-sm border border-outline-variant rounded-lg px-sm py-xs focus:border-primary focus:ring-0 bg-surface font-bold text-on-surface">
                            </td>
                            <td class="px-md py-sm">
                                <span class="font-label-sm text-label-sm text-on-surface-variant font-bold">{{ $detail->unit->abbreviation ?? $detail->unit->name }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Foto & Catatan --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden mb-md">
            <div class="px-md py-sm border-b border-outline-variant bg-surface-container-low flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[20px]">attach_file</span>
                <h3 class="font-label-lg text-label-lg font-bold text-on-surface">Bukti & Keterangan</h3>
            </div>
            <div class="p-md space-y-md">
                {{-- Upload Foto --}}
                <div x-data="{ preview: null, fileName: '' }">
                    <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">
                        Foto Bukti Serah Terima <span class="font-normal text-on-surface-variant">(opsional)</span>
                    </label>
                    <div class="border-2 border-dashed border-outline-variant rounded-xl p-lg flex flex-col items-center justify-center gap-sm cursor-pointer hover:border-primary hover:bg-primary-fixed/20 transition-all"
                         @click="$refs.photoInput.click()"
                         @dragover.prevent
                         @drop.prevent="
                            const file = $event.dataTransfer.files[0];
                            if (file && file.type.startsWith('image/')) {
                                preview = URL.createObjectURL(file);
                                fileName = file.name;
                                const dt = new DataTransfer();
                                dt.items.add(file);
                                $refs.photoInput.files = dt.files;
                            }">
                        <template x-if="!preview">
                            <div class="text-center">
                                <span class="material-symbols-outlined text-outline text-[48px] mb-sm block">photo_camera</span>
                                <p class="font-label-lg text-label-lg text-on-surface-variant">Klik atau drag foto ke sini</p>
                                <p class="font-body-sm text-body-sm text-outline mt-xs">JPG, PNG, WEBP — maks. 5 MB</p>
                            </div>
                        </template>
                        <template x-if="preview">
                            <div class="text-center">
                                <img :src="preview" class="max-h-40 max-w-full rounded-lg shadow-sm mx-auto mb-sm object-contain" alt="Preview">
                                <p class="font-label-sm text-label-sm text-on-surface-variant" x-text="fileName"></p>
                                <button type="button" @click.stop="preview = null; fileName = ''; $refs.photoInput.value = ''"
                                    class="mt-xs text-error font-label-sm text-label-sm hover:underline">Hapus foto</button>
                            </div>
                        </template>
                    </div>
                    <input type="file" name="receive_photo" accept="image/*" class="hidden" x-ref="photoInput"
                           @change="
                            const file = $event.target.files[0];
                            if (file) { preview = URL.createObjectURL(file); fileName = file.name; }
                           ">
                    @error('receive_photo') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block font-label-md text-label-md font-bold text-on-surface-variant mb-xs">
                        Catatan ke Pusat <span class="font-normal text-on-surface-variant">(opsional — tuliskan jika ada masalah)</span>
                    </label>
                    <textarea name="receive_notes" rows="3"
                        placeholder="Contoh: 2 pcs Roti Abon datang dalam kondisi rusak. Kotak pengiriman terbuka."
                        class="w-full font-body-md text-body-md bg-surface-container border border-outline-variant focus:border-primary focus:ring-0 rounded-xl text-on-surface px-sm py-sm resize-none">{{ old('receive_notes') }}</textarea>
                    @error('receive_notes') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Tombol Aksi --}}
        <div class="flex items-center justify-end gap-sm">
            <a href="{{ route('hendhys.transfer-to-branch.show', $transferToBranch->id) }}"
                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant bg-surface border border-outline-variant rounded-lg hover:bg-surface-container transition-colors">
                Batal
            </a>
            <button type="submit"
                onclick="return confirm('Konfirmasi penerimaan barang? Stok cabang akan bertambah sesuai qty yang diterima.')"
                class="inline-flex items-center gap-xs px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg font-bold shadow-sm hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                Konfirmasi Terima Barang
            </button>
        </div>

    </form>
</div>
@endsection
