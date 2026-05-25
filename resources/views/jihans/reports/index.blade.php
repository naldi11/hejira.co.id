@extends('layouts.jihans')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @php
        $menus = [
            [
                'title' => 'Laci Kasir',
                'desc' => 'Laporan transaksi kasir yang sedang login hari ini.',
                'route' => 'jihans.reports.laci',
                'icon' => 'account_balance_wallet',
                'color' => 'bg-blue-500'
            ],
            [
                'title' => 'Laporan Harian',
                'desc' => 'Rekapitulasi penjualan harian dari semua kasir.',
                'route' => 'jihans.reports.harian',
                'icon' => 'calendar_today',
                'color' => 'bg-orange-500'
            ],
            [
                'title' => 'Laporan Mingguan',
                'desc' => 'Rekapitulasi penjualan berdasarkan rentang minggu.',
                'route' => 'jihans.reports.mingguan',
                'icon' => 'view_week',
                'color' => 'bg-green-500'
            ],
            [
                'title' => 'Laporan Bulanan',
                'desc' => 'Rekapitulasi penjualan bulanan sepanjang tahun.',
                'route' => 'jihans.reports.bulanan',
                'icon' => 'calendar_month',
                'color' => 'bg-purple-500'
            ],
            [
                'title' => 'Laporan Pelanggan',
                'desc' => 'Data statistik transaksi berdasarkan nama pelanggan.',
                'route' => 'jihans.reports.pelanggan',
                'icon' => 'groups',
                'color' => 'bg-red-500'
            ],
        ];
    @endphp

    @foreach($menus as $menu)
    <a href="{{ route($menu['route']) }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md hover:border-orange-200 transition-all duration-300">
        <div class="flex items-start gap-4">
            <div class="{{ $menu['color'] }} text-white p-3 rounded-xl shadow-sm group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-2xl block">{{ $menu['icon'] }}</span>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-orange-700 transition-colors">{{ $menu['title'] }}</h3>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">{{ $menu['desc'] }}</p>
            </div>
            <div class="text-gray-300 group-hover:text-orange-400 transition-colors self-center">
                <span class="material-symbols-outlined">chevron_right</span>
            </div>
        </div>
    </a>
    @endforeach
</div>
@endsection
