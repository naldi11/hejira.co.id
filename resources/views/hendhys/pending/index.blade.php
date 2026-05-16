@extends('layouts.hendhys')
@section('title', 'Transaksi Pending')
@section('page-title', 'Daftar Transaksi Pending / Hold')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200" x-data="pendingManager()">
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('hendhys.pending.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama Customer / No..." class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] min-w-[250px]">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">Filter</button>
            @if(request()->filled('search'))
                <a href="{{ route('hendhys.pending.index') }}" class="text-sm text-red-500 hover:text-red-700">Reset</a>
            @endif
        </form>
        <a href="{{ route('hendhys.pos.index') }}" class="text-[#d97706] hover:text-[#b45309] text-sm font-medium transition-colors">
            &larr; Kembali ke POS
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="p-4 font-medium w-16">Waktu</th>
                    <th class="p-4 font-medium">No. Hold</th>
                    <th class="p-4 font-medium">Customer / Keterangan</th>
                    <th class="p-4 font-medium">Operator</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($pendings as $p)
                <tr class="hover:bg-amber-50/50 transition-colors">
                    <td class="p-4 whitespace-nowrap text-gray-500 text-xs">
                        {{ \Carbon\Carbon::parse($p->created_at)->diffForHumans() }}
                    </td>
                    <td class="p-4 font-medium text-gray-800">{{ $p->pending_number }}</td>
                    <td class="p-4">
                        <p class="font-bold text-[#d97706]">{{ $p->customer_name }} <span class="text-xs text-gray-400 font-normal ml-1">({{ $p->customer_type }})</span></p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $p->notes ?: 'Tanpa keterangan' }}</p>
                    </td>
                    <td class="p-4 text-gray-600">{{ $p->creator->name }}</td>
                    <td class="p-4 text-right space-x-2">
                        <button @click="showDetail({{ $p->id }})" class="text-blue-600 hover:text-blue-800 font-medium text-xs bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-md transition-colors">Lihat Item</button>
                        
                        <form action="{{ route('hendhys.pending.destroy', $p->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus transaksi pending ini? Data tidak dapat dikembalikan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-md transition-colors">Batalkan</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p>Tidak ada transaksi yang di-hold saat ini.</p>
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
    <div x-show="isOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isOpen" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="isOpen" x-transition.scale class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100">
                    <div class="sm:flex sm:items-start justify-between">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Detail Pending <span x-text="detailData.pending_number" class="text-[#d97706]"></span></h3>
                            <div class="mt-2 text-sm text-gray-500">
                                <p>Customer: <span class="font-semibold text-gray-800" x-text="detailData.customer_name"></span></p>
                                <p>Catatan: <span class="italic" x-text="detailData.notes || '-'"></span></p>
                            </div>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 max-h-60 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="item in detailData.details" :key="item.id">
                                <tr>
                                    <td class="px-3 py-2 text-gray-900 font-medium" x-text="item.product_name"></td>
                                    <td class="px-3 py-2 text-right text-gray-500"><span x-text="parseFloat(item.quantity)"></span> <span class="text-[10px]" x-text="item.unit.code"></span></td>
                                    <td class="px-3 py-2 text-right text-gray-500" x-text="formatCurrency(item.price)"></td>
                                    <td class="px-3 py-2 text-right text-gray-900 font-semibold" x-text="formatCurrency(item.total)"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right text-gray-700">Total (Belum PPN/Diskon)</td>
                                <td class="px-3 py-2 text-right text-[#d97706]" x-text="formatCurrency(calculateTotal())"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="bg-white px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                    <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-50 text-amber-700 text-base font-medium hover:bg-amber-100 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm" @click="closeModal">
                        Tutup
                    </button>
                    <p class="text-xs text-gray-400 mt-3 sm:mt-0 sm:flex sm:items-center">Note: Fitur Lanjutkan (Resume) ke keranjang belum tersedia, hubungi IT untuk integrasi.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pendingManager', () => ({
        isOpen: false,
        detailData: { details: [] },
        
        async showDetail(id) {
            try {
                const res = await fetch(`{{ url('hendhys/pending') }}/${id}`);
                this.detailData = await res.json();
                this.isOpen = true;
            } catch (e) {
                alert('Gagal mengambil data detail.');
            }
        },
        
        closeModal() {
            this.isOpen = false;
        },
        
        calculateTotal() {
            if(!this.detailData.details) return 0;
            return this.detailData.details.reduce((sum, item) => sum + Number(item.total), 0);
        },
        
        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
        }
    }));
});
</script>
@endsection
