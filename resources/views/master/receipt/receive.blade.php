@extends($layout)
@section('title', 'Terima Barang dari Gudang')
@section('page-title', 'Terima Barang: ' . $transferOut->transfer_number)

@section('content')
<div class="max-w-4xl mx-auto p-margin-mobile md:p-margin-desktop">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route($routePrefix . 'transfer-requests.index') }}" class="text-primary hover:underline font-medium text-sm flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Kembali ke Daftar Request
        </a>
    </div>

    <form action="{{ route(Route::currentRouteName() == $routePrefix . 'transfer-requests.receive-form' ? $routePrefix . 'transfer-requests.receive' : $routePrefix . 'transfer-requests.receive-gudang', $transferOut->id) }}" method="POST" enctype="multipart/form-data" class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant overflow-hidden">
        @csrf
        
        <div class="p-6 border-b border-outline-variant bg-surface-container-low">
            <h2 class="text-xl font-bold text-on-surface">Konfirmasi Penerimaan dari Gudang Utama</h2>
            <p class="text-sm text-on-surface-variant mt-1">Gudang telah mengirim barang berikut. Silakan masukkan jumlah aktual yang Anda terima.</p>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto mb-6">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface-container border-b border-outline-variant text-on-surface-variant">
                            <th class="py-3 px-4 font-semibold">Produk Gudang</th>
                            <th class="py-3 px-4 font-semibold text-center w-32">Qty Dikirim</th>
                            <th class="py-3 px-4 font-semibold text-center w-40">Qty Diterima</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transferOut->details as $index => $detail)
                        <tr class="border-b border-surface-container last:border-0">
                            <td class="py-3 px-4 font-medium text-on-surface">
                                {{ $detail->product->name }}
                                <span class="block text-[10px] text-on-surface-variant uppercase font-mono">{{ $detail->product->code }}</span>
                            </td>
                            <td class="py-3 px-4 text-center text-on-surface-variant">
                                {{ (float) $detail->quantity }} {{ $detail->unit->abbreviation ?? '' }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <input type="number" name="received_quantities[{{ $detail->id }}]" 
                                           value="{{ (float) $detail->quantity }}" 
                                           max="{{ (float) $detail->quantity }}" 
                                           min="0" step="0.001" required
                                           class="w-full text-center bg-surface-container-low border border-outline-variant rounded-md focus:border-primary focus:ring-primary text-sm font-bold text-on-surface">
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-4 border-t border-outline-variant pt-6">
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Catatan ke Gudang (Opsional)</label>
                    <p class="text-xs text-on-surface-variant mb-2">Tuliskan jika ada kendala saat pengiriman atau barang tidak sesuai.</p>
                    <textarea name="receive_notes" rows="3" class="w-full rounded-lg bg-surface-container-low border border-outline-variant focus:border-primary focus:ring-primary text-sm text-on-surface" placeholder="Contoh: Barang diterima dalam kondisi baik, namun satu karung bocor..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-on-surface mb-1">Foto Bukti Penerimaan (Opsional)</label>
                    <p class="text-xs text-on-surface-variant mb-2">Unggah foto saat serah terima atau kondisi barang (Maks 5MB).</p>
                    <input type="file" name="receive_photo" accept="image/*" class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-fixed file:text-on-primary-fixed-variant hover:file:bg-primary-fixed-dim cursor-pointer border border-outline-variant rounded-md p-1">
                </div>
            </div>
        </div>

        <div class="p-6 bg-surface-container-low border-t border-outline-variant flex justify-end">
            <button type="submit" onclick="return confirm('Konfirmasi penerimaan barang? Stok entitas Anda akan bertambah sesuai Qty Diterima.')" class="bg-primary text-on-primary px-6 py-2.5 rounded-lg text-sm font-bold transition-all shadow-sm flex items-center gap-2 hover:bg-opacity-90 active:scale-95">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                Simpan & Update Stok
            </button>
        </div>
    </form>
</div>
@endsection