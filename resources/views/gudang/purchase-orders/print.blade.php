<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pesanan — {{ $po->po_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page { size: 9.5in 11in; margin: 10mm; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            background: #fff;
        }

        .page {
            max-width: 9.5in;
            min-height: 11in;
            margin: 10px auto;
            background: #fff;
            padding: 10mm;
            box-shadow: none;
        }

        .doc-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            border-bottom: 2px solid #000;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .header-text h1 {
            font-size: 9px;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 3px;
        }

        .header-text h2 {
            font-size: 20px;
            font-weight: 900;
            color: #000;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .header-text p { font-size: 10px; color: #000; }

        .po-badge {
            margin-left: auto;
            text-align: right;
        }

        .po-number {
            font-size: 16px;
            font-weight: 900;
            color: #000;
            letter-spacing: 1px;
        }

        .po-status {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 10px;
            border: 1px solid #000;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #000;
        }

        .status-draft    { background: #fff; color: #000; }
        .status-sent     { background: #fff; color: #000; }
        .status-partial  { background: #fff; color: #000; }
        .status-received { background: #fff; color: #000; }
        .status-cancelled{ background: #fff; color: #000; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
            background: #fff;
            border: 1px solid #000;
            padding: 12px 14px;
            margin-bottom: 16px;
        }

        .info-row { display: flex; gap: 6px; align-items: baseline; }
        .info-label { font-size: 9px; color: #000; text-transform: uppercase; letter-spacing: 0.5px; min-width: 80px; flex-shrink: 0; }
        .info-value { font-size: 11px; font-weight: 600; color: #000; }

        .section-title {
            font-size: 9px;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }

        thead tr { background: #fff; border-bottom: 2px solid #000; }
        thead th {
            padding: 8px 8px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            text-align: left;
        }
        thead th.text-center { text-align: center; }
        thead th.text-right  { text-align: right; }

        tbody tr { border-bottom: 1px dashed #000; }
        tbody tr:nth-child(even) { background: none; }
        tbody td { padding: 8px; font-size: 10px; vertical-align: middle; color: #000; }
        tbody td.text-center { text-align: center; }
        tbody td.text-right  { text-align: right; }

        tfoot td {
            padding: 10px 8px;
            font-size: 12px;
            font-weight: 700;
            color: #000;
            border-top: 2px solid #000;
        }

        .notes-box {
            border: 1px dashed #000;
            padding: 10px 12px;
            margin-bottom: 16px;
            background: #fff;
        }

        .notes-box .notes-title { font-size: 9px; font-weight: 700; color: #000; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .notes-box p { font-size: 11px; color: #000; }

        .signature-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
            border-top: 1px dashed #000;
            padding-top: 14px;
        }

        .sign-box { text-align: center; min-width: 180px; }
        .sign-label { font-size: 10px; color: #000; font-weight: 600; margin-bottom: 2px; }
        .sign-sublabel { font-size: 9px; color: #000; margin-bottom: 48px; }
        .sign-line { border-top: 1px solid #000; padding-top: 6px; margin: 0 20px; }
        .sign-name { font-size: 11px; font-weight: 700; color: #000; }

        .doc-footer {
            margin-top: 16px;
            font-size: 8px;
            color: #000;
            border-top: 1px dashed #000;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
        }

        .action-bar {
            max-width: 9.5in;
            margin: 0 auto 12px;
            display: flex;
            gap: 10px;
            padding: 10px 0;
        }
        .btn { padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back { background: #f3f4f6; color: #374151; }
        .btn-print { background: #000; color: white; }

        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; padding: 0; }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <a href="{{ route('gudang.po.show', $po) }}" class="btn btn-back">← Kembali</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Cetak Surat Pesanan</button>
</div>

<div class="page">

    {{-- Header --}}
    <div class="doc-header">
        <img src="{{ asset('logo/gudang-logo.png') }}" alt="Logo" class="logo" onerror="this.style.display='none'">
        <div class="header-text">
            <h1>Surat Pesanan Barang</h1>
            <h2>GUDANG TEMPUA</h2>
            <p>Purchase Order — Dokumen Resmi</p>
        </div>
        <div class="po-badge">
            <div class="po-number">{{ $po->po_number }}</div>
            @php
                $statusClass = ['draft'=>'status-draft','sent'=>'status-sent','partial'=>'status-partial','received'=>'status-received','cancelled'=>'status-cancelled'];
                $statusLabel = ['draft'=>'Draft','sent'=>'Terkirim ke Supplier','partial'=>'Diterima Sebagian','received'=>'Diterima Semua','cancelled'=>'Dibatalkan'];
            @endphp
            <span class="po-status {{ $statusClass[$po->status] ?? '' }}">{{ $statusLabel[$po->status] ?? $po->status }}</span>
        </div>
    </div>

    {{-- Info --}}
    <div class="info-grid">
        <div class="info-row"><span class="info-label">Kepada</span><span class="info-value">{{ $po->supplier->name }}</span></div>
        <div class="info-row"><span class="info-label">Tanggal PO</span><span class="info-value">{{ $po->date->translatedFormat('d F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Alamat Supplier</span><span class="info-value">{{ $po->supplier->address ?? '-' }}</span></div>
        <div class="info-row"><span class="info-label">Estimasi Tiba</span><span class="info-value">{{ $po->expected_date?->translatedFormat('d F Y') ?? '-' }}</span></div>
        <div class="info-row"><span class="info-label">Kontak</span><span class="info-value">{{ $po->supplier->phone ?? '-' }}</span></div>
        <div class="info-row"><span class="info-label">Dibuat Oleh</span><span class="info-value">{{ $po->creator->name }}</span></div>
    </div>

    {{-- Items --}}
    <p class="section-title">Daftar Barang yang Dipesan</p>
    @php $grandTotal = 0; @endphp
    <table>
        <thead>
            <tr>
                <th style="width:22px">No</th>
                <th>Nama Produk</th>
                <th class="text-center" style="width:70px">Qty</th>
                <th class="text-center" style="width:50px">Satuan</th>
                <th class="text-right" style="width:90px">Harga/Unit</th>
                <th class="text-right" style="width:100px">Total</th>
                <th style="width:80px">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->details as $i => $item)
            @php $grandTotal += $item->total; @endphp
            <tr>
                <td class="text-center" style="color:#9ca3af">{{ $i + 1 }}</td>
                <td style="font-weight:600">{{ $item->product->name }}</td>
                <td class="text-center" style="font-weight:700">{{ floatval($item->quantity_ordered) }}</td>
                <td class="text-center" style="color:#718096">{{ $item->unit->abbreviation ?? '-' }}</td>
                <td class="text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="text-right" style="font-weight:700">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                <td style="color:#718096;font-size:9px">{{ $item->notes ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right">TOTAL NILAI PESANAN</td>
                <td class="text-right" style="font-size:14px">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if($po->notes)
    <div class="notes-box">
        <div class="notes-title">Catatan / Instruksi Khusus</div>
        <p>{{ $po->notes }}</p>
    </div>
    @endif

    <div class="signature-section">
        <div class="sign-box">
            <div class="sign-label">Hormat Kami,</div>
            <div class="sign-sublabel">Tanda Tangan &amp; Cap</div>
            <div class="sign-line">
                <div class="sign-name">{{ $po->creator->name }}</div>
            </div>
        </div>
    </div>

    <div class="doc-footer">
        <span>Dicetak oleh: {{ auth()->user()->name }} pada {{ now()->format('d/m/Y H:i') }}</span>
        <span>{{ $po->po_number }} | {{ $po->supplier->name }}</span>
    </div>
</div>

<script>
    window.onload = function () { setTimeout(function () { window.print(); }, 500); };
</script>
</body>
</html>
