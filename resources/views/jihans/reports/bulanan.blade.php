@extends('layouts.jihans')

@section('title', 'Laporan Penjualan Bulanan')
@section('page-title', 'Rekap Penjualan Bulanan')

@section('content')
<div class="space-y-6">
    {{-- Filter Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <form action="{{ route('jihans.reports.bulanan') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="space-y-1">
                <label for="date_from" class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Dari Tanggal</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                       class="block w-full rounded-xl border-gray-200 focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-all duration-200">
            </div>
            <div class="space-y-1">
                <label for="date_to" class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Sampai Tanggal</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                       class="block w-full rounded-xl border-gray-200 focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-all duration-200">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-all duration-200 shadow-sm">
                    Filter
                </button>
                <a href="{{ route('jihans.reports.pdf', ['type' => 'bulanan'] + request()->all()) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-200 shadow-sm">
                    <span class="material-symbols-outlined text-sm mr-1">picture_as_pdf</span>
                    Cetak PDF
                </a>
                <a href="{{ route('jihans.reports.bulanan') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-xl font-semibold text-xs text-gray-600 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-all duration-200">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-orange-50 border-b border-orange-100">
                        <th class="px-6 py-4 text-xs font-bold text-orange-800 uppercase tracking-wider">Bulan</th>
                        <th class="px-6 py-4 text-xs font-bold text-orange-800 uppercase tracking-wider text-center">Jml Transaksi</th>
                        <th class="px-6 py-4 text-xs font-bold text-orange-800 uppercase tracking-wider text-right">Total Transaksi</th>
                        <th class="px-6 py-4 text-xs font-bold text-orange-800 uppercase tracking-wider text-right">Tunai</th>
                        <th class="px-6 py-4 text-xs font-bold text-orange-800 uppercase tracking-wider text-right">Kredit</th>
                        <th class="px-6 py-4 text-xs font-bold text-orange-800 uppercase tracking-wider text-right">Kartu Debit</th>
                        <th class="px-6 py-4 text-xs font-bold text-orange-800 uppercase tracking-wider text-right">Kartu Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                            {{ $row->label_bulan }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                            {{ number_format($row->jumlah_transaksi) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                            Rp {{ number_format($row->total_transaksi) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right">
                            {{ number_format($row->tunai) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right">
                            {{ number_format($row->kredit) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 text-right">
                            {{ number_format($row->kartu_debit) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-600 text-right">
                            {{ number_format($row->kartu_kredit) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">
                            Belum ada data transaksi untuk periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($rows->count() > 0)
                <tfoot class="bg-gray-50 font-bold border-t-2 border-gray-200">
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-800 uppercase">Total Halaman Ini</td>
                        <td class="px-6 py-4 text-sm text-gray-800 text-center">{{ number_format($rows->sum('jumlah_transaksi')) }}</td>
                        <td class="px-6 py-4 text-sm text-orange-700 text-right">Rp {{ number_format($rows->sum('total_transaksi')) }}</td>
                        <td class="px-6 py-4 text-sm text-green-700 text-right">{{ number_format($rows->sum('tunai')) }}</td>
                        <td class="px-6 py-4 text-sm text-red-700 text-right">{{ number_format($rows->sum('kredit')) }}</td>
                        <td class="px-6 py-4 text-sm text-blue-700 text-right">{{ number_format($rows->sum('kartu_debit')) }}</td>
                        <td class="px-6 py-4 text-sm text-purple-700 text-right">{{ number_format($rows->sum('kartu_kredit')) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($rows->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $rows->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
