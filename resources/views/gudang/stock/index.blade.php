@extends('layouts.gudang')
@section('title', 'Stok Gudang Utama')
@section('page-title', 'Gudang — Stok Barang')

@section('content')
<div x-data="{ adjustModalOpen: false, selectedProduct: null, quantity: 0, notes: '', unitId: '' }">

    <div class="flex items-center justify-between mt-4 mb-5">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Stok Gudang Utama</h2>
            <p class="text-sm text-gray-400">Master inventori dan saldo stok saat ini</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('gudang.stock.movements') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Histori Pergerakan (Kartu Stok)
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center gap-4">
            <form method="GET" action="{{ route('gudang.stock.index') }}" class="flex-1 flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk atau kode..." 
                       class="w-1/3 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <select name="jenis" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    <option value="">Semua Jenis</option>
                    <option value="bahan_baku" {{ request('jenis') == 'bahan_baku' ? 'selected' : '' }}>Bahan Baku</option>
                    <option value="bahan_jadi" {{ request('jenis') == 'bahan_jadi' ? 'selected' : '' }}>Bahan Jadi</option>
                    <option value="lainnya" {{ request('jenis') == 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
                <label class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 px-3 py-2 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') == '1' ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500">
                    Stok Menipis
                </label>
                <button type="submit" class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
                @if(request()->anyFilled(['search', 'jenis', 'low_stock']))
                    <a href="{{ route('gudang.stock.index') }}" class="text-gray-400 hover:text-red-500 px-2 py-2 flex items-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Nama Produk</th>
                        <th class="px-4 py-3 font-medium">Kategori / Jenis</th>
                        <th class="px-4 py-3 font-medium text-right">Stok Min</th>
                        <th class="px-4 py-3 font-medium text-right">Stok Saat Ini</th>
                        <th class="px-4 py-3 font-medium">Satuan</th>
                        <th class="px-4 py-3 font-medium text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($stocks as $item)
                    @php 
                        $qty = $item->current_stock ?? 0;
                        $isLow = $qty <= $item->stock_min;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $item->code }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $item->name }}</td>
                        <td class="px-4 py-3">
                            <span class="block text-gray-800">{{ $item->category->name ?? '-' }}</span>
                            <span class="text-xs text-gray-400 capitalize">{{ str_replace('_', ' ', $item->jenis) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ floatval($item->stock_min) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center justify-center px-2 py-1 rounded text-sm font-bold {{ $isLow ? 'bg-red-100 text-red-700' : 'text-gray-800' }}">
                                {{ floatval($qty) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->unit->abbreviation ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" 
                                    @click="adjustModalOpen = true; selectedProduct = { id: '{{ $item->id }}', name: '{{ addslashes($item->name) }}', stock: {{ $qty }}, unit_id: '{{ $item->unit_id }}', unit_name: '{{ $item->unit->abbreviation ?? '-' }}' }; quantity = {{ $qty }}; notes = ''; unitId = '{{ $item->unit_id }}';"
                                    class="text-indigo-600 hover:text-indigo-800 text-xs font-medium border border-indigo-200 bg-indigo-50 px-2 py-1 rounded">
                                Sesuaikan (SO)
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">Tidak ada data produk.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($stocks->hasPages())
        <div class="p-4 border-t border-gray-100">
            {{ $stocks->links() }}
        </div>
        @endif
    </div>

    {{-- Modal Adjustment --}}
    <div x-show="adjustModalOpen" style="display: none;" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="adjustModalOpen" x-transition.opacity></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="adjustModalOpen" x-transition
                     @click.away="adjustModalOpen = false"
                     class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <form method="POST" action="{{ route('gudang.stock.adjust') }}">
                        @csrf
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">Penyesuaian Stok (Stock Opname)</h3>
                                    <div class="mt-4 space-y-4">
                                        <input type="hidden" name="product_id" :value="selectedProduct?.id">
                                        <input type="hidden" name="unit_id" :value="unitId">
                                        
                                        <div>
                                            <p class="text-sm font-medium text-gray-500 mb-1">Produk</p>
                                            <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700" x-text="selectedProduct?.name"></div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500 mb-1">Stok Sistem Saat Ini</p>
                                            <div class="text-sm font-bold text-gray-700"><span x-text="selectedProduct?.stock"></span> <span class="text-xs text-gray-500 font-normal" x-text="selectedProduct?.unit_name"></span></div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok Fisik Sebenarnya (Final) <span class="text-red-500">*</span></label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="quantity" x-model="quantity" min="0" step="0.001" required
                                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none text-right">
                                                <span class="text-sm text-gray-500 w-12" x-text="selectedProduct?.unit_name"></span>
                                            </div>
                                            <p class="text-xs text-red-500 mt-1" x-show="quantity != selectedProduct?.stock">
                                                Sistem akan membuat selisih otomatis.
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penyesuaian <span class="text-red-500">*</span></label>
                                            <input type="text" name="notes" x-model="notes" required placeholder="Contoh: Barang rusak, hilang, salah input..."
                                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:ml-3 sm:w-auto">
                                Simpan Penyesuaian
                            </button>
                            <button type="button" @click="adjustModalOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
