@extends('layouts.gudang')
@section('title', 'Buat Penerimaan Barang (GRN)')
@section('page-title', 'Penerimaan Barang')

@section('content')
<div x-data="grnForm()" class="max-w-6xl mx-auto space-y-8 pb-20">

    {{-- Header & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('gudang.receiving.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Batal & Kembali
        </a>
        <h2 class="text-xl font-black text-slate-800 font-headline tracking-tight">Dokumen Penerimaan Barang (GRN)</h2>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
    <div class="bg-rose-50 border border-rose-200 rounded-3xl p-6 text-rose-800 space-y-2">
        <div class="flex items-center gap-2 font-black text-sm uppercase tracking-wider">
            <span class="material-symbols-outlined text-[20px]">error</span>
            Terjadi Kesalahan Validasi
        </div>
        <ul class="list-disc pl-5 text-xs font-semibold space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('gudang.receiving.store') }}" method="POST" class="space-y-8">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Left: Main Info --}}
            <div class="lg:col-span-2 space-y-8">
                
                {{-- Metadata Card --}}
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-8 sm:p-10 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Referensi PO <span class="text-slate-400 font-medium">(Opsional)</span></label>
                            <div>
                                <select name="purchase_order_id" x-model="po_id" @change="loadPoDetails()"
                                        class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
                                    <option value="">Input Manual (Tanpa PO)</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->id }}">{{ $po->po_number }} - {{ $po->supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Supplier <span class="text-rose-500">*</span></label>
                            <div>
                                <select name="supplier_id" x-model="supplier_id" required :disabled="po_id != ''"
                                        class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none disabled:opacity-60 disabled:cursor-not-allowed">
                                    <option value="">Pilih Supplier...</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Items Section --}}
                    <div class="pt-8 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">Verifikasi Barang Masuk</h3>
                            <button type="button" @click="addItem()" x-show="!po_id" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                Tambah Item
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        <th class="pb-4 pl-2">Produk</th>
                                        <th x-show="po_id" class="pb-4 text-center" style="width: 100px;">Pesan</th>
                                        <th class="pb-4 text-center" style="width: 120px;">Terima</th>
                                        <th class="pb-4 text-center" style="width: 80px;">Satuan</th>
                                        <th class="pb-4 text-center" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="group">
                                            <td class="py-4 pr-4">
                                                <div>
                                                    <select x-model="item.product_id" :name="'items['+index+'][product_id]'" required :disabled="po_id != ''"
                                                            @change="onProductChange(item)"
                                                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none disabled:opacity-100 disabled:cursor-not-allowed">
                                                        <option value="">Pilih Produk...</option>
                                                        @foreach($products as $p)
                                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id" x-if="po_id != ''">
                                            </td>
                                            <td x-show="po_id" class="py-4 px-2 text-center">
                                                <span class="text-xs font-bold text-slate-400 tabular-nums" x-text="item.ordered_qty"></span>
                                            </td>
                                            <td class="py-4 px-2">
                                                <input type="number" x-model.number="item.quantity" :name="'items['+index+'][quantity]'" min="0" :max="po_id ? item.ordered_qty : null" step="any" required
                                                       class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-100 rounded-xl text-xs font-black text-center text-slate-900 focus:bg-white focus:border-indigo-500 transition-all outline-none tabular-nums">
                                            </td>
                                            <td class="py-4 px-2 text-center">
                                                <span class="text-[10px] font-black text-slate-500 uppercase bg-slate-100 px-2 py-1 rounded-lg" x-text="item.unit_name || 'PCS'"></span>
                                                <input type="hidden" :name="'items['+index+'][unit_id]'" :value="item.unit_id">
                                                <input type="hidden" :name="'items['+index+'][hpp_price]'" :value="item.hpp_price">
                                            </td>
                                            <td class="py-4 pl-4 text-center">
                                                <button type="button" @click="removeItem(index)" x-show="!po_id" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:text-rose-500 transition-colors">
                                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="items.length === 0" class="py-12 text-center bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                            <span class="material-symbols-outlined text-slate-300 text-[48px] mb-2">inventory_2</span>
                            <p class="text-slate-400 font-bold">Silakan pilih PO atau tambah item manual.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Sidebar Details --}}
            <div class="space-y-8">
                
                {{-- Ref Info --}}
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8 space-y-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Tanggal Terima <span class="text-rose-500">*</span></label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required
                               class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">No. Surat Jalan / Faktur</label>
                        <input type="text" name="reference_number" placeholder="Contoh: SJ/2024/001"
                               class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Catatan</label>
                        <textarea name="notes" rows="3" placeholder="Kondisi barang, kurir, dll..."
                                  class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all outline-none resize-none"></textarea>
                    </div>
                </div>

                {{-- Action Card --}}
                <div class="bg-[#0f172a] rounded-[2.5rem] shadow-2xl shadow-slate-900/20 p-10 text-white relative overflow-hidden">
                    <div class="relative z-10 space-y-6">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-indigo-400">task_alt</span>
                            <h3 class="text-sm font-black uppercase tracking-[0.2em]">Finalisasi</h3>
                        </div>
                        
                        <p class="text-slate-400 text-xs font-medium leading-relaxed">
                            Dengan mengklik tombol di bawah, stok di gudang akan otomatis bertambah sesuai dengan jumlah yang Anda input.
                        </p>

                        <button type="submit" class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-3xl font-black uppercase tracking-[0.2em] text-xs transition-all shadow-xl shadow-indigo-600/30 active:scale-[0.98]">
                            Konfirmasi Penerimaan
                        </button>
                    </div>
                    
                    <span class="material-symbols-outlined absolute -right-6 -bottom-6 text-white/5 text-[140px] rotate-12">local_shipping</span>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function grnForm() {
        return {
            po_id: '',
            supplier_id: '',
            items: [],
            products: @json($products),
            
            init() {
                // Initialize with one empty item if manual
                if(this.items.length === 0) this.addItem();
            },

            addItem() {
                this.items.push({
                    product_id: '',
                    quantity: 1,
                    ordered_qty: 0,
                    unit_id: '',
                    unit_name: 'PCS',
                    hpp_price: 0
                });
            },

            removeItem(index) {
                this.items.splice(index, 1);
            },

            onProductChange(item) {
                if (!item.product_id) {
                    item.unit_id = '';
                    item.unit_name = 'PCS';
                    item.hpp_price = 0;
                    return;
                }
                const prod = this.products.find(p => p.id == item.product_id);
                if (prod) {
                    item.unit_id = prod.unit_id || '';
                    item.unit_name = prod.unit ? prod.unit.abbreviation : 'PCS';
                    item.hpp_price = parseFloat(prod.hpp) || 0;
                }
            },

            loadPoDetails() {
                if(!this.po_id) {
                    this.items = [];
                    this.addItem();
                    this.supplier_id = '';
                    return;
                }

                // Fetch PO details via AJAX
                axios.get(`/gudang/purchase-orders/${this.po_id}/json`)
                    .then(res => {
                        const po = res.data;
                        this.supplier_id = po.supplier_id;
                        
                        this.items = po.details.map(d => {
                            const remaining = (parseFloat(d.quantity_ordered) || 0) - (parseFloat(d.quantity_received) || 0);
                            return {
                                product_id: d.product_id,
                                quantity: Math.max(0, remaining), // Default to remaining quantity
                                ordered_qty: parseFloat(d.quantity_ordered) || 0,
                                unit_id: d.unit_id,
                                unit_name: d.unit ? d.unit.abbreviation : 'PCS',
                                hpp_price: parseFloat(d.price) || 0
                            };
                        });
                    })
                    .catch(err => {
                        alert('Gagal memuat detail PO. Pastikan koneksi stabil.');
                    });
            }
        }
    }
</script>
@endpush
