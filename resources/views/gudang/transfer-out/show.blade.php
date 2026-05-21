@extends('layouts.gudang')
@section('title', 'Detail Transfer Keluar (DO) '.$transferOut->transfer_number)
@section('page-title', 'Transfer Keluar — '.$transferOut->transfer_number)

@section('content')
<div class="mt-4 max-w-5xl">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $transferOut->transfer_number }}</h2>
            <p class="text-sm text-gray-500 mt-1">Dikirim pada {{ \Carbon\Carbon::parse($transferOut->date)->format('d F Y') }} oleh {{ $transferOut->creator->name ?? 'Sistem' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('gudang.transfer-out.index') }}" class="border border-gray-300 bg-white text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                Kembali
            </a>
            <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Surat Jalan (DO)
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Info Tujuan --}}
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Informasi Tujuan Pengiriman</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500">Dikirim Ke (Entitas)</p>
                    <p class="text-sm font-medium text-gray-800">
                        @if($transferOut->to_entity === 'hendhys')
                            Hendhys - {{ $transferOut->branch->name ?? 'Cabang' }}
                        @else
                            Jihans - Stok Produksi
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Catatan Pengiriman</p>
                    <p class="text-sm text-gray-800">{{ $transferOut->notes ?: '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Info Referensi --}}
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Informasi Referensi</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500">Referensi Request</p>
                    <p class="text-sm font-medium text-gray-800">
                        @if($transferOut->request)
                            <a href="{{ route('gudang.transfer-requests.show', $transferOut->request) }}" class="text-indigo-600 hover:underline">{{ $transferOut->request->request_number }}</a>
                        @else
                            <span class="text-gray-400 italic">Tanpa Request (Pengiriman Langsung)</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Status Stok</p>
                    <p class="text-sm text-green-600 font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Stok telah berhasil dipindahkan
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Items --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Item Produk yang Dikirim</h3>
            <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2 py-1 rounded">Nilai HPP dicatat untuk keperluan akuntansi/mutasi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Produk</th>
                        <th class="px-5 py-3 font-medium text-center">Qty Dikirim</th>
                        <th class="px-5 py-3 font-medium text-center">Satuan</th>
                        <th class="px-5 py-3 font-medium text-right">HPP / Unit</th>
                        <th class="px-5 py-3 font-medium text-right">Total Nilai Barang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $grandTotal = 0; @endphp
                    @foreach($transferOut->details as $item)
                    @php $grandTotal += $item->total; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $item->product->name }}</td>
                        <td class="px-5 py-3 text-center font-bold text-gray-900">{{ floatval($item->quantity) }}</td>
                        <td class="px-5 py-3 text-center text-gray-500">{{ $item->unit->abbreviation ?? '-' }}</td>
                        <td class="px-5 py-3 text-right text-gray-500">Rp {{ number_format($item->hpp_price, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-medium text-gray-800">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-5 py-4 text-right font-semibold text-gray-700">Total Nilai Mutasi Barang:</td>
                        <td class="px-5 py-4 text-right font-bold text-gray-900 text-base">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    {{-- Tanda Tangan DO --}}
    <div class="grid grid-cols-2 gap-6 print:block hidden print:mt-12">
        <div class="text-center">
            <p class="text-sm text-gray-600 mb-20">Gudang Utama (Pengirim)</p>
            <p class="font-medium border-t border-gray-400 inline-block px-8 pt-2">{{ $transferOut->creator->name ?? 'Admin Gudang' }}</p>
        </div>
        <div class="text-center">
            <p class="text-sm text-gray-600 mb-20">Penerima ({{ ucfirst($transferOut->to_entity) }})</p>
            <p class="font-medium border-t border-gray-400 inline-block px-8 pt-2">Nama & Tanda Tangan</p>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .max-w-5xl, .max-w-5xl * { visibility: visible; }
    .max-w-5xl { position: absolute; left: 0; top: 0; width: 100%; }
    button, a { display: none !important; }
    .shadow-sm { box-shadow: none !important; }
    .border { border-color: #ddd !important; }
    .print\:block { display: block !important; }
    .print\:mt-12 { margin-top: 3rem !important; }
}
</style>
@endsection
