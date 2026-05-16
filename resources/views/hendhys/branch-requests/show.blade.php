@extends('layouts.hendhys')
@section('title', 'Detail Request Cabang')
@section('page-title', 'Detail Request: ' . $branchRequest->request_number)

@section('content')
@php
    $isPusat = auth()->user()->branch->type === 'pusat';
@endphp

<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('hendhys.branch-requests.index') }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
        
        @if($isPusat && $branchRequest->status === 'pending')
            <a href="{{ route('hendhys.transfer-to-branch.create', ['request_id' => $branchRequest->id]) }}" class="bg-[#d97706] hover:bg-[#b45309] text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Proses Distribusi Barang
            </a>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-100 flex flex-wrap gap-6 items-center justify-between bg-[#faf7f5]">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $branchRequest->request_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">Tanggal Request: {{ \Carbon\Carbon::parse($branchRequest->date)->format('d F Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Status</p>
                @if($branchRequest->status == 'pending')
                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Pending</span>
                @elseif($branchRequest->status == 'completed')
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Completed</span>
                @elseif($branchRequest->status == 'partial')
                    <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-bold uppercase tracking-wider">Partial</span>
                @else
                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold uppercase tracking-wider">Rejected</span>
                @endif
            </div>
        </div>
        
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Dari Cabang</p>
                <p class="font-bold text-gray-800 text-lg">{{ $branchRequest->branch->name }}</p>
                <p class="text-sm text-gray-500 mt-1">Diminta oleh: {{ $branchRequest->creator->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Catatan Tambahan</p>
                <p class="font-medium text-gray-800">{{ $branchRequest->notes ?: '-' }}</p>
            </div>
        </div>

        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Rincian Stok Diminta</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="py-3 px-4 font-medium border-r border-gray-200 w-16">No</th>
                            <th class="py-3 px-4 font-medium border-r border-gray-200">Nama Produk</th>
                            <th class="py-3 px-4 font-medium border-r border-gray-200 text-right">Qty Diminta</th>
                            <th class="py-3 px-4 font-medium border-r border-gray-200 text-right">Qty Disetujui (Dikirim)</th>
                            <th class="py-3 px-4 font-medium">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        @foreach($branchRequest->details as $index => $detail)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-gray-500 border-r border-gray-200">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-medium text-gray-800 border-r border-gray-200">{{ $detail->product->name }}</td>
                            <td class="py-3 px-4 text-right font-bold text-gray-800 border-r border-gray-200">{{ (float) $detail->quantity_requested }}</td>
                            <td class="py-3 px-4 text-right font-bold border-r border-gray-200 {{ $detail->quantity_approved < $detail->quantity_requested ? 'text-red-500' : 'text-green-600' }}">
                                {{ $detail->quantity_approved !== null ? (float) $detail->quantity_approved : 'Menunggu' }}
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $detail->unit->code }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
