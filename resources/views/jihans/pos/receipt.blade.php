<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - {{ $transaction->transaction_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background-color: #f3f4f6; color: #1f2937; }
        .receipt-container { max-width: 80mm; margin: 2rem auto; background: white; padding: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .receipt-header { text-align: center; margin-bottom: 1rem; border-bottom: 1px dashed #ccc; padding-bottom: 1rem; }
        .receipt-logo { font-size: 2rem; margin-bottom: 0.5rem; }
        .receipt-info { font-size: 0.75rem; color: #4b5563; margin-bottom: 1rem; }
        .item-list { border-bottom: 1px dashed #ccc; padding-bottom: 0.5rem; margin-bottom: 0.5rem; }
        .item { font-size: 0.8rem; margin-bottom: 0.5rem; }
        .item-name { font-weight: 600; display: block; }
        .item-details { display: flex; justify-content: space-between; color: #4b5563; }
        .totals { font-size: 0.8rem; border-bottom: 1px dashed #ccc; padding-bottom: 0.5rem; margin-bottom: 0.5rem; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 0.25rem; }
        .grand-total { font-weight: bold; font-size: 1rem; }
        .footer { text-align: center; font-size: 0.7rem; color: #6b7280; margin-top: 1rem; }
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .receipt-container { box-shadow: none; margin: 0; padding: 0; max-width: 100%; }
            .print-btn, .back-btn { display: none !important; }
        }
    </style>
</head>
<body>

<div class="max-w-xs mx-auto mt-4 mb-2 flex justify-center gap-2 print:hidden">
    <a href="{{ route('jihans.pos.index') }}" class="back-btn px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">Kembali ke POS</a>
    <button onclick="window.print()" class="print-btn px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">Cetak Struk</button>
</div>

<div class="receipt-container">
    <div class="receipt-header">
        <div class="receipt-logo">🫓</div>
        <h1 class="text-xl font-bold">JIHAN'S FOOD</h1>
        <p class="text-xs text-gray-500">Pabrik Tortilla & Kebab</p>
        <p class="text-xs text-gray-500">Jl. Contoh No. 123, Kota</p>
    </div>

    <div class="receipt-info">
        <div class="flex justify-between">
            <span>No:</span>
            <span class="font-mono">{{ $transaction->transaction_number }}</span>
        </div>
        <div class="flex justify-between">
            <span>Tgl:</span>
            <span>{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/y') }} {{ $transaction->time }}</span>
        </div>
        <div class="flex justify-between">
            <span>Kasir:</span>
            <span>{{ $transaction->creator->name ?? 'Admin' }}</span>
        </div>
        <div class="flex justify-between">
            <span>Plg:</span>
            <span>{{ $transaction->customer_name }}</span>
        </div>
    </div>

    <div class="item-list">
        @foreach($transaction->details as $item)
        <div class="item">
            <span class="item-name">{{ $item->product_name }}</span>
            <div class="item-details">
                <span>{{ (float) $item->quantity }} {{ $item->unit->abbreviation ?? 'pcs' }} x {{ number_format($item->price, 0, ',', '.') }}</span>
                <span>{{ number_format($item->total, 0, ',', '.') }}</span>
            </div>
            @if($item->discount_amount > 0)
                <div class="text-xs text-right text-gray-500">
                    Disc: -{{ number_format($item->discount_amount, 0, ',', '.') }}
                </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="totals">
        <div class="total-row">
            <span>Subtotal</span>
            <span>{{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
        </div>
        @if($transaction->discount_amount > 0)
        <div class="total-row">
            <span>Diskon Item</span>
            <span>-{{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($transaction->tax_amount > 0)
        <div class="total-row">
            <span>PPN ({{ $transaction->ppn_type }})</span>
            <span>+{{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($transaction->other_costs > 0)
        <div class="total-row">
            <span>Biaya Lain/Ongkir</span>
            <span>+{{ number_format($transaction->other_costs, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="total-row grand-total mt-2 pt-1 border-t border-gray-200">
            <span>TOTAL</span>
            <span>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
        </div>
    </div>

    @php
        $payment = $transaction->payments->first();
    @endphp
    @if($payment)
    <div class="totals !border-none !pb-0 !mb-0">
        <div class="total-row">
            <span>Bayar ({{ ucfirst($payment->payment_method) }})</span>
            <span>{{ number_format($payment->amount, 0, ',', '.') }}</span>
        </div>
        <div class="total-row">
            <span>Kembali</span>
            <span>{{ number_format(max(0, $payment->amount - $transaction->grand_total), 0, ',', '.') }}</span>
        </div>
    </div>
    @endif

    <div class="footer">
        <p class="font-medium">Terima Kasih</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
    </div>
</div>

<script>
    // Otomatis trigger print saat halaman diload
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    }
</script>
</body>
</html>
