@extends('layouts.jihans')
@section('title', 'Rekap Produksi Karyawan')
@section('page-title', 'Rekap Produksi Tortilla')

@section('content')
<div class="space-y-6">

    {{-- Filter Periode --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">

        {{-- Tombol Shortcut Periode --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('jihans.tortilla.recap', ['periode' => 'hari']) }}"
                class="px-4 py-1.5 rounded-full text-sm font-bold border transition-all
                    {{ $periode === 'hari' ? 'bg-orange-600 text-white border-orange-600' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' }}">
                Hari Ini
            </a>
            <a href="{{ route('jihans.tortilla.recap', ['periode' => 'minggu']) }}"
                class="px-4 py-1.5 rounded-full text-sm font-bold border transition-all
                    {{ $periode === 'minggu' ? 'bg-orange-600 text-white border-orange-600' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' }}">
                Minggu Ini
            </a>
            <a href="{{ route('jihans.tortilla.recap', ['periode' => 'bulan']) }}"
                class="px-4 py-1.5 rounded-full text-sm font-bold border transition-all
                    {{ $periode === 'bulan' ? 'bg-orange-600 text-white border-orange-600' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' }}">
                Bulan Ini
            </a>
            <a href="{{ route('jihans.tortilla.recap') }}"
                class="px-4 py-1.5 rounded-full text-sm font-bold border transition-all
                    {{ $noFilter ? 'bg-orange-600 text-white border-orange-600' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' }}">
                Semua Data
            </a>
        </div>

        {{-- Custom Range --}}
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom ? $dateFrom->format('Y-m-d') : '' }}"
                    class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all">
            </div>
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo ? $dateTo->format('Y-m-d') : '' }}"
                    class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-700 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-all flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Custom Range
            </button>
            @php
                $exportParams = $periode
                    ? ['periode' => $periode]
                    : ($dateFrom && $dateTo ? ['date_from' => $dateFrom->format('Y-m-d'), 'date_to' => $dateTo->format('Y-m-d')] : []);
            @endphp
            <a href="{{ route('jihans.tortilla.recap.export', $exportParams) }}"
                class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-bold hover:bg-green-700 transition-all flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Export Excel
            </a>
        </form>
    </div>

    {{-- Table Recap --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-sm font-black text-slate-600 uppercase tracking-wider">
                @if($noFilter)
                    Semua Data Produksi
                @elseif($dateFrom && $dateTo)
                    Periode: {{ $dateFrom->format('d M Y') }} — {{ $dateTo->format('d M Y') }}
                @elseif($periode === 'hari')
                    Produksi Hari Ini
                @elseif($periode === 'minggu')
                    Produksi Minggu Ini
                @elseif($periode === 'bulan')
                    Produksi Bulan Ini
                @endif
            </h3>
            <span class="text-xs font-bold text-slate-400">{{ $recap->count() }} karyawan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-black tracking-wider">
                        <th class="px-6 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3 text-center">Hadir</th>
                        <th class="px-4 py-3 text-center">TB</th>
                        <th class="px-4 py-3 text-center">TS</th>
                        <th class="px-4 py-3 text-center">TK</th>
                        <th class="px-4 py-3 text-center">TC</th>
                        <th class="px-4 py-3 text-center">Kribab</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recap as $item)
                        <tr class="hover:bg-orange-50/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-800">
                                {{ $item->karyawan->name }}
                            </td>
                            <td class="px-4 py-4 text-center font-bold text-orange-600">
                                {{ $item->hadir_count }}x
                            </td>
                            <td class="px-4 py-4 text-center font-black text-slate-800">
                                {{ number_format($item->total_tb, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-center font-black text-slate-800">
                                {{ number_format($item->total_ts, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-center font-black text-slate-800">
                                {{ number_format($item->total_tk, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-center font-black text-slate-800">
                                {{ number_format($item->total_tc, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-center font-black text-slate-800">
                                {{ number_format($item->total_kribab, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 italic">
                                Tidak ada data produksi untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($recap->isNotEmpty())
                    <tfoot>
                        <tr class="bg-orange-50 border-t-2 border-orange-200 font-black">
                            <td class="px-6 py-4 text-slate-700">GRAND TOTAL</td>
                            <td class="px-4 py-4 text-center"></td>
                            <td class="px-4 py-4 text-center text-slate-700">{{ number_format($recap->sum('total_tb'), 0, ',', '.') }}</td>
                            <td class="px-4 py-4 text-center text-slate-700">{{ number_format($recap->sum('total_ts'), 0, ',', '.') }}</td>
                            <td class="px-4 py-4 text-center text-slate-700">{{ number_format($recap->sum('total_tk'), 0, ',', '.') }}</td>
                            <td class="px-4 py-4 text-center text-slate-700">{{ number_format($recap->sum('total_tc'), 0, ',', '.') }}</td>
                            <td class="px-4 py-4 text-center text-slate-700">{{ number_format($recap->sum('total_kribab'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
