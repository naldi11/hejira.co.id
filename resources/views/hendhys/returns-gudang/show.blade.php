@extends('layouts.hendhys')
@section('title', 'Detail Return ke Gudang')
@section('page-title', 'Detail Return: ' . $return->return_number)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('hendhys.returns-to-gudang.index') }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-100 flex flex-wrap gap-6 items-center justify-between bg-[#faf7f5]">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $return->return_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">Tanggal Return: {{ \Carbon\Carbon::parse($return->date)->format('d F Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Status Pengiriman</p>
                @if($return->status == 'sent')
                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Dikirim ke Gudang Utama</span>
                @elseif($return->status == 'received')
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Diterima Gudang Utama</span>
                @endif
            </div>
        </div>
        
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Pengirim (Pusat)</p>
                <p class="font-bold text-gray-800 text-lg">{{ $return->branch->name ?? 'Hendhys Brownies' }}</p>
                <p class="text-sm text-gray-500 mt-1">Dikirim oleh: {{ $return->creator->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Catatan</p>
                <p class="font-medium text-gray-800">{{ $return->notes ?: '-' }}</p>
            </div>
        </div>

        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Rincian Barang Diretur</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="py-3 px-4 font-medium border-r border-gray-200 w-16">No</th>
                            <th class="py-3 px-4 font-medium border-r border-gray-200">Nama Produk</th>
                            <th class="py-3 px-4 font-medium border-r border-gray-200">Kondisi</th>
                            <th class="py-3 px-4 font-medium border-r border-gray-200 text-right">Kuantitas</th>
                            <th class="py-3 px-4 font-medium border-r border-gray-200 text-right">Diterima</th>
                            <th class="py-3 px-4">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        @foreach($return->details as $index => $detail)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-gray-500 border-r border-gray-200">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-medium text-gray-800 border-r border-gray-200">{{ $detail->product->name }}</td>
                            <td class="py-3 px-4 border-r border-gray-200">
                                <span class="px-2 py-1 bg-amber-50 text-amber-700 rounded text-xs font-semibold uppercase">{{ $detail->condition }}</span>
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-gray-800 border-r border-gray-200">{{ (float) $detail->quantity }}</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600 border-r border-gray-200">
                                {{ $detail->received_quantity !== null ? (float) $detail->received_quantity : '-' }}
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $detail->unit->abbreviation }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($return->status === 'received')
        <div class="p-6 bg-gray-50 border-t border-gray-100">
            <p class="text-sm text-gray-600">Diterima oleh Gudang Utama: <span class="font-semibold text-gray-800">{{ $return->receiver->name ?? '-' }}</span> pada {{ \Carbon\Carbon::parse($return->received_at)->format('d M Y H:i') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
