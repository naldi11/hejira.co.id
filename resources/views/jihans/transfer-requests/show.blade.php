@extends('layouts.jihans')
@section('title', 'Detail Request Barang')
@section('page-title', 'Request Bahan Baku — '.$transferRequest->request_number)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex justify-between items-center print:hidden">
        <a href="{{ route('jihans.transfer-requests.index') }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Daftar Request
        </a>
        <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Request
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Header Status --}}
        <div class="p-8 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $transferRequest->request_number }}</h1>
                <p class="text-sm text-gray-500">Tanggal: {{ \Carbon\Carbon::parse($transferRequest->date)->format('d F Y') }}</p>
            </div>
            <div class="text-left md:text-right">
                @php
                    $colors = [
                        'pending' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                        'approved' => 'bg-blue-100 text-blue-800 border border-blue-200',
                        'partial' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                        'completed' => 'bg-green-100 text-green-800 border border-green-200',
                        'rejected' => 'bg-red-100 text-red-800 border border-red-200',
                        'cancelled' => 'bg-gray-100 text-gray-800 border border-gray-200',
                    ];
                    $color = $colors[$transferRequest->status] ?? 'bg-gray-100 text-gray-800';
                @endphp
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Status Request</p>
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold uppercase shadow-sm {{ $color }}">
                    {{ $transferRequest->status }}
                </span>
            </div>
        </div>

        {{-- Detail Informasi --}}
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Dari Entitas</p>
                    <p class="text-base font-medium text-gray-800">Jihan's Food - Manufaktur & Retail</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Tujuan Request</p>
                    <p class="text-base font-medium text-gray-800">Gudang Tempua (Pusat Inventory)</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Dibuat Oleh</p>
                    <p class="text-base font-medium text-gray-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">{{ substr($transferRequest->creator->name ?? 'S', 0, 1) }}</span>
                        {{ $transferRequest->creator->name ?? 'Sistem' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Catatan Tambahan</p>
                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-100">{{ $transferRequest->notes ?: 'Tidak ada catatan.' }}</p>
                </div>
            </div>

            @if(in_array($transferRequest->status, ['approved', 'partial', 'completed', 'rejected']))
            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Informasi Keputusan Gudang</h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Diputuskan Oleh</p>
                        <p class="font-medium text-gray-800">{{ $transferRequest->approver->name ?? 'Admin Gudang' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Catatan Keputusan</p>
                        <p class="text-sm text-gray-700">{{ $transferRequest->approval_notes ?: '-' }}</p>
                    </div>
                    
                    @if($transferRequest->transferOuts->count() > 0)
                        <div>
                            <p class="text-xs text-gray-500 mb-2">Pengiriman dari Gudang</p>
                            <div class="space-y-1.5">
                                @foreach($transferRequest->transferOuts as $do)
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono bg-white border border-gray-200 px-2 py-1 rounded shadow-sm text-gray-600">{{ $do->transfer_number }}</span>
                                    @if($do->status === 'sent')
                                    <a href="{{ route('jihans.transfer-requests.receive-form', $do->id) }}"
                                       class="text-xs bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 font-medium">Konfirmasi Terima</a>
                                    @elseif($do->status === 'received')
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded font-medium">Diterima</span>
                                    <a href="{{ route('jihans.transfer-requests.print', $do->id) }}" target="_blank"
                                       class="text-xs bg-gray-700 text-white px-2 py-1 rounded hover:bg-gray-800 font-medium">Cetak BAST</a>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Tabel Item --}}
        <div class="border-t border-gray-100">
            <div class="px-8 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="font-bold text-gray-800">Daftar Barang yang Diminta</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                        <tr>
                            <th class="px-8 py-4 font-medium">Barang (Bahan Baku / Lainnya)</th>
                            <th class="px-8 py-4 font-medium text-center">Qty Request</th>
                            <th class="px-8 py-4 font-medium text-center">Qty Disetujui (Gudang)</th>
                            <th class="px-8 py-4 font-medium text-center">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($transferRequest->details as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-4">
                                <p class="font-medium text-gray-800">{{ $item->product->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ $item->product->code ?? '' }}</p>
                            </td>
                            <td class="px-8 py-4 text-center">
                                <span class="font-bold text-gray-900 text-base">{{ (float) $item->quantity_requested }}</span>
                            </td>
                            <td class="px-8 py-4 text-center">
                                @if(in_array($transferRequest->status, ['pending', 'cancelled']))
                                    <span class="text-gray-400 italic">Menunggu</span>
                                @else
                                    <span class="font-bold text-orange-600 text-base">{{ (float) $item->quantity_approved }}</span>
                                @endif
                            </td>
                            <td class="px-8 py-4 text-center text-gray-600">
                                {{ $item->unit->abbreviation ?? '' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
