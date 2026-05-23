@extends('layouts.jihans')
@section('title', 'Rekap Gaji Karyawan')
@section('page-title', 'Rekap Gaji Mingguan (Tortilla)')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full bg-surface space-y-md">

        {{-- Filter Periode --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm p-md">
            <form method="GET" class="flex flex-wrap items-end gap-md">
                <div class="space-y-xs">
                    <label class="block font-label-sm text-label-sm text-on-surface-variant">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}"
                        class="px-sm py-sm bg-surface-container-low border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface outline-none focus:border-primary">
                </div>
                <div class="space-y-xs">
                    <label class="block font-label-sm text-label-sm text-on-surface-variant">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}"
                        class="px-sm py-sm bg-surface-container-low border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface outline-none focus:border-primary">
                </div>
                <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg hover:bg-on-primary-fixed-variant transition-all flex items-center gap-xs shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span>
                    Tampilkan Rekap
                </button>
                <a href="{{ route('jihans.tortilla.recap.export', ['date_from' => $dateFrom->format('Y-m-d'), 'date_to' => $dateTo->format('Y-m-d')]) }}" 
                    class="px-md py-sm bg-secondary text-on-secondary rounded-lg font-label-lg text-label-lg hover:bg-secondary-fixed-dim transition-all flex items-center gap-xs shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export Excel
                </a>
            </form>
        </div>

        {{-- Table Recap --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">
                    Periode: {{ $dateFrom->format('d M Y') }} — {{ $dateTo->format('d M Y') }}
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-xs uppercase text-on-surface-variant font-bold">
                            <th class="px-md py-sm">Nama Karyawan</th>
                            <th class="px-sm py-sm text-center">Hadir</th>
                            <th class="px-sm py-sm text-center">TB</th>
                            <th class="px-sm py-sm text-center">TS</th>
                            <th class="px-sm py-sm text-center">TK</th>
                            <th class="px-sm py-sm text-center">TC</th>
                            <th class="px-sm py-sm text-center">KRIBAB</th>
                            <th class="px-md py-sm text-right">Total Upah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @forelse($recap as $item)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-md py-sm font-bold text-on-surface">
                                    {{ $item->karyawan->name }}
                                </td>
                                <td class="px-sm py-sm text-center font-body-md text-secondary font-bold">
                                    {{ $item->hadir_count }}x
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ number_format($item->total_tb, 0, ',', '.') }}
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ number_format($item->total_ts, 0, ',', '.') }}
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ number_format($item->total_tk, 0, ',', '.') }}
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ number_format($item->total_tc, 0, ',', '.') }}
                                </td>
                                <td class="px-sm py-sm text-center font-body-md">
                                    {{ number_format($item->total_kribab, 0, ',', '.') }}
                                </td>
                                <td class="px-md py-sm text-right font-bold text-primary">
                                    {{ number_format($item->total_gaji, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-md py-xl text-center text-on-surface-variant italic">
                                    Tidak ada data produksi untuk periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($recap->isNotEmpty())
                        <tfoot>
                            <tr class="bg-surface-container-low font-bold text-on-surface">
                                <td class="px-md py-sm">GRAND TOTAL</td>
                                <td class="px-sm py-sm text-center"></td>
                                <td class="px-sm py-sm text-center">{{ number_format($recap->sum('total_tb'), 0, ',', '.') }}</td>
                                <td class="px-sm py-sm text-center">{{ number_format($recap->sum('total_ts'), 0, ',', '.') }}</td>
                                <td class="px-sm py-sm text-center">{{ number_format($recap->sum('total_tk'), 0, ',', '.') }}</td>
                                <td class="px-sm py-sm text-center">{{ number_format($recap->sum('total_tc'), 0, ',', '.') }}</td>
                                <td class="px-sm py-sm text-center">{{ number_format($recap->sum('total_kribab'), 0, ',', '.') }}</td>
                                <td class="px-md py-sm text-right text-primary text-xl">
                                    {{ number_format($recap->sum('total_gaji'), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
@endsection
