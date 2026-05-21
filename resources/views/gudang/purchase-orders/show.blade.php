@extends('layouts.gudang')
@section('title', 'Detail PO '.$po->po_number)
@section('page-title', 'Purchase Order — '.$po->po_number)

@section('content')
@php
$statusClass = ['draft'=>'bg-gray-100 text-gray-600','sent'=>'bg-blue-100 text-blue-700','partial'=>'bg-yellow-100 text-yellow-700','received'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-600'];
$statusLabel = ['draft'=>'Draft','sent'=>'Terkirim','partial'=>'Sebagian Diterima','received'=>'Diterima','cancelled'=>'Dibatalkan'];
@endphp

<div class="mt-4 space-y-4 max-w-4xl">

    {{-- Actions --}}
    <div class="flex items-center gap-2">
        <a href="{{ route('gudang.po.index') }}" class="text-sm text-gray-500 hover:text-gray-700">â† Kembali</a>
        @if($po->status === 'draft')
        <form method="POST" action="{{ route('gudang.po.send', $po) }}" class="inline">
            @csrf
            <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-1.5 rounded-lg font-medium">Tandai Terkirim</button>
        </form>
        <a href="{{ route('gudang.po.edit', $po) }}" class="border border-gray-300 text-gray-600 text-sm px-3 py-1.5 rounded-lg hover:bg-gray-50">Edit</a>
        @endif
        @if(in_array($po->status, ['draft','sent']))
        <form method="POST" action="{{ route('gudang.po.cancel', $po) }}" class="inline"
              onsubmit="return confirm('Batalkan PO ini?')">
            @csrf
            <button class="border border-red-300 text-red-600 text-sm px-3 py-1.5 rounded-lg hover:bg-red-50">Batalkan</button>
        </form>
        @endif
        @if(in_array($po->status, ['sent','partial']))
        <a href="{{ route('gudang.receiving.create', ['po_id'=>$po->id]) }}"
           class="bg-green-600 hover:bg-green-700 text-white text-sm px-3 py-1.5 rounded-lg font-medium">+ Terima Barang</a>
        @endif
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xl font-bold text-gray-900">{{ $po->po_number }}</p>
                <p class="text-sm text-gray-500 mt-0.5">Dibuat oleh {{ $po->creator->name }} Â· {{ $po->created_at->format('d M Y H:i') }}</p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ $statusClass[$po->status]??'' }}">
                {{ $statusLabel[$po->status]??$po->status }}
            </span>
        </div>
        <div class="grid grid-cols-3 gap-4 mt-4 text-sm">
            <div><p class="text-gray-400 text-xs">Supplier</p><p class="font-medium text-gray-800">{{ $po->supplier->name }}</p></div>
            <div><p class="text-gray-400 text-xs">Tanggal PO</p><p class="font-medium text-gray-800">{{ $po->date->format('d M Y') }}</p></div>
            <div><p class="text-gray-400 text-xs">Estimasi Tiba</p><p class="font-medium text-gray-800">{{ $po->expected_date?->format('d M Y') ?? '-' }}</p></div>
            @if($po->notes)
            <div class="col-span-3"><p class="text-gray-400 text-xs">Catatan</p><p class="text-gray-700">{{ $po->notes }}</p></div>
            @endif
        </div>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-sm text-gray-700">Item Produk</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500">Produk</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500">Qty Order</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500">Qty Terima</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500">Satuan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500">Harga</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($po->details as $d)
                <tr>
                    <td class="px-4 py-3 text-gray-800">{{ $d->product->name }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ number_format($d->quantity_ordered,3,',','.') }}</td>
                    <td class="px-4 py-3 text-right {{ $d->quantity_received >= $d->quantity_ordered ? 'text-green-600 font-medium' : 'text-yellow-600' }}">
                        {{ number_format($d->quantity_received,3,',','.') }}
                    </td>
                    <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $d->unit->abbreviation }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format($d->price,0,',','.') }}</td>
                    <td class="px-4 py-3 text-right font-medium text-gray-800">Rp {{ number_format($d->total,0,',','.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-200">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-700">Grand Total</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900">Rp {{ number_format($po->total_amount,0,',','.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- GRN History --}}
    @if($po->receivings->count())
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-sm text-gray-700">Riwayat Penerimaan</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-left text-xs text-gray-500">No. GRN</th>
                <th class="px-4 py-2 text-left text-xs text-gray-500">Tanggal</th>
                <th class="px-4 py-2 text-center text-xs text-gray-500">Item</th>
                <th class="px-4 py-2 text-right text-xs text-gray-500">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($po->receivings as $grn)
                <tr>
                    <td class="px-4 py-2.5 font-mono text-xs text-indigo-700">{{ $grn->grn_number }}</td>
                    <td class="px-4 py-2.5 text-gray-600">{{ $grn->date->format('d M Y') }}</td>
                    <td class="px-4 py-2.5 text-center text-gray-500">{{ $grn->details->count() }}</td>
                    <td class="px-4 py-2.5 text-right">
                        <a href="{{ route('gudang.receiving.show', $grn) }}" class="text-indigo-600 text-xs hover:underline">Detail</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
