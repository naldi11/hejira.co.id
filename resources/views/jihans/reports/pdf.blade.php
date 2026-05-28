<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A5 {{ $orientation }};
            margin-top: {{ $isDetailed ? '0.8cm' : '1.2cm' }};
            margin-bottom: {{ $isDetailed ? '1.0cm' : '0.6cm' }};
            margin-left: 0.6cm;
            margin-right: 0.6cm;
        }
        body { font-family: 'Courier', 'Courier New', monospace; font-size: 8px; color: #000; line-height: 1.3; }
        
        /* Fixed Header/Footer for PDF Pages */
        .page-header {
            position: fixed;
            top: -0.9cm;
            left: 0;
            right: 0;
            height: 0.5cm;
            font-size: 7.5px;
            color: #000;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }
        .page-header-left {
            float: left;
        }
        .page-header-right {
            float: right;
        }
        .page-footer {
            position: fixed;
            bottom: -0.3cm;
            left: 0;
            right: 0;
            height: 0.4cm;
            font-size: 7.5px;
            color: #000;
            padding-top: 2px;
        }
        .page-footer-left {
            float: left;
        }
        .page-footer-right {
            float: right;
        }
        .page-number:before {
            content: counter(page);
        }
        .page-count:before {
            content: counter(pages);
        }

        /* Header Layout */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px; }
        .header-table td { vertical-align: top; }
        .logo-cell { width: 45px; }
        .logo { width: 35px; height: 35px; object-fit: contain; }
        
        .brand-cell { padding-left: 8px; }
        .report-title { font-size: 11px; font-weight: bold; color: #000; margin-bottom: 1px; }
        .brand-name { font-size: 10px; font-weight: bold; color: #000; margin: 0; }
        .brand-sub { font-size: 7.5px; color: #000; font-weight: bold; text-transform: uppercase; margin: 0; }
        .brand-addr { font-size: 7.5px; color: #000; margin: 0; }
        
        .period-cell { text-align: right; }
        .period-label { font-size: 7.5px; font-weight: bold; color: #000; }

        /* Data Table */
        table.data { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.data th { 
            background: none; 
            color: #000; 
            padding: 4px 2px; 
            border-top: 1px solid #000;
            border-bottom: 3px double #000; 
            font-size: 8.5px; 
            text-align: left;
            font-weight: bold;
        }
        table.data td { 
            padding: 4px 2px; 
            border: none;
            vertical-align: middle;
            font-size: 8px;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .total-row { font-weight: bold; }
        .total-row td {
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            padding: 4px 2px;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 7.5;
            $text = "{PAGE_NUM}/{PAGE_COUNT}";
            
            // Hitung lebar teks agar rata kanan dengan sempurna
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - $width - 17; // 17pt setara 0.6cm margin
            
            @if(!$isDetailed)
                // Letakkan di kanan atas (header)
                $pdf->page_text($x, 15, $text, $font, $size, array(0,0,0));
            @else
                // Letakkan di kanan bawah (footer)
                $pdf->page_text($x, $pdf->get_height() - 23, $text, $font, $size, array(0,0,0));
            @endif
        }
    </script>

    @if(!$isDetailed)
        <div class="page-header">
            <div class="page-header-left">{{ now()->translatedFormat('d/m/Y H:i') }}</div>
        </div>
    @else
        <div class="page-footer">
            <div class="page-footer-left">{{ now()->translatedFormat('d/m/Y H:i') }} &nbsp; &nbsp; {{ strtoupper(auth()->user()->name ?? '-') }}</div>
        </div>
    @endif

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('logo/jihans-logo.png') }}" class="logo" onerror="this.style.display='none'">
            </td>
            <td class="brand-cell">
                <div class="report-title">
                    @if($type === 'harian') LHI DETAIL
                    @elseif($type === 'pelanggan') LAPORAN JUAL PER PELANGGAN
                    @elseif($type === 'laci' || $type === 'bulanan') LAPORAN PENJUALAN HARIAN
                    @elseif($type === 'mingguan') LAPORAN PENJUALAN MINGGUAN
                    @else {{ strtoupper($title) }}
                    @endif
                </div>
                <h1 class="brand-name">JIHAAN'S FOOD</h1>
                <p class="brand-sub">MANUFACTURE FOR KEBAB</p>
                <p class="brand-sub">&amp; TORTILLA</p>
                <p class="brand-addr">Jl. Beringin Pasar 7</p>
                <p class="brand-addr">081362148090 - 085373736060</p>
                <p class="brand-addr">-</p>
            </td>
            <td class="period-cell">
                <div class="period-label">
                    PERIODE : {{ $request->date_from ? \Carbon\Carbon::parse($request->date_from)->format('d/m/y') : 'Awal' }} - {{ $request->date_to ? \Carbon\Carbon::parse($request->date_to)->format('d/m/y') : \Carbon\Carbon::now()->format('d/m/y') }}
                </div>
            </td>
        </tr>
    </table>

    @if($isDetailed)
        {{-- LHI DETAIL Layout --}}
        @foreach($rows as $index => $tx)
        <div style="margin-bottom: 12px; {{ $index > 0 ? 'page-break-inside: avoid;' : '' }}">
            <!-- Transaksi Header Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 8px;">
                <thead>
                    <tr style="font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                        <th style="text-align: left; width: 18%; padding: 3px 0; font-weight: bold;">No Transaksi</th>
                        <th style="text-align: left; width: 12%; padding: 3px 0; font-weight: bold;">Tanggal</th>
                        <th style="text-align: left; width: 12%; padding: 3px 0; font-weight: bold;">Dept.</th>
                        <th style="text-align: left; width: 13%; padding: 3px 0; font-weight: bold;">Kode Pel.</th>
                        <th style="text-align: left; width: 20%; padding: 3px 0; font-weight: bold;">Nama Pelanggan</th>
                        <th style="text-align: left; width: 25%; padding: 3px 0; font-weight: bold;">Alamat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 4px 0; font-weight: bold;">{{ $tx->transaction_number }}</td>
                        <td style="padding: 4px 0;">{{ \Carbon\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                        <td style="padding: 4px 0;">{{ strtoupper($tx->operator ?? '-') }}</td>
                        <td style="padding: 4px 0;">{{ strtoupper($tx->customer_code) }}</td>
                        <td style="padding: 4px 0; font-weight: bold;">{{ strtoupper($tx->customer_name) }}</td>
                        <td style="padding: 4px 0;">{{ strtoupper($tx->customer_address) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Sub-tabel Item Details -->
            <table style="width: 95%; border-collapse: collapse; margin-left: 20px; font-size: 7.5px; margin-bottom: 4px;">
                <thead>
                    <tr style="font-style: italic; border-bottom: 1px dashed #000;">
                        <th style="text-align: left; width: 5%; padding: 2px 0;">No.</th>
                        <th style="text-align: left; width: 15%; padding: 2px 0;">Kd. Item</th>
                        <th style="text-align: left; width: 35%; padding: 2px 0;">Nama Item</th>
                        <th style="text-align: center; width: 10%; padding: 2px 0;">Jml</th>
                        <th style="text-align: center; width: 10%; padding: 2px 0;">Satuan</th>
                        <th style="text-align: right; width: 10%; padding: 2px 0;">Harga</th>
                        <th style="text-align: right; width: 10%; padding: 2px 0;">Pot.%</th>
                        <th style="text-align: right; width: 15%; padding: 2px 0;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tx->details as $index => $item)
                    <tr>
                        <td style="padding: 3px 0;">{{ $index + 1 }}</td>
                        <td style="padding: 3px 0;">{{ $item->kode_item }}</td>
                        <td style="padding: 3px 0; font-weight: bold;">{{ $item->nama_item }}</td>
                        <td style="padding: 3px 0; text-align: center;">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td style="padding: 3px 0; text-align: center;">{{ $item->satuan }}</td>
                        <td style="padding: 3px 0; text-align: right;">{{ number_format($item->price, 0, ',', '.') }}</td>
                        <td style="padding: 3px 0; text-align: right;">{{ $item->pot > 0 ? number_format($item->pot, 0, ',', '.') : '0' }}</td>
                        <td style="padding: 3px 0; text-align: right; font-weight: bold;">{{ number_format($item->total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <!-- Baris Total Kuantitas & Total Item (Garis putus-putus) -->
                    <tr style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; font-weight: bold;">
                        <td colspan="3" style="padding: 3px 0;"></td>
                        <td style="padding: 3px 0; text-align: center;">{{ number_format($tx->details->sum('quantity'), 0, ',', '.') }}</td>
                        <td style="padding: 3px 0;"></td>
                        <td colspan="2" style="padding: 3px 0;"></td>
                        <td style="padding: 3px 0; text-align: right;">{{ number_format($tx->details->sum('total'), 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Ringkasan Biaya di bawah sub-tabel -->
            <table style="width: 95%; border-collapse: collapse; margin-left: 20px; font-size: 7.5px; font-weight: bold; border-top: 1px dashed #000; border-bottom: 1px dashed #000; margin-bottom: 8px;">
                <tr>
                    <td style="width: 25%; text-align: left; padding: 4px 0;">Pot. : {{ number_format($tx->discount_total ?? 0, 0, ',', '.') }}</td>
                    <td style="width: 25%; text-align: left; padding: 4px 0;">Pajak : {{ number_format($tx->tax_total ?? 0, 0, ',', '.') }}</td>
                    <td style="width: 25%; text-align: left; padding: 4px 0;">Biaya : 0</td>
                    <td style="width: 25%; text-align: right; padding: 4px 0;">Total Akhir : {{ number_format($tx->grand_total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
        @endforeach
    @else
        {{-- SUMMARY Layout --}}
        <table class="data">
            <thead>
                <tr>
                    @if($type === 'pelanggan')
                        <th>Pelanggan</th>
                    @elseif($type === 'mingguan')
                        <th>Minggu</th>
                    @elseif($type === 'bulanan')
                        <th>Bulan</th>
                    @else
                        <th>Tanggal</th>
                    @endif
                    @if($type !== 'pelanggan')
                        <th class="text-center" style="width: 80px;">Jml Trs</th>
                    @endif
                    <th class="text-right" style="width: 130px;">Total Transaksi</th>
                    <th class="text-right" style="width: 120px;">Jml Bayar Tunai</th>
                    <th class="text-right" style="width: 120px;">Jml Bayar Kredit</th>
                    <th class="text-right" style="width: 120px;">Jml Bayar K.Debit</th>
                    <th class="text-right" style="width: 120px;">Jml Bayar K.Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td>
                        @if($type === 'pelanggan') {{ strtoupper($row->pelanggan) }}
                        @elseif($type === 'mingguan') {{ \Carbon\Carbon::parse($row->minggu_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($row->minggu_akhir)->format('d/m/Y') }}
                        @elseif($type === 'bulanan') {{ strtoupper($row->label_bulan) }}
                        @else {{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}
                        @endif
                    </td>
                    @if($type !== 'pelanggan')
                        <td class="text-center">{{ number_format($row->jumlah_transaksi, 0, ',', '.') }}</td>
                    @endif
                    <td class="text-right font-bold">{{ number_format($row->total_transaksi, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->tunai, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->kredit, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->kartu_debit, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row->kartu_kredit, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td class="text-left" style="font-style: italic;">TOTAL :</td>
                    @if($type !== 'pelanggan')
                        <td class="text-center">{{ number_format($rows->sum('jumlah_transaksi'), 0, ',', '.') }}</td>
                    @endif
                    <td class="text-right">{{ number_format($rows->sum('total_transaksi'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($rows->sum('tunai'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($rows->sum('kredit'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($rows->sum('kartu_debit'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($rows->sum('kartu_kredit'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
