<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur - {{ $transaction->transaction_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: 9.5in 5.5in;
            margin: 0; /* Menonaktifkan header & footer bawaan browser secara otomatis */
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            background: #fff;
            color: #000;
            font-size: 13px;
            line-height: 1.35;
            padding: 0;
            margin: 0;
        }

        /* ===== Wrapper ===== */
        .page-wrapper {
            width: 8.2in; /* Dibatasi ke 8.2 inci agar area cetak tidak mengenai lubang traktor kanan (mencegah terpotong samping) */
            background: #fff;
            padding: 9mm 6mm 4mm 6mm; /* Diperbesar dari atas agar Kop/No Transaksi tidak terpotong batas cetak printer */
            margin: 0;
            box-sizing: border-box;
        }

        /* ===== Header Grid ===== */
        .header-section {
            width: 100%;
            margin-bottom: 10px;
            display: table;
        }
        .header-left {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            padding-left: 10px;
        }

        .brand-logo-container {
            display: table-cell;
            vertical-align: middle;
            width: 58px;
            padding-right: 8px;
        }
        .brand-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            display: block;
        }
        .brand-text {
            display: table-cell;
            vertical-align: middle;
        }
        .invoice-title {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 1px;
        }
        .brand-name {
            font-size: 13.5px;
            font-weight: bold;
        }
        .brand-sub {
            font-size: 10.5px;
            font-weight: bold;
        }
        .brand-detail {
            font-size: 10.5px;
        }

        /* ===== Metadata Table ===== */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .meta-table td {
            padding: 2px 2px;
            vertical-align: top;
        }

        /* ===== Items Table ===== */
        .table-section {
            width: 100%;
            margin-top: 6px;
            margin-bottom: 6px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.items-table th {
            padding: 5px 3px;
            font-size: 12.5px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
            text-align: left;
        }
        table.items-table td {
            padding: 5px 3px;
            border: none;
            font-size: 12.5px;
        }
        table.items-table th.text-center, table.items-table td.text-center { text-align: center; }
        table.items-table th.text-right, table.items-table td.text-right { text-align: right; }

        /* ===== Bottom Section ===== */
        .bottom-section {
            width: 100%;
            display: table;
            margin-top: 10px;
            font-size: 12px;
        }
        .bottom-left {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }
        .bottom-mid {
            display: table-cell;
            width: 25%;
            vertical-align: top;
            padding-left: 10px;
        }
        .bottom-right {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            padding-left: 10px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 2.5px 0;
        }

        /* ===== Signature Section ===== */
        .signature-table {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 5px;
            text-align: center;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
        }

        /* ===== Action Bar (Print Hidden) ===== */
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
        .btn-back:hover { background: #d1d5db; }
        .btn-print { background: #c2410c; color: white; }
        .btn-print:hover { background: #9a3412; }

        @media print {
            .action-bar { display: none !important; }
            body { background: white; width: 100%; height: 100%; }
            .page-wrapper { margin: 0; box-shadow: none; border-radius: 0; width: 100%; height: 100%; }
        }
    </style>
</head>
<body>

<div class="action-bar print:hidden">
    <a href="{{ route('jihans.pos.index') }}" class="btn btn-back">
        Kembali ke POS
    </a>
    <button onclick="window.print()" class="btn btn-print">
        Cetak Faktur
    </button>
</div>

<div class="page-wrapper">
    {{-- ===== HEADER ===== --}}
    <div class="header-section">
        <div class="header-left">
            <div style="display: table; width: 100%;">
                <div class="brand-logo-container">
                    <img src="{{ asset('logo/jihans-logo.png') }}" class="brand-logo" onerror="this.style.display='none'">
                </div>
                <div class="brand-text">
                    <div class="invoice-title">FAKTUR PENJUALAN</div>
                     <div class="brand-name">JIHAAN'S FOOD</div>
                    <div class="brand-sub">MANUFACTURE FOR KEBAB &amp; TORTILLA</div>
                    <div class="brand-detail">JL. Beringin Pasar 7</div>
                    <div class="brand-detail">081362148090 - 085373736060</div>
                </div>
            </div>
        </div>
        
        <div class="header-right">
            <table class="meta-table">
                <tr>
                    <td style="width: 23%;">No Transaksi</td>
                    <td style="width: 32%;">: {{ $transaction->transaction_number }}</td>
                    <td style="width: 15%;">Dept</td>
                    <td style="width: 30%;">: UTM</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: {{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }} {{ $transaction->time }}</td>
                    <td>User</td>
                    <td>: {{ strtoupper($transaction->creator->name ?? 'KASIR') }}</td>
                </tr>
                <tr>
                    <td>Kode Sales</td>
                    <td>: -</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td>Pelanggan</td>
                    <td colspan="3">: {{ strtoupper($transaction->customer_name ?: 'PELANGGAN UMUM') }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td colspan="3">: 
                        @if($transaction->customer && $transaction->customer->address)
                            {{ strtoupper($transaction->customer->address) }}
                            @if($transaction->customer->phone)
                                ({{ $transaction->customer->phone }})
                            @endif
                        @else
                            {{ $transaction->customer_phone ?: '-' }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ===== ITEMS TABLE ===== --}}
    <div class="table-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">No.</th>
                    <th style="width: 15%;">Kode Item</th>
                    <th style="width: 38%;">Nama Item</th>
                    <th class="text-center" style="width: 10%;">Jml</th>
                    <th class="text-center" style="width: 8%;">Satuan</th>
                    <th class="text-right" style="width: 10%;">Harga</th>
                    <th class="text-right" style="width: 4%;">Pot</th>
                    <th class="text-right" style="width: 10%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $item->product->code ?? '-' }}</td>
                    <td style="font-weight: bold;">{{ $item->product_name }}</td>
                    <td class="text-center font-bold">{{ (int) $item->quantity }}</td>
                    <td class="text-center">{{ $item->unit->abbreviation ?? 'PCS' }}</td>
                    <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $item->discount_amount > 0 ? number_format($item->discount_amount, 0, ',', '.') : '0' }}</td>
                    <td class="text-right font-bold">{{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ===== BOTTOM SECTION ===== --}}
    <div class="bottom-section" style="border-top: 1px solid #000; padding-top: 4px;">
        <div class="bottom-left">
            <div>Keterangan :</div>
            <div style="font-style: italic; min-height: 15px; margin-bottom: 10px;">{{ $transaction->notes ?: '-' }}</div>
            
            <table class="signature-table">
                <tr>
                    <td>Hormat Kami,</td>
                    <td>Penerima,</td>
                </tr>
                <tr style="height: 50px;">
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td>( ............ )</td>
                    <td>( ............ )</td>
                </tr>
            </table>

            <div style="margin-top: 5px; font-weight: bold;">
                Terbilang : <span id="terbilang-text" style="font-style: italic; font-weight: normal;"></span>
            </div>
        </div>

        <div class="bottom-mid">
            <table class="totals-table">
                <tr>
                    <td>Jml Item</td>
                    <td>:</td>
                    <td class="text-right font-bold" style="font-size: 11.5px;">{{ (int) $transaction->details->sum('quantity') }}</td>
                </tr>
                <tr>
                    <td>Potongan</td>
                    <td>:</td>
                    <td class="text-right">0 % / {{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
                </tr>
                @php $payment = $transaction->payments->first(); @endphp
                <tr>
                    <td>Tunai</td>
                    <td>:</td>
                    <td class="text-right font-bold" style="font-size: 11.5px;">
                        {{ number_format($payment ? $payment->amount : 0, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="bottom-right">
            <table class="totals-table">
                <tr>
                    <td>Sub Total</td>
                    <td>:</td>
                    <td class="text-right font-bold">{{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Ongkir/Kurir</td>
                    <td>:</td>
                    <td class="text-right">{{ number_format($transaction->other_costs ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 1px solid #000; font-size: 11.5px; font-weight: bold;">
                    <td style="padding-top: 3px;">Total Akhir</td>
                    <td style="padding-top: 3px;">:</td>
                    <td class="text-right font-bold text-black" style="padding-top: 3px;">
                        {{ number_format($transaction->grand_total, 0, ',', '.') }}
                    </td>
                </tr>
            </table>

            <div style="margin-top: 8px; text-align: right; font-size: 10.5px; font-weight: bold; line-height: 1.35;">
                BANK BRI<br>
                1092-0100-0385-583<br>
                A/N ANNY RITONGA
            </div>
        </div>
    </div>

    {{-- ===== DOUBLE BOTTOM BORDER ===== --}}
    <div style="border-bottom: 3px double #000; margin-top: 2px; margin-bottom: 4px; width: 100%;"></div>

    <div style="width: 100%; text-align: center; font-size: 10px; font-weight: bold; margin-top: 10px;">
        Layanan Pelanggan 085373736060
    </div>
</div>

<script>
    function terbilang(nilai) {
        nilai = Math.floor(Math.abs(nilai));
        var huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        var temp = "";
        if (nilai < 12) {
            temp = " " + huruf[nilai];
        } else if (nilai < 20) {
            temp = terbilang(nilai - 10) + " belas";
        } else if (nilai < 100) {
            temp = terbilang(Math.floor(nilai / 10)) + " puluh" + terbilang(nilai % 10);
        } else if (nilai < 200) {
            temp = " seratus" + terbilang(nilai - 100);
        } else if (nilai < 1000) {
            temp = terbilang(Math.floor(nilai / 100)) + " ratus" + terbilang(nilai % 100);
        } else if (nilai < 2000) {
            temp = " seribu" + terbilang(nilai - 1000);
        } else if (nilai < 1000000) {
            temp = terbilang(Math.floor(nilai / 1000)) + " ribu" + terbilang(nilai % 1000);
        } else if (nilai < 1000000000) {
            temp = terbilang(Math.floor(nilai / 1000000)) + " juta" + terbilang(nilai % 1000000);
        } else if (nilai < 1000000000000) {
            temp = terbilang(Math.floor(nilai / 1000000000)) + " milyar" + terbilang(nilai % 1000000000);
        }
        return temp.trim();
    }

    document.getElementById('terbilang-text').innerText = terbilang({{ $transaction->grand_total }}) + " rupiah";

    window.onload = function() {
        setTimeout(function() { window.print(); }, 600);
    }
</script>
</body>
</html>
