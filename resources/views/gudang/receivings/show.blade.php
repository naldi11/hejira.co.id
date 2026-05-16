@extends('layouts.gudang')
@section('title', 'Detail GRN '.$receiving->grn_number)
@section('page-title', 'Penerimaan Barang — '.$receiving->grn_number)

@section('content')
<div class="mt-4 max-w-5xl">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $receiving->grn_number }}</h2>
            <p class="text-sm text-gray-500 mt-1">Diterima pada {{ \Carbon\Carbon::parse($receiving->date)->format('d F Y') }} oleh {{ $receiving->creator->name ?? 'Sistem' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('gudang.receiving.index') }}" class="border border-gray-300 bg-white text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                Kembali
            </a>
            <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak GRN
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Info Supplier --}}
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Informasi Supplier</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500">Nama Supplier</p>
                    <p class="text-sm font-medium text-gray-800">{{ $receiving->supplier->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Kontak</p>
                    <p class="text-sm text-gray-800">{{ $receiving->supplier->phone ?? '-' }} / {{ $receiving->supplier->email ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Alamat</p>
                    <p class="text-sm text-gray-800">{{ $receiving->supplier->address ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Info Referensi --}}
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Informasi Referensi</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500">Referensi PO</p>
                    <p class="text-sm font-medium">
                        @if($receiving->po)
                            <a href="{{ route('gudang.po.show', $receiving->po->id) }}" class="text-indigo-600 hover:underline">{{ $receiving->po->po_number }}</a>
                        @else
                            <span class="text-gray-400">Penerimaan Langsung (Tanpa PO)</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Catatan Penerimaan / No. Surat Jalan</p>
                    <p class="text-sm text-gray-800">{{ $receiving->notes ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Items --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Item Produk</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Produk</th>
                        <th class="px-5 py-3 font-medium text-center">Qty Diterima</th>
                        <th class="px-5 py-3 font-medium text-center">Satuan</th>
                        <th class="px-5 py-3 font-medium text-right">Harga Beli</th>
                        <th class="px-5 py-3 font-medium text-right">Total</th>
                        <th class="px-5 py-3 font-medium">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $grandTotal = 0; @endphp
                    @foreach($receiving->details as $item)
                    @php $grandTotal += $item->total; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $item->product->name }}</td>
                        <td class="px-5 py-3 text-center">{{ number_format($item->quantity, 3, ',', '.') }}</td>
                        <td class="px-5 py-3 text-center text-gray-500">{{ $item->unit->abbreviation ?? '-' }}</td>
                        <td class="px-5 py-3 text-right">Rp {{ number_format($item->hpp_price, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-medium text-gray-800">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $item->notes ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-5 py-4 text-right font-semibold text-gray-700">Estimasi Total Nilai Penerimaan:</td>
                        <td class="px-5 py-4 text-right font-bold text-gray-900 text-base">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
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
}
</style>
@endsection
