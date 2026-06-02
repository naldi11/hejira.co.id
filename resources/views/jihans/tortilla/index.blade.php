@extends('layouts.jihans')
@section('title', 'Produksi Tortilla')
@section('page-title', 'Produksi Tortilla')

@section('content')
<div class="space-y-6">

    @if(session('success'))
    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-2xl shadow-sm">
        <span class="material-symbols-outlined text-green-500 text-[20px]">check_circle</span>
        <p class="text-sm font-semibold">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-2xl shadow-sm">
        <span class="material-symbols-outlined text-red-500 text-[20px]">error</span>
        <p class="text-sm font-semibold">{{ session('error') }}</p>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Produksi Tortilla</h2>
            <p class="text-sm text-slate-500 mt-1">Riwayat sesi produksi per karyawan</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('jihans.tortilla.recap') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-sm font-bold hover:bg-amber-200 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[18px]">bar_chart</span>
                Rekap Produksi
            </a>
            <a href="{{ route('jihans.tortilla.prediksi.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-yellow-500 text-white rounded-xl text-sm font-bold hover:bg-yellow-600 transition-all shadow-lg shadow-yellow-500/20 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]">edit_note</span>
                Prediksi Baru
            </a>
            <a href="{{ route('jihans.tortilla.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-bold hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Input Aktual Baru
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            {{-- Search --}}
            <div class="relative flex-1 min-w-[200px]">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Sesi..."
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-400/10 transition-all">
            </div>

            {{-- Date Range --}}
            <div class="flex items-center gap-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 outline-none focus:border-orange-400 transition-all">
                <span class="text-slate-400 text-sm font-bold">—</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 outline-none focus:border-orange-400 transition-all">
            </div>

            <button type="submit"
                    class="px-5 py-2.5 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-900 transition-all">
                Filter
            </button>

            @if(request()->anyFilled(['search', 'date_from', 'date_to']))
            <a href="{{ route('jihans.tortilla.index') }}"
               class="flex items-center gap-1.5 px-4 py-2.5 text-red-600 bg-red-50 border border-red-100 rounded-xl text-sm font-bold hover:bg-red-100 transition-all">
                <span class="material-symbols-outlined text-[16px]">close</span>
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 font-black text-slate-500 text-xs uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 font-black text-slate-500 text-xs uppercase tracking-wider">No. Sesi</th>
                        <th class="px-6 py-4 font-black text-slate-500 text-xs uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 font-black text-slate-500 text-xs uppercase tracking-wider text-center">Jml Karyawan</th>
                        <th class="px-6 py-4 font-black text-slate-500 text-xs uppercase tracking-wider">Catatan</th>
                        <th class="px-6 py-4 font-black text-slate-500 text-xs uppercase tracking-wider">Dibuat Oleh</th>
                        <th class="px-6 py-4 font-black text-slate-500 text-xs uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sessions as $session)
                    <tr class="hover:bg-orange-50/40 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-slate-700 font-bold">{{ \Carbon\Carbon::parse($session->date)->format('d M Y') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-xs font-black bg-orange-50 text-orange-700 border border-orange-100 px-2.5 py-1 rounded-lg">{{ $session->session_number }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($session->type === 'prediksi')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700 border border-yellow-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 inline-block"></span>
                                    Prediksi
                                    @if($session->isOverridden())
                                        <span class="ml-1 text-yellow-500 font-normal">(Digantikan)</span>
                                    @endif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                    Aktual
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center whitespace-nowrap">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-black text-xs">
                                {{ $session->details_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-sm italic min-w-[150px]">
                            {{ $session->notes ?: '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center text-xs font-black shrink-0">
                                    {{ substr($session->creator->name ?? 'S', 0, 1) }}
                                </div>
                                <span class="text-slate-600 text-sm">{{ $session->creator->name ?? 'Sistem' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                @if($session->isPrediksi())
                                <a href="{{ route('jihans.tortilla.faktur', $session) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-xs font-bold hover:bg-yellow-100 transition-all">
                                    <span class="material-symbols-outlined text-[14px]">receipt</span>
                                    Faktur
                                </a>
                                @if(!$session->isOverridden())
                                <a href="{{ route('jihans.tortilla.create', ['date' => \Carbon\Carbon::parse($session->date)->format('Y-m-d')]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-600 text-white border border-orange-700 rounded-lg text-xs font-bold hover:bg-orange-700 shadow-sm transition-all">
                                    <span class="material-symbols-outlined text-[14px]">edit_square</span>
                                    Input Aktual
                                </a>
                                @endif
                                @endif
                                <a href="{{ route('jihans.tortilla.show', $session) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-xs font-bold hover:bg-orange-50 hover:text-orange-700 hover:border-orange-200 transition-all">
                                    <span class="material-symbols-outlined text-[14px]">visibility</span>
                                    Detail
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-20 text-center">
                            <span class="material-symbols-outlined text-[56px] text-slate-200 block mb-3">assignment</span>
                            <p class="text-slate-400 font-bold text-sm">Belum ada data produksi tortilla.</p>
                            <a href="{{ route('jihans.tortilla.create') }}"
                               class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-bold hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                Input Produksi Pertama
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
            {{ $sessions->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
