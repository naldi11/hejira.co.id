@extends('layouts.gudang')

@section('title', 'Dashboard')
@section('page-title', 'Overview Gudang')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Selamat Datang, {{ auth()->user()->name }}!</h2>
            <p class="text-slate-500 text-sm mt-1">HEJIRA — Sistem Manajemen Inventori Terpadu</p>
        </div>
        <div class="flex items-center gap-3 bg-slate-50 rounded-lg p-3 border border-slate-200">
            <div class="text-right">
                <p class="text-xs font-semibold text-slate-500 uppercase">Server Time</p>
                <p class="text-sm font-medium text-slate-900">{{ now()->format('H:i') }} <span class="text-slate-500 text-xs">WIB</span></p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                <span class="material-symbols-outlined text-[20px]">schedule</span>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Produk -->
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">inventory_2</span>
                </div>
                <div class="bg-slate-100 text-slate-600 text-xs font-medium px-2 py-1 rounded">Total Assets</div>
            </div>
            <div class="mt-4">
                <p class="text-slate-500 text-xs font-medium uppercase tracking-wide">Jenis Produk</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($totalProduk) }}</h3>
            </div>
        </div>

        <!-- Purchase Order -->
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">shopping_cart_checkout</span>
                </div>
                <div class="bg-slate-100 text-slate-600 text-xs font-medium px-2 py-1 rounded">Pending PO</div>
            </div>
            <div class="mt-4">
                <p class="text-slate-500 text-xs font-medium uppercase tracking-wide">Pesanan Aktif</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($pendingPo) }}</h3>
            </div>
        </div>

        <!-- Transfer Pending -->
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">move_to_inbox</span>
                </div>
                <div class="bg-slate-100 text-slate-600 text-xs font-medium px-2 py-1 rounded">Needs Approval</div>
            </div>
            <div class="mt-4">
                <p class="text-slate-500 text-xs font-medium uppercase tracking-wide">Permintaan Stok</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($pendingRequest) }}</h3>
            </div>
        </div>

        <!-- Total Cabang -->
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">storefront</span>
                </div>
                <div class="bg-slate-100 text-slate-600 text-xs font-medium px-2 py-1 rounded">Connected</div>
            </div>
            <div class="mt-4">
                <p class="text-slate-500 text-xs font-medium uppercase tracking-wide">Jaringan Cabang</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($totalCabang) }}</h3>
            </div>
        </div>
    </div>

    {{-- Shortcut Section --}}
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-slate-900">Quick Actions</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Stok Opname', 'icon' => 'inventory', 'route' => 'gudang.stock.index', 'color' => 'indigo'],
                ['label' => 'Buat PO', 'icon' => 'add_shopping_cart', 'route' => 'gudang.po.create', 'color' => 'emerald'],
                ['label' => 'Terima Barang', 'icon' => 'input', 'route' => 'gudang.receiving.create', 'color' => 'blue'],
                ['label' => 'Kirim Barang', 'icon' => 'output', 'route' => 'gudang.transfer-out.index', 'color' => 'rose'],
            ] as $act)
            <a href="{{ route($act['route']) }}" class="flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                <div class="w-12 h-12 rounded-lg bg-slate-50 text-slate-600 flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-[24px]">{{ $act['icon'] }}</span>
                </div>
                <span class="text-sm font-medium text-slate-700 text-center">{{ $act['label'] }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection

