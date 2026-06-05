<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #{{ $transaction->transaction_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            background: #e5e7eb;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11.5px;
            color: #000;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .receipt-container {
            background: #fff;
            width: 100%;
            max-width: 320px;  /* Lebar standar struk thermal 80mm */
            padding: 12px 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        .mb-1 { margin-bottom: 2px; }
        .mb-2 { margin-bottom: 4px; }
        .mb-3 { margin-bottom: 6px; }
        .mt-3 { margin-top: 6px; }
        .mt-6 { margin-top: 15px; }

        /* ===== Divider ===== */
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
            width: 100%;
        }

        /* ===== Metadata Table ===== */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        /* ===== Item List ===== */
        .item-row {
            margin-bottom: 6px;
        }
        .item-name {
            font-weight: bold;
            display: block;
        }
        .item-details {
            width: 100%;
            display: table;
            font-size: 11px;
        }
        .item-qty-price {
            display: table-cell;
            width: 65%;
            text-align: left;
        }
        .item-total {
            display: table-cell;
            width: 35%;
            text-align: right;
        }

        /* ===== Totals Box ===== */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .totals-table td {
            padding: 2px 0;
        }

        /* ===== Buttons Bar (Hidden in Print) ===== */
        .action-bar {
            margin-top: 15px;
            display: flex;
            gap: 8px;
        }
        .btn {
            flex: 1;
            padding: 6px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #000;
        }
        .btn-print { background: #000; color: #fff; }
        .btn-pdf { background: #1d4ed8; color: #fff; }
        .btn-back { background: #fff; color: #000; }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                box-shadow: none;
                max-width: 80mm;
                width: 80mm;          /* lebar struk thermal 80mm (cocok juga utk rol 75mm) */
                padding: 2mm 3mm;
            }
            .action-bar { display: none !important; }
            @page {
                margin: 0;
                size: 80mm auto;      /* lebar 80mm, tinggi mengalir sesuai isi */
            }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        {{-- Header Brand --}}
        <div class="text-center mb-3">
            <h1 class="font-bold text-center" style="font-size: 14px; letter-spacing: 1px;">HENDHY'S BROWNIES</h1>
            <p>JL. PASAR VI TEMBUNG</p>
            <p>PERCUT SEI TUAN</p>
            <p>Telp: 081213772502 Fax: -</p>
        </div>

        {{-- Metadata Struk --}}
        <table class="meta-table mb-2">
            <tr>
                <td style="width: 18%;">No.</td>
                <td style="width: 45%;">: {{ $transaction->transaction_number }}</td>
                <td style="text-align: right; width: 37%;">{{ \Carbon\Carbon::parse($transaction->date)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td>: {{ strtoupper($transaction->creator->name ?? 'Kasir') }}</td>
                <td style="text-align: right;">{{ $transaction->time }}</td>
            </tr>
            <tr>
                <td>Pel.</td>
                <td colspan="2">: {{ strtoupper($transaction->customer_name ?: 'UMUM/CASH') }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- Daftar Barang --}}
        <div class="mb-3">
            @foreach($transaction->details as $detail)
            <div class="item-row">
                <span class="item-name">{{ $detail->product_name }}</span>
                <div class="item-details">
                    <span class="item-qty-price">
                        {{ number_format($detail->price, 0, ',', '.') }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x {{ (int) $detail->quantity }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $detail->unit->abbreviation ?? 'PCS' }} =
                    </span>
                    <span class="item-total">
                        {{ number_format($detail->total, 0, ',', '.') }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>

        <div class="divider"></div>

        {{-- Summary (Baris, Qty, Total) --}}
        <table class="totals-table">
            <tr>
                <td style="width: 25%;">BARIS={{ count($transaction->details) }}</td>
                <td style="width: 35%;">QTY={{ (int) $transaction->details->sum('quantity') }}</td>
                <td style="width: 5%;"></td>
                <td class="text-right font-bold" style="width: 35%;">
                    {{ number_format($transaction->subtotal, 0, ',', '.') }}
                </td>
            </tr>
            
            @if($transaction->discount_amount > 0)
            <tr>
                <td colspan="2">Diskon</td>
                <td>=</td>
                <td class="text-right font-bold">-{{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            
            @if($transaction->tax_amount > 0)
            <tr>
                <td colspan="2">PPN ({{ $transaction->ppn_type }})</td>
                <td>=</td>
                <td class="text-right font-bold">{{ number_format($transaction->tax_amount, 0, ',', '.') }}</td>
            </tr>
            @endif

            @php $payment = $transaction->payments->first(); @endphp
            <tr>
                <td colspan="2">Tunai</td>
                <td>=</td>
                <td class="text-right font-bold">
                    {{ number_format($payment ? $payment->amount : $transaction->grand_total, 0, ',', '.') }}
                </td>
            </tr>
            
            <tr>
                <td colspan="3"></td>
                <td style="border-top: 1px dashed #000; height: 1px; padding: 0;"></td>
            </tr>
            
            <tr>
                <td colspan="2">Kembali</td>
                <td>=</td>
                <td class="text-right font-bold">
                    {{ number_format($payment ? max(0, $payment->amount - $transaction->grand_total) : 0, 0, ',', '.') }}
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- Footer --}}
        <div class="text-center" style="font-size: 10.5px; line-height: 1.3; margin-top: 8px;">
            <p>Barang yang telah dibeli tidak dapat</p>
            <p>dikembalikan kecuali ada perjanjian</p>
        </div>

        {{-- Actions Bar --}}
        <div class="action-bar no-print">
            <button onclick="window.print()" class="btn btn-print">🖨️ Cetak</button>
            <a href="{{ route('hendhys.pos.invoice', $transaction->id) }}" target="_blank" class="btn btn-pdf">PDF</a>
            <a href="{{ route('hendhys.pos.index') }}" class="btn btn-back">POS Baru</a>
        </div>
    </div>

    <script>
        // Preview-first: struk tidak auto-cetak; kasir klik "Cetak" sendiri.
    </script>
</body>
</html>
