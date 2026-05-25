<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm;
        }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1b1c1c; line-height: 1.4; }
        
        /* Header Layout */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { vertical-align: top; }
        .logo-cell { width: 80px; }
        .logo { width: 70px; height: 70px; object-fit: contain; border-radius: 8px; }
        
        .brand-cell { padding-left: 15px; }
        .report-title { font-size: 16px; font-weight: bold; color: #6c2f00; margin-bottom: 2px; }
        .brand-name { font-size: 14px; font-weight: 800; color: #1b1c1c; margin: 0; }
        .brand-sub { font-size: 9px; color: #54433a; font-weight: bold; text-transform: uppercase; margin: 0; }
        .brand-addr { font-size: 9px; color: #877369; margin: 0; }
        
        .period-cell { text-align: right; }
        .period-label { font-size: 10px; font-weight: bold; color: #1b1c1c; }

        /* Data Table */
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th { 
            background: #ffdbc9; 
            color: #321200; 
            padding: 10px 8px; 
            border: 1px solid #dac2b6; 
            font-size: 9px; 
            text-transform: uppercase; 
            text-align: left;
        }
        table.data td { 
            padding: 8px; 
            border: 1px solid #efeded; 
            vertical-align: middle;
        }
        table.data tr:nth-child(even) { background: #fbf9f8; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-primary { color: #6c2f00; }
        
        .total-row { background: #eae8e7 !important; font-weight: bold; border-top: 2px solid #877369 !important; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; color: #877369; border-top: 1px dashed #dac2b6; padding-top: 10px; }
    </style>
</head>
<body>
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
                    Periode: {{ $request->date_from ? \Carbon\Carbon::parse($request->date_from)->format('d/m/Y') : 'Awal' }} - {{ $request->date_to ? \Carbon\Carbon::parse($request->date_to)->format('d/m/Y') : \Carbon\Carbon::now()->format('d/m/Y') }}
                </div>
                <div style="font-size: 8px; color: #877369; margin-top: 5px;">
                    Dicetak: {{ now()->translatedFormat('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                @if($type === 'pelanggan')
                    <th>Nama Pelanggan</th>
                    <th class="text-center">Awal</th>
                    <th class="text-center">Akhir</th>
                @elseif($type === 'mingguan')
                    <th>Periode Minggu</th>
                @elseif($type === 'bulanan')
                    <th>Bulan</th>
                @else
                    <th>Tanggal</th>
                @endif
                <th class="text-center">Qty Trx</th>
                <th class="text-right">Grand Total</th>
                <th class="text-right">Tunai</th>
                <th class="text-right">Piutang</th>
                <th class="text-right">Debit</th>
                <th class="text-right">CC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                @if($type === 'pelanggan')
                    <td class="font-bold">{{ $row->pelanggan }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($row->tanggal_pertama)->format('d/m/y') }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($row->tanggal_terakhir)->format('d/m/y') }}</td>
                @elseif($type === 'mingguan')
                    <td>{{ \Carbon\Carbon::parse($row->minggu_mulai)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($row->minggu_akhir)->format('d/m/y') }}</td>
                @elseif($type === 'bulanan')
                    <td>{{ $row->label_bulan }}</td>
                @else
                    <td>{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
                @endif
                <td class="text-center">{{ number_format($row->jumlah_transaksi) }}</td>
                <td class="text-right font-bold text-primary">Rp {{ number_format($row->total_transaksi) }}</td>
                <td class="text-right">{{ number_format($row->tunai) }}</td>
                <td class="text-right text-red-600">{{ number_format($row->kredit) }}</td>
                <td class="text-right">{{ number_format($row->kartu_debit) }}</td>
                <td class="text-right">{{ number_format($row->kartu_kredit) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="{{ in_array($type, ['pelanggan', 'mingguan', 'bulanan']) ? ($type === 'pelanggan' ? 3 : 1) : 1 }}" class="text-right">TOTAL</td>
                <td class="text-center">{{ number_format($rows->sum('jumlah_transaksi')) }}</td>
                <td class="text-right">Rp {{ number_format($rows->sum('total_transaksi')) }}</td>
                <td class="text-right">{{ number_format($rows->sum('tunai')) }}</td>
                <td class="text-right">{{ number_format($rows->sum('kredit')) }}</td>
                <td class="text-right">{{ number_format($rows->sum('kartu_debit')) }}</td>
                <td class="text-right">{{ number_format($rows->sum('kartu_kredit')) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Laporan ini digenerate secara otomatis oleh Sistem Hendhys Brownies.
    </div>
</body>
</html>
