<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST — {{ $transferToBranch->transfer_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { size: A4 portrait; margin: 15mm 15mm; }
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #1a1a1a; background: #f5f5f5; }
        .page { max-width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 20mm 18mm; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }

        .doc-header { display: flex; align-items: flex-start; gap: 16px; border-bottom: 2px solid #92400e; padding-bottom: 12px; margin-bottom: 14px; }
        .header-text h1 { font-size: 9px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 3px; }
        .header-text h2 { font-size: 18px; font-weight: 900; color: #78350f; line-height: 1.1; margin-bottom: 2px; }
        .header-text p { font-size: 10px; color: #a16207; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .badge-received { background: #d1fae5; color: #065f46; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 12px 14px; margin-bottom: 14px; }
        .info-row { display: flex; gap: 6px; align-items: baseline; }
        .info-label { font-size: 9px; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; min-width: 90px; flex-shrink: 0; }
        .info-value { font-size: 11px; font-weight: 600; color: #78350f; }

        .section-title { font-size: 9px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        thead tr { background: #78350f; }
        thead th { padding: 7px 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; text-align: left; }
        thead th.text-center { text-align: center; }
        tbody tr { border-bottom: 1px solid #fde68a; }
        tbody tr:nth-child(even) { background: #fffbeb; }
        tbody td { padding: 7px 8px; font-size: 10px; vertical-align: middle; }
        tbody td.text-center { text-align: center; }

        .k-baik   { background: #d1fae5; color: #065f46; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }
        .k-rusak  { background: #fee2e2; color: #7f1d1d; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }
        .k-kurang { background: #fef3c7; color: #78350f; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }

        .kendala-box { border: 1px solid #fed7aa; background: #fff7ed; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; }
        .kendala-box .kendala-title { font-size: 9px; font-weight: 700; color: #c2410c; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .kendala-box p { font-size: 11px; color: #7c2d12; }

        .photo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
        .photo-item img { width: 100%; height: 55mm; object-fit: cover; border: 1px solid #fde68a; border-radius: 4px; }
        .photo-caption { font-size: 8px; color: #92400e; margin-top: 3px; text-align: center; }

        .signature-section { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 16px; border-top: 1px dashed #fcd34d; padding-top: 14px; }
        .sign-box { text-align: center; }
        .sign-label { font-size: 10px; color: #78350f; font-weight: 600; margin-bottom: 2px; }
        .sign-sublabel { font-size: 9px; color: #a16207; margin-bottom: 40px; }
        .sign-line { border-top: 1px solid #78350f; margin: 0 20px; padding-top: 6px; }
        .sign-name { font-size: 11px; font-weight: 700; color: #78350f; }

        .doc-footer { margin-top: 14px; font-size: 8px; color: #a0aec0; border-top: 1px solid #fde68a; padding-top: 6px; display: flex; justify-content: space-between; }

        .action-bar { max-width: 210mm; margin: 0 auto 12px; display: flex; gap: 10px; padding: 10px 0; }
        .btn { padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back { background: #f3f4f6; color: #374151; }
        .btn-print { background: #78350f; color: white; }

        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; padding: 0; }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <a href="{{ route('hendhys.transfer-to-branch.show', $transferToBranch) }}" class="btn btn-back">← Kembali</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Cetak BAST</button>
</div>

<div class="page">

    {{-- Header --}}
    <div class="doc-header">
        <div class="header-text">
            <h1>Berita Acara Serah Terima Barang</h1>
            <h2>HENDHYS BROWNIES</h2>
            <p>Distribusi Pusat → {{ $transferToBranch->branch->name }}</p>
        </div>
        <div style="margin-left:auto; text-align:right;">
            <span class="badge badge-received">DITERIMA</span>
        </div>
    </div>

    {{-- Info --}}
    <div class="info-grid">
        <div class="info-row"><span class="info-label">No. Transfer</span><span class="info-value">{{ $transferToBranch->transfer_number }}</span></div>
        <div class="info-row"><span class="info-label">Tanggal Terima</span><span class="info-value">{{ $transferToBranch->updated_at->translatedFormat('d F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Pengirim (Pusat)</span><span class="info-value">{{ $transferToBranch->receive_pengirim_name ?: ($transferToBranch->creator->name ?? '-') }}</span></div>
        <div class="info-row"><span class="info-label">Penerima (Cabang)</span><span class="info-value">{{ $transferToBranch->receive_received_by_name ?: '-' }}</span></div>
        <div class="info-row"><span class="info-label">Cabang Tujuan</span><span class="info-value">{{ $transferToBranch->branch->name }}</span></div>
        <div class="info-row"><span class="info-label">Dicetak</span><span class="info-value">{{ now()->translatedFormat('d F Y H:i') }}</span></div>
    </div>

    {{-- Kendala --}}
    @if($transferToBranch->receive_kendala)
    <div class="kendala-box">
        <div class="kendala-title">⚠ Kendala / Catatan Masalah</div>
        <p>{{ $transferToBranch->receive_kendala }}</p>
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
                <th class="text-center" style="width:70px">Qty Terima</th>
                <th class="text-center" style="width:45px">Satuan</th>
                <th class="text-center" style="width:60px">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transferToBranch->details as $i => $item)
            <tr>
                <td class="text-center" style="color:#9ca3af">{{ $i + 1 }}</td>
                <td style="font-weight:600">{{ $item->product->name }}</td>
                <td class="text-center" style="color:#9ca3af">{{ (int) $item->quantity }}</td>
                <td class="text-center" style="font-weight:700">{{ floatval($item->received_quantity ?? $item->quantity) }}</td>
                <td class="text-center" style="color:#718096">{{ $item->unit->abbreviation ?? '-' }}</td>
                <td class="text-center">
                    @if($item->kondisi)
                    <span class="k-{{ $item->kondisi }}">{{ ucfirst($item->kondisi) }}</span>
                    @else <span style="color:#d1d5db">—</span> @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Photos --}}
    @if($transferToBranch->photos->isNotEmpty())
    <p class="section-title">Foto Bukti Penerimaan ({{ $transferToBranch->photos->count() }} foto)</p>
    <div class="photo-grid">
        @foreach($transferToBranch->photos as $photo)
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
            <div class="sign-label">Pengirim (Hendhys Pusat)</div>
            <div class="sign-sublabel">Tanda Tangan &amp; Cap</div>
            <div class="sign-line">
                <div class="sign-name">{{ $transferToBranch->receive_pengirim_name ?: ($transferToBranch->creator->name ?? '.................................') }}</div>
            </div>
        </div>
        <div class="sign-box">
            <div class="sign-label">Penerima ({{ $transferToBranch->branch->name }})</div>
            <div class="sign-sublabel">Tanda Tangan &amp; Cap</div>
            <div class="sign-line">
                <div class="sign-name">{{ $transferToBranch->receive_received_by_name ?: '.................................' }}</div>
            </div>
        </div>
    </div>

    <div class="doc-footer">
        <span>Dicetak oleh: {{ auth()->user()->name }} pada {{ now()->format('d/m/Y H:i') }}</span>
        <span>{{ $transferToBranch->transfer_number }} | {{ $transferToBranch->branch->name }}</span>
    </div>
</div>

<script>
    window.onload = function () { setTimeout(function () { window.print(); }, 500); };
</script>
</body>
</html>
