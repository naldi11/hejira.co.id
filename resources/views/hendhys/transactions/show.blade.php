@extends('layouts.hendhys')
@section('title', 'Cetak Faktur')
@section('page-title', 'Faktur Penjualan')

@section('content')
<style>
    @media print {
        @page {
            size: 9.5in 5.5in;
            margin: 0; /* Menonaktifkan header & footer default browser secara otomatis */
        }
        body { 
            visibility: hidden; 
            background: #fff !important;
            font-family: 'Courier New', Courier, monospace !important;
            color: #000 !important;
            font-size: 11px !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .print-area, .print-area * { 
            visibility: visible; 
            font-family: 'Courier New', Courier, monospace !important;
            color: #000 !important;
        }
        .print-area { 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 8.2in; /* Dibatasi ke 8.2 inci agar area cetak tidak mengenai lubang traktor kanan (mencegah terpotong samping) */
            margin: 0; 
            padding: 9mm 6mm 4mm 6mm; /* Diperbesar dari atas agar Kop/No Transaksi tidak terpotong batas cetak printer */
            box-shadow: none !important;
            border: none !important;
            background: #fff !important;
            box-sizing: border-box !important;
        }
        .no-print { display: none !important; }
        /* Hide sidebar/header if any are not caught by body visibility hidden */
        aside, header, nav { display: none !important; }
        
        /* Clean table for dot matrix */
        table th {
            color: #000 !important;
            border-bottom: 1px solid #000 !important;
            background: none !important;
        }
        table td {
            border-bottom: 1px dashed #000 !important;
            color: #000 !important;
        }
        /* Hide logo strip on print */
        .text-primary, .text-gray-800, .text-gray-600 {
            color: #000 !important;
        }
    }
</style>

<div class="p-6 bg-surface-container-lowest h-full overflow-y-auto">
    <div class="mb-4 flex justify-between items-center no-print">
        <a href="{{ route('hendhys.transactions.index') }}" class="text-primary hover:underline flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali
        </a>
        <button onclick="window.print()" class="bg-primary text-on-primary px-4 py-2 rounded shadow hover:bg-opacity-90 flex items-center gap-2">
            <span class="material-symbols-outlined">print</span> Cetak Faktur
        </button>
    </div>

    <!-- Area yang akan di-print -->
    <div class="print-area bg-white p-8 border border-outline-variant rounded shadow-sm max-w-4xl mx-auto text-on-surface">
        <div class="flex justify-between items-start border-b border-outline-variant pb-6 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-primary mb-2">HENDHYS BROWNIES</h1>
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
            <h3 class="font-bold mb-2">Kepada Yth:</h3>
            <p class="text-lg font-semibold">{{ $transaction->customer_name ?? 'Pelanggan Umum' }}</p>
            @if($transaction->customer)
                <p class="text-sm">{{ $transaction->customer->phone }}</p>
                <p class="text-sm">{{ $transaction->customer->address }}</p>
            @endif
            <p class="text-sm text-gray-600 mt-1">Kategori: <span class="capitalize">{{ $transaction->customer_type }}</span></p>
        </div>

        <table class="w-full text-left border-collapse mb-6">
            <thead>
                <tr class="bg-gray-100 border-y border-gray-300">
                    <th class="p-3 text-sm font-bold">No</th>
                    <th class="p-3 text-sm font-bold">Deskripsi Barang</th>
                    <th class="p-3 text-sm font-bold text-right">Harga</th>
                    <th class="p-3 text-sm font-bold text-center">Qty</th>
                    <th class="p-3 text-sm font-bold text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $index => $detail)
                <tr class="border-b border-gray-200">
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
                    <span class="text-sm font-bold">Subtotal</span>
                    <span class="text-sm">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($transaction->discount_amount > 0)
                <div class="flex justify-between py-1">
                    <span class="text-sm font-bold text-error">Diskon</span>
                    <span class="text-sm text-error">- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($transaction->tax_amount > 0)
                <div class="flex justify-between py-1">
                    <span class="text-sm font-bold">PPN ({{ $transaction->ppn_type }})</span>
                    <span class="text-sm">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($transaction->other_costs > 0)
                <div class="flex justify-between py-1">
                    <span class="text-sm font-bold">Biaya Lain</span>
                    <span class="text-sm">Rp {{ number_format($transaction->other_costs, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between py-2 mt-2 border-t-2 border-gray-800">
                    <span class="text-lg font-bold">Total Akhir</span>
                    <span class="text-lg font-bold text-primary">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-end border-t border-gray-200 pt-6">
            <div class="text-sm">
                <p class="font-bold mb-1">Status Pembayaran: <span class="uppercase {{ $transaction->status == 'sukses' ? 'text-green-600' : 'text-orange-600' }}">{{ $transaction->status }}</span></p>
                <p>Metode: <span class="capitalize">{{ $transaction->payment_method }}</span></p>
                @if($transaction->notes)
                    <p class="mt-2 italic text-gray-600">Catatan: {{ $transaction->notes }}</p>
                @endif
            </div>
            <div class="text-sm text-center">
                <p class="mb-12">Hormat Kami,</p>
                <p class="font-bold underline">{{ $transaction->creator->name ?? 'Admin' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection