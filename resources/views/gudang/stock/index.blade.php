@extends('layouts.gudang')
@section('title', 'Stok Gudang Utama')
@section('page-title', 'Inventori Gudang')

@section('content')
<div x-data="{ adjustModalOpen: false, selectedProduct: null, quantity: 0, notes: '', unitId: '' }" class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Stok Gudang Utama</h2>
            <p class="text-sm text-slate-500 font-medium">Monitoring saldo inventori dan penyesuaian fisik (Stock Opname)</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('gudang.stock.movements') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[20px]">history</span>
                Kartu Stok
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <form method="GET" action="{{ route('gudang.stock.index') }}" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[300px] relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk atau kode..." 
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                </div>
                
                <label class="flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition-all">
                    <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') == '1' ? 'checked' : '' }} 
                           class="w-5 h-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500 transition-all">
                    <span class="text-sm font-bold text-slate-600">Stok Menipis</span>
                </label>

                <button type="submit" class="px-6 py-3 bg-slate-900 text-white rounded-2xl text-sm font-bold hover:bg-indigo-600 transition-all shadow-lg shadow-slate-900/10">
                    Terapkan
                </button>

                @if(request()->anyFilled(['search', 'low_stock']))
                    <a href="{{ route('gudang.stock.index') }}" class="w-11 h-11 flex items-center justify-center bg-rose-50 text-rose-600 rounded-2xl hover:bg-rose-100 transition-all" title="Reset Filter">
                        <span class="material-symbols-outlined">refresh</span>
                    </a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Info Produk</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kategori</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Safety Stock</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Stok Akhir</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stocks as $item)
                    @php 
                        $qty = floatval($item->current_stock ?? 0);
                        $isLow = $qty <= $item->stock_min;
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-slate-800 tracking-tight group-hover:text-indigo-600 transition-colors">{{ $item->name }}</span>
                                <span class="text-[10px] font-bold text-slate-400 font-mono uppercase tracking-widest mt-0.5">{{ $item->code }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-600">{{ $item->category->name ?? '-' }}</span>
                                <span class="text-[10px] text-slate-400 capitalize font-medium">{{ str_replace('_', ' ', $item->jenis) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-lg">{{ floatval($item->stock_min) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl {{ $isLow ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' }}">
                                <span class="text-sm font-black tabular-nums">{{ number_format($qty, 0, ',', '.') }}</span>
                                <span class="text-[10px] font-bold uppercase">{{ $item->unit->abbreviation ?? 'PCS' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button type="button" 
                                    @click="adjustModalOpen = true; selectedProduct = { id: '{{ $item->id }}', name: '{{ addslashes($item->name) }}', stock: {{ $qty }}, unit_id: '{{ $item->unit_id }}', unit_name: '{{ $item->unit->abbreviation ?? 'PCS' }}' }; quantity = {{ $qty }}; notes = ''; unitId = '{{ $item->unit_id }}';"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[16px]">edit_note</span>
                                Opname
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <span class="material-symbols-outlined text-slate-200 text-[64px] mb-4">inventory_2</span>
                                <p class="text-slate-400 font-bold italic">Tidak ada data produk ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stocks->hasPages())
        <div class="p-6 border-t border-slate-100">
            {{ $stocks->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Adjustment (Modern Glassmorphism Style) --}}
    <div x-show="adjustModalOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" x-show="adjustModalOpen" x-transition.opacity @click="adjustModalOpen = false"></div>
        
        <div x-show="adjustModalOpen" 
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-lg bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-200">
            
            <div class="absolute top-0 right-0 p-6">
                <button @click="adjustModalOpen = false" class="w-10 h-10 flex items-center justify-center rounded-2xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form method="POST" action="{{ route('gudang.stock.adjust') }}" class="p-8 sm:p-10">
                @csrf
                <div class="space-y-8">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-[1.25rem] flex items-center justify-center shadow-inner">
                            <span class="material-symbols-outlined text-[28px]">balance</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 font-headline tracking-tight">Stock Opname</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Penyesuaian Saldo Fisik</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <input type="hidden" name="product_id" :value="selectedProduct?.id">
                        <input type="hidden" name="unit_id" :value="unitId">
                        
                        <div class="p-5 bg-slate-50 rounded-3xl border border-slate-100 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Produk Terpilih</span>
                                <span class="text-[10px] font-black text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-lg" x-text="selectedProduct?.id"></span>
                            </div>
                            <p class="text-base font-black text-slate-800 font-headline leading-tight" x-text="selectedProduct?.name"></p>
                            <div class="flex items-center gap-2 pt-2 border-t border-slate-200/50">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Stok Sistem:</span>
                                <span class="text-sm font-black text-slate-700" x-text="selectedProduct?.stock + ' ' + selectedProduct?.unit_name"></span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Stok Fisik Sebenarnya <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="quantity" x-model.number="quantity" min="0" step="1" required
                                       @change="quantity = Math.max(0, Math.round(quantity || 0))"
                                       class="w-full pl-6 pr-16 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-lg font-black text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:outline-none transition-all outline-none tabular-nums">
                                <span class="absolute right-6 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400" x-text="selectedProduct?.unit_name"></span>
                            </div>
                            <div class="flex items-center gap-2 px-2" x-show="quantity != selectedProduct?.stock">
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div>
                                <p class="text-[11px] font-bold text-amber-600 italic">
                                    Akan dicatat sebagai selisih <span x-text="quantity - selectedProduct?.stock > 0 ? '+' : ''"></span><span x-text="quantity - selectedProduct?.stock"></span> units.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Alasan Penyesuaian <span class="text-rose-500">*</span></label>
                            <textarea name="notes" x-model="notes" required rows="3" placeholder="Contoh: Barang rusak, hilang, salah input..."
                                      class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:outline-none transition-all outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" class="flex-1 px-8 py-4 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl shadow-slate-900/20 active:scale-[0.98]">
                            Simpan Perubahan
                        </button>
                        <button type="button" @click="adjustModalOpen = false" class="px-8 py-4 bg-slate-100 text-slate-600 rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-slate-200 transition-all">
                            Batal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
