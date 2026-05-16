<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Gudang Tempua</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full" x-data="{ sidebarOpen: true }">

<div class="flex h-full">

    {{-- SIDEBAR --}}
    <aside class="flex flex-col w-64 bg-indigo-900 text-white shrink-0 min-h-screen"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64'"
           style="transition: transform .2s;">

        {{-- Logo --}}
        <div class="flex items-center gap-2 px-5 py-4 border-b border-indigo-700">
            <div class="w-8 h-8 bg-indigo-400 rounded-lg flex items-center justify-center text-xs font-bold">GT</div>
            <div>
                <p class="font-bold text-sm leading-tight">Gudang Tempua</p>
                <p class="text-indigo-300 text-xs">Manajemen Bisnis</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto text-sm">

            <a href="{{ route('gudang.dashboard') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs('gudang.dashboard') ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            {{-- Master Data --}}
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">Master Data</p>

            @foreach([
                ['route' => 'master.suppliers.index',   'label' => 'Supplier',     'icon' => 'truck'],
                ['route' => 'master.customers.index',   'label' => 'Customer',     'icon' => 'users'],
                ['route' => 'master.products.index',    'label' => 'Produk',       'icon' => 'cube'],
                ['route' => 'master.categories.index',  'label' => 'Kategori',     'icon' => 'tag'],
                ['route' => 'master.units.index',       'label' => 'Satuan',       'icon' => 'scale'],
                ['route' => 'master.brands.index',      'label' => 'Brand',        'icon' => 'bookmark'],
                ['route' => 'master.branches.index',    'label' => 'Cabang',       'icon' => 'office-building'],
            ] as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg {{ request()->routeIs($item['route']) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800' }}">
                @if($item['icon'] === 'truck')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l3 1m3-11h4l3 4v6m0 0h-1m-6 0H9"/></svg>
                @elseif($item['icon'] === 'users')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                @elseif($item['icon'] === 'cube')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                @elseif($item['icon'] === 'tag')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                @elseif($item['icon'] === 'scale')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                @elseif($item['icon'] === 'bookmark')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                @endif
                {{ $item['label'] }}
            </a>
            @endforeach

            {{-- Gudang Operasional --}}
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">Operasional</p>

            @foreach([
                ['route' => 'gudang.po.index',               'label' => 'Purchase Order'],
                ['route' => 'gudang.receiving.index',         'label' => 'Penerimaan Barang'],
                ['route' => 'gudang.stock.index',             'label' => 'Stok Gudang'],
                ['route' => 'gudang.transfer-requests.index', 'label' => 'Transfer Request'],
                ['route' => 'gudang.transfer-out.index',      'label' => 'Transfer Keluar'],
            ] as $item)
            <a href="#" {{-- href="{{ route($item['route']) }}" --}}
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-indigo-300 opacity-50 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $item['label'] }}
            </a>
            @endforeach

            {{-- User Management --}}
            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">Pengaturan</p>
            <a href="#" class="flex items-center gap-2 px-3 py-2 rounded-lg text-indigo-300 opacity-50 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Manajemen User
            </a>
        </nav>

        {{-- User info at bottom --}}
        <div class="px-4 py-3 border-t border-indigo-700 text-xs">
            <p class="text-white font-medium truncate">{{ auth()->user()->name }}</p>
            <p class="text-indigo-300">Admin Gudang</p>
        </div>
    </aside>

    {{-- MAIN --}}
    <div class="flex-1 flex flex-col min-h-screen overflow-auto">

        {{-- Top bar --}}
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-gray-800 font-semibold text-sm">@yield('page-title', 'Dashboard')</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-gray-500 hover:text-red-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </form>
        </header>

        {{-- Flash messages --}}
        <div class="px-6 pt-4">
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-4">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 px-6 pb-8">
            @yield('content')
        </main>
    </div>

</div>

</body>
</html>
