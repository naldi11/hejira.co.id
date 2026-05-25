@extends('layouts.gudang')

@section('title', 'Dashboard')
@section('page-title', 'Overview Gudang')

@section('content')
<div class="space-y-8">
    {{-- Header Section --}}
    <div class="relative overflow-hidden rounded-3xl bg-[#0f172a] p-8 shadow-2xl shadow-slate-900/20">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-2xl font-black text-white font-headline tracking-tight">Selamat Datang, {{ auth()->user()->name }}!</h2>
                <p class="text-slate-400 text-sm mt-1 font-medium tracking-wide italic">Sistem Manajemen Inventori Terpadu — Gudang Tempua</p>
            </div>
            <div class="flex items-center gap-3 bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10">
                <div class="text-right">
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Server Time</p>
                    <p class="text-sm font-bold text-white tabular-nums">{{ now()->format('H:i') }} <span class="text-slate-500 font-medium">WIB</span></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center border border-indigo-500/30">
                    <span class="material-symbols-outlined text-indigo-400 text-[22px]">schedule</span>
                </div>
            </div>
        </div>
        {{-- Abstract background decoration --}}
        <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/4 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/4 w-64 h-64 bg-violet-600/10 rounded-full blur-3xl"></div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Produk -->
        <div class="group bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:shadow-xl hover:shadow-indigo-500/5 transition-all duration-300">
            <div class="flex items-start justify-between">
                <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px] fill">inventory_2</span>
                </div>
                <div class="bg-indigo-50 text-indigo-700 text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-tighter">Total Assets</div>
            </div>
            <div class="mt-6">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Jenis Produk</p>
                <h3 class="text-3xl font-black text-slate-900 mt-1 tabular-nums">{{ number_format($totalProduk) }}</h3>
            </div>
        </div>

        <!-- Purchase Order -->
        <div class="group bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300">
            <div class="flex items-start justify-between">
                <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px] fill">shopping_cart_checkout</span>
                </div>
                <div class="bg-emerald-50 text-emerald-700 text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-tighter">Pending PO</div>
            </div>
            <div class="mt-6">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Pesanan Aktif</p>
                <h3 class="text-3xl font-black text-slate-900 mt-1 tabular-nums">{{ number_format($pendingPo) }}</h3>
            </div>
        </div>

        <!-- Transfer Pending -->
        <div class="group bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:shadow-xl hover:shadow-amber-500/5 transition-all duration-300">
            <div class="flex items-start justify-between">
                <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px] fill">move_to_inbox</span>
                </div>
                <div class="bg-amber-50 text-amber-700 text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-tighter">Needs Approval</div>
            </div>
            <div class="mt-6">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Permintaan Stok</p>
                <h3 class="text-3xl font-black text-slate-900 mt-1 tabular-nums">{{ number_format($pendingRequest) }}</h3>
            </div>
        </div>

        <!-- Total Cabang -->
        <div class="group bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:shadow-xl hover:shadow-rose-500/5 transition-all duration-300">
            <div class="flex items-start justify-between">
                <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px] fill">storefront</span>
                </div>
                <div class="bg-rose-50 text-rose-700 text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-tighter">Connected</div>
            </div>
            <div class="mt-6">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Jaringan Cabang</p>
                <h3 class="text-3xl font-black text-slate-900 mt-1 tabular-nums">{{ number_format($totalCabang) }}</h3>
            </div>
        </div>
    </div>

    {{-- Shortcut Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">Quick Actions</h3>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach([
                    ['label' => 'Stok Opname', 'icon' => 'inventory', 'route' => 'gudang.stock.index', 'color' => 'indigo'],
                    ['label' => 'Buat PO', 'icon' => 'add_shopping_cart', 'route' => 'gudang.po.create', 'color' => 'emerald'],
                    ['label' => 'Terima Barang', 'icon' => 'input', 'route' => 'gudang.receiving.create', 'color' => 'blue'],
                    ['label' => 'Kirim Barang', 'icon' => 'output', 'route' => 'gudang.transfer-out.index', 'color' => 'rose'],
                ] as $act)
                <a href="{{ route($act['route']) }}" class="flex flex-col items-center justify-center p-6 bg-white border border-slate-200 rounded-3xl hover:border-{{ $act['color'] }}-300 hover:bg-{{ $act['color'] }}-50/30 transition-all duration-300 group shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $act['color'] }}-50 text-{{ $act['color'] }}-600 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-[24px]">{{ $act['icon'] }}</span>
                    </div>
                    <span class="text-xs font-black text-slate-700 uppercase tracking-tight text-center">{{ $act['label'] }}</span>
                </a>
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">System Status</h3>
            <div class="bg-[#0f172a] rounded-3xl p-8 text-white relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex items-center gap-2 text-emerald-400 mb-4">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em]">Operational</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed font-medium">
                        Semua sistem sinkronisasi stok real-time antar entitas Jihans dan Hendhys berjalan normal.
                    </p>
                    <div class="mt-6 pt-6 border-t border-white/10 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Versi Sistem</span>
                        <span class="text-xs font-black text-indigo-400">v2.4.0-pro</span>
                    </div>
                </div>
                <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-white/5 text-[120px] rotate-12">verified_user</span>
            </div>
        </div>
    </div>
</div>
@endsection

