@extends('layouts.hendhys')
@section('title', 'Detail Pengiriman')
@section('page-title', 'Surat Jalan Pengiriman: ' . $transferToBranch->transfer_number)

@section('content')
@php
    $isPusat = auth()->user()->branch->type === 'pusat';
@endphp

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('hendhys.transfer-to-branch.index') }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
        <button onclick="window.print()" class="bg-gray-800 text-white hover:bg-gray-900 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm print:hidden">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Surat Jalan
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden print:shadow-none print:border-gray-800">
        <div class="p-8 border-b border-gray-100 bg-amber-50/30 print:bg-transparent print:border-b-2 print:border-gray-800">
            <div class="flex flex-col md:flex-row justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 tracking-tight uppercase">Surat Jalan</h2>
                    <p class="text-sm text-gray-600 mt-2">No. Pengiriman: <span class="font-bold text-gray-800">{{ $transferToBranch->transfer_number }}</span></p>
                    <p class="text-sm text-gray-600">No. Request Asal: <a href="{{ route('hendhys.branch-requests.show', $transferToBranch->request_id) }}" class="text-[#d97706] hover:underline">{{ $transferToBranch->branchRequest->request_number }}</a></p>
                    <p class="text-sm text-gray-600 mt-2">Tanggal Pengiriman: {{ \Carbon\Carbon::parse($transferToBranch->date)->format('d F Y') }}</p>
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
        
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8 border-b border-gray-100 print:border-b-2 print:border-gray-800">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Dari (Pengirim)</p>
                <p class="font-bold text-gray-800 text-lg">Hendhys Pusat Bakery</p>
                <p class="text-sm text-gray-600 mt-1">Admin: {{ $transferToBranch->creator->name }}</p>
            </div>
            <div class="md:border-l md:border-gray-100 md:pl-8 print:border-gray-800">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Kepada (Penerima)</p>
                <p class="font-bold text-[#d97706] text-lg">{{ $transferToBranch->branch->name }}</p>
                <p class="text-sm text-gray-600 mt-1">Alamat: {{ $transferToBranch->branch->address ?: '-' }}</p>
            </div>
        </div>

        <div class="p-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 print:mb-2">Daftar Barang</h3>
            <table class="w-full text-left border-collapse print:border print:border-gray-800">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-y border-gray-200 print:bg-gray-100 print:border-gray-800">
                        <th class="py-3 px-4 font-medium border-r border-gray-100 print:border-gray-800 w-16">No</th>
                        <th class="py-3 px-4 font-medium border-r border-gray-100 print:border-gray-800">Nama Produk</th>
                        <th class="py-3 px-4 font-medium border-r border-gray-100 print:border-gray-800 text-right">Kuantitas</th>
                        <th class="py-3 px-4 font-medium">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm print:divide-gray-800">
                    @foreach($transferToBranch->details as $index => $detail)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500 border-r border-gray-100 print:border-gray-800">{{ $index + 1 }}</td>
                        <td class="py-3 px-4 font-medium text-gray-800 border-r border-gray-100 print:border-gray-800">{{ $detail->product->name }}</td>
                        <td class="py-3 px-4 text-right font-bold text-[#d97706] border-r border-gray-100 print:border-gray-800">{{ (float) $detail->quantity }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $detail->unit->code }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($transferToBranch->notes)
            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-100 print:border-none print:p-0 print:bg-transparent">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Catatan Pengiriman</p>
                <p class="text-sm text-gray-800">{{ $transferToBranch->notes }}</p>
            </div>
            @endif

            <div class="mt-12 grid grid-cols-2 gap-8 text-center text-sm print:mt-16">
                <div>
                    <p class="text-gray-500 mb-16">Pihak Pengirim (Pusat)</p>
                    <p class="font-medium text-gray-800 underline decoration-gray-300 underline-offset-4">{{ $transferToBranch->creator->name }}</p>
                </div>
                <div>
                    <p class="text-gray-500 mb-16">Pihak Penerima (Cabang)</p>
                    <p class="font-medium text-gray-800 underline decoration-gray-300 underline-offset-4">{{ $transferToBranch->receiver->name ?? '( .................... )' }}</p>
                    @if($transferToBranch->status == 'received')
                        <p class="text-xs text-gray-500 mt-1">Diterima pada: {{ \Carbon\Carbon::parse($transferToBranch->updated_at)->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Tombol Terima (Hanya untuk Cabang yang bersangkutan) --}}
        @if(!$isPusat && $transferToBranch->status === 'sent' && $transferToBranch->branch_id === auth()->user()->branch_id)
        <div class="p-6 bg-amber-50 border-t border-amber-100 flex justify-between items-center print:hidden">
            <div>
                <p class="font-bold text-amber-800">Konfirmasi Penerimaan Barang</p>
                <p class="text-sm text-amber-700">Pastikan fisik barang sesuai dengan surat jalan sebelum menekan tombol terima.</p>
            </div>
            <form action="{{ route('hendhys.transfer-to-branch.receive', $transferToBranch->id) }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('Apakah Anda yakin barang sudah diterima dan sesuai? Stok cabang akan bertambah.')" class="bg-[#d97706] hover:bg-[#b45309] text-white px-6 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm">
                    Konfirmasi Terima Barang
                </button>
            </form>
        </div>
        @endif

    </div>
</div>
@endsection
