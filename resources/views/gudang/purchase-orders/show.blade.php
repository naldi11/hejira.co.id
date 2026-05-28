@extends('layouts.gudang')
@section('title', 'Detail PO ' . $po->po_number)
@section('page-title', 'Purchase Order — ' . $po->po_number)

@section('content')
@php
$statusConfig = [
    'draft'     => ['label' => 'Draft',             'bg' => 'bg-slate-100',  'text' => 'text-slate-600',  'dot' => 'bg-slate-400'],
    'sent'      => ['label' => 'Terkirim',          'bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'dot' => 'bg-blue-500'],
    'partial'   => ['label' => 'Sebagian Diterima', 'bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'dot' => 'bg-amber-400'],
    'received'  => ['label' => 'Selesai',           'bg' => 'bg-green-50',  'text' => 'text-green-700',  'dot' => 'bg-green-500'],
    'cancelled' => ['label' => 'Dibatalkan',        'bg' => 'bg-red-50',    'text' => 'text-red-600',    'dot' => 'bg-red-400'],
];
$s = $statusConfig[$po->status] ?? $statusConfig['draft'];
@endphp

<div class="space-y-6">

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-2xl text-sm font-semibold">
        <span class="material-symbols-outlined text-green-500 text-[20px]">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    {{-- Header + Actions --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight font-mono">{{ $po->po_number }}</h2>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $s['bg'] }} {{ $s['text'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $s['dot'] }}"></span>
                            {{ $s['label'] }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Dibuat oleh <span class="font-semibold text-slate-600">{{ $po->creator->name }}</span> · {{ $po->created_at->format('d M Y, H:i') }}</p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('gudang.po.index') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-slate-500 border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        Kembali
                    </a>

                    @if($po->status === 'draft')
                    <a href="{{ route('gudang.po.edit', $po) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-indigo-600 border border-indigo-200 bg-indigo-50 rounded-xl text-sm font-bold hover:bg-indigo-100 transition-all">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                        Edit PO
                    </a>
                    @endif

                    @if(in_array($po->status, ['draft', 'sent', 'partial']))
                    <a href="{{ route('gudang.receiving.create', ['po_id' => $po->id]) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-bold hover:bg-green-700 transition-all shadow-lg shadow-green-600/20">
                        <span class="material-symbols-outlined text-[16px]">inventory</span>
                        Terima Barang
                    </a>
                    @endif

                    <a href="{{ route('gudang.po.print', $po) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-900 transition-all">
                        <span class="material-symbols-outlined text-[16px]">print</span>
                        Cetak PO
                    </a>

                    @if(in_array($po->status, ['draft', 'sent']))
                    <form method="POST" action="{{ route('gudang.po.cancel', $po) }}"
                          onsubmit="return confirm('Batalkan PO ini? Tindakan tidak bisa dibatalkan.')" class="inline">
                        @csrf
                        <button class="inline-flex items-center gap-1.5 px-4 py-2 text-red-600 border border-red-200 bg-red-50 rounded-xl text-sm font-bold hover:bg-red-100 transition-all">
                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                            Batalkan
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Info Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Supplier</p>
                    <p class="font-bold text-slate-800">{{ $po->supplier->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal PO</p>
                    <p class="font-bold text-slate-800">{{ $po->date->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Est. Tiba</p>
                    <p class="font-bold text-slate-800">{{ $po->expected_date?->format('d M Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Tagihan</p>
                    <p class="font-black text-indigo-600 text-lg">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</p>
                </div>
                @if($po->notes)
                <div class="col-span-2 md:col-span-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Catatan</p>
                    <p class="text-slate-600 text-sm">{{ $po->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Progress Bar (jika ada penerimaan) --}}
    @php
        $totalOrdered  = $po->details->sum('quantity_ordered');
        $totalReceived = $po->details->sum('quantity_received');
        $progressPct   = $totalOrdered > 0 ? min(100, round($totalReceived / $totalOrdered * 100)) : 0;
    @endphp
    @if($po->status !== 'draft' && $po->status !== 'cancelled')
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-black text-slate-500 uppercase tracking-wider">Progress Penerimaan</span>
            <span class="text-sm font-black {{ $progressPct >= 100 ? 'text-green-600' : 'text-indigo-600' }}">{{ $progressPct }}%</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2.5">
            <div class="h-2.5 rounded-full transition-all duration-500 {{ $progressPct >= 100 ? 'bg-green-500' : 'bg-indigo-500' }}"
                 style="width: {{ $progressPct }}%"></div>
        </div>
        <p class="text-xs text-slate-400 mt-1.5">{{ number_format($totalReceived, 0, ',', '.') }} dari {{ number_format($totalOrdered, 0, ',', '.') }} unit diterima</p>
    </div>
    @endif

    {{-- Tabel Item --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-black text-slate-700 text-sm uppercase tracking-wider">Daftar Item Pesanan</h3>
            <span class="text-xs text-slate-400 font-bold">{{ $po->details->count() }} item</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-black text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-3">Produk</th>
                        <th class="px-4 py-3 text-center">Qty Order</th>
                        <th class="px-4 py-3 text-center">Qty Diterima</th>
                        <th class="px-4 py-3 text-center">Satuan</th>
                        <th class="px-4 py-3 text-right">Harga Satuan</th>
                        <th class="px-6 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($po->details as $d)
                    @php $fulfilled = $d->quantity_received >= $d->quantity_ordered; @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-800">{{ $d->product->name }}</p>
                            @if($d->notes)
                            <p class="text-xs text-slate-400 mt-0.5 italic">{{ $d->notes }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="font-black text-slate-700">{{ (int) $d->quantity_ordered }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-lg text-xs font-black
                                {{ $fulfilled ? 'bg-green-100 text-green-700' : ($d->quantity_received > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">
                                {{ (int) $d->quantity_received }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="font-mono text-xs text-slate-500 uppercase">{{ $d->unit->abbreviation }}</span>
                        </td>
                        <td class="px-4 py-4 text-right text-slate-600 font-semibold">
                            Rp {{ number_format($d->price, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-black text-slate-800">
                            Rp {{ number_format($d->total, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-indigo-50 border-t-2 border-indigo-200">
                        <td colspan="5" class="px-6 py-4 text-right font-black text-indigo-700 text-sm uppercase tracking-wider">Grand Total</td>
                        <td class="px-6 py-4 text-right font-black text-indigo-700 text-xl">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Riwayat Penerimaan --}}
    @if($po->receivings->count())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
            <span class="material-symbols-outlined text-green-600 text-[20px]">inventory_2</span>
            <h3 class="font-black text-slate-700 text-sm uppercase tracking-wider">Riwayat Penerimaan Barang</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-black text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-3">No. GRN</th>
                        <th class="px-4 py-3">Tanggal Terima</th>
                        <th class="px-4 py-3 text-center">Jml Item</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($po->receivings as $grn)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-3">
                            <span class="font-mono text-xs font-black text-indigo-700">{{ $grn->grn_number }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 font-semibold">{{ $grn->date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="w-7 h-7 inline-flex items-center justify-center rounded-full bg-slate-100 text-slate-700 text-xs font-black">
                                {{ $grn->details->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('gudang.receiving.show', $grn) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold hover:bg-indigo-50 hover:text-indigo-700 transition-all">
                                <span class="material-symbols-outlined text-[14px]">visibility</span>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
