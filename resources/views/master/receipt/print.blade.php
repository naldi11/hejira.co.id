<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST — {{ $transferOut->transfer_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { size: 9.5in 11in; margin: 10mm; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; background: #fff; }
        .page { max-width: 9.5in; min-height: 11in; margin: 20px auto; background: #fff; padding: 10mm; box-shadow: none; }

        .doc-header { display: flex; align-items: flex-start; gap: 16px; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 14px; }
        .logo { width: 64px; height: 64px; object-fit: contain; }
        .header-text h1 { font-size: 9px; font-weight: 700; color: #000; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 3px; }
        .header-text h2 { font-size: 18px; font-weight: 900; color: #000; line-height: 1.1; margin-bottom: 2px; }
        .header-text p { font-size: 10px; color: #000; }
        .badge { display: inline-block; padding: 2px 10px; border: 1px solid #000; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #000; }
        .badge-received { background: #fff; color: #000; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; background: #fff; border: 1px solid #000; padding: 12px 14px; margin-bottom: 14px; }
        .info-row { display: flex; gap: 6px; align-items: baseline; }
        .info-label { font-size: 9px; color: #000; text-transform: uppercase; letter-spacing: 0.5px; min-width: 90px; flex-shrink: 0; }
        .info-value { font-size: 11px; font-weight: 600; color: #000; }

        .section-title { font-size: 9px; font-weight: 700; color: #000; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        thead tr { background: #fff; border-bottom: 2px solid #000; }
        thead th { padding: 7px 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #000; text-align: left; }
        thead th.text-center { text-align: center; }
        tbody tr { border-bottom: 1px dashed #000; }
        tbody tr:nth-child(even) { background: none; }
        tbody td { padding: 7px 8px; font-size: 10px; vertical-align: middle; color: #000; }
        tbody td.text-center { text-align: center; }

        .k-baik   { border: 1px solid #000; padding: 1px 6px; font-size: 9px; font-weight: 700; color: #000; }
        .k-rusak  { border: 1px dashed #000; padding: 1px 6px; font-size: 9px; font-weight: 700; color: #000; }
        .k-kurang { border: 1px solid #000; padding: 1px 6px; font-size: 9px; font-weight: 700; color: #000; }

        .kendala-box { border: 1px dashed #000; background: #fff; padding: 10px 12px; margin-bottom: 14px; }
        .kendala-box .kendala-title { font-size: 9px; font-weight: 700; color: #000; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .kendala-box p { font-size: 11px; color: #000; }

        .photo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
        .photo-item img { width: 100%; height: 55mm; object-fit: cover; border: 1px solid #000; border-radius: 4px; }
        .photo-caption { font-size: 8px; color: #000; margin-top: 3px; text-align: center; }

        .signature-section { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 16px; border-top: 1px dashed #000; padding-top: 14px; }
        .sign-box { text-align: center; }
        .sign-label { font-size: 10px; color: #000; font-weight: 600; margin-bottom: 2px; }
        .sign-sublabel { font-size: 9px; color: #000; margin-bottom: 40px; }
        .sign-line { border-top: 1px solid #000; margin: 0 20px; padding-top: 6px; }
        .sign-name { font-size: 11px; font-weight: 700; color: #000; }

        .doc-footer { margin-top: 14px; font-size: 8px; color: #000; border-top: 1px dashed #000; padding-top: 6px; display: flex; justify-content: space-between; }

        .action-bar { max-width: 9.5in; margin: 0 auto 12px; display: flex; gap: 10px; padding: 10px 0; }
        .btn { padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back { background: #f3f4f6; color: #374151; }
        .btn-print { background: #000; color: white; }

        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; padding: 0; }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <a href="javascript:history.back()" class="btn btn-back">← Kembali</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Cetak BAST</button>
</div>

<div class="page">

    {{-- Header --}}
    <div class="doc-header">
        @php
            $transferOut->loadMissing('receiptConfirmation.details');
            $entityName = $currentScope === 'jihans' ? "JIHAN'S FOOD" : 'HENDHYS BROWNIES';
        @endphp
        <div class="header-text">
            <h1>Berita Acara Serah Terima Barang</h1>
            <h2>{{ $entityName }}</h2>
            <p>Dokumen Penerimaan dari Gudang Tempua</p>
        </div>
        <div style="margin-left:auto; text-align:right;">
            <span class="badge badge-received">DITERIMA</span>
        </div>
    </div>

    {{-- Info --}}
    <div class="info-grid">
        <div class="info-row"><span class="info-label">No. Transfer</span><span class="info-value">{{ $transferOut->transfer_number }}</span></div>
        <div class="info-row"><span class="info-label">Tanggal Terima</span><span class="info-value">{{ ($transferOut->received_at ?? $transferOut->updated_at)->translatedFormat('d F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Pengirim (Gudang)</span><span class="info-value">{{ $transferOut->receive_pengirim_name ?: ($transferOut->creator->name ?? '-') }}</span></div>
        <div class="info-row"><span class="info-label">Penerima</span><span class="info-value">{{ $transferOut->receive_received_by_name ?: '-' }}</span></div>
        @if($transferOut->receive_notes)
        <div class="info-row"><span class="info-label">No. Surat Jalan</span><span class="info-value">{{ $transferOut->receive_notes }}</span></div>
        @endif
        <div class="info-row"><span class="info-label">Dicetak</span><span class="info-value">{{ now()->translatedFormat('d F Y H:i') }}</span></div>
    </div>

    {{-- Kendala --}}
    @if($transferOut->receive_kendala)
    <div class="kendala-box">
        <div class="kendala-title">⚠ Kendala / Catatan Masalah</div>
        <p>{{ $transferOut->receive_kendala }}</p>
    </div>
    @endif

    {{-- Items --}}
    <p class="section-title">Daftar Barang yang Diterima</p>
    <table>
        <thead>
            <tr>
                <th style="width:22px">No</th>
                <th>Nama Produk</th>
                <th class="text-center" style="width:60px">Qty Kirim</th>
                <th class="text-center" style="width:70px">Qty Bagus</th>
                <th class="text-center" style="width:60px">Qty Rusak</th>
                <th class="text-center" style="width:60px">Qty Kurang</th>
                <th class="text-center" style="width:45px">Satuan</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $receipt = $transferOut->receiptConfirmation;
                $detailsGrouped = $receipt ? $receipt->details->groupBy('product_id') : null;
            @endphp
            @foreach($transferOut->details as $i => $item)
            @php
                $qtySent = (float) $item->quantity;
                $qtyBagus = 0;
                $qtyRusak = 0;
                $qtyKurang = 0;
                $notes = [];

                if ($detailsGrouped && isset($detailsGrouped[$item->product_id])) {
                    foreach ($detailsGrouped[$item->product_id] as $rcDetail) {
                        if ($rcDetail->condition === 'baik') {
                            $qtyBagus += (float) $rcDetail->actual_qty;
                        } elseif ($rcDetail->condition === 'rusak') {
                            $qtyRusak += (float) $rcDetail->actual_qty;
                            if ($rcDetail->notes) {
                                $notes[] = $rcDetail->notes;
                            }
                        } elseif (in_array($rcDetail->condition, ['kurang', 'hilang'])) {
                            $qtyKurang += (float) $rcDetail->actual_qty;
                            if ($rcDetail->notes) {
                                $notes[] = $rcDetail->notes;
                            }
                        }
                    }
                } else {
                    // Fallback untuk data lama
                    $qtyBagus = (float) ($item->received_quantity ?? $item->quantity);
                    $qtyRusak = 0;
                    $qtyKurang = 0;
                    if ($item->kondisi === 'rusak') {
                        $qtyRusak = (float) ($item->quantity - $qtyBagus);
                    } elseif ($item->kondisi === 'kurang') {
                        $qtyKurang = (float) ($item->quantity - $qtyBagus);
                    }
                }
                $notesStr = implode(', ', $notes);
            @endphp
            <tr>
                <td class="text-center" style="color:#9ca3af">{{ $i + 1 }}</td>
                <td style="font-weight:600">{{ $item->product->name }}</td>
                <td class="text-center" style="color:#9ca3af">{{ floatval($qtySent) }}</td>
                <td class="text-center" style="font-weight:700; color:#065f46">{{ floatval($qtyBagus) }}</td>
                <td class="text-center" style="font-weight:700; color:#b91c1c">{{ floatval($qtyRusak) > 0 ? floatval($qtyRusak) : '-' }}</td>
                <td class="text-center" style="font-weight:700; color:#d97706">{{ floatval($qtyKurang) > 0 ? floatval($qtyKurang) : '-' }}</td>
                <td class="text-center" style="color:#718096">{{ $item->unit->abbreviation ?? '-' }}</td>
                <td style="color:#4a5568">{{ $notesStr ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Photos --}}
    @if($transferOut->photos->isNotEmpty())
    <p class="section-title">Foto Bukti Penerimaan ({{ $transferOut->photos->count() }} foto)</p>
    <div class="photo-grid">
        @foreach($transferOut->photos as $photo)
        <div class="photo-item">
            <img src="{{ Storage::url($photo->path) }}" alt="Foto bukti">
            @if($photo->caption)<p class="photo-caption">{{ $photo->caption }}</p>@endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Signature --}}
    <div class="signature-section">
        <div class="sign-box">
            <div class="sign-label">Pengirim (Gudang Tempua)</div>
            <div class="sign-sublabel">Tanda Tangan &amp; Cap</div>
            <div class="sign-line">
                <div class="sign-name">{{ $transferOut->receive_pengirim_name ?: '.................................' }}</div>
            </div>
        </div>
        <div class="sign-box">
            <div class="sign-label">Penerima ({{ $entityName }})</div>
            <div class="sign-sublabel">Tanda Tangan &amp; Cap</div>
            <div class="sign-line">
                <div class="sign-name">{{ $transferOut->receive_received_by_name ?: '.................................' }}</div>
            </div>
        </div>
    </div>

    <div class="doc-footer">
        <span>Dicetak oleh: {{ auth()->user()->name }} pada {{ now()->format('d/m/Y H:i') }}</span>
        <span>{{ $transferOut->transfer_number }} | {{ $entityName }}</span>
    </div>
</div>

<script>
    window.onload = function () { setTimeout(function () { window.print(); }, 500); };
</script>
</body>
</html>
