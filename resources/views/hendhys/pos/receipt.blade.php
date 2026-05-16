<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #{{ $transaction->transaction_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; background: #fff; }
            .no-print { display: none !important; }
            .print-area { width: 100%; max-width: 300px; margin: 0 auto; padding: 0; }
            @page { margin: 0; size: auto; }
        }
        body { background: #e5e7eb; display: flex; justify-content: center; padding: 20px; font-family: 'Inter', sans-serif; }
        .receipt-container { background: #fff; width: 100%; max-width: 380px; padding: 24px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border-radius: 8px; }
    </style>
</head>
<body>

    <div class="receipt-container print-area">
        {{-- Header --}}
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-gray-900 tracking-wider uppercase mb-1">Hendhys Bakery</h1>
            <p class="text-xs text-gray-600 font-medium">{{ auth()->user()->branch->type === 'pusat' ? 'Pusat' : 'Cabang ' . auth()->user()->branch->name }}</p>
            <p class="text-[11px] text-gray-500 mt-1">{{ auth()->user()->branch->address ?? 'Alamat Cabang' }}</p>
        </div>

        {{-- Info Struk --}}
        <div class="flex justify-between items-end border-b border-dashed border-gray-300 pb-3 mb-3 text-[11px] text-gray-600">
            <div>
                <p>Tgl: {{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }} {{ $transaction->time }}</p>
                <p>No: <span class="font-bold text-gray-800">{{ $transaction->transaction_number }}</span></p>
            </div>
            <div class="text-right">
                <p>Ksr: {{ $transaction->creator->name }}</p>
                <p>Plg: {{ $transaction->customer_name }}</p>
            </div>
        </div>

        {{-- Items --}}
        <div class="mb-4">
            <table class="w-full text-[11px]">
                @foreach($transaction->details as $detail)
                <tr>
                    <td colspan="3" class="pb-1 font-bold text-gray-800">{{ $detail->product_name }}</td>
                </tr>
                <tr class="text-gray-600 border-b border-gray-100 last:border-0">
                    <td class="pb-2 w-16">{{ (float) $detail->quantity }} {{ $detail->unit->code }}</td>
                    <td class="pb-2">x {{ number_format($detail->price, 0, ',', '.') }}</td>
                    <td class="pb-2 text-right font-medium text-gray-800">{{ number_format($detail->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </table>
        </div>

        {{-- Summary --}}
        <div class="border-t border-dashed border-gray-300 pt-3 mb-4 space-y-1.5 text-[11px]">
            <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>{{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>
            
            @if($transaction->discount_amount > 0)
            <div class="flex justify-between text-gray-600">
                <span>Diskon</span>
                <span>- {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            
            @if($transaction->tax_amount > 0)
            <div class="flex justify-between text-gray-600">
                <span>PPN ({{ $transaction->ppn_type }})</span>
                <span>{{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
            </div>
            @endif

            <div class="flex justify-between items-center pt-2 font-bold text-sm text-gray-900 border-t border-gray-200 mt-2">
                <span>TOTAL</span>
                <span>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Payment Info --}}
        <div class="border-t border-dashed border-gray-300 pt-3 text-[11px] text-gray-600 space-y-1">
            @foreach($transaction->payments as $payment)
            <div class="flex justify-between font-medium">
                <span class="uppercase">BAYAR ({{ $payment->payment_method }})</span>
                <span>{{ number_format($payment->amount, 0, ',', '.') }}</span>
            </div>
            @if($payment->payment_method === 'cash')
                <div class="flex justify-between">
                    <span>KEMBALI</span>
                    <span>{{ number_format(max(0, $payment->amount - $transaction->grand_total), 0, ',', '.') }}</span>
                </div>
            @else
                <div class="flex justify-between text-[10px]">
                    <span>Bank: {{ $payment->bank_name ?: '-' }}</span>
                    <span>Ref: {{ $payment->reference_number ?: '-' }}</span>
                </div>
            @endif
            @endforeach
        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-[10px] text-gray-500">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
        </div>

        {{-- Actions --}}
        <div class="mt-8 pt-6 border-t border-gray-200 flex gap-2 no-print">
            <button onclick="window.print()" class="flex-1 bg-gray-800 text-white py-2 rounded font-medium text-sm hover:bg-black transition-colors">
                Cetak Struk
            </button>
            <a href="{{ route('hendhys.pos.index') }}" class="flex-1 bg-[#d97706] text-white py-2 rounded font-medium text-sm text-center hover:bg-[#b45309] transition-colors">
                POS Baru
            </a>
        </div>
    </div>

    <script>
        // Auto print upon load if desired (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
