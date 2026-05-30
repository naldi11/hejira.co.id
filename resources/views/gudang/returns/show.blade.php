@extends('layouts.gudang')
@section('title', 'Detail Penerimaan Retur')
@section('page-title', 'Gudang — Detail Retur ' . $return->return_number)

@section('content')
<div class="max-w-5xl mx-auto mt-4">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('gudang.returns.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        {{-- Metadata Header --}}
        <div class="p-6 border-b border-gray-100 flex flex-wrap gap-6 items-center justify-between bg-gray-50">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $return->return_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">Tanggal Kirim: {{ \Carbon\Carbon::parse($return->date)->format('d F Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Status Retur</p>
                @if($return->status == 'sent')
                    <span class="px-3 py-1 rounded bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Menunggu Penerimaan</span>
                @elseif($return->status == 'received')
                    <span class="px-3 py-1 rounded bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Diterima di Gudang</span>
                @endif
            </div>
        </div>
        
        {{-- Entity Info --}}
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Asal Pengirim</p>
                <p class="font-bold text-gray-800 text-lg">{{ $return->from_entity_label }}</p>
                <p class="text-sm text-gray-500 mt-1">Dibuat oleh: {{ $return->creator->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Catatan Pengirim</p>
                <p class="font-medium text-gray-800">{{ $return->notes ?: '-' }}</p>
            </div>
        </div>

        <form action="{{ route('gudang.returns.receive', $return) }}" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-md font-bold text-gray-800 mb-4">Rincian Barang Retur</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse border border-gray-200 text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                                <th class="py-3 px-4 font-medium border-r border-gray-200 w-16">No</th>
                                <th class="py-3 px-4 font-medium border-r border-gray-200">Nama Produk</th>
                                <th class="py-3 px-4 font-medium border-r border-gray-200 text-right w-36">Kuantitas Dikirim</th>
                                <th class="py-3 px-4 font-medium border-r border-gray-200 text-right w-44">Kuantitas Diterima</th>
                                <th class="py-3 px-4 font-medium border-r border-gray-200 w-44">Kondisi Fisik</th>
                                <th class="py-3 px-4">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($return->details as $index => $detail)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 text-gray-500 border-r border-gray-200">{{ $index + 1 }}</td>
                                <td class="py-3 px-4 font-medium text-gray-800 border-r border-gray-200">{{ $detail->product->name }}</td>
                                <td class="py-3 px-4 text-right font-bold text-gray-800 border-r border-gray-200">
                                    {{ (float) $detail->quantity }}
                                </td>
                                <td class="py-3 px-4 text-right border-r border-gray-200">
                                    @if($return->status === 'sent')
                                        <input type="number" step="0.001" min="0" max="{{ $detail->quantity }}" 
                                               name="items[{{ $detail->id }}][received_quantity]" 
                                               value="{{ old('items.'.$detail->id.'.received_quantity', (float) $detail->quantity) }}" 
                                               required 
                                               class="w-full text-right border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    @else
                                        <span class="font-bold text-emerald-600">{{ (float) $detail->received_quantity }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 border-r border-gray-200">
                                    @if($return->status === 'sent')
                                        <select name="items[{{ $detail->id }}][condition]" required 
                                                class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            <option value="Bagus (Siap Jual)">Bagus (Siap Jual)</option>
                                            <option value="Rusak (Defect)">Rusak (Defect)</option>
                                            <option value="Kadaluwarsa">Kadaluwarsa</option>
                                        </select>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold uppercase">{{ $detail->condition }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-600">{{ $detail->unit->abbreviation }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($return->status === 'sent')
                <div class="p-6 bg-indigo-50 border-t border-indigo-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <p class="font-bold text-indigo-900">Konfirmasi Penerimaan Retur</p>
                        <p class="text-sm text-indigo-700 mt-1">Stok Gudang Utama akan otomatis bertambah sesuai dengan jumlah kuantitas yang diterima.</p>
                    </div>
                    <button type="submit" onclick="return confirm('Konfirmasi penerimaan barang retur ke Gudang Utama?')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm whitespace-nowrap">
                        Konfirmasi Penerimaan
                    </button>
                </div>
            @else
                <div class="p-6 bg-gray-50 border-t border-gray-100">
                    <p class="text-sm text-gray-600">Diterima oleh: <span class="font-semibold text-gray-800">{{ $return->receiver->name ?? '-' }}</span> pada {{ \Carbon\Carbon::parse($return->received_at)->format('d M Y H:i') }}</p>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection
