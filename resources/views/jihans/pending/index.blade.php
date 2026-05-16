@extends('layouts.jihans')
@section('title', 'Transaksi Pending')
@section('page-title', 'Daftar Transaksi Kasir (Hold/Pending)')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
    <a href="{{ route('jihans.pos.index') }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali ke Kasir
    </a>

    <form action="{{ route('jihans.pending.index') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
        <div class="relative flex-1 sm:w-64">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Pending atau Pelanggan..." 
                   class="w-full pl-8 pr-4 py-2 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900">
            Cari
        </button>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden" x-data="pendingManager()">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 font-medium">Tanggal</th>
                    <th class="px-6 py-4 font-medium">No. Pending</th>
                    <th class="px-6 py-4 font-medium">Pelanggan</th>
                    <th class="px-6 py-4 font-medium">Kasir</th>
                    <th class="px-6 py-4 font-medium">Catatan</th>
                    <th class="px-6 py-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($pendings as $pending)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ \Carbon\Carbon::parse($pending->date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-mono font-semibold text-gray-800">{{ $pending->pending_number }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800">{{ $pending->customer_name }}</p>
                        <p class="text-xs text-gray-500 capitalize">{{ $pending->customer_type }}</p>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $pending->creator->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-gray-500 max-w-xs truncate">
                        {{ $pending->notes ?: '-' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <button @click="showDetail({{ $pending->id }})" class="text-orange-600 hover:text-orange-900 font-medium text-sm">Lihat Item</button>
                            <form action="{{ route('jihans.pending.destroy', $pending) }}" method="POST" onsubmit="return confirm('Hapus data pending ini secara permanen?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 font-medium text-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p>Tidak ada transaksi pending saat ini.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pendings->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $pendings->links() }}
    </div>
    @endif

    {{-- Modal Detail --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="modalOpen = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-lg">Detail Transaksi Pending</h3>
                <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                <template x-if="isLoading">
                    <div class="flex justify-center py-8">
                        <svg class="animate-spin w-8 h-8 text-orange-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                </template>

                <template x-if="!isLoading && detailData">
                    <div>
                        <div class="flex justify-between items-center mb-4 bg-orange-50 p-4 rounded-lg border border-orange-100">
                            <div>
                                <p class="text-xs text-gray-500">No. Pending</p>
                                <p class="font-bold text-gray-800" x-text="detailData.pending_number"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Pelanggan</p>
                                <p class="font-bold text-gray-800" x-text="detailData.customer_name"></p>
                            </div>
                        </div>

                        <h4 class="font-semibold text-gray-700 mb-3">Daftar Item:</h4>
                        <ul class="divide-y divide-gray-100 border border-gray-100 rounded-lg">
                            <template x-for="item in detailData.details" :key="item.id">
                                <li class="p-3 flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-gray-800 text-sm" x-text="item.product_name"></p>
                                        <p class="text-xs text-gray-500"><span x-text="item.quantity"></span> <span x-text="item.unit?.abbreviation || 'pcs'"></span> x <span x-text="formatCurrency(item.price)"></span></p>
                                    </div>
                                    <div class="text-right font-bold text-gray-900 text-sm" x-text="formatCurrency(item.total)">
                                    </div>
                                </li>
                            </template>
                        </ul>
                        
                        <div class="mt-4 p-3 bg-blue-50 text-blue-700 text-sm rounded-lg flex gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p>Untuk melanjutkan transaksi, silakan input ulang barang di halaman Kasir, atau hapus pending ini jika batal.</p>
                        </div>
                    </div>
                </template>
            </div>
            
            <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-end">
                <button @click="modalOpen = false" class="px-5 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors shadow-sm">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pendingManager', () => ({
            modalOpen: false,
            isLoading: false,
            detailData: null,
            
            async showDetail(id) {
                this.modalOpen = true;
                this.isLoading = true;
                this.detailData = null;
                
                try {
                    const response = await fetch(`/jihans/pending/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (response.ok) {
                        this.detailData = await response.json();
                    }
                } catch (error) {
                    console.error('Error fetching detail:', error);
                } finally {
                    this.isLoading = false;
                }
            },
            
            formatCurrency(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
            }
        }));
    });
</script>
@endsection
