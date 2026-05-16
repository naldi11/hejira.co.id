@extends('layouts.hendhys')
@section('title', 'Detail Produksi')
@section('page-title', 'Detail Produksi #' . $production->production_number)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('hendhys.productions.index') }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Daftar
        </a>
        <button onclick="window.print()" class="bg-gray-800 text-white hover:bg-gray-900 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm print:hidden">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Detail
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden print:shadow-none print:border-gray-800">
        {{-- Header Bukti Produksi --}}
        <div class="p-8 border-b border-gray-100 bg-amber-50/30 print:bg-transparent print:border-b-2 print:border-gray-800">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 tracking-tight">BUKTI PRODUKSI</h2>
                    <p class="text-sm text-gray-500 mt-1">Hendhys Pusat Bakery</p>
                </div>
                <div class="flex gap-8">
                    <div>
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">No. Produksi</p>
                        <p class="font-bold text-gray-800">{{ $production->production_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Tanggal</p>
                        <p class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($production->date)->format('d F Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Informasi --}}
        <div class="p-8">
            <div class="flex flex-wrap gap-x-12 gap-y-6 mb-8 p-4 bg-gray-50 rounded-lg border border-gray-100 print:bg-transparent print:border-none print:p-0">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Operator Produksi</p>
                    <p class="font-semibold text-gray-800">{{ $production->creator->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Total Macam Item</p>
                    <p class="font-semibold text-gray-800">{{ $production->total_items }} Item</p>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Catatan</p>
                    <p class="font-semibold text-gray-800">{{ $production->notes ?: '-' }}</p>
                </div>
            </div>

            {{-- Tabel Item --}}
            <h3 class="text-lg font-bold text-gray-800 mb-4">Rincian Hasil Produksi</h3>
            <table class="w-full text-left border-collapse print:border print:border-gray-800">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-y border-gray-200 print:border-gray-800 print:bg-gray-100">
                        <th class="py-3 px-4 font-medium w-16">No</th>
                        <th class="py-3 px-4 font-medium">Produk Jadi</th>
                        <th class="py-3 px-4 font-medium text-right w-32">Kuantitas</th>
                        <th class="py-3 px-4 font-medium w-24">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm print:divide-gray-800">
                    @foreach($production->details as $index => $detail)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500">{{ $index + 1 }}</td>
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $detail->product->name }}</td>
                        <td class="py-3 px-4 text-right font-bold text-[#d97706]">{{ (float) $detail->quantity_produced }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $detail->unit->code }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="mt-8 pt-6 border-t border-gray-100 text-sm text-gray-500 text-center print:border-gray-800">
                <p>Dokumen ini adalah bukti sah pencatatan hasil produksi dan penambahan stok Gudang Pusat secara otomatis.</p>
                <p class="mt-1 text-xs">Dicetak pada: {{ now()->format('d M Y H:i:s') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
