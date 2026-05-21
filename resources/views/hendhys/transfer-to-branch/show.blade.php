@extends('layouts.hendhys')
@section('title', 'Detail Pengiriman')
@section('page-title', 'Surat Jalan Pengiriman: ' . $transferToBranch->transfer_number)

@push('styles')
<style>
@media print {
    /* Sembunyikan seluruh layout Hendhys */
    aside, nav, header,
    [class*="sidebar"], [class*="navbar"], [class*="topbar"],
    .print\:hidden { display: none !important; }

    /* Reset body & wrapper */
    body { background: white !important; margin: 0 !important; padding: 0 !important; }

    /* Buat konten mengambil full width */
    main, [class*="content"], #app, .min-h-screen,
    [class*="ml-"], [class*="lg:ml-"], [class*="pl-"] {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Dokumen surat jalan */
    .surat-jalan-doc {
        max-width: 100% !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: 1.5px solid #000 !important;
        border-radius: 0 !important;
    }

    /* Tombol dan aksi */
    .no-print, button, a.back-btn { display: none !important; }

    @page { margin: 10mm; size: A4 portrait; }
}
</style>
@endpush

@section('content')
@php
    $isPusat = auth()->user()->branch->type === 'pusat';
@endphp

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between no-print print:hidden">
        <a href="{{ route('hendhys.transfer-to-branch.index') }}" class="back-btn text-[#d97706] hover:text-[#b45309] font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
        <button onclick="window.print()" class="bg-gray-800 text-white hover:bg-gray-900 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Surat Jalan
        </button>
    </div>

    <div class="surat-jalan-doc bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- ===== HEADER ===== --}}
        <div class="p-8 border-b-2 border-gray-800">
            <div class="flex flex-col md:flex-row justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 tracking-tight uppercase">Surat Jalan</h2>
                    <p class="text-sm text-gray-600 mt-2">No. Pengiriman: <span class="font-bold text-gray-800">{{ $transferToBranch->transfer_number }}</span></p>
                    @if($transferToBranch->branchRequest)
                    <p class="text-sm text-gray-600">No. Request Asal: <span class="font-bold text-gray-800">{{ $transferToBranch->branchRequest->request_number }}</span></p>
                    @else
                    <p class="text-sm text-gray-600">Tipe: <span class="font-bold text-gray-800">Distribusi Manual</span></p>
                    @endif
                    <p class="text-sm text-gray-600 mt-1">Tanggal Pengiriman: <span class="font-bold">{{ \Carbon\Carbon::parse($transferToBranch->date)->format('d F Y') }}</span></p>
                </div>
                <div class="md:text-right">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Status Pengiriman</p>
                    @if($transferToBranch->status == 'sent')
                        <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Sedang Dikirim
                        </span>
                    @elseif($transferToBranch->status == 'received')
                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Telah Diterima
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== PENGIRIM & PENERIMA ===== --}}
        <div class="p-8 grid grid-cols-2 gap-8 border-b-2 border-gray-800">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Dari (Pengirim)</p>
                <p class="font-bold text-gray-800 text-lg">Hendhys Pusat Bakery</p>
                <p class="text-sm text-gray-600 mt-1">Admin: {{ $transferToBranch->creator->name }}</p>
            </div>
            <div class="border-l border-gray-300 pl-8">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Kepada (Penerima)</p>
                <p class="font-bold text-gray-800 text-lg">{{ $transferToBranch->branch->name }}</p>
                <p class="text-sm text-gray-600 mt-1">Alamat: {{ $transferToBranch->branch->address ?: '-' }}</p>
            </div>
        </div>

        {{-- ===== TABEL BARANG ===== --}}
        <div class="p-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Daftar Barang</h3>
            <table class="w-full text-left border-collapse border border-gray-800 text-sm">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 text-xs uppercase tracking-wider">
                        <th class="py-3 px-4 font-bold border border-gray-800 w-12 text-center">No</th>
                        <th class="py-3 px-4 font-bold border border-gray-800">Nama Produk</th>
                        <th class="py-3 px-4 font-bold border border-gray-800 text-center w-28">Qty Kirim</th>
                        @if($transferToBranch->status == 'received')
                        <th class="py-3 px-4 font-bold border border-gray-800 text-center w-28">Qty Diterima</th>
                        @endif
                        <th class="py-3 px-4 font-bold border border-gray-800 w-24">Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transferToBranch->details as $index => $detail)
                    <tr>
                        <td class="py-3 px-4 border border-gray-800 text-center text-gray-600">{{ $index + 1 }}</td>
                        <td class="py-3 px-4 border border-gray-800 font-medium text-gray-800">{{ $detail->product->name }}</td>
                        <td class="py-3 px-4 border border-gray-800 text-center font-bold text-gray-800">{{ (float) $detail->quantity }}</td>
                        @if($transferToBranch->status == 'received')
                        <td class="py-3 px-4 border border-gray-800 text-center font-bold {{ $detail->received_quantity < $detail->quantity ? 'text-red-600' : 'text-green-600' }}">
                            {{ (float) $detail->received_quantity }}
                        </td>
                        @endif
                        <td class="py-3 px-4 border border-gray-800 text-gray-600">{{ $detail->unit->abbreviation ?? $detail->unit->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($transferToBranch->notes)
            <div class="mt-6 p-4 bg-gray-50 rounded border border-gray-200">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Catatan Pengiriman</p>
                <p class="text-sm text-gray-800">{{ $transferToBranch->notes }}</p>
            </div>
            @endif

            {{-- Informasi Penerimaan (tampil jika sudah diterima) --}}
            @if($transferToBranch->status === 'received')
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-bold text-gray-700 mb-3">Informasi Penerimaan</h4>
                <table class="w-full text-left border-collapse border border-gray-400 text-sm mb-4">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-2 px-3 font-bold border border-gray-400">Produk</th>
                            <th class="py-2 px-3 font-bold border border-gray-400 text-right">Qty Dikirim</th>
                            <th class="py-2 px-3 font-bold border border-gray-400 text-right">Qty Diterima</th>
                            <th class="py-2 px-3 font-bold border border-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transferToBranch->details as $detail)
                        <tr>
                            <td class="py-2 px-3 border border-gray-400">{{ $detail->product->name }}</td>
                            <td class="py-2 px-3 border border-gray-400 text-right">{{ (int)$detail->quantity }}</td>
                            <td class="py-2 px-3 border border-gray-400 text-right font-bold
                                {{ ($detail->received_quantity !== null && $detail->received_quantity < $detail->quantity) ? 'text-red-600' : 'text-green-700' }}">
                                {{ $detail->received_quantity !== null ? (int)$detail->received_quantity : '-' }}
                            </td>
                            <td class="py-2 px-3 border border-gray-400 text-xs">
                                @if($detail->received_quantity !== null && $detail->received_quantity < $detail->quantity)
                                    <span class="text-red-600 font-bold">Kurang {{ (int)($detail->quantity - $detail->received_quantity) }}</span>
                                @elseif($detail->received_quantity !== null)
                                    <span class="text-green-700 font-bold">Sesuai</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($transferToBranch->receive_notes)
                <div class="bg-yellow-50 border border-yellow-300 rounded p-3 text-sm mb-3">
                    <p class="font-bold text-yellow-800 mb-1">Catatan dari Cabang:</p>
                    <p class="text-yellow-900">{{ $transferToBranch->receive_notes }}</p>
                </div>
                @endif
                @if($transferToBranch->receive_photo)
                <div class="mb-4">
                    <p class="text-xs font-bold text-gray-600 mb-2">Foto Bukti Serah Terima:</p>
                    <img src="{{ asset('storage/' . $transferToBranch->receive_photo) }}"
                         alt="Bukti Serah Terima" class="max-h-48 rounded border border-gray-300 object-contain">
                </div>
                @endif
            </div>
            @endif

            {{-- Tanda Tangan --}}
            <div class="mt-16 grid grid-cols-2 gap-8 text-center text-sm">
                <div>
                    <p class="text-gray-600 mb-1">Pihak Pengirim (Pusat)</p>
                    <div class="h-20 border-b border-gray-400 mx-8"></div>
                    <p class="font-semibold text-gray-800 mt-2">{{ $transferToBranch->creator->name }}</p>
                    <p class="text-xs text-gray-500">Kasir Hendhys Pusat</p>
                </div>
                <div>
                    <p class="text-gray-600 mb-1">Pihak Penerima (Cabang)</p>
                    <div class="h-20 border-b border-gray-400 mx-8"></div>
                    <p class="font-semibold text-gray-800 mt-2">{{ $transferToBranch->receiver->name ?? '( ........................ )' }}</p>
                    <p class="text-xs text-gray-500">
                        @if($transferToBranch->status == 'received')
                            Diterima: {{ \Carbon\Carbon::parse($transferToBranch->updated_at)->format('d/m/Y H:i') }}
                        @else
                            Kasir Hendhys {{ $transferToBranch->branch->name }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Tombol Terima Barang (Hanya untuk Cabang) --}}
        @if(!$isPusat && $transferToBranch->status === 'sent' && $transferToBranch->branch_id === auth()->user()->branch_id)
        <div class="p-6 bg-amber-50 border-t border-amber-100 flex justify-between items-center no-print print:hidden">
            <div>
                <p class="font-bold text-amber-800">Konfirmasi Penerimaan Barang</p>
                <p class="text-sm text-amber-700">Periksa fisik barang, isi qty yang diterima, dan foto bukti sebelum konfirmasi.</p>
            </div>
            <a href="{{ route('hendhys.transfer-to-branch.receive-form', $transferToBranch->id) }}"
               class="bg-[#d97706] hover:bg-[#b45309] text-white px-6 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Terima &amp; Konfirmasi Barang
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
