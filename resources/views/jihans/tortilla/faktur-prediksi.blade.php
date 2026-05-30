<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur Prediksi - {{ $tortilla->session_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: 9.5in auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            background: #fff;
            color: #000;
            font-size: 13px;
            line-height: 1.4;
        }

        .page-wrapper {
            width: 8.2in;
            padding: 9mm 6mm 6mm 6mm;
            margin: 0;
        }

        .action-bar {
            max-width: 100%;
            margin: 10px auto;
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 8px 0;
            background: #f3f4f6;
            border-radius: 6px;
        }

        .btn {
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-back { background: #e5e7eb; color: #374151; }
        .btn-print { background: #c2410c; color: white; }

        .header-section {
            width: 100%;
            display: table;
            margin-bottom: 8px;
        }

        .header-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: right;
        }

        .invoice-title {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .brand-name { font-size: 13px; font-weight: bold; }
        .brand-sub  { font-size: 10px; font-weight: bold; }
        .brand-detail { font-size: 10px; }

        .meta-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .meta-table td { padding: 1px 2px; vertical-align: top; }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.items-table th {
            padding: 4px 6px;
            font-size: 12px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
            text-align: left;
        }

        table.items-table td { padding: 5px 6px; font-size: 12px; }

        table.items-table td.text-right,
        table.items-table th.text-right { text-align: right; }

        .total-row td {
            border-top: 1px solid #000;
            font-weight: bold;
            padding-top: 5px;
        }

        .footer-section {
            margin-top: 10px;
            width: 100%;
            display: table;
        }

        .footer-left, .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .signature-box { text-align: center; }
        .signature-space { height: 45px; }

        .prediksi-banner {
            text-align: center;
            margin-top: 8px;
            font-weight: bold;
            font-size: 11px;
            border: 1px dashed #000;
            padding: 3px 0;
            letter-spacing: 1px;
        }

        @media print {
            .action-bar { display: none !important; }
            body { background: white; }
            .page-wrapper { margin: 0; box-shadow: none; width: 8.2in; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <a href="{{ route('jihans.tortilla.index') }}" class="btn btn-back">&larr; Kembali</a>
    <button onclick="window.print()" class="btn btn-print">Cetak Faktur</button>
</div>

<div class="page-wrapper">

    {{-- HEADER --}}
    <div class="header-section">
        <div class="header-left">
            <div class="invoice-title">FAKTUR PREDIKSI PRODUKSI</div>
            <div class="brand-name">JIHAAN'S FOOD</div>
            <div class="brand-sub">MANUFACTURE FOR KEBAB &amp; TORTILLA</div>
            <div class="brand-detail">JL. Beringin Pasar 7</div>
            <div class="brand-detail">081362148090 - 085373736060</div>
        </div>
        <div class="header-right">
            <table class="meta-table">
                <tr>
                    <td>No. Sesi</td>
                    <td>: {{ $tortilla->session_number }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: {{ $tortilla->date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Dibuat oleh</td>
                    <td>: {{ strtoupper($tortilla->creator->name ?? 'KASIR') }}</td>
                </tr>
                @if($tortilla->notes)
                <tr>
                    <td>Catatan</td>
                    <td>: {{ $tortilla->notes }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <div style="border-top: 2px solid #000; margin-bottom: 4px;"></div>

    {{-- ITEMS TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">No.</th>
                <th style="width: 60%;">Nama Produk</th>
                <th class="text-right" style="width: 20%;">Qty Prediksi</th>
                <th class="text-right" style="width: 14%;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; $grandTotal = 0; @endphp
            @foreach($variants as $key => $label)
            @if($totals[$key] > 0)
            @php $grandTotal += $totals[$key]; @endphp
            <tr>
                <td>{{ $no++ }}</td>
                <td style="font-weight: bold;">{{ $label }}</td>
                <td class="text-right" style="font-weight: bold;">{{ $totals[$key] }}</td>
                <td class="text-right">Pcs</td>
            </tr>
            @endif
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align: right; padding-right: 10px;">TOTAL</td>
                <td class="text-right">{{ $grandTotal }}</td>
                <td class="text-right">Pcs</td>
            </tr>
        </tbody>
    </table>

    {{-- FOOTER --}}
    <div class="footer-section" style="margin-top: 14px;">
        <div class="footer-left">
            <div class="signature-box">
                <div>Dibuat oleh,</div>
                <div class="signature-space"></div>
                <div>( ................ )</div>
            </div>
        </div>
        <div class="footer-right">
            <div class="signature-box">
                <div>Penerima,</div>
                <div class="signature-space"></div>
                <div>( ................ )</div>
            </div>
        </div>
    </div>

    <div class="prediksi-banner">
        *** DATA PREDIKSI &mdash; BELUM FINAL &mdash; AKAN DIPERBARUI SETELAH PRODUKSI SELESAI ***
    </div>

</div>

<script>
    window.onload = function () {
        setTimeout(function () { window.print(); }, 600);
    };
</script>
</body>
</html>
