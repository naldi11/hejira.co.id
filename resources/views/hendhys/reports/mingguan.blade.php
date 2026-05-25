@extends('layouts.hendhys')

@section('title', 'Laporan Penjualan Mingguan')
@section('page-title', 'Rekap Penjualan Mingguan')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop space-y-md overflow-y-auto custom-scrollbar h-full">
    {{-- Filter Card --}}
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant p-md">
        <form action="{{ route('hendhys.reports.mingguan') }}" method="GET" class="flex flex-wrap items-end gap-sm">
            <div class="flex flex-col gap-[4px]">
                <label for="date_from" class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider ml-1">Dari</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                       class="h-10 rounded-lg border-outline-variant bg-surface focus:border-primary focus:ring-primary sm:text-sm transition-all duration-200">
            </div>
            <div class="flex flex-col gap-[4px]">
                <label for="date_to" class="text-label-sm font-bold text-on-surface-variant uppercase tracking-wider ml-1">Sampai</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                       class="h-10 rounded-lg border-outline-variant bg-surface focus:border-primary focus:ring-primary sm:text-sm transition-all duration-200">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="h-10 px-md bg-primary text-on-primary rounded-lg font-label-lg uppercase tracking-wider hover:bg-primary-container hover:text-on-primary-container transition-all shadow-sm">
                    Filter
                </button>
                <a href="{{ route('hendhys.reports.pdf', ['type' => 'mingguan'] + request()->all()) }}" target="_blank" class="h-10 px-md bg-error text-on-error rounded-lg font-label-lg uppercase tracking-wider hover:bg-red-700 transition-all shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                    PDF
                </a>
                <a href="{{ route('hendhys.reports.mingguan') }}" class="h-10 px-md bg-surface-container-high text-on-surface rounded-lg font-label-lg uppercase tracking-wider hover:bg-surface-container-highest transition-all flex items-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table Card --}}
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-high border-b border-outline-variant">
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface uppercase tracking-wider">Periode Minggu</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface uppercase tracking-wider text-center">Jml Transaksi</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface uppercase tracking-wider text-right">Total Transaksi</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface uppercase tracking-wider text-right">Tunai</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface uppercase tracking-wider text-right">Kredit</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface uppercase tracking-wider text-right">Debit</th>
                        <th class="px-md py-sm text-label-sm font-bold text-on-surface uppercase tracking-wider text-right">CC</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($rows as $row)
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-md py-sm whitespace-nowrap text-body-md font-medium">
                            {{ \Carbon\Carbon::parse($row->minggu_mulai)->translatedFormat('d M') }} - 
                            {{ \Carbon\Carbon::parse($row->minggu_akhir)->translatedFormat('d M Y') }}
                        </td>
                        <td class="px-md py-sm whitespace-nowrap text-body-md text-on-surface-variant text-center">
                            {{ number_format($row->jumlah_transaksi) }}
                        </td>
                        <td class="px-md py-sm whitespace-nowrap text-body-md font-bold text-on-surface text-right text-primary">
                            Rp {{ number_format($row->total_transaksi) }}
                        </td>
                        <td class="px-md py-sm whitespace-nowrap text-body-md text-on-surface-variant text-right">
                            {{ number_format($row->tunai) }}
                        </td>
                        <td class="px-md py-sm whitespace-nowrap text-body-md text-error text-right">
                            {{ number_format($row->kredit) }}
                        </td>
                        <td class="px-md py-sm whitespace-nowrap text-body-md text-on-surface-variant text-right">
                            {{ number_format($row->kartu_debit) }}
                        </td>
                        <td class="px-md py-sm whitespace-nowrap text-body-md text-on-surface-variant text-right">
                            {{ number_format($row->kartu_kredit) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-md py-xl text-center text-on-surface-variant italic font-body-md">
                            Belum ada data transaksi untuk periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($rows->count() > 0)
                <tfoot class="bg-surface-container-high font-bold border-t border-outline-variant">
                    <tr>
                        <td class="px-md py-sm text-label-lg uppercase">Total Halaman Ini</td>
                        <td class="px-md py-sm text-label-lg text-center">{{ number_format($rows->sum('jumlah_transaksi')) }}</td>
                        <td class="px-md py-sm text-label-lg text-right text-primary">Rp {{ number_format($rows->sum('total_transaksi')) }}</td>
                        <td class="px-md py-sm text-label-lg text-right">{{ number_format($rows->sum('tunai')) }}</td>
                        <td class="px-md py-sm text-label-lg text-right text-error">{{ number_format($rows->sum('kredit')) }}</td>
                        <td class="px-md py-sm text-label-lg text-right">{{ number_format($rows->sum('kartu_debit')) }}</td>
                        <td class="px-md py-sm text-label-lg text-right">{{ number_format($rows->sum('kartu_kredit')) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($rows->hasPages())
        <div class="px-md py-sm border-t border-outline-variant bg-surface-container-lowest">
            {{ $rows->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
