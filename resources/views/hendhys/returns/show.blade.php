@extends('layouts.hendhys')
@section('title', 'Detail Return')
@section('page-title', 'Detail Return: ' . $return->return_number)

@section('content')
@php
    $isPusat = auth()->user()->branch->type === 'pusat';
@endphp

<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('hendhys.returns.index') }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-sm flex items-center gap-1">
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
                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Dikirim ke Pusat</span>
                @elseif($return->status == 'received')
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Diterima Pusat</span>
                @endif
            </div>
        </div>
        
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Cabang Asal</p>
                <p class="font-bold text-gray-800 text-lg">{{ $return->branch->name }}</p>
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
                            <th class="py-3 px-4 font-medium">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        @foreach($return->details as $index => $detail)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-gray-500 border-r border-gray-200">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-medium text-gray-800 border-r border-gray-200">{{ $detail->product->name }}</td>
                            <td class="py-3 px-4 border-r border-gray-200">
                                <span class="px-2 py-1 bg-red-50 text-red-700 rounded text-xs font-semibold uppercase">{{ $detail->condition }}</span>
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-gray-800 border-r border-gray-200">{{ (float) $detail->quantity }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $detail->unit->code }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                <p class="text-sm text-gray-500 bg-gray-50 p-4 rounded-lg border border-gray-100">Barang retur tidak akan ditambahkan kembali ke stok aktif pusat karena kondisinya rusak/basi.</p>
            </div>
        </div>

        {{-- Form Konfirmasi Pusat --}}
        @if($isPusat && $return->status === 'sent')
        <div class="p-6 bg-blue-50 border-t border-blue-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <p class="font-bold text-blue-900">Konfirmasi Penerimaan Retur</p>
                <p class="text-sm text-blue-700 mt-1">Lakukan konfirmasi ini setelah fisik barang cacat diterima oleh Pusat.</p>
            </div>
            <form action="{{ route('hendhys.returns.receive', $return->id) }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('Konfirmasi penerimaan barang retur?')" class="bg-[#d97706] hover:bg-[#b45309] text-white px-6 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm whitespace-nowrap">
                    Terima Barang Cacat
                </button>
            </form>
        </div>
        @elseif($return->status === 'received')
        <div class="p-6 bg-gray-50 border-t border-gray-100">
            <p class="text-sm text-gray-600">Diterima oleh Pusat: <span class="font-semibold text-gray-800">{{ $return->receiver->name ?? '-' }}</span> pada {{ \Carbon\Carbon::parse($return->updated_at)->format('d M Y H:i') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
