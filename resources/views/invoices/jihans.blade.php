<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $transaction->transaction_number }}</title>
    <style>
        @page { size: 9.5in 5.5in; margin: 4mm 6mm; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 11px; line-height: 1.3; color: #000; background: #fff; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 6px; }
        .logo { font-size: 18px; font-weight: bold; color: #000; }
        .company-info { float: left; width: 50%; }
        .invoice-info { float: right; width: 45%; text-align: right; }
        .clearfix { clear: both; }
        .customer-info { margin-bottom: 15px; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table th { background: #fff; border-bottom: 2px solid #000; padding: 6px; text-align: left; font-weight: bold; color: #000; }
        table td { border-bottom: 1px dashed #000; padding: 6px; color: #000; }
        .totals { float: right; width: 280px; }
        .totals-row { margin-bottom: 4px; font-size: 10px; }
        .totals-label { display: inline-block; width: 140px; color: #000; }
        .totals-value { display: inline-block; width: 130px; text-align: right; font-weight: bold; color: #000; }
        .footer { margin-top: 25px; text-align: center; color: #000; font-size: 9px; border-top: 1px dashed #000; padding-top: 6px; }
        .status-paid { color: #000; font-weight: bold; text-transform: uppercase; border: 1px solid #000; padding: 1px 4px; }
        .status-pending { color: #000; font-weight: bold; text-transform: uppercase; border: 1px dashed #000; padding: 1px 4px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="logo">JIHAN'S FOOD</div>
            <div>Pabrik & Retail Tortilla</div>
            <div>Jl. Contoh No. 123, Indonesia</div>
            <div>Telp: 0812-3456-7890</div>
        </div>
        <div class="invoice-info">
            <h2 style="margin: 0; color: #333;">FAKTUR PENJUALAN</h2>
            <div>No: <strong>{{ $transaction->transaction_number }}</strong></div>
            <div>Tanggal: {{ $transaction->date->format('d/m/Y') }}</div>
            <div>Waktu: {{ $transaction->time }}</div>
            <div>Kasir: {{ $transaction->creator->name }}</div>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="customer-info">
        <div><strong>Kepada:</strong></div>
        <div>{{ $transaction->customer_name }}</div>
        @if($transaction->customer)
            <div>{{ $transaction->customer->phone }}</div>
            <div>{{ $transaction->customer->address }}</div>
        @endif
        <div>Kategori: {{ $transaction->customer_type }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Item</th>
                <th style="width: 15%; text-align: right;">Harga</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 15%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->details as $index => $detail)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $detail->product->name }}</td>
                <td style="text-align: right;">{{ number_format($detail->selling_price, 0, ',', '.') }}</td>
                <td style="text-align: center;">{{ $detail->quantity }}</td>
                <td style="text-align: right;">{{ number_format($detail->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">Subtotal</span>
            <span class="totals-value">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
        </div>
        @if($transaction->discount_amount > 0)
        <div class="totals-row">
            <span class="totals-label">Diskon</span>
            <span class="totals-value">- Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($transaction->tax_amount > 0)
        <div class="totals-row">
            <span class="totals-label">PPN ({{ $transaction->ppn_rate }}%)</span>
            <span class="totals-value">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($transaction->other_costs > 0)
        <div class="totals-row">
            <span class="totals-label">Biaya Lain</span>
            <span class="totals-value">Rp {{ number_format($transaction->other_costs, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="totals-row" style="border-top: 1px solid #333; padding-top: 5px; margin-top: 5px;">
            <span class="totals-label" style="font-size: 14px;"><strong>Total Akhir</strong></span>
            <span class="totals-value" style="font-size: 14px;"><strong>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</strong></span>
        </div>
    </div>
    <div class="clearfix"></div>

    <div style="margin-top: 20px;">
        <div>Status Pembayaran: 
            <span class="status-{{ $transaction->status }}">
                {{ $transaction->status == 'paid' ? 'LUNAS' : 'PENDING/HUTANG' }}
            </span>
        </div>
        @if($transaction->notes)
            <div style="margin-top: 10px; font-style: italic;">Catatan: {{ $transaction->notes }}</div>
        @endif
    </div>

    <div class="footer">
        <div>Terima kasih atas kunjungan Anda</div>
        <div>Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan</div>
        <div>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
