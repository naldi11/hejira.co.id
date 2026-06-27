<!DOCTYPE html>
<html>
<head>
    <title>Laporan Omset Owner</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0 0 5px 0; color: #111; }
        .header p { margin: 0; color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { bg-color: #f7f7f7; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN OMSET KONSOLIDASI</h2>
        <p>Unit Bisnis: {{ strtoupper($unit) }} | Periode: {{ ucfirst($period) }}</p>
        @if($dateFrom || $dateTo)
            <p>Rentang Tanggal: {{ $dateFrom ?? '-' }} s/d {{ $dateTo ?? '-' }}</p>
        @endif
        <p>Tanggal Cetak: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Periode</th>
                <th class="text-right">Jihan's Food</th>
                <th class="text-right">Hendhys Brownies</th>
                <th class="text-right">Total Omset</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalJihans = 0; 
                $totalHendhys = 0; 
                $totalConsolidated = 0; 
            @endphp
            @foreach($data as $row)
                @php
                    $totalJihans += $row['jihans'];
                    $totalHendhys += $row['hendhys'];
                    $totalConsolidated += $row['total'];
                @endphp
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="text-right">Rp {{ number_format($row['jihans'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($row['hendhys'], 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold;">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right">Rp {{ number_format($totalJihans, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalHendhys, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalConsolidated, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
