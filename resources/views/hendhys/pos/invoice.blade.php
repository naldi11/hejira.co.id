<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur - {{ $transaction->transaction_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: 9.5in 5.5in landscape;
            margin: 0; /* Menonaktifkan header & footer bawaan browser secara otomatis */
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            background: #fff;
            color: #000;
            font-size: 11px;
            padding: 0;
            margin: 0;
        }

        /* ===== Wrapper ===== */
        .page-wrapper {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            box-shadow: none;
            padding: 4mm 6mm; /* Memberikan margin fisik aman sebagai padding */
            min-height: 5.5in;
            box-sizing: border-box;
        }

        /* ===== Header Strip ===== */
        .header-strip {
            background: #000;
            height: 2px;
        }

        /* ===== Invoice Header ===== */
        .invoice-header {
            padding: 10px 20px 5px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .brand-block {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 4px;
        }

        .brand-info .invoice-label {
            font-size: 13px;
            font-weight: bold;
            color: #000;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .brand-info h1 {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            line-height: 1.1;
        }

        .brand-info p {
            font-size: 10px;
            color: #000;
            margin-top: 1px;
            line-height: 1.3;
        }

        .invoice-meta-bar {
            display: flex;
            justify-content: space-between;
            padding: 6px 20px;
            background: #fff;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
            color: #000;
        }

        /* ===== Customer Info ===== */
        .meta-section {
            padding: 8px 20px;
            display: flex;
            justify-content: space-between;
        }

        .meta-item {
            font-size: 10px;
        }

        .meta-item span {
            display: block;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            margin-bottom: 2px;
        }

        .meta-item b {
            color: #000;
            font-size: 11px;
        }

        /* ===== Items Table ===== */
        .table-section {
            padding: 5px 20px;
            min-height: 50mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            border-bottom: 1px solid #000;
        }

        thead th {
            padding: 4px 2px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
            text-align: left;
        }

        thead th.text-right { text-align: right; }
        thead th.text-center { text-align: center; }

        tbody td {
            padding: 4px 2px;
            font-size: 10px;
            border-bottom: 1px dashed #000;
            vertical-align: middle;
            color: #000;
        }

        td.no { width: 25px; text-align: center; color: #000; }
        td.qty { width: 40px; text-align: center; font-weight: bold; }
        td.unit { width: 40px; text-align: center; color: #000; }
        td.price { width: 80px; text-align: right; }
        td.disc  { width: 70px; text-align: right; color: #000; }
        td.total { width: 90px; text-align: right; font-weight: bold; color: #000; }
        td.product-name { font-weight: bold; color: #000; }

        /* ===== Totals ===== */
        .totals-section {
            display: flex;
            justify-content: space-between;
            padding: 8px 20px;
            align-items: flex-end;
        }

        .payment-status {
            width: 200px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border: 1px solid #000;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .totals-box {
            width: 240px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px 0;
            font-size: 10px;
        }

        .totals-row.grand {
            border-top: 1px solid #000;
            margin-top: 3px;
            padding-top: 6px;
            font-size: 12px;
            font-weight: bold;
        }

        .totals-row .label { color: #000; font-weight: bold; }
        .totals-row.grand .label { color: #000; }

        .totals-row .value { font-weight: bold; color: #000; }
        .totals-row.grand .value { color: #000; font-size: 13px; }

        /* ===== Footer ===== */
        .invoice-footer {
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px dashed #000;
        }

        .footer-note {
            font-size: 9px;
            color: #000;
            line-height: 1.4;
            max-width: 60%;
        }

        .signature-block {
            text-align: center;
            font-size: 10px;
            color: #000;
        }

        .signature-name {
            font-weight: bold;
            margin-top: 35px;
            text-decoration: underline;
        }

        /* ===== Action Buttons ===== */
        .action-bar {
            max-width: 9.5in;
            margin: 0 auto 16px;
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 12px 0;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back { background: #f3f4f6; color: #374151; }
        .btn-back:hover { background: #e5e7eb; }
        .btn-print { background: #000; color: white; }
        .btn-print:hover { background: #333; }

        @media print {
            body { background: white; }
            .page-wrapper { margin: 0; box-shadow: none; border-radius: 0; max-width: 100%; min-height: auto; }
            .action-bar { display: none !important; }
            .header-strip { display: none; }
        }
    </style>
</head>
<body>

<div class="action-bar print:hidden">
    <a href="{{ route('hendhys.pos.index') }}" class="btn btn-back">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke POS
    </a>
    <button onclick="window.print()" class="btn btn-print">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Cetak Faktur
    </button>
</div>

<div class="page-wrapper">
    <div class="header-strip"></div>

    {{-- ===== HEADER ===== --}}
    <div class="invoice-header">
        <div class="brand-block">
            <img src="{{ asset('logo/hendhys-logo.png') }}" alt="Hendhys Logo" class="brand-logo" onerror="this.style.display='none'">
            <div class="brand-info">
                <div class="invoice-label">FAKTUR PENJUALAN</div>
                <h1>HENDHYS BROWNIES</h1>
                @php
                    $branch = auth()->user()->branch;
                @endphp
                <p>{{ $branch->name }}</p>
                <p>{{ $branch->address }}</p>
            </div>
        </div>
    </div>

    <div class="invoice-meta-bar">
        <span>No: {{ $transaction->transaction_number }}</span>
        <span>{{ \Carbon\Carbon::parse($transaction->date)->translatedFormat('d F Y') }} {{ $transaction->time }}</span>
    </div>

    {{-- ===== CUSTOMER INFO ===== --}}
    <div class="meta-section">
        <div class="meta-item">
            <span>Pembeli / Customer:</span>
            <b>{{ $transaction->customer_name ?: 'PELANGGAN UMUM' }}</b>
        </div>
        <div class="meta-item" style="text-align: right">
            <span>Petugas Kasir:</span>
            <b>{{ $transaction->creator->name ?? 'Kasir' }}</b>
        </div>
    </div>

    {{-- ===== ITEMS TABLE ===== --}}
    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:25px">No</th>
                    <th>Nama Produk</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Sat</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $i => $item)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td class="product-name">{{ $item->product_name }}</td>
                    <td class="qty">{{ (int) $item->quantity }}</td>
                    <td class="unit">{{ $item->unit->abbreviation ?? 'pcs' }}</td>
                    <td class="price">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="total">{{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== TOTALS ===== --}}
    <div class="totals-section">
        <div class="payment-status">
            @php $payment = $transaction->payments->first(); @endphp
            @if($payment)
                <div class="status-badge">LUNAS</div>
                <div style="font-size: 9px; color: #6b7280; font-weight: 700">
                    METODE: {{ strtoupper($payment->method->name ?? $payment->payment_method) }}
                </div>
            @else
                <div class="status-badge" style="border-color: #dc2626; color: #dc2626;">PENDING</div>
            @endif
        </div>
        
        <div class="totals-box">
            <div class="totals-row">
                <span class="label">Subtotal</span>
                <span class="value">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($transaction->discount_amount > 0)
            <div class="totals-row">
                <span class="label">Diskon</span>
                <span class="value">-(Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }})</span>
            </div>
            @endif
            @if($transaction->tax_amount > 0)
            <div class="totals-row">
                <span class="label">Pajak</span>
                <span class="value">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="totals-row grand">
                <span class="label">TOTAL AKHIR</span>
                <span class="value">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
            </div>
            @if($payment)
            <div class="totals-row" style="margin-top: 4px">
                <span class="label">Tunai</span>
                <span class="value">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
            </div>
            <div class="totals-row">
                <span class="label">Kembalian</span>
                <span class="value" style="color:#059669">
                    Rp {{ number_format(max(0, $payment->amount - $transaction->grand_total), 0, ',', '.') }}
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="invoice-footer">
        <div class="footer-note">
            <b>Catatan:</b> Terima kasih telah membeli produk Hendhys Brownies. 
            Semoga hari Anda menyenangkan!
        </div>
        <div class="signature-block">
            <div>Diterima Oleh,</div>
            <div class="signature-name">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
        </div>
        <div class="signature-block">
            <div>Hormat Kami,</div>
            <div class="signature-name">{{ auth()->user()->name }}</div>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        setTimeout(function() { window.print(); }, 600);
    }
</script>
</body>
</html>
