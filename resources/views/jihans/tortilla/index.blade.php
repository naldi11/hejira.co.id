@extends('layouts.jihans')
@section('title', 'Produksi Tortilla')
@section('page-title', 'Produksi Tortilla per Karyawan')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full bg-surface space-y-md">

        @if (session('success'))
            <div class="mb-md bg-tertiary-container text-on-tertiary-container p-sm rounded-lg shadow-sm border border-tertiary/20 flex items-center gap-sm">
                <span class="material-symbols-outlined text-tertiary">check_circle</span>
                <p class="text-sm font-medium">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Header & Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md">
            <div class="flex items-center gap-sm">
                <a href="{{ route('jihans.tortilla.recap') }}"
                    class="inline-flex items-center gap-xs px-md py-sm bg-secondary text-on-secondary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-secondary-fixed-dim transition-all">
                    <span class="material-symbols-outlined text-[18px]">payments</span>
                    Rekap Gaji Mingguan
                </a>
            </div>
            <div class="flex items-center gap-sm">
                <a href="{{ route('jihans.tortilla.create') }}"
                    class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Input Produksi Baru
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden p-md">
            <form method="GET" class="flex flex-wrap gap-sm">
                <div class="relative flex-1 min-w-[200px]">
                    <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Sesi..."
                        class="w-full pl-xl pr-sm py-sm bg-surface-container-low border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface placeholder-on-surface-variant rounded-t-lg transition-colors outline-none">
                </div>
                <div class="flex items-center gap-xs">
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="px-sm py-sm bg-surface-container-low border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface outline-none focus:border-primary">
                    <span class="text-on-surface-variant">s/d</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="px-sm py-sm bg-surface-container-low border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface outline-none focus:border-primary">
                </div>
                <button type="submit" class="px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'date_from', 'date_to']))
                    <a href="{{ route('jihans.tortilla.index') }}" class="px-md py-sm text-error font-label-lg text-label-lg flex items-center gap-xs hover:bg-error-container rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">TANGGAL</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">NO. SESI</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">JML KARYAWAN</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">CATATAN</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">OLEH</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @forelse($sessions as $session)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-md py-sm font-body-md text-body-md text-on-surface">
                                    {{ \Carbon\Carbon::parse($session->date)->format('d/m/Y') }}
                                </td>
                                <td class="px-md py-sm font-mono text-sm font-bold text-on-surface">
                                    {{ $session->session_number }}
                                </td>
                                <td class="px-md py-sm text-on-surface font-body-md">
                                    {{ $session->details_count }} orang
                                </td>
                                <td class="px-md py-sm text-on-surface-variant text-sm italic">
                                    {{ $session->notes ?? '-' }}
                                </td>
                                <td class="px-md py-sm text-on-surface-variant text-xs">
                                    {{ $session->creator->name ?? 'System' }}
                                </td>
                                <td class="px-md py-sm text-right">
                                    <a href="{{ route('jihans.tortilla.show', $session) }}"
                                        class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-primary rounded-lg font-label-sm text-label-sm hover:bg-primary-container transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-md py-xl text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[48px] opacity-20 block mb-sm">assignment</span>
                                    <p class="font-label-lg">Belum ada data produksi tortilla.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sessions->hasPages())
                <div class="px-md py-sm bg-surface-container-low border-t border-outline-variant">
                    {{ $sessions->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
