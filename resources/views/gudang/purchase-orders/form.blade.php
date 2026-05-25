@extends('layouts.gudang')
@section('title', ($po->id ? 'Edit' : 'Buat') . ' Purchase Order')
@section('page-title', 'Purchase Order')

@php
    $formattedDetails = [];
    if ($po->id) {
        $formattedDetails = $po->details->map(function($d) {
            return [
                'product_id' => $d->product_id,
                'quantity' => $d->quantity_ordered,
                'unit_id' => $d->unit_id,
                'price' => (float)$d->price,
            ];
        })->toArray();
    }
@endphp

@section('content')
<div x-data="poForm()" class="max-w-6xl mx-auto space-y-8 pb-20">

    {{-- Header & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('gudang.po.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Batal & Kembali
        </a>
        <h2 class="text-xl font-black text-slate-800 font-headline tracking-tight">{{ $po->id ? 'Edit Dokumen PO' : 'Draft Pesanan Baru' }}</h2>
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

    <form action="{{ $po->id ? route('gudang.po.update', $po) : route('gudang.po.store') }}" method="POST" class="space-y-8">
        @csrf
        @if($po->id) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Left: Main Info --}}
            <div class="lg:col-span-2 space-y-8">
                
                {{-- Metadata Card --}}
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-8 sm:p-10 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Supplier / Vendor <span class="text-rose-500">*</span></label>
                            <select name="supplier_id" required 
                                    class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
                                <option value="">Pilih Supplier...</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ old('supplier_id', $po->supplier_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Tanggal Pesanan <span class="text-rose-500">*</span></label>
                            <input type="date" name="date" value="{{ old('date', $po->date?->format('Y-m-d') ?? date('Y-m-d')) }}" required
                                   class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
                        </div>
                    </div>

                    {{-- Items Section --}}
                    <div class="pt-8 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">Daftar Barang</h3>
                            <button type="button" @click="addItem()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                Tambah Item
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        <th class="pb-4 pl-2" style="width: 40%; min-width: 250px;">Pilih Produk</th>
                                        <th class="pb-4 pl-2" style="width: 15%; min-width: 120px;">Satuan</th>
                                        <th class="pb-4 text-center" style="width: 12%; min-width: 90px;">Qty</th>
                                        <th class="pb-4 text-right" style="width: 18%; min-width: 160px;">Harga Satuan</th>
                                        <th class="pb-4 text-right" style="width: 12%; min-width: 120px;">Subtotal</th>
                                        <th class="pb-4 text-center" style="width: 3%; min-width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="group">
                                            <td class="py-4 pr-4" style="width: 40%; min-width: 250px;">
                                                <select x-model="item.product_id" :name="'items['+index+'][product_id]'" required
                                                        @change="onProductChange(item)"
                                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none">
                                                    <option value="">Pilih Produk...</option>
                                                    @foreach($products as $p)
                                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="py-4 pr-2" style="width: 15%; min-width: 120px;">
                                                <select x-model="item.unit_id" :name="'items['+index+'][unit_id]'" required
                                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none">
                                                    <option value="">Pilih Satuan...</option>
                                                    @foreach($units as $u)
                                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="py-4 px-2" style="width: 12%; min-width: 90px;">
                                                <input type="number" x-model.number="item.quantity" :name="'items['+index+'][quantity]'" min="1" step="any" required
                                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-black text-center text-slate-900 focus:bg-white focus:border-indigo-500 transition-all outline-none tabular-nums">
                                            </td>
                                            <td class="py-4 px-2" style="width: 18%; min-width: 160px;">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">Rp</span>
                                                    <input type="number" x-model.number="item.price" :name="'items['+index+'][price]'" min="0" step="any" required
                                                           class="w-full pl-8 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-black text-right text-slate-900 focus:bg-white focus:border-indigo-500 transition-all outline-none tabular-nums">
                                                </div>
                                            </td>
                                            <td class="py-4 px-2 text-right" style="width: 12%; min-width: 120px;">
                                                <span class="text-xs font-black text-slate-900 tabular-nums" x-text="formatNumber(item.quantity * item.price)"></span>
                                            </td>
                                            <td class="py-4 pl-4 text-center" style="width: 3%; min-width: 50px;">
                                                <button type="button" @click="removeItem(index)" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:text-rose-500 transition-colors">
                                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="items.length === 0" class="py-12 text-center bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                            <span class="material-symbols-outlined text-slate-300 text-[48px] mb-2">playlist_add</span>
                            <p class="text-slate-400 font-bold">Belum ada item ditambahkan.</p>
                            <button type="button" @click="addItem()" class="mt-4 text-indigo-600 text-xs font-black uppercase tracking-widest hover:underline">Klik untuk tambah</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Sidebar Summary --}}
            <div class="space-y-8">
                
                {{-- Date & Note --}}
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8 space-y-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Catatan Tambahan</label>
                        <textarea name="notes" rows="4" placeholder="Instruksi pengiriman, termin pembayaran, dll..."
                                  class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all outline-none resize-none">{{ old('notes', $po->notes) }}</textarea>
                    </div>
                </div>

                {{-- Grand Total Card --}}
                <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-slate-900/20 p-10 text-white relative overflow-hidden">
                    <div class="relative z-10 space-y-6">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-indigo-400">payments</span>
                            <h3 class="text-sm font-black uppercase tracking-[0.2em]">Ringkasan Biaya</h3>
                        </div>
                        
                        <div class="space-y-4 pt-4 border-t border-white/10">
                            <div class="flex justify-between items-center text-slate-400">
                                <span class="text-xs font-bold uppercase tracking-widest">Total Item</span>
                                <span class="text-sm font-black tabular-nums" x-text="items.length"></span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em]">Total Tagihan</span>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-indigo-400 text-lg font-black italic">Rp</span>
                                    <span class="text-4xl font-black tracking-tighter tabular-nums" x-text="formatNumber(calculateTotal())"></span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-3xl font-black uppercase tracking-[0.2em] text-xs transition-all shadow-xl shadow-indigo-600/30 active:scale-[0.98] mt-4">
                            Simpan & Kirim PO
                        </button>
                    </div>
                    
                    {{-- Decoration --}}
                    <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function poForm() {
        return {
            items: @json(old('items', $formattedDetails)),
            products: @json($products),
            
            init() {
                if(this.items.length === 0) {
                    this.addItem();
                }
            },

            addItem() {
                this.items.push({
                    product_id: '',
                    unit_id: '',
                    quantity: 1,
                    price: 0
                });
            },

            removeItem(index) {
                this.items.splice(index, 1);
            },

            onProductChange(item) {
                if (!item.product_id) {
                    item.unit_id = '';
                    item.price = 0;
                    return;
                }
                const prod = this.products.find(p => p.id == item.product_id);
                if (prod) {
                    item.unit_id = prod.unit_id || '';
                    item.price = parseFloat(prod.hpp) || 0;
                }
            },

            calculateTotal() {
                return this.items.reduce((sum, item) => sum + (item.quantity * item.price), 0);
            },

            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
        }
    }
</script>
@endpush
