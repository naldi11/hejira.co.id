<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: {{ $type === 'laci' ? ($requestedSize == '80' ? '80mm 297mm' : '58mm 297mm') : ($type === 'pelanggan' ? 'legal portrait' : '11in 9.5in') }};
            margin-top: {{ $type === 'laci' ? '0.2cm' : '1.2cm' }};
            margin-bottom: {{ $type === 'laci' ? '0.2cm' : '0.8cm' }};
            margin-left: {{ $type === 'laci' ? '0.2cm' : '0.8cm' }};
            margin-right: {{ $type === 'laci' ? '0.2cm' : '0.8cm' }};
        }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #000000; line-height: 1.35; }
        
        /* Fixed Header/Footer for PDF Pages */
        .page-header {
            position: fixed;
            top: -1.0cm;
            left: 0;
            right: 0;
            height: 0.5cm;
            font-size: 9px;
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
        .brand-sub { font-size: 9px; color: #000; font-weight: bold; text-transform: uppercase; margin: 0; }
        .brand-addr { font-size: 9px; color: #000; margin: 0; }
        
        .period-cell { text-align: right; }
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
            border-bottom: 3px double #000 !important;
            padding: 5px 2px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    @if($type !== 'laci')
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
    @endif

    @if($type === 'laci')
    <div style="text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px;">
        <h1 class="brand-name" style="font-size: 14px;">HENDHYS BROWNIES</h1>
        <p class="brand-sub" style="font-size: 10px; margin-bottom: 2px;">{{ strtoupper($branch->name ?? 'PUSAT') }}</p>
        <p class="brand-addr" style="font-size: 9px; margin-bottom: 5px;">{{ $branch->address ?? '' }}</p>
        <div class="report-title" style="font-size: 12px; margin-top: 5px; border-top: 1px dashed #000; padding-top: 5px;">LAPORAN PENJUALAN/KASIR</div>
    </div>
    @else
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
    @endif

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
    @elseif($type === 'laci')
        {{-- SHIFT SUMMARY Thermal Layout --}}
        <div class="thermal-receipt" style="font-size: 11px;">
            @foreach($rows as $row)
            @php
                $isClosed = $row->status === 'closed';
                $sales = $row->sales_summary ?? [];
                $payment = $row->payment_summary ?? [];
                
                $jmlTransaksi = $sales['jumlah_transaksi'] ?? 0;
                $totPotongan = $sales['tot_potongan'] ?? 0;
                $totPajak = $sales['tot_pajak'] ?? 0;
                $totBiaya = $sales['tot_biaya'] ?? 0;
                $totalPenjualan = $sales['total'] ?? 0;
                
                $tunai = $payment['tunai'] ?? 0;
                $kredit = $payment['kredit'] ?? 0;
                $debit = $payment['kartu_debit'] ?? 0;
                $kkredit = $payment['kartu_kredit'] ?? 0;
                $emoney = $payment['transfer'] ?? 0;
                $deposit = 0;
                $donasi = 0;
                $kembalian = 0;
                
                $returTunai = 0;
                $returKredit = 0;
                $returDeposit = 0;
                $totRetur = 0;
            @endphp
            
            <div style="text-align: left; margin-bottom: 5px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 35%;">PERIODE</td>
                        <td style="width: 5%;">:</td>
                        <td>{{ $row->opened_at ? $row->opened_at->format('d-m-y') : '-' }} s/d {{ $row->closed_at ? $row->closed_at->format('d-m-y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td>USER</td>
                        <td>:</td>
                        <td>{{ strtoupper($row->user->name ?? 'Sistem') }}</td>
                    </tr>
                    <tr>
                        <td>DEPT/GDNG</td>
                        <td>:</td>
                        <td>UTM</td>
                    </tr>
                </table>
            </div>

            <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; margin-bottom: 5px;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 55%;">JML TRANSAKSI</td>
                        <td style="width: 5%;">:</td>
                        <td style="text-align: right;">{{ $jmlTransaksi }}</td>
                    </tr>
                    <tr>
                        <td>TOT. POTONGAN</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($totPotongan, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>TOT. PAJAK</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($totPajak, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>TOT. BIAYA</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($totBiaya, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>TOTAL</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($totalPenjualan, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BAYAR TUNAI</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($tunai, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BAYAR KREDIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($kredit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BAYAR DEBIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($debit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BAYAR KKREDIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($kkredit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BAYAR EMONEY</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($emoney, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BAYAR DEPOSIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($deposit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>KEMBALIAN</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($kembalian, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>TOT. DEPOSIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($deposit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>TOT. DONASI</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($donasi, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <div style="border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px;">
                <div style="margin-bottom: 2px;">RETUR PENJUALAN</div>
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 55%;">RET.TUNAI</td>
                        <td style="width: 5%;">:</td>
                        <td style="text-align: right;">{{ number_format($returTunai, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>RET.KREDIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($returKredit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>RET.DEPOSIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($returDeposit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>TOT.RETUR</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($totRetur, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <div style="border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 15px;">
                <div style="margin-bottom: 2px;">SETELAH POTONG RETUR</div>
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <td style="width: 55%;">TOTAL</td>
                        <td style="width: 5%;">:</td>
                        <td style="text-align: right;">{{ number_format($totalPenjualan - $totRetur, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>TUNAI</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($tunai - $returTunai, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>KREDIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($kredit - $returKredit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>DEPOSIT</td>
                        <td>:</td>
                        <td style="text-align: right;">{{ number_format($deposit - $returDeposit, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
            
            @if(!$loop->last)
                <div style="border-top: 2px solid #000; margin: 15px 0;"></div>
            @endif
            @endforeach
        </div>
    @else
        {{-- SUMMARY Layout --}}
        <table class="data">
            <colgroup>
                <col style="width: 18%;">
                <col style="width: 9%;">
                <col style="width: 15%;">
                <col style="width: 14%;">
                <col style="width: 14%;">
                <col style="width: 15%;">
                <col style="width: 15%;">
            </colgroup>
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
                    <th class="text-center">Jml Trs</th>
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
