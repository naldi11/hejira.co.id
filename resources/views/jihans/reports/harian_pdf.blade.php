<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LHI Detail</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 0.5cm;
        }
        body { 
            font-family: 'Courier', 'Courier New', monospace; 
            font-size: 8px; 
            color: #000; 
            line-height: 1.2; 
            margin: 0; 
            padding: 0; 
        }
        
        /* Header Kop Surat Layout */
        .header-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 5px; 
            border-bottom: 1px solid #000; 
            padding-bottom: 3px; 
        }
        .header-table td { 
            padding: 0; 
        }
        .logo-cell { 
            width: 35px; 
            vertical-align: middle; 
        }
        .logo { 
            width: 30px; 
            height: 30px; 
            display: block; 
        }
        
        .brand-cell { 
            padding-left: 6px; 
            text-align: left; 
            vertical-align: middle; 
        }
        .report-title { 
            font-size: 10px; 
            font-weight: bold; 
            margin: 0; 
        }
        .brand-name { 
            font-size: 9px; 
            font-weight: bold; 
            margin: 0; 
        }
        .brand-sub { 
            font-size: 7px; 
            font-weight: bold; 
            margin: 0; 
        }
        .brand-addr { 
            font-size: 7px; 
            margin: 0; 
        }
        
        .period-cell { 
            text-align: right; 
            vertical-align: top; 
            font-size: 8px;
            font-weight: bold;
        }

        /* Pembungkus transaksi agar tidak terpotong halaman di tengah jalan */
        .transaction-block {
            page-break-inside: avoid;
            margin-bottom: 10px;
        }
        
        /* Tabel Data Transaksi */
        .tx-header-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            margin-bottom: 2px;
        }
        .tx-header-table td {
            padding: 2px 2px;
        }
        
        /* Tabel Detail Item */
        .item-table {
            width: 95%;
            border-collapse: collapse;
            margin-left: auto;
            font-size: 7px;
            margin-bottom: 2px;
        }
        .item-table th {
            font-style: italic;
            font-weight: bold;
            text-align: left;
            border-bottom: 1px dashed #000;
            padding: 1px 2px;
        }
        .item-table td {
            padding: 1px 2px;
        }
        
        /* Tabel Ringkasan Bawah */
        .summary-table {
            width: 95%;
            border-collapse: collapse;
            margin-left: auto;
            font-size: 7px;
            font-weight: bold;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .summary-table td {
            padding: 2px 2px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    {{-- Script Cetak Nomor Halaman Manual di Kanan Bawah domPDF --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 7.5;
            $text = "{PAGE_NUM}/{PAGE_COUNT}";
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - $width - 14; 
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size, array(0,0,0));
            
            // Cetak info waktu pembuatan di kiri bawah
            $leftText = date('d/m/Y H:i') . "   " . strtoupper("{{ auth()->user()->name ?? '-' }}");
            $pdf->page_text(14, $y, $leftText, $font, $size, array(0,0,0));
        }
    </script>

    {{-- KOP SURAT (Logo & Judul Rapat Tanpa Gap) --}}
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('logo/jihans-logo.png') }}" class="logo" onerror="this.style.display='none'">
            </td>
            <td class="brand-cell">
                <div class="report-title">LHI DETAIL</div>
                <h1 class="brand-name">JIHAAN'S FOOD</h1>
                <p class="brand-sub">MANUFACTURE FOR KEBAB &amp; TORTILLA</p>
                <p class="brand-addr">Jl. Beringin Pasar 7 | 081362148090 - 085373736060</p>
            </td>
            <td class="period-cell">
                PERIODE : {{ $request->date_from ? \Carbon\Carbon::parse($request->date_from)->format('d/m/y') : 'Awal' }} - {{ $request->date_to ? \Carbon\Carbon::parse($request->date_to)->format('d/m/y') : \Carbon\Carbon::now()->format('d/m/y') }}
            </td>
        </tr>
    </table>

    {{-- DATA TRANSAKSI LOOP --}}
    @foreach($rows as $tx)
    <div class="transaction-block">
        {{-- Header per transaksi --}}
        <table class="tx-header-table">
            <tr style="font-weight: bold;">
                <td style="width: 18%;">No Transaksi</td>
                <td style="width: 12%;">Tanggal</td>
                <td style="width: 12%;">Dept.</td>
                <td style="width: 13%;">Kode Pel.</td>
                <td style="width: 20%;">Nama Pelanggan</td>
                <td style="width: 25%;">Alamat</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">{{ $tx->transaction_number }}</td>
                <td>{{ \Carbon\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                <td>{{ strtoupper($tx->operator ?? '-') }}</td>
                <td>{{ strtoupper($tx->customer_code) }}</td>
                <td style="font-weight: bold;">{{ strtoupper($tx->customer_name) }}</td>
                <td>{{ strtoupper($tx->customer_address) }}</td>
            </tr>
        </table>

        {{-- Detail item barang --}}
        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 14%;">Kd. Item</th>
                    <th style="width: 31%;">Nama Item</th>
                    <th style="width: 8%; text-align: center;">Jml</th>
                    <th style="width: 10%; text-align: center;">Satuan</th>
                    <th style="width: 12%; text-align: right;">Harga</th>
                    <th style="width: 8%; text-align: right;">Pot.%</th>
                    <th style="width: 12%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tx->details as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->kode_item }}</td>
                    <td style="font-weight: bold;">{{ $item->nama_item }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $item->satuan }}</td>
                    <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $item->pot > 0 ? number_format($item->pot, 0, ',', '.') : '0' }}</td>
                    <td class="text-right font-bold">{{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                {{-- Total kuantitas barang --}}
                <tr style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; font-weight: bold;">
                    <td colspan="3"></td>
                    <td class="text-center">{{ number_format($tx->details->sum('quantity'), 0, ',', '.') }}</td>
                    <td></td>
                    <td colspan="2"></td>
                    <td class="text-right">{{ number_format($tx->details->sum('total'), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Ringkasan Biaya akhir transaksi --}}
        <table class="summary-table">
            <tr>
                <td style="width: 25%;">Pot. : {{ number_format($tx->discount_total ?? 0, 0, ',', '.') }}</td>
                <td style="width: 25%;">Pajak : {{ number_format($tx->tax_total ?? 0, 0, ',', '.') }}</td>
                <td style="width: 25%;">Biaya : 0</td>
                <td style="width: 25%; text-align: right;">Total Akhir : {{ number_format($tx->grand_total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    @endforeach

</body>
</html>