<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST — {{ $receiving->grn_number }}</title>
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
            margin: 20px auto;
            background: #fff;
            padding: 10mm;
            box-shadow: none;
        }

        /* Header */
        .doc-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 14px;
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
            letter-spacing: 1.5px;
            margin-bottom: 3px;
        }

        .header-text h2 {
            font-size: 18px;
            font-weight: 900;
            color: #000;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .header-text p {
            font-size: 10px;
            color: #000;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
            background: #fff;
            border: 1px solid #000;
            padding: 12px 14px;
            margin-bottom: 14px;
        }

        .info-row { display: flex; gap: 6px; align-items: baseline; }
        .info-label { font-size: 9px; color: #000; text-transform: uppercase; letter-spacing: 0.5px; min-width: 90px; flex-shrink: 0; }
        .info-value { font-size: 11px; font-weight: 600; color: #000; }

        /* Status badge */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border: 1px solid #000;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
        }
        .badge-open   { background: #fff; color: #000; }
        .badge-closed { background: #fff; color: #000; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }

        .section-title {
            font-size: 9px;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        thead tr { background: #fff; border-bottom: 2px solid #000; }
        thead th {
            padding: 7px 8px;
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
        tbody td { padding: 7px 8px; font-size: 10px; vertical-align: middle; color: #000; }
        tbody td.text-center { text-align: center; }
        tbody td.text-right  { text-align: right; }

        tfoot td {
            padding: 8px;
            font-size: 11px;
            font-weight: 700;
            color: #000;
            border-top: 2px solid #000;
        }

        /* Kondisi badge */
        .k-baik   { border: 1px solid #000; padding: 1px 6px; font-size: 9px; font-weight: 700; color: #000; }
        .k-rusak  { border: 1px dashed #000; padding: 1px 6px; font-size: 9px; font-weight: 700; color: #000; }
        .k-kurang { border: 1px solid #000; padding: 1px 6px; font-size: 9px; font-weight: 700; color: #000; }

        /* Kendala box */
        .kendala-box {
            border: 1px dashed #000;
            background: #fff;
            padding: 10px 12px;
            margin-bottom: 14px;
        }
        .kendala-box .kendala-title { font-size: 9px; font-weight: 700; color: #000; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .kendala-box p { font-size: 11px; color: #000; }

        /* Photo grid */
        .photo-section { margin-bottom: 14px; }
        .photo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .photo-item img { width: 100%; height: 55mm; object-fit: cover; border: 1px solid #000; border-radius: 4px; }
        .photo-caption { font-size: 8px; color: #000; margin-top: 3px; text-align: center; }

        /* Signature */
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 16px;
            border-top: 1px dashed #000;
            padding-top: 14px;
        }

        .sign-box { text-align: center; }
        .sign-label { font-size: 10px; color: #000; font-weight: 600; margin-bottom: 2px; }
        .sign-sublabel { font-size: 9px; color: #000; margin-bottom: 40px; }
        .sign-line { border-top: 1px solid #000; margin: 0 20px; padding-top: 6px; }
        .sign-name { font-size: 11px; font-weight: 700; color: #000; }

        /* Footer */
        .doc-footer {
            margin-top: 14px;
            font-size: 8px;
            color: #000;
            border-top: 1px dashed #000;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
        }

        /* Print */
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
    <a href="{{ route('gudang.receiving.show', $receiving) }}" class="btn btn-back">← Kembali</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Cetak BAST</button>
</div>

<div class="page">

    {{-- Header --}}
    <div class="doc-header">
        <img src="{{ asset('logo/gudang-logo.png') }}" alt="Logo" class="logo" onerror="this.style.display='none'">
        <div class="header-text">
            <h1>Berita Acara Serah Terima Barang</h1>
            <h2>GUDANG TEMPUA</h2>
            <p>Dokumen Penerimaan Stok Resmi</p>
        </div>
        <div style="margin-left:auto; text-align:right;">
            <span class="badge {{ $receiving->isOpen() ? 'badge-open' : 'badge-closed' }}">
                {{ $receiving->isOpen() ? 'DRAFT / TERBUKA' : 'FINAL / DITUTUP' }}
            </span>
        </div>
    </div>

    {{-- Info --}}
    <div class="info-grid">
        <div class="info-row"><span class="info-label">No. GRN</span><span class="info-value">{{ $receiving->grn_number }}</span></div>
        <div class="info-row"><span class="info-label">Tanggal Terima</span><span class="info-value">{{ $receiving->date->translatedFormat('d F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Supplier</span><span class="info-value">{{ $receiving->supplier->name }}</span></div>
        <div class="info-row"><span class="info-label">Ref. PO</span><span class="info-value">{{ $receiving->po?->po_number ?? 'Penerimaan Langsung' }}</span></div>
        <div class="info-row"><span class="info-label">No. Surat Jalan</span><span class="info-value">{{ $receiving->notes ?: '-' }}</span></div>
        <div class="info-row"><span class="info-label">Dicetak</span><span class="info-value">{{ now()->translatedFormat('d F Y H:i') }}</span></div>
    </div>

    {{-- Kendala --}}
    @if($receiving->kendala)
    <div class="kendala-box">
        <div class="kendala-title">⚠ Kendala / Catatan Masalah</div>
        <p>{{ $receiving->kendala }}</p>
    </div>
    @endif

    {{-- Items Table --}}
    <p class="section-title">Daftar Barang yang Diterima</p>
    @php $grandTotal = 0; @endphp
    <table>
        <thead>
            <tr>
                <th style="width:22px">No</th>
                <th>Nama Produk</th>
                @if($receiving->details->whereNotNull('quantity_ordered')->isNotEmpty())
                <th class="text-center" style="width:60px">Qty PO</th>
                @endif
                <th class="text-center" style="width:70px">Qty Terima</th>
                <th class="text-center" style="width:45px">Satuan</th>
                <th class="text-center" style="width:55px">Kondisi</th>
                <th class="text-right" style="width:80px">Harga/Unit</th>
                <th class="text-right" style="width:90px">Total</th>
                <th style="width:80px">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receiving->details as $i => $item)
            @php $grandTotal += $item->total; @endphp
            <tr>
                <td class="text-center" style="color:#9ca3af">{{ $i + 1 }}</td>
                <td style="font-weight:600">{{ $item->product->name }}</td>
                @if($receiving->details->whereNotNull('quantity_ordered')->isNotEmpty())
                <td class="text-center" style="color:#9ca3af">{{ $item->quantity_ordered ? number_format($item->quantity_ordered, 0) : '—' }}</td>
                @endif
                <td class="text-center" style="font-weight:700">{{ floatval($item->quantity) }}</td>
                <td class="text-center" style="color:#718096">{{ $item->unit->abbreviation ?? '-' }}</td>
                <td class="text-center">
                    @if($item->kondisi)
                    <span class="k-{{ $item->kondisi }}">{{ ucfirst($item->kondisi) }}</span>
                    @else <span style="color:#d1d5db">—</span> @endif
                </td>
                <td class="text-right">Rp {{ number_format($item->hpp_price, 0, ',', '.') }}</td>
                <td class="text-right" style="font-weight:700">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                <td style="color:#718096;font-size:9px">{{ $item->notes ?: '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ $receiving->details->whereNotNull('quantity_ordered')->isNotEmpty() ? 7 : 6 }}" style="text-align:right">TOTAL NILAI PENERIMAAN</td>
                <td class="text-right" style="color:#2d3748;font-size:13px">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- Foto --}}
    @if($receiving->photos->isNotEmpty())
    <div class="photo-section">
        <p class="section-title">Foto Bukti Penerimaan ({{ $receiving->photos->count() }} foto)</p>
        <div class="photo-grid">
            @foreach($receiving->photos as $photo)
            <div class="photo-item">
                <img src="{{ Storage::url($photo->path) }}" alt="{{ $photo->caption }}">
                @if($photo->caption)
                <p class="photo-caption">{{ $photo->caption }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tanda Tangan --}}
    <div class="signature-section">
        <div class="sign-box">
            <div class="sign-label">Penerima Barang (Gudang)</div>
            <div class="sign-sublabel">Tanda Tangan &amp; Cap</div>
            <div class="sign-line">
                <div class="sign-name">{{ $receiving->received_by_name ?: '.................................' }}</div>
            </div>
        </div>
        <div class="sign-box">
            <div class="sign-label">Pengirim Barang (Supplier)</div>
            <div class="sign-sublabel">Tanda Tangan &amp; Cap</div>
            <div class="sign-line">
                <div class="sign-name">{{ $receiving->supplier_rep_name ?: '.................................' }}</div>
            </div>
        </div>
    </div>

    <div class="doc-footer">
        <span>Dicetak oleh: {{ auth()->user()->name }} pada {{ now()->format('d/m/Y H:i') }}</span>
        <span>GRN: {{ $receiving->grn_number }} | {{ $receiving->supplier->name }}</span>
    </div>
</div>

<script>
    window.onload = function () { setTimeout(function () { window.print(); }, 500); };
</script>
</body>
</html>
