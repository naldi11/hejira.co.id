@extends('layouts.hendhys')
@section('title', 'Distribusi ke Cabang')
@section('page-title', 'Distribusi Barang ke Cabang')

@section('content')
@php
    $isPusat = auth()->user()->branch->type === 'pusat';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('hendhys.transfer-to-branch.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <select name="status" class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                <option value="">Semua Status</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Dalam Perjalanan (Sent)</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Diterima (Received)</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Pengiriman..." class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] min-w-[200px]">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['status', 'search']))
                <a href="{{ route('hendhys.transfer-to-branch.index') }}" class="text-sm text-red-500 hover:text-red-700">Reset</a>
            @endif
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="p-4 font-medium">Tanggal Pengiriman</th>
                    <th class="p-4 font-medium">No. Pengiriman</th>
                    <th class="p-4 font-medium">No. Request Asal</th>
                    @if($isPusat) <th class="p-4 font-medium">Cabang Tujuan</th> @endif
                    <th class="p-4 font-medium">Status</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($transfers as $trf)
                <tr class="hover:bg-amber-50/50 transition-colors">
                    <td class="p-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($trf->date)->format('d M Y') }}</td>
                    <td class="p-4 font-medium text-[#d97706]">{{ $trf->transfer_number }}</td>
                    <td class="p-4 font-medium text-gray-600">{{ $trf->branchRequest->request_number }}</td>
                    @if($isPusat) <td class="p-4 font-semibold text-gray-800">{{ $trf->branch->name }}</td> @endif
                    <td class="p-4">
                        @if($trf->status == 'sent')
                            <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider flex w-max items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Dikirim
                            </span>
                        @elseif($trf->status == 'received')
                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider flex w-max items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Diterima
                            </span>
                        @endif
                    </td>
                    <td class="p-4 text-right">
                        <a href="{{ route('hendhys.transfer-to-branch.show', $trf->id) }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-xs bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-md transition-colors inline-block">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isPusat ? 6 : 5 }}" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <p>Belum ada data distribusi barang.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transfers->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $transfers->links() }}
    </div>
    @endif
</div>
@endsection
