@extends('layouts.jihans')
@section('title', 'Detail Produksi')
@section('page-title', 'Detail Produksi '.$production->production_number)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex justify-between items-center print:hidden">
        <a href="{{ route('jihans.productions.index') }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Data Produksi
        </a>
        <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Bukti Produksi
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Header Bukti Cetak --}}
        <div class="p-8 border-b border-gray-100 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Bukti Produksi</h1>
                <p class="text-sm text-gray-500">Jihan's Food &mdash; Sistem Terpadu</p>
            </div>
            <div class="text-right">
                <p class="text-3xl font-mono font-bold text-orange-600">{{ $production->production_number }}</p>
                <p class="text-sm text-gray-500 mt-1">Dicetak pada: {{ now()->format('d M Y H:i') }}</p>
            </div>
        </div>

        {{-- Detail Informasi --}}
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Tanggal Produksi</p>
                    <p class="text-base font-medium text-gray-800">{{ \Carbon\Carbon::parse($production->date)->format('l, d F Y') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Person In Charge (PIC)</p>
                    <p class="text-base font-medium text-gray-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">{{ substr($production->creator->name ?? 'S', 0, 1) }}</span>
                        {{ $production->creator->name ?? 'Sistem' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Catatan Tambahan</p>
                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-100">{{ $production->notes ?: 'Tidak ada catatan khusus.' }}</p>
                </div>
            </div>

            <div class="bg-orange-50/50 rounded-xl p-6 border border-orange-100 relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-orange-100 rounded-full opacity-50"></div>
                <h3 class="text-sm font-semibold text-orange-800 mb-4 relative z-10">Rincian Hasil Jadi</h3>
                
                <div class="space-y-4 relative z-10">
                    <div class="bg-white p-3 rounded-lg border border-orange-100/50 shadow-sm">
                        <p class="text-xs text-gray-500 mb-1">Produk</p>
                        <p class="font-bold text-gray-800">{{ $production->product->name ?? '-' }} <span class="text-xs font-mono bg-gray-100 px-1 py-0.5 rounded ml-1">{{ $production->product->code ?? '' }}</span></p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-3 rounded-lg border border-orange-100/50 shadow-sm">
                            <p class="text-xs text-gray-500 mb-1">Ukuran</p>
                            <p class="font-bold text-gray-800 capitalize">{{ $production->size }}</p>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-orange-100/50 shadow-sm">
                            <p class="text-xs text-gray-500 mb-1">Kuantitas Hasil</p>
                            <p class="font-bold text-gray-900 text-lg">{{ (float) $production->quantity_produced }} <span class="text-sm text-gray-500 font-normal">{{ $production->unit->abbreviation ?? '' }}</span></p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2 text-green-700 bg-green-50 px-3 py-2 rounded-lg border border-green-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span class="text-xs font-medium">Stok Jihan's otomatis bertambah.</span>
                </div>
            </div>
        </div>

        {{-- Signature --}}
        <div class="p-8 border-t border-gray-100 print:block hidden mt-10">
            <div class="grid grid-cols-2 gap-8 text-center">
                <div>
                    <p class="text-sm text-gray-500 mb-20">Operator Produksi</p>
                    <p class="font-medium inline-block border-t border-gray-300 px-8 pt-2">{{ $production->creator->name ?? 'Sistem' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-20">Mengetahui (Admin Jihan's)</p>
                    <p class="font-medium inline-block border-t border-gray-300 px-8 pt-2">Tanda Tangan</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
