<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: {{ $type === 'pelanggan' ? 'legal portrait' : '11in 9.5in' }};
            margin-top: {{ $isDetailed ? '0.3cm' : '1.2cm' }};
            margin-bottom: {{ $isDetailed ? '1.0cm' : '0.6cm' }};
            margin-left: 0.6cm;
            margin-right: 0.6cm;
        }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #000000; line-height: 1.25; margin: 0; padding: 0; }
        
        /* Fixed Header/Footer for PDF Pages */
        .page-header {
            position: fixed;
            top: -0.9cm;
            left: 0;
            right: 0;
            height: 0.5cm;
            font-size: 9px;
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
            font-size: 9px;
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
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 0; border-bottom: 1px solid #000; padding-bottom: 2px; }
        .header-table td { vertical-align: top; padding: 0; }
        .logo-cell { width: 45px; padding-right: 0; }
        .logo { width: 38px; height: 38px; display: block; object-fit: contain; }
        
        .brand-cell { padding-left: 8px; text-align: left; }
        .report-title { font-size: 14px; font-weight: bold; color: #000; margin: 0; line-height: 1.2; }
        .brand-name { font-size: 12px; font-weight: bold; color: #000; margin: 0; line-height: 1.2; }
        .brand-sub { font-size: 9px; color: #000; font-weight: bold; text-transform: uppercase; margin: 0; line-height: 1.2; }
        .brand-addr { font-size: 9px; color: #000; margin: 0; line-height: 1.2; }
        
        .period-cell { text-align: right; vertical-align: top; }
        .period-label { font-size: 10px; font-weight: bold; color: #000; }

        /* Data Table */
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        table.data th { 
            background: none; 
            color: #000; 
            padding: 5px 2px; 
            border-top: 1px solid #000;
            border-bottom: 3px double #000; 
            font-size: 11px; 
            text-align: left;
            font-weight: bold;
        }
        table.data td { 
            padding: 5px 2px; 
            border: none;
            vertical-align: middle;
            font-size: 10px;
        }
        
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }
        
        .total-row { font-weight: bold; }
        .total-row td {
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            padding: 5px 2px;
            font-size: 10px;
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
            </td>
            <td class="period-cell">
                <div class="period-label">
                    PERIODE : {{ $request->date_from ? \Carbon\Carbon::parse($request->date_from)->format('d/m/y') : 'Awal' }} - {{ $request->date_to ? \Carbon\Carbon::parse($request->date_to)->format('d/m/y') : \Carbon\Carbon::now()->format('d/m/y') }}
                </div>
            </td>
        </tr>
    </table>

    @if($isDetailed)
        {{-- LHI DETAIL Layout - Flat structure untuk menghindari page break berlebihan --}}
        @foreach($rows as $txIndex => $tx)
        <div style="margin-bottom: 6px;">
            {{-- Header transaksi --}}
            <table style="width: 100%; border-collapse: collapse; font-size: 7.5px; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                <tr style="font-weight: bold;">
                    <td style="width: 18%; padding: 2px 0;">No Transaksi</td>
                    <td style="width: 12%; padding: 2px 0;">Tanggal</td>
                    <td style="width: 12%; padding: 2px 0;">Dept.</td>
                    <td style="width: 13%; padding: 2px 0;">Kode Pel.</td>
                    <td style="width: 20%; padding: 2px 0;">Nama Pelanggan</td>
                    <td style="width: 25%; padding: 2px 0;">Alamat</td>
                </tr>
                <tr>
                    <td style="padding: 2px 0; font-weight: bold;">{{ $tx->transaction_number }}</td>
                    <td style="padding: 2px 0;">{{ \Carbon\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                    <td style="padding: 2px 0;">{{ strtoupper($tx->operator ?? '-') }}</td>
                    <td style="padding: 2px 0;">{{ strtoupper($tx->customer_code) }}</td>
                    <td style="padding: 2px 0; font-weight: bold;">{{ strtoupper($tx->customer_name) }}</td>
                    <td style="padding: 2px 0;">{{ strtoupper($tx->customer_address) }}</td>
                </tr>
            </table>

            {{-- Detail item --}}
            <table style="width: 93%; border-collapse: collapse; margin-left: 15px; font-size: 7px;">
                <thead>
                    <tr style="font-style: italic; border-bottom: 1px dashed #000;">
                        <th style="text-align: left; width: 5%; padding: 1px 0;">No.</th>
                        <th style="text-align: left; width: 14%; padding: 1px 0;">Kd. Item</th>
                        <th style="text-align: left; width: 31%; padding: 1px 0;">Nama Item</th>
                        <th style="text-align: center; width: 8%; padding: 1px 0;">Jml</th>
                        <th style="text-align: center; width: 10%; padding: 1px 0;">Satuan</th>
                        <th style="text-align: right; width: 12%; padding: 1px 0;">Harga</th>
                        <th style="text-align: right; width: 8%; padding: 1px 0;">Pot.%</th>
                        <th style="text-align: right; width: 12%; padding: 1px 0;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tx->details as $index => $item)
                    <tr>
                        <td style="padding: 1px 0;">{{ $index + 1 }}</td>
                        <td style="padding: 1px 0;">{{ $item->kode_item }}</td>
                        <td style="padding: 1px 0; font-weight: bold;">{{ $item->nama_item }}</td>
                        <td style="padding: 1px 0; text-align: center;">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td style="padding: 1px 0; text-align: center;">{{ $item->satuan }}</td>
                        <td style="padding: 1px 0; text-align: right;">{{ number_format($item->price, 0, ',', '.') }}</td>
                        <td style="padding: 1px 0; text-align: right;">{{ $item->pot > 0 ? number_format($item->pot, 0, ',', '.') : '0' }}</td>
                        <td style="padding: 1px 0; text-align: right; font-weight: bold;">{{ number_format($item->total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; font-weight: bold;">
                        <td colspan="3" style="padding: 1px 0;"></td>
                        <td style="padding: 1px 0; text-align: center;">{{ number_format($tx->details->sum('quantity'), 0, ',', '.') }}</td>
                        <td style="padding: 1px 0;"></td>
                        <td colspan="2" style="padding: 1px 0;"></td>
                        <td style="padding: 1px 0; text-align: right;">{{ number_format($tx->details->sum('total'), 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- Ringkasan biaya --}}
            <table style="width: 93%; border-collapse: collapse; margin-left: 15px; font-size: 7px; font-weight: bold; border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
                <tr>
                    <td style="width: 25%; text-align: left; padding: 2px 0;">Pot. : {{ number_format($tx->discount_total ?? 0, 0, ',', '.') }}</td>
                    <td style="width: 25%; text-align: left; padding: 2px 0;">Pajak : {{ number_format($tx->tax_total ?? 0, 0, ',', '.') }}</td>
                    <td style="width: 25%; text-align: left; padding: 2px 0;">Biaya : 0</td>
                    <td style="width: 25%; text-align: right; padding: 2px 0;">Total Akhir : {{ number_format($tx->grand_total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
        @endforeach
    @elseif($type === 'laci')
        {{-- SHIFT SUMMARY Layout --}}
        <table class="data">
            <colgroup>
                <col style="width: 15%;">
                <col style="width: 15%;">
                <col style="width: 15%;">
                <col style="width: 10%;">
                <col style="width: 12%;">
                <col style="width: 12%;">
                <col style="width: 12%;">
                <col style="width: 9%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Kasir</th>
                    <th>Waktu Buka</th>
                    <th>Waktu Tutup</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Modal Awal</th>
                    <th class="text-right">Expected Cash</th>
                    <th class="text-right">Actual Cash</th>
                    <th class="text-right">Selisih</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td>{{ strtoupper($row->user->name ?? 'Sistem') }}</td>
                    <td>{{ $row->opened_at ? $row->opened_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $row->closed_at ? $row->closed_at->format('d/m/Y H:i') : '-' }}</td>
                    <td class="text-center">{{ strtoupper($row->status) }}</td>
                    <td class="text-right">{{ number_format($row->starting_cash, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $row->status === 'closed' ? number_format($row->expected_cash, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $row->status === 'closed' ? number_format($row->actual_cash, 0, ',', '.') : '-' }}</td>
                    <td class="text-right font-bold">{{ $row->status === 'closed' ? number_format($row->discrepancy, 0, ',', '.') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-left" style="font-style: italic;">TOTAL CLOSED SHIFTS:</td>
                    <td class="text-right">{{ number_format($rows->where('status', 'closed')->sum('starting_cash'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($rows->where('status', 'closed')->sum('expected_cash'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($rows->where('status', 'closed')->sum('actual_cash'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($rows->where('status', 'closed')->sum('discrepancy'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        
        <br><br><br>
        <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
            <tr>
                <td style="width: 50%; text-align: center;">
                    Dibuat Oleh,<br><br><br><br>
                    <strong>( ............................ )</strong><br>
                    Kasir
                </td>
                <td style="width: 50%; text-align: center;">
                    Diverifikasi Oleh,<br><br><br><br>
                    <strong>( ............................ )</strong><br>
                    Supervisor / Toko
                </td>
            </tr>
        </table>
    @else
        {{-- SUMMARY Layout --}}
        <table class="data">
            @if($type === 'pelanggan')
                <colgroup>
                    <col style="width: 25%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                </colgroup>
            @else
                <colgroup>
                    <col style="width: 18%;">
                    <col style="width: 9%;">
                    <col style="width: 15%;">
                    <col style="width: 14%;">
                    <col style="width: 14%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                </colgroup>
            @endif
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
                        <th class="text-center">Jml Trs</th>
                    @endif
                    <th class="text-right">Total Transaksi</th>
                    <th class="text-right">Jml Bayar Tunai</th>
                    <th class="text-right">Jml Bayar Kredit</th>
                    <th class="text-right">Jml Bayar K.Debit</th>
                    <th class="text-right">Jml Bayar K.Kredit</th>
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
