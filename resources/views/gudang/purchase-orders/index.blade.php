@extends('layouts.gudang')
@section('title','Purchase Order')
@section('page-title','Gudang — Purchase Order')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Purchase Order</h2>
        <p class="text-sm text-gray-400">{{ $orders->total() }} dokumen</p>
    </div>
    <a href="{{ route('gudang.po.create') }}"
       class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Buat PO
    </a>
</div>

<form method="GET" class="flex gap-2 mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor PO atau supplier..."
           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">Semua Status</option>
        @foreach(['draft'=>'Draft','sent'=>'Terkirim','partial'=>'Sebagian','received'=>'Diterima','cancelled'=>'Batal'] as $val=>$lbl)
        <option value="{{ $val }}" {{ request('status')===$val?'selected':'' }}>{{ $lbl }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg">Cari</button>
    @if(request()->hasAny(['search','status']))
    <a href="{{ route('gudang.po.index') }}" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-500 text-sm px-3 py-2 rounded-lg">Reset</a>
    @endif
</form>

@php
$statusClass = ['draft'=>'bg-gray-100 text-gray-600','sent'=>'bg-blue-100 text-blue-700','partial'=>'bg-yellow-100 text-yellow-700','received'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-600'];
$statusLabel = ['draft'=>'Draft','sent'=>'Terkirim','partial'=>'Sebagian','received'=>'Diterima','cancelled'=>'Batal'];
@endphp

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">No. PO</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Supplier</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Est. Tiba</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($orders as $po)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-indigo-700 font-medium">{{ $po->po_number }}</td>
                <td class="px-4 py-3 text-gray-800">{{ $po->supplier->name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $po->date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $po->expected_date?->format('d/m/Y') ?? '-' }}</td>
                <td class="px-4 py-3 text-right font-medium text-gray-800">Rp {{ number_format($po->total_amount,0,',','.') }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass[$po->status] ?? '' }}">
                        {{ $statusLabel[$po->status] ?? $po->status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('gudang.po.show', $po) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Detail</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada Purchase Order.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
