@extends('layouts.jihans')
@section('title', 'Cetak Faktur')
@section('page-title', 'Faktur Penjualan')

@section('content')
<style>
    @media print {
        body { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 20px; }
        .no-print { display: none !important; }
        aside, header, nav { display: none !important; }
    }
</style>

<div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 h-full overflow-y-auto">
    <div class="mb-4 flex justify-between items-center no-print border-b pb-4">
        <a href="{{ route('jihans.transactions.index') }}" class="text-orange-600 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Kembali
        </a>
        <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded-lg shadow hover:bg-gray-900 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg> Cetak Faktur
        </button>
    </div>

    <!-- Area yang akan di-print -->
    <div class="print-area bg-white p-8 max-w-4xl mx-auto text-gray-800">
        <div class="flex justify-between items-start border-b border-gray-200 pb-6 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-orange-600 mb-2">JIHAAN'S FOOD</h1>
                <p class="text-sm">{{ $transaction->branch->name ?? 'Pusat' }}</p>
                <p class="text-sm">{{ $transaction->branch->address ?? 'Alamat' }}</p>
                <p class="text-sm">Telp: {{ $transaction->branch->phone ?? '-' }}</p>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">FAKTUR PENJUALAN</h2>
                <p class="text-sm"><strong>No:</strong> {{ $transaction->transaction_number }}</p>
                <p class="text-sm"><strong>Tanggal:</strong> {{ $transaction->date->format('d/m/Y') }} {{ $transaction->time }}</p>
                <p class="text-sm"><strong>Kasir:</strong> {{ $transaction->creator->name ?? 'Sistem' }}</p>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="font-bold mb-2 text-gray-700">Kepada Yth:</h3>
            <p class="text-lg font-semibold">{{ $transaction->customer ? $transaction->customer->name : ($transaction->customer_name ?? 'Pelanggan Umum') }}</p>
            @if($transaction->customer)
                <p class="text-sm">{{ $transaction->customer->phone }}</p>
                <p class="text-sm">{{ $transaction->customer->address }}</p>
            @endif
            <p class="text-sm text-gray-500 mt-1">Kategori: <span class="capitalize">{{ $transaction->customer_type }}</span></p>
        </div>

        <table class="w-full text-left border-collapse mb-6">
            <thead>
                <tr class="bg-gray-50 border-y border-gray-200">
                    <th class="p-3 text-sm font-bold text-gray-600">No</th>
                    <th class="p-3 text-sm font-bold text-gray-600">Deskripsi Barang</th>
                    <th class="p-3 text-sm font-bold text-right text-gray-600">Harga</th>
                    <th class="p-3 text-sm font-bold text-center text-gray-600">Qty</th>
                    <th class="p-3 text-sm font-bold text-right text-gray-600">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($transaction->details as $index => $detail)
                <tr class="border-b border-gray-100">
                    <td class="p-3 text-sm">{{ $index + 1 }}</td>
                    <td class="p-3 text-sm">{{ $detail->product_name }}</td>
                    <td class="p-3 text-sm text-right">{{ number_format($detail->price, 0, ',', '.') }}</td>
                    <td class="p-3 text-sm text-center">{{ (float) $detail->quantity }} {{ $detail->unit->code ?? '' }}</td>
                    <td class="p-3 text-sm text-right">{{ number_format($detail->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end mb-8">
            <div class="w-72">
                <div class="flex justify-between py-1">
                    <span class="text-sm font-bold text-gray-600">Subtotal</span>
                    <span class="text-sm">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($transaction->discount_amount > 0)
                <div class="flex justify-between py-1">
                    <span class="text-sm font-bold text-red-600">Diskon</span>
                    <span class="text-sm text-red-600">- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($transaction->tax_amount > 0)
                <div class="flex justify-between py-1">
                    <span class="text-sm font-bold text-gray-600">PPN ({{ $transaction->ppn_type }})</span>
                    <span class="text-sm">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($transaction->other_costs > 0)
                <div class="flex justify-between py-1">
                    <span class="text-sm font-bold text-gray-600">Biaya Lain</span>
                    <span class="text-sm">Rp {{ number_format($transaction->other_costs, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between py-2 mt-2 border-t-2 border-gray-800">
                    <span class="text-lg font-bold text-gray-800">Total Akhir</span>
                    <span class="text-lg font-bold text-orange-600">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-end border-t border-gray-200 pt-6">
            <div class="text-sm text-gray-600">
                <p class="font-bold mb-1 text-gray-800">Status Pembayaran: <span class="uppercase {{ $transaction->status == 'sukses' ? 'text-green-600' : 'text-orange-600' }}">{{ $transaction->status }}</span></p>
                <p>Metode: <span class="capitalize">{{ $transaction->payment_method }}</span></p>
                @if($transaction->notes)
                    <p class="mt-2 italic">Catatan: {{ $transaction->notes }}</p>
                @endif
            </div>
            <div class="text-sm text-center text-gray-800">
                <p class="mb-12">Hormat Kami,</p>
                <p class="font-bold underline">{{ $transaction->creator->name ?? 'Admin' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection