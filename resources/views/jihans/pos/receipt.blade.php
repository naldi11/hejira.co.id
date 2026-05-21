<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur - {{ $transaction->transaction_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Arial', sans-serif;
            background: #e5e7eb;
            color: #1f2937;
            font-size: 13px;
        }

        /* ===== Wrapper ===== */
        .page-wrapper {
            max-width: 210mm;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            border-radius: 4px;
            overflow: hidden;
        }

        /* ===== Header Strip ===== */
        .header-strip {
            background: #c2410c;
            height: 6px;
        }

        /* ===== Invoice Header ===== */
        .invoice-header {
            padding: 28px 36px 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 2px solid #f3f4f6;
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .brand-info h1 {
            font-size: 22px;
            font-weight: 800;
            color: #c2410c;
            letter-spacing: 1px;
            line-height: 1.1;
        }

        .brand-info p {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
            line-height: 1.5;
        }

        .invoice-title-block {
            text-align: right;
        }

        .invoice-title-block h2 {
            font-size: 26px;
            font-weight: 900;
            color: #c2410c;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .invoice-number {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-top: 6px;
            font-family: 'Courier New', monospace;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 3px 10px;
            border-radius: 4px;
            display: inline-block;
        }

        .invoice-date {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ===== Meta Row ===== */
        .meta-row {
            display: flex;
            gap: 0;
            padding: 16px 36px;
            background: #fafafa;
            border-bottom: 1px solid #f0f0f0;
        }

        .meta-block {
            flex: 1;
        }

        .meta-block + .meta-block {
            border-left: 1px solid #e5e7eb;
            padding-left: 20px;
            margin-left: 20px;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
        }

        .meta-value.customer {
            color: #c2410c;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-retail { background: #dbeafe; color: #1d4ed8; }
        .badge-agen   { background: #d1fae5; color: #065f46; }
        .badge-lainnya{ background: #f3f4f6; color: #6b7280; }

        /* ===== Items Table ===== */
        .table-section {
            padding: 24px 36px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: #c2410c;
            color: white;
        }

        thead th {
            padding: 10px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        thead th:last-child { text-align: right; }
        thead th.text-right { text-align: right; }
        thead th.text-center { text-align: center; }

        tbody tr:nth-child(even) { background: #fefce8; }
        tbody tr:hover { background: #fef9c3; }

        tbody td {
            padding: 9px 12px;
            font-size: 12.5px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        td.no { width: 32px; text-align: center; color: #9ca3af; font-size: 11px; }
        td.qty { width: 70px; text-align: center; font-weight: 600; }
        td.price { width: 110px; text-align: right; }
        td.disc  { width: 100px; text-align: right; color: #dc2626; }
        td.total { width: 120px; text-align: right; font-weight: 700; color: #1f2937; }
        td.product-name { font-weight: 600; color: #111827; }

        /* ===== Totals ===== */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            padding: 0 36px 28px;
        }

        .totals-box {
            width: 320px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            font-size: 12.5px;
            border-bottom: 1px solid #f3f4f6;
        }

        .totals-row:last-child { border-bottom: none; }

        .totals-row.grand {
            background: #c2410c;
            color: white;
            font-size: 15px;
            font-weight: 800;
            padding: 12px 16px;
        }

        .totals-row .label { color: #6b7280; }
        .totals-row.grand .label { color: rgba(255,255,255,0.85); }

        .totals-row .value { font-weight: 600; color: #1f2937; }
        .totals-row.grand .value { color: white; font-size: 17px; }

        .totals-row.discount .value { color: #dc2626; }

        .payment-info {
            margin-top: 1px;
            background: #f8fafc;
        }

        /* ===== Footer ===== */
        .invoice-footer {
            border-top: 2px solid #f3f4f6;
            padding: 16px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
        }

        .footer-note {
            font-size: 10px;
            color: #9ca3af;
            line-height: 1.6;
        }

        .footer-note strong { color: #6b7280; }

        .signature-block {
            text-align: center;
            font-size: 11px;
            color: #6b7280;
        }

        .signature-line {
            width: 120px;
            height: 1px;
            background: #d1d5db;
            margin: 40px auto 4px;
        }

        .bottom-strip {
            background: #c2410c;
            height: 4px;
        }

        /* ===== Action Buttons ===== */
        .action-bar {
            max-width: 210mm;
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
        .btn-print { background: #c2410c; color: white; }
        .btn-print:hover { background: #9a3412; }

        @media print {
            body { background: white; }
            .page-wrapper { margin: 0; box-shadow: none; border-radius: 0; max-width: 100%; }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

<div class="action-bar print:hidden">
    <a href="{{ route('jihans.pos.index') }}" class="btn btn-back">
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
            <img src="{{ asset('logo/jihans-logo.png') }}" alt="Jihan's Food Logo" class="brand-logo">
            <div class="brand-info">
                <h1>JIHAN'S FOOD</h1>
                <p>Pabrik Tortilla & Kebab</p>
                <p>Jl. Contoh No. 123, Kota</p>
                <p>Telp. 0812-3456-7890</p>
            </div>
        </div>
        <div class="invoice-title-block">
            <h2>FAKTUR</h2>
            <div class="invoice-number">{{ $transaction->transaction_number }}</div>
            <div class="invoice-date">
                {{ \Carbon\Carbon::parse($transaction->date)->translatedFormat('d F Y') }}
                &nbsp;Â·&nbsp; {{ $transaction->time }}
            </div>
        </div>
    </div>

    {{-- ===== META ROW ===== --}}
    <div class="meta-row">
        <div class="meta-block">
            <div class="meta-label">Pelanggan</div>
            <div class="meta-value customer">{{ $transaction->customer_name ?: '—' }}</div>
        </div>
        <div class="meta-block">
            <div class="meta-label">Kasir</div>
            <div class="meta-value">{{ $transaction->creator->name ?? 'Admin' }}</div>
        </div>
        <div class="meta-block">
            <div class="meta-label">Status Pembayaran</div>
            <div class="meta-value">
                @php $payment = $transaction->payments->first(); @endphp
                @if($payment)
                    <span class="badge" style="background:#d1fae5;color:#065f46">LUNAS</span>
                    &nbsp; {{ ucfirst($payment->payment_method) }}
                @else
                    <span class="badge" style="background:#fee2e2;color:#b91c1c">BELUM DIBAYAR</span>
                @endif
            </div>
        </div>
        <div class="meta-block">
            <div class="meta-label">PPN</div>
            <div class="meta-value">
                @if($transaction->ppn_type === 'none' || !$transaction->ppn_type)
                    Tanpa PPN
                @elseif($transaction->ppn_type === 'include')
                    Include PPN 11%
                @else
                    Exclude PPN 11%
                @endif
            </div>
        </div>
    </div>

    {{-- ===== ITEMS TABLE ===== --}}
    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:36px">No</th>
                    <th>Nama Produk / Barang</th>
                    <th class="text-center" style="width:80px">Qty</th>
                    <th class="text-center" style="width:60px">Sat</th>
                    <th class="text-right" style="width:120px">Harga Satuan</th>
                    <th class="text-right" style="width:110px">Diskon</th>
                    <th class="text-right" style="width:130px">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $i => $item)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td class="product-name">{{ $item->product_name }}</td>
                    <td class="qty">{{ (int) $item->quantity }}</td>
                    <td style="text-align:center;color:#6b7280">{{ $item->unit->abbreviation ?? 'pcs' }}</td>
                    <td class="price">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="disc">
                        @if($item->discount_amount > 0)
                            ({{ number_format($item->discount_amount, 0, ',', '.') }})
                        @else
                            —
                        @endif
                    </td>
                    <td class="total">{{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== TOTALS ===== --}}
    <div class="totals-section">
        <div class="totals-box">
            <div class="totals-row">
                <span class="label">Subtotal</span>
                <span class="value">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($transaction->discount_amount > 0)
            <div class="totals-row discount">
                <span class="label">Diskon</span>
                <span class="value">(Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }})</span>
            </div>
            @endif
            @if($transaction->tax_amount > 0)
            <div class="totals-row">
                <span class="label">PPN ({{ strtoupper($transaction->ppn_type) }})</span>
                <span class="value">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($transaction->other_costs > 0)
            <div class="totals-row">
                <span class="label">Biaya Lain</span>
                <span class="value">Rp {{ number_format($transaction->other_costs, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="totals-row grand">
                <span class="label">TOTAL</span>
                <span class="value">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
            </div>
            @if($payment)
            <div class="totals-row payment-info">
                <span class="label">Bayar ({{ ucfirst($payment->payment_method) }})</span>
                <span class="value">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
            </div>
            <div class="totals-row payment-info">
                <span class="label">Kembalian</span>
                <span class="value" style="color:#059669;font-weight:700">
                    Rp {{ number_format(max(0, $payment->amount - $transaction->grand_total), 0, ',', '.') }}
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="invoice-footer">
        <div class="footer-note">
            <strong>Catatan:</strong><br>
            Â· Barang yang sudah dibeli tidak dapat dikembalikan.<br>
            Â· Simpan faktur ini sebagai bukti pembelian yang sah.<br>
            Â· Terima kasih telah berbelanja di Jihan's Food!
        </div>
        <div class="signature-block">
            <div class="signature-line"></div>
            <div>Hormat Kami,</div>
            <div style="font-weight:700;color:#1f2937;margin-top:2px">Jihan's Food</div>
        </div>
    </div>
    <div class="bottom-strip"></div>
</div>

<script>
    window.onload = function() {
        setTimeout(function() { window.print(); }, 600);
    }
</script>
</body>
</html>
