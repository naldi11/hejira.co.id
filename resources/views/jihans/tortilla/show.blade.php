@extends('layouts.jihans')
@section('title', 'Detail Produksi Tortilla')
@section('page-title', 'Detail Produksi Sesi ' . $tortilla->session_number)

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full bg-surface space-y-lg">

        {{-- Header Info --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm p-md">
            <div class="space-y-1">
                <p class="text-xs font-bold text-outline uppercase tracking-wider">Nomor Sesi</p>
                <h3 class="text-xl font-bold text-on-surface font-mono">{{ $tortilla->session_number }}</h3>
                <p class="text-sm text-on-surface-variant italic">{{ $tortilla->notes ?? 'Tidak ada catatan' }}</p>
            </div>
            <div class="grid grid-cols-2 gap-md text-right">
                <div class="text-left sm:text-right">
                    <p class="text-xs font-bold text-outline uppercase tracking-wider">Tanggal</p>
                    <p class="text-on-surface font-bold">{{ \Carbon\Carbon::parse($tortilla->date)->format('d F Y') }}</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-xs font-bold text-outline uppercase tracking-wider">Input Oleh</p>
                    <p class="text-on-surface font-bold">{{ $tortilla->creator->name ?? 'System' }}</p>
                </div>
            </div>
        </div>

        {{-- Table Details --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant">
                <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Rincian Produksi Karyawan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-xs uppercase text-on-surface-variant">
                            <th class="px-md py-sm">Karyawan</th>
                            <th class="px-sm py-sm text-center">TB</th>
                            <th class="px-sm py-sm text-center">TS</th>
                            <th class="px-sm py-sm text-center">TK</th>
                            <th class="px-sm py-sm text-center">TC</th>
                            <th class="px-sm py-sm text-center">KRIBAB</th>
                            <th class="px-md py-sm text-right">Total Upah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach($tortilla->details as $detail)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-md py-sm font-bold text-on-surface">
                                    {{ $detail->karyawan->name }}
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ $detail->tb_qty }} <span class="text-[10px] text-on-surface-variant">(@ Rp {{ number_format($detail->tb_rate, 0, ',', '.') }})</span>
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ $detail->ts_qty }} <span class="text-[10px] text-on-surface-variant">(@ Rp {{ number_format($detail->ts_rate, 0, ',', '.') }})</span>
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ $detail->tk_qty }} <span class="text-[10px] text-on-surface-variant">(@ Rp {{ number_format($detail->tk_rate, 0, ',', '.') }})</span>
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ $detail->tc_qty }} <span class="text-[10px] text-on-surface-variant">(@ Rp {{ number_format($detail->tc_rate, 0, ',', '.') }})</span>
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ $detail->kribab_qty }} <span class="text-[10px] text-on-surface-variant">(@ Rp {{ number_format($detail->kribab_rate, 0, ',', '.') }})</span>
                                </td>
                                <td class="px-md py-sm text-right font-bold text-primary">
                                    Rp {{ number_format($detail->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-surface-container-low font-bold">
                            <td class="px-md py-sm">TOTAL KESELURUHAN</td>
                            <td class="px-sm py-sm text-center">{{ $tortilla->details->sum('tb_qty') }}</td>
                            <td class="px-sm py-sm text-center">{{ $tortilla->details->sum('ts_qty') }}</td>
                            <td class="px-sm py-sm text-center">{{ $tortilla->details->sum('tk_qty') }}</td>
                            <td class="px-sm py-sm text-center">{{ $tortilla->details->sum('tc_qty') }}</td>
                            <td class="px-sm py-sm text-center">{{ $tortilla->details->sum('kribab_qty') }}</td>
                            <td class="px-md py-sm text-right text-primary text-xl">
                                Rp {{ number_format($tortilla->details->sum('total_amount'), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center gap-md">
            <a href="{{ route('jihans.tortilla.index') }}" class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Kembali ke Daftar
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-sm px-md py-sm bg-secondary text-on-secondary rounded-lg font-label-lg text-label-lg hover:bg-secondary-fixed-dim transition-all">
                <span class="material-symbols-outlined text-[18px]">print</span>
                Cetak Detail
            </button>
        </div>

    </div>
@endsection
