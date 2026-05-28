@extends('layouts.hendhys')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop space-y-md overflow-y-auto custom-scrollbar h-full">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-md">
        @php
            $menus = [
                [
                    'title' => 'Laci Kasir',
                    'desc' => 'Laporan transaksi kasir yang sedang login hari ini.',
                    'route' => 'hendhys.reports.laci',
                    'icon' => 'account_balance_wallet',
                    'color' => 'bg-blue-600'
                ],
                [
                    'title' => 'Laporan Perpelanggan Detail',
                    'desc' => 'Detail rincian transaksi penjualan per pelanggan.',
                    'route' => 'hendhys.reports.harian',
                    'icon' => 'assignment_ind',
                    'color' => 'bg-amber-600'
                ],
                [
                    'title' => 'Laporan Mingguan',
                    'desc' => 'Rekapitulasi penjualan berdasarkan rentang minggu.',
                    'route' => 'hendhys.reports.mingguan',
                    'icon' => 'view_week',
                    'color' => 'bg-green-600'
                ],
                [
                    'title' => 'Laporan Bulanan',
                    'desc' => 'Rekapitulasi penjualan bulanan sepanjang tahun.',
                    'route' => 'hendhys.reports.bulanan',
                    'icon' => 'calendar_month',
                    'color' => 'bg-purple-600'
                ],
                [
                    'title' => 'Laporan Pelanggan',
                    'desc' => 'Data statistik transaksi berdasarkan nama pelanggan.',
                    'route' => 'hendhys.reports.pelanggan',
                    'icon' => 'groups',
                    'color' => 'bg-rose-600'
                ],
            ];
        @endphp

        @foreach($menus as $menu)
        <a href="{{ route($menu['route']) }}" class="group bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant p-md hover:shadow-md hover:border-primary-container transition-all duration-300">
            <div class="flex items-start gap-md">
                <div class="{{ $menu['color'] }} text-white p-sm rounded-lg shadow-sm group-hover:scale-110 transition-transform duration-300 flex items-center justify-center">
                    <span class="material-symbols-outlined text-headline-md block">{{ $menu['icon'] }}</span>
                </div>
                <div class="flex-1">
                    <h3 class="text-title-lg font-bold text-on-surface group-hover:text-primary transition-colors leading-tight">{{ $menu['title'] }}</h3>
                    <p class="text-label-sm text-on-surface-variant mt-[4px] leading-relaxed">{{ $menu['desc'] }}</p>
                </div>
                <div class="text-outline-variant group-hover:text-primary-container transition-colors self-center">
                    <span class="material-symbols-outlined">chevron_right</span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection
