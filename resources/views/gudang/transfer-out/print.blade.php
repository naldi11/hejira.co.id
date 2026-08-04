@php
    $tujuan = $transferOut->to_entity === 'hendhys'
        ? ($transferOut->branch->name ?? 'Cabang Hendhys')
        : 'Jihans — Stok Produksi';

    $sudahDiterima = $transferOut->status === 'received';

    // APP_LOCALE aplikasi ini 'en', sehingga translatedFormat() menghasilkan
    // "August" di dokumen berbahasa Indonesia. Dipaksa 'id' di sini saja.
    $tgl = fn (?\Carbon\Carbon $d, string $f) => $d?->locale('id')->translatedFormat($f) ?? '-';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan — {{ $transferOut->transfer_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page { size: 9.5in 11in; margin: 10mm; }

        body { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; background: #fff; }
        .page { max-width: 9.5in; min-height: 11in; margin: 20px auto; background: #fff; padding: 10mm; }

        .doc-header { display: flex; align-items: flex-start; gap: 16px; border-bottom: 2px solid #000; padding-bottom: 14px; margin-bottom: 16px; }
        .header-text h1 { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 3px; }
        .header-text h2 { font-size: 18px; font-weight: 900; line-height: 1.1; margin-bottom: 2px; }
        .header-text p { font-size: 10px; }

        .doc-badge { margin-left: auto; text-align: right; flex-shrink: 0; }
        .doc-number { font-size: 13px; font-weight: 900; letter-spacing: 0.5px; }
        .badge { display: inline-block; margin-top: 4px; padding: 2px 10px; border: 1px solid #000; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .badge-open { border-style: dashed; }

        /* Pengirim & penerima disandingkan supaya arah kiriman terbaca sekali lihat. */
        .party-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
        .party { border: 1px solid #000; padding: 10px 12px; }
        .party-role { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .party-name { font-size: 12px; font-weight: 700; margin-bottom: 3px; }
        .party-line { font-size: 10px; line-height: 1.45; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px 20px; border: 1px solid #000; padding: 10px 12px; margin-bottom: 14px; }
        .info-row { display: flex; gap: 6px; align-items: baseline; }
        .info-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; min-width: 96px; flex-shrink: 0; }
        .info-value { font-size: 11px; font-weight: 700; }

        .section-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        thead tr { border-bottom: 2px solid #000; }
        thead th { padding: 7px 8px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        thead th.c { text-align: center; }
        tbody tr { border-bottom: 1px dashed #000; }
        tbody td { padding: 7px 8px; font-size: 10px; vertical-align: middle; }
        tbody td.c { text-align: center; }
        tbody td.muted { color: #555; }
        tfoot td { padding: 7px 8px; font-size: 10px; font-weight: 700; border-top: 2px solid #000; }

        .note-box { border: 1px dashed #000; padding: 9px 12px; margin-bottom: 14px; }
        .note-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .note-box p { font-size: 11px; }

        /* Tiga pihak: gudang melepas, pengangkut membawa, cabang menerima. */
        .signature-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; border-top: 1px dashed #000; padding-top: 14px; }
        .sign-box { text-align: center; }
        .sign-label { font-size: 10px; font-weight: 700; margin-bottom: 2px; }
        .sign-sublabel { font-size: 9px; margin-bottom: 46px; }
        .sign-line { border-top: 1px solid #000; margin: 0 8px; padding-top: 6px; }
        .sign-name { font-size: 10px; font-weight: 700; }

        .copies { margin-top: 12px; font-size: 9px; text-align: center; letter-spacing: 0.5px; }
        .doc-footer { margin-top: 8px; font-size: 8px; border-top: 1px dashed #000; padding-top: 6px; display: flex; justify-content: space-between; }

        .action-bar { max-width: 9.5in; margin: 0 auto 12px; display: flex; gap: 10px; padding: 10px 0; }
        .btn { padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back { background: #f3f4f6; color: #374151; }
        .btn-print { background: #000; color: #fff; }

        @media print {
            body { background: #fff; }
            .page { margin: 0; padding: 0; }
            .action-bar { display: none !important; }
            tbody tr, .signature-section { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <a href="{{ route('gudang.transfer-out.show', $transferOut) }}" class="btn btn-back">← Kembali</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Cetak Surat Jalan</button>
</div>

<div class="page">

    {{-- Header --}}
    <div class="doc-header">
        <div class="header-text">
            <h1>Surat Jalan Pengiriman Barang</h1>
            <h2>GUDANG TEMPUA</h2>
            <p>Dokumen Resmi — Menyertai Barang</p>
        </div>
        <div class="doc-badge">
            <div class="doc-number">{{ $transferOut->transfer_number }}</div>
            <span class="badge {{ $sudahDiterima ? '' : 'badge-open' }}">
                {{ $sudahDiterima ? 'Diterima' : 'Dalam Pengiriman' }}
            </span>
        </div>
    </div>

    {{-- Pengirim & Penerima --}}
    <div class="party-grid">
        <div class="party">
            <div class="party-role">Pengirim</div>
            <div class="party-name">GUDANG TEMPUA</div>
            <div class="party-line">Gudang Utama</div>
        </div>
        <div class="party">
            <div class="party-role">Penerima</div>
            <div class="party-name">{{ $tujuan }}</div>
            @if($transferOut->branch?->address)
                <div class="party-line">{{ $transferOut->branch->address }}</div>
            @endif
            @if($transferOut->branch?->phone)
                <div class="party-line">Telp. {{ $transferOut->branch->phone }}</div>
            @endif
        </div>
    </div>

    {{-- Info dokumen --}}
    <div class="info-grid">
        <div class="info-row"><span class="info-label">No. Surat Jalan</span><span class="info-value">{{ $transferOut->transfer_number }}</span></div>
        <div class="info-row"><span class="info-label">Tanggal Kirim</span><span class="info-value">{{ $tgl($transferOut->date, 'd F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Referensi</span><span class="info-value">{{ $transferOut->request?->request_number ?? 'Pengiriman Langsung' }}</span></div>
        <div class="info-row"><span class="info-label">Petugas Gudang</span><span class="info-value">{{ $transferOut->creator->name ?? '-' }}</span></div>
        @if($sudahDiterima)
            <div class="info-row"><span class="info-label">Tanggal Terima</span><span class="info-value">{{ $tgl($transferOut->received_at, 'd F Y H:i') }}</span></div>
            <div class="info-row"><span class="info-label">Diterima Oleh</span><span class="info-value">{{ $transferOut->receive_received_by_name ?: '-' }}</span></div>
        @endif
    </div>

    {{-- Catatan pengiriman --}}
    @if($transferOut->notes)
        <div class="note-box">
            <div class="note-title">Catatan Pengiriman</div>
            <p>{{ $transferOut->notes }}</p>
        </div>
    @endif

    {{-- Daftar barang. Sengaja TANPA HPP/nilai: surat jalan ikut ke penerima,
         harga pokok internal bukan konsumsi mereka. --}}
    <p class="section-title">Daftar Barang yang Dikirim</p>
    <table>
        <thead>
            <tr>
                <th class="c" style="width:28px">No</th>
                <th>Nama Produk</th>
                <th class="c" style="width:80px">Qty Kirim</th>
                <th class="c" style="width:60px">Satuan</th>
                <th class="c" style="width:150px">Keterangan Penerima</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transferOut->details as $i => $item)
                <tr>
                    <td class="c muted">{{ $i + 1 }}</td>
                    <td style="font-weight:700">{{ $item->product->name ?? 'Produk dihapus' }}</td>
                    <td class="c" style="font-weight:700">{{ (int) $item->quantity }}</td>
                    <td class="c muted">{{ $item->unit->abbreviation ?? '-' }}</td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="5" class="c muted" style="padding:16px">Tidak ada item.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right">Total Item / Total Kuantitas</td>
                <td class="c">{{ (int) $transferOut->details->sum('quantity') }}</td>
                <td colspan="2" class="muted" style="font-weight:400">{{ $transferOut->details->count() }} jenis produk</td>
            </tr>
        </tfoot>
    </table>

    {{-- Tanda tangan --}}
    <div class="signature-section">
        <div class="sign-box">
            <div class="sign-label">Pengirim</div>
            <div class="sign-sublabel">Gudang Tempua</div>
            <div class="sign-line">
                <div class="sign-name">{{ $transferOut->creator->name ?? '.........................' }}</div>
            </div>
        </div>
        <div class="sign-box">
            <div class="sign-label">Pengangkut</div>
            <div class="sign-sublabel">Sopir / Kurir</div>
            <div class="sign-line">
                <div class="sign-name">.........................</div>
            </div>
        </div>
        <div class="sign-box">
            <div class="sign-label">Penerima</div>
            <div class="sign-sublabel">{{ $tujuan }}</div>
            <div class="sign-line">
                <div class="sign-name">{{ $transferOut->receive_received_by_name ?: '.........................' }}</div>
            </div>
        </div>
    </div>

    <p class="copies">Lembar 1: Penerima &nbsp;·&nbsp; Lembar 2: Arsip Gudang &nbsp;·&nbsp; Lembar 3: Pengangkut</p>

    <div class="doc-footer">
        <span>Dicetak oleh: {{ auth()->user()->name ?? 'Sistem' }} pada {{ $tgl(now(), 'd F Y H:i') }}</span>
        <span>{{ $transferOut->transfer_number }} | {{ $tujuan }}</span>
    </div>
</div>

<script>
    window.onload = function () { setTimeout(function () { window.print(); }, 500); };
</script>
</body>
</html>
