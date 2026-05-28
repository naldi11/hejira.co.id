@extends('layouts.gudang')
@section('title', 'Buat Penerimaan Barang (GRN)')
@section('page-title', 'Penerimaan Barang')

@section('content')
<div x-data="grnForm()" class="space-y-6">

    {{-- Back --}}
    <div>
        <a href="{{ route('gudang.receiving.index') }}"
           class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 font-bold transition-colors group text-sm">
            <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Batal &amp; Kembali
        </a>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
    <div class="flex gap-3 bg-red-50 border border-red-200 rounded-2xl p-5">
        <span class="material-symbols-outlined text-red-500 text-[20px] shrink-0 mt-0.5">error</span>
        <div>
            <p class="text-sm font-bold text-red-700 mb-1">Perbaiki kesalahan berikut:</p>
            <ul class="text-sm text-red-600 list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-2xl text-sm font-bold">
        <span class="material-symbols-outlined text-green-500 text-[20px]">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('gudang.receiving.store') }}" method="POST"
          class="space-y-6" enctype="multipart/form-data">
        @csrf

        {{-- Hidden input for supplier_id when select is disabled --}}
        <template x-if="po_id !== ''">
            <input type="hidden" name="supplier_id" :value="supplier_id">
        </template>

        {{-- ═══ BAGIAN 1: Header GRN ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Informasi Penerimaan</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">

                {{-- Referensi PO --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        Referensi PO
                        <span class="normal-case font-normal text-slate-400">(opsional)</span>
                    </label>
                    <select id="po_select" name="po_id">
                        <option value="">— Input Manual (Tanpa PO) —</option>
                        @foreach($purchaseOrders as $poItem)
                        <option value="{{ $poItem->id }}"
                            {{ (isset($po) && $po->id == $poItem->id) || old('po_id') == $poItem->id ? 'selected' : '' }}>
                            {{ $poItem->po_number }} — {{ $poItem->supplier->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Supplier --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        Supplier <span class="text-red-500">*</span>
                    </label>
                    <select id="supplier_select" name="supplier_id" required
                            :disabled="po_id !== ''">
                        <option value="">Pilih supplier...</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal Terima --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        Tanggal Terima <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all outline-none">
                </div>

                {{-- No. Surat Jalan --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">No. Surat Jalan / Faktur</label>
                    <input type="text" name="reference_number" placeholder="Contoh: SJ/2024/001"
                           value="{{ old('reference_number') }}"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all outline-none">
                </div>

                {{-- Catatan --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan</label>
                    <textarea name="notes" rows="1" placeholder="Kondisi barang, kurir, dll..."
                              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all outline-none resize-none">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ═══ BAGIAN 2: Daftar Barang Masuk ═══ --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Verifikasi Barang Masuk</h3>
                    <p x-show="po_id" class="text-xs text-indigo-600 font-bold mt-0.5">
                        ↳ Item diisi otomatis dari PO
                    </p>
                </div>
                <button type="button" @click="addItem()" x-show="!po_id"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Tambah Item
                </button>
            </div>

            {{-- Loading state --}}
            <div x-show="loadingPo" class="py-12 text-center">
                <span class="material-symbols-outlined text-indigo-400 text-[40px] animate-spin block mb-2">progress_activity</span>
                <p class="text-sm text-slate-500 font-bold">Memuat data PO...</p>
            </div>

            {{-- Empty state --}}
            <div x-show="!loadingPo && items.length === 0" class="py-16 text-center">
                <span class="material-symbols-outlined text-slate-200 text-[56px] block mb-3">inventory_2</span>
                <p class="text-slate-400 font-bold text-sm">Pilih PO di atas atau klik "Tambah Item".</p>
            </div>

            {{-- Table --}}
            <div x-show="!loadingPo && items.length > 0" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-3 text-left" style="min-width:220px">Produk</th>
                            <th x-show="po_id" class="px-4 py-3 text-center" style="min-width:80px">Dipesan</th>
                            <th class="px-4 py-3 text-left" style="min-width:180px">Jumlah Terima</th>
                            <th class="px-4 py-3 text-left" style="min-width:180px">Batch &amp; Kedaluwarsa</th>
                            <th class="px-4 py-3 text-left" style="min-width:150px">Catatan</th>
                            <th class="px-3 py-3 text-center" style="min-width:48px"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 transition-colors">

                                {{-- Produk --}}
                                <td class="px-6 py-4">
                                    <template x-if="po_id">
                                        <div>
                                            <p class="font-bold text-slate-800 text-sm" x-text="item.product_name"></p>
                                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                            <input type="hidden" :name="`items[${index}][unit_id]`" :value="item.unit_id">
                                            <input type="hidden" :name="`items[${index}][hpp_price]`" :value="item.hpp_price">
                                        </div>
                                    </template>
                                    <template x-if="!po_id">
                                        <div>
                                            <select :id="'prod_'+index" :name="`items[${index}][product_id]`" required
                                                    x-init="$nextTick(() => {
                                                        let s = $('#prod_'+index);
                                                        s.select2({ placeholder: 'Pilih produk...', width: '100%', minimumResultsForSearch: 5 })
                                                         .on('select2:select', e => {
                                                              item.product_id = e.params.data.id;
                                                              onProductChange(item);
                                                          });
                                                        if (item.product_id) s.val(item.product_id).trigger('change.select2');
                                                    })"
                                                    class="w-full">
                                                <option value="">Pilih produk...</option>
                                                @foreach($products as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" :name="`items[${index}][unit_id]`" :value="item.unit_id">
                                            <input type="hidden" :name="`items[${index}][hpp_price]`" :value="item.hpp_price">
                                        </div>
                                    </template>
                                </td>

                                {{-- Dipesan (hanya saat PO) --}}
                                <td x-show="po_id" class="px-4 py-4 text-center">
                                    <span class="text-sm font-bold text-slate-400 tabular-nums"
                                          x-text="item.ordered_qty + ' ' + item.unit_name"></span>
                                </td>

                                {{-- Qty Terima (Bagus vs Rusak) --}}
                                <td class="px-4 py-4">
                                    <div class="space-y-2">
                                        {{-- Qty Bagus --}}
                                        <div class="flex items-center gap-2">
                                            <span class="w-14 text-xs font-bold text-green-600">Bagus:</span>
                                            <input type="number" :name="`items[${index}][quantity_bagus]`"
                                                   x-model.number="item.quantity_bagus"
                                                   min="0" step="1" required
                                                   class="w-20 px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-center text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all outline-none tabular-nums">
                                            <span class="text-xs font-semibold text-slate-400" x-text="item.unit_name"></span>
                                        </div>
                                        {{-- Qty Rusak --}}
                                        <div class="flex items-center gap-2">
                                            <span class="w-14 text-xs font-bold text-red-500">Rusak:</span>
                                            <input type="number" :name="`items[${index}][quantity_rusak]`"
                                                   x-model.number="item.quantity_rusak"
                                                   min="0" step="1" required
                                                   class="w-20 px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-center text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all outline-none tabular-nums">
                                            <span class="text-xs font-semibold text-slate-400" x-text="item.unit_name"></span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Batch & Expired --}}
                                <td class="px-4 py-4 space-y-2">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">No. Batch</label>
                                        <input type="text" x-model="item.batch_number"
                                               :name="`items[${index}][batch_number]`"
                                               placeholder="Contoh: NO BATCH"
                                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none uppercase">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tgl Kedaluwarsa</label>
                                        <input type="date" x-model="item.expired_date"
                                               :name="`items[${index}][expired_date]`"
                                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none">
                                    </div>
                                </td>

                                {{-- Catatan Item --}}
                                <td class="px-4 py-4">
                                    <textarea :name="`items[${index}][notes]`" x-model="item.notes" rows="2" placeholder="Catatan item..."
                                              class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none resize-none"></textarea>
                                </td>

                                {{-- Delete --}}
                                <td class="px-3 py-4 text-center">
                                    <button type="button" @click="removeItem(index)" x-show="!po_id"
                                            class="w-8 h-8 inline-flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══ BAGIAN 3: Foto & Submit ═══ --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Upload Foto --}}
            <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                    Foto Bukti Penerimaan
                    <span class="normal-case font-normal text-slate-400">(opsional, maks 5MB/foto)</span>
                </label>

                {{-- Preview Grid --}}
                <div x-show="photos.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mb-4">
                    <template x-for="(photo, pIdx) in photos" :key="pIdx">
                        <div class="relative group aspect-square rounded-xl overflow-hidden border border-slate-200 bg-slate-50">
                            <img :src="photo.url" class="w-full h-full object-cover">
                            
                            {{-- Overlay info / Hover --}}
                            <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button type="button" @click="removePhoto(pIdx)"
                                        class="w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center shadow-lg hover:bg-red-700 transition-colors active:scale-95">
                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                </button>
                            </div>
                        </div>
                    </template>
                    
                    {{-- Tambah Foto Card inside the grid --}}
                    <button type="button" onclick="document.getElementById('photoInput').click()"
                            class="flex flex-col items-center justify-center aspect-square rounded-xl border-2 border-dashed border-slate-200 hover:border-indigo-300 hover:bg-slate-50 transition-all text-slate-400">
                        <span class="material-symbols-outlined text-[24px] mb-1">add_a_photo</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Tambah</span>
                    </button>
                </div>

                {{-- Default dashed upload area (shown when no photos) --}}
                <div x-show="photos.length === 0" 
                     class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center hover:bg-slate-50 hover:border-indigo-300 transition-all cursor-pointer"
                     onclick="document.getElementById('photoInput').click()">
                    <span class="material-symbols-outlined text-slate-300 text-[36px] block mb-2">add_a_photo</span>
                    <p class="text-sm font-bold text-slate-500">Klik untuk upload foto</p>
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG, WebP</p>
                </div>

                <input type="file" id="photoInput" name="photos[]" accept="image/*" multiple class="hidden"
                       @change="handlePhotoChange($event)">
                <p x-show="photos.length > 0" class="text-xs font-bold text-indigo-600 mt-2" 
                   x-text="photos.length + ' foto dipilih'"></p>
            </div>

            {{-- Submit Card --}}
            <div class="bg-slate-900 rounded-2xl shadow-xl p-6 flex flex-col justify-between text-white">
                <div class="space-y-2 mb-6">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-[22px]">task_alt</span>
                    </div>
                    <h3 class="font-bold text-sm uppercase tracking-wider">Konfirmasi Penerimaan</h3>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Stok gudang aktif akan bertambah sesuai kuantitas Bagus yang diterima setelah dikonfirmasi.
                    </p>
                </div>
                <button type="submit"
                        class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-sm uppercase tracking-wider transition-all shadow-lg shadow-indigo-600/30 active:scale-[0.98]">
                    Konfirmasi &amp; Simpan
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // ── Init Select2 untuk PO select ──
    $('#po_select').select2({
        placeholder: '— Input Manual (Tanpa PO) —',
        allowClear: true,
        width: '100%'
    }).on('select2:select select2:unselect', function(e) {
        const val = $(this).val() || '';
        const comp = window._grnComp;
        if (comp) {
            comp.po_id = val;
            comp.loadPoDetails();
        }
    });

    // ── Init Select2 untuk Supplier select ──
    $('#supplier_select').select2({
        placeholder: 'Pilih supplier...',
        width: '100%'
    }).on('select2:select', function(e) {
        const comp = window._grnComp;
        if (comp) comp.supplier_id = $(this).val();
    });

    // Auto-load jika po_id sudah ada dari URL
    const comp = window._grnComp;
    if (comp && comp.po_id) {
        comp.loadPoDetails();
    }
});

function grnForm() {
    return {
        po_id: '{{ isset($po) && $po->id ? $po->id : '' }}',
        supplier_id: '',
        items: [],
        photos: [],
        products: @json($products),
        loadingPo: false,

        init() {
            // Simpan referensi ke komponen agar bisa diakses dari jQuery
            window._grnComp = this;

            if (this.po_id) {
                // PO sudah dipilih dari redirect — langsung load
                this.loadPoDetails();
            } else if (this.items.length === 0) {
                this.addItem();
            }
        },

        addItem() {
            this.items.push({
                product_id: '', product_name: '', 
                quantity_bagus: 1, quantity_rusak: 0,
                ordered_qty: 0, unit_id: '', unit_name: 'PCS',
                hpp_price: 0, batch_number: '', expired_date: '', notes: ''
            });
        },

        removeItem(index) {
            const s = $('#prod_' + index);
            if (s.length && s.hasClass('select2-hidden-accessible')) s.select2('destroy');
            this.items.splice(index, 1);
        },

        onProductChange(item) {
            if (!item.product_id) { item.unit_id = ''; item.unit_name = 'PCS'; item.hpp_price = 0; return; }
            const p = this.products.find(x => x.id == item.product_id);
            if (!p) return;
            item.unit_id   = p.unit_id ?? '';
            item.unit_name = p.unit?.abbreviation ?? 'PCS';
            item.hpp_price = parseFloat(p.hpp) || 0;
        },

        loadPoDetails() {
            if (!this.po_id) {
                this.items = [];
                this.addItem();
                this.supplier_id = '';
                $('#supplier_select').val('').trigger('change.select2');
                return;
            }

            this.loadingPo = true;
            this.items = [];

            axios.get(`/gudang/po/${this.po_id}/json`)
                .then(res => {
                    const po = res.data;
                    this.supplier_id = po.supplier_id;

                    // Update Select2 supplier
                    $('#supplier_select').val(po.supplier_id).trigger('change.select2');

                    this.items = po.details.map(d => ({
                        product_id:     d.product_id,
                        product_name:   d.product_name,
                        quantity_bagus: Math.max(0, d.quantity_ordered - d.quantity_received),
                        quantity_rusak: 0,
                        ordered_qty:    d.quantity_ordered,
                        unit_id:        d.unit_id,
                        unit_name:      d.unit?.abbreviation ?? 'PCS',
                        hpp_price:      d.price,
                        batch_number:   '',
                        expired_date:   '',
                        notes:          ''
                    }));
                })
                .catch(() => {
                    alert('Gagal memuat detail PO.');
                })
                .finally(() => {
                    this.loadingPo = false;
                });
        },

        handlePhotoChange(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                if (this.photos.some(p => p.file.name === file.name && p.file.size === file.size)) return;
                this.photos.push({
                    file: file,
                    url: URL.createObjectURL(file)
                });
            });
            this.syncFileInput();
        },

        removePhoto(index) {
            URL.revokeObjectURL(this.photos[index].url);
            this.photos.splice(index, 1);
            this.syncFileInput();
        },

        syncFileInput() {
            const dt = new DataTransfer();
            this.photos.forEach(p => dt.items.add(p.file));
            document.getElementById('photoInput').files = dt.files;
        }
    }
}
</script>
@endpush
