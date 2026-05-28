@extends('layouts.jihans')
@section('title', 'Detail Produksi — ' . $tortilla->session_number)
@section('page-title', 'Detail Sesi Produksi')

@section('content')
<div class="space-y-6">

    {{-- Back + Print --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('jihans.tortilla.index') }}"
           class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Kembali ke Daftar
        </a>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-900 transition-all print:hidden">
            <span class="material-symbols-outlined text-[18px]">print</span>
            Cetak
        </button>
    </div>

    {{-- Header Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Nomor Sesi</p>
                <h2 class="text-2xl font-black text-slate-900 font-mono">{{ $tortilla->session_number }}</h2>
                @if($tortilla->notes)
                <p class="text-sm text-slate-500 italic mt-1">{{ $tortilla->notes }}</p>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-6 sm:text-right">
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal</p>
                    <p class="text-base font-bold text-slate-700">{{ \Carbon\Carbon::parse($tortilla->date)->format('d F Y') }}</p>
                </div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Diinput Oleh</p>
                    <p class="text-base font-bold text-slate-700">{{ $tortilla->creator->name ?? 'Sistem' }}</p>
                </div>
            </div>
        </div>

        {{-- Summary badges --}}
        <div class="mt-5 pt-5 border-t border-slate-100 flex flex-wrap gap-3">
            <div class="inline-flex items-center gap-1.5 bg-orange-50 border border-orange-100 text-orange-700 px-3 py-1.5 rounded-xl text-xs font-bold">
                <span class="material-symbols-outlined text-[14px]">group</span>
                {{ $tortilla->details->count() }} Karyawan
            </div>
            <div class="inline-flex items-center gap-1.5 bg-blue-50 border border-blue-100 text-blue-700 px-3 py-1.5 rounded-xl text-xs font-bold">
                <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                Total Produksi: {{ $tortilla->details->sum('tb_qty') + $tortilla->details->sum('ts_qty') + $tortilla->details->sum('tk_qty') + $tortilla->details->sum('tc_qty') + $tortilla->details->sum('kribab_qty') }} pack
            </div>
        </div>
    </div>

    {{-- Detail Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-black text-slate-700 text-sm uppercase tracking-wider">Rincian Produksi per Karyawan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-black tracking-wider">
                        <th class="px-6 py-3">Karyawan</th>
                        <th class="px-4 py-3 text-center">TB</th>
                        <th class="px-4 py-3 text-center">TS</th>
                        <th class="px-4 py-3 text-center">TK</th>
                        <th class="px-4 py-3 text-center">TC</th>
                        <th class="px-4 py-3 text-center">Kribab</th>
                        <th class="px-6 py-3 text-right">Total Pack</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($tortilla->details as $detail)
                    <tr class="hover:bg-orange-50/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center text-xs font-black shrink-0">
                                    {{ substr($detail->karyawan->name, 0, 1) }}
                                </div>
                                <span class="font-bold text-slate-800">{{ $detail->karyawan->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center font-black text-slate-800 text-base">{{ $detail->tb_qty }}</td>
                        <td class="px-4 py-4 text-center font-black text-slate-800 text-base">{{ $detail->ts_qty }}</td>
                        <td class="px-4 py-4 text-center font-black text-slate-800 text-base">{{ $detail->tk_qty }}</td>
                        <td class="px-4 py-4 text-center font-black text-slate-800 text-base">{{ $detail->tc_qty }}</td>
                        <td class="px-4 py-4 text-center font-black text-slate-800 text-base">{{ $detail->kribab_qty }}</td>
                        <td class="px-6 py-4 text-right font-black text-orange-600 text-base">
                            {{ $detail->tb_qty + $detail->ts_qty + $detail->tk_qty + $detail->tc_qty + $detail->kribab_qty }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-orange-50 border-t-2 border-orange-200 font-black">
                        <td class="px-6 py-4 text-slate-700">TOTAL</td>
                        <td class="px-4 py-4 text-center text-slate-700">{{ $tortilla->details->sum('tb_qty') }}</td>
                        <td class="px-4 py-4 text-center text-slate-700">{{ $tortilla->details->sum('ts_qty') }}</td>
                        <td class="px-4 py-4 text-center text-slate-700">{{ $tortilla->details->sum('tk_qty') }}</td>
                        <td class="px-4 py-4 text-center text-slate-700">{{ $tortilla->details->sum('tc_qty') }}</td>
                        <td class="px-4 py-4 text-center text-slate-700">{{ $tortilla->details->sum('kribab_qty') }}</td>
                        <td class="px-6 py-4 text-right text-orange-700 text-xl">
                            {{ $tortilla->details->sum('tb_qty') + $tortilla->details->sum('ts_qty') + $tortilla->details->sum('tk_qty') + $tortilla->details->sum('tc_qty') + $tortilla->details->sum('kribab_qty') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
