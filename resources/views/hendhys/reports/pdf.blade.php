<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin-top: 1.4cm;
            margin-bottom: 0.8cm;
            margin-left: 0.8cm;
            margin-right: 0.8cm;
        }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; color: #000; line-height: 1.4; }
        
        /* Fixed Header/Footer for PDF Pages */
        .page-header {
            position: fixed;
            top: -1.0cm;
            left: 0;
            right: 0;
            height: 0.5cm;
            font-size: 8px;
            color: #000;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        .page-header-left {
            float: left;
        }
        .page-header-right {
            float: right;
        }
        .page-number:before {
            content: counter(page);
        }
        .page-count:before {
            content: counter(pages);
        }

        /* Header Layout */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .header-table td { vertical-align: top; }
        .logo-cell { width: 60px; }
        .logo { width: 50px; height: 50px; object-fit: contain; border-radius: 8px; }
        
        .brand-cell { padding-left: 10px; }
        .report-title { font-size: 14px; font-weight: bold; color: #000; margin-bottom: 2px; }
        .brand-name { font-size: 12px; font-weight: 800; color: #000; margin: 0; }
        .brand-sub { font-size: 8px; color: #000; font-weight: bold; text-transform: uppercase; margin: 0; }
        .brand-addr { font-size: 8px; color: #000; margin: 0; }
        
        .period-cell { text-align: right; }
        .period-label { font-size: 9px; font-weight: bold; color: #000; }

        /* Data Table */
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th { 
            background: none; 
            color: #000; 
            padding: 5px 2px; 
            border-top: 1px solid #000;
            border-bottom: 3px double #000; 
            font-size: 9px; 
            text-align: left;
            font-weight: bold;
        }
        table.data td { 
            padding: 5px 2px; 
            border: none;
            vertical-align: middle;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .total-row { font-weight: bold; }
        .total-row td {
            border-top: 1px solid #000 !important;
            border-bottom: 3px double #000 !important;
            padding: 5px 2px;
        }
    </style>
</head>
<body>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 8;
            $text = "{PAGE_NUM}/{PAGE_COUNT}";
            
            // Hitung lebar teks agar rata kanan dengan sempurna
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - $width - 23; // 23pt setara 0.8cm margin
            
            $pdf->page_text($x, 20, $text, $font, $size, array(0,0,0));
        }
    </script>
    <div class="page-header">
        <div class="page-header-left">{{ now()->translatedFormat('d/m/Y H:i') }}</div>
    </div>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('logo/hendhys-logo.png') }}" class="logo" onerror="this.style.display='none'">
            </td>
            <td class="brand-cell">
                <div class="report-title">{{ strtoupper($title) }}</div>
                <h1 class="brand-name">HENDHYS BROWNIES</h1>
                <p class="brand-sub">{{ strtoupper($branch->name ?? 'PUSAT') }}</p>
                <p class="brand-addr">{{ $branch->address ?? '' }}</p>
            </td>
            <td class="period-cell">
                <div class="period-label">
                    PERIODE : {{ $request->date_from ? \Carbon\Carbon::parse($request->date_from)->format('d/m/y') : 'Awal' }} - {{ $request->date_to ? \Carbon\Carbon::parse($request->date_to)->format('d/m/y') : \Carbon\Carbon::now()->format('d/m/y') }}
                </div>
            </td>
        </tr>
    </table>

    @if($isDetailed)
        {{-- LHI DETAIL Layout - Flat structure tanpa page break berlebihan --}}
        @foreach($rows as $tx)
        <div style="margin-bottom: 6px;">
            {{-- Header transaksi --}}
            <table style="width: 100%; border-collapse: collapse; font-size: 8px; border-top: 1px solid #000; border-bottom: 1px solid #000;">
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
            <table style="width: 93%; border-collapse: collapse; margin-left: 15px; font-size: 7.5px;">
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
            <table style="width: 93%; border-collapse: collapse; margin-left: 15px; font-size: 7.5px; font-weight: bold; border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
                <tr>
                    <td style="width: 25%; text-align: left; padding: 2px 0;">Pot. : {{ number_format($tx->discount_total ?? 0, 0, ',', '.') }}</td>
                    <td style="width: 25%; text-align: left; padding: 2px 0;">Pajak : {{ number_format($tx->tax_total ?? 0, 0, ',', '.') }}</td>
                    <td style="width: 25%; text-align: left; padding: 2px 0;">Biaya : 0</td>
                    <td style="width: 25%; text-align: right; padding: 2px 0;">Total Akhir : {{ number_format($tx->grand_total, 0, ',', '.') }}</td>
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
                    <th class="text-center" style="width: 80px;">Jml Trs</th>
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
                    <td class="text-center">{{ number_format($row->jumlah_transaksi, 0, ',', '.') }}</td>
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
                    <td class="text-center">{{ number_format($rows->sum('jumlah_transaksi'), 0, ',', '.') }}</td>
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
