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
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #000; line-height: 1.4; }
        
        /* Header Layout */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .logo-cell { width: 80px; }
        .logo { width: 70px; height: 70px; object-fit: contain; border-radius: 8px; }
        
        .brand-cell { padding-left: 15px; }
        .report-title { font-size: 16px; font-weight: bold; color: #000; margin-bottom: 2px; }
        .brand-name { font-size: 14px; font-weight: 800; color: #000; margin: 0; }
        .brand-sub { font-size: 9px; color: #000; font-weight: bold; text-transform: uppercase; margin: 0; }
        .brand-addr { font-size: 9px; color: #333; margin: 0; }
        
        .period-cell { text-align: right; }
        .period-label { font-size: 10px; font-weight: bold; color: #000; }

        /* Data Table */
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th { 
            background: #fff; 
            color: #000; 
            padding: 8px 5px; 
            border: 1px solid #000; 
            font-size: 9px; 
            text-transform: uppercase; 
            text-align: left;
        }
        table.data td { 
            padding: 8px; 
            border: 1px solid #ccc; 
            vertical-align: middle;
        }
        table.data tr:nth-child(even) { background: #f9f9f9; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .total-row { background: #fff !important; font-weight: bold; border-top: 2px solid #000 !important; }
        .total-row td { border-bottom: 2px solid #000 !important; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; color: #666; border-top: 1px dashed #ccc; padding-top: 10px; }
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
                <div style="font-size: 8px; color: #666; margin-top: 5px;">
                    Dicetak: {{ now()->translatedFormat('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th class="text-center">Jml Transaksi</th>
                <th class="text-right">Total Transaksi</th>
                <th class="text-right">Jml Bayar Tunai</th>
                <th class="text-right">Jml Bayar Kredit</th>
                <th class="text-right">Jml Bayar Debit</th>
                <th class="text-right">Jml Bayar K.Kredit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>
                    @if($type === 'pelanggan') {{ $row->pelanggan }}
                    @elseif($type === 'mingguan') {{ \Carbon\Carbon::parse($row->minggu_mulai)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($row->minggu_akhir)->format('d/m/y') }}
                    @elseif($type === 'bulanan') {{ $row->label_bulan }}
                    @else {{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}
                    @endif
                </td>
                <td class="text-center">{{ number_format($row->jumlah_transaksi) }}</td>
                <td class="text-right font-bold">Rp {{ number_format($row->total_transaksi) }}</td>
                <td class="text-right">{{ number_format($row->tunai) }}</td>
                <td class="text-right">{{ number_format($row->kredit) }}</td>
                <td class="text-right">{{ number_format($row->kartu_debit) }}</td>
                <td class="text-right">{{ number_format($row->kartu_kredit) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="text-right">TOTAL</td>
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
