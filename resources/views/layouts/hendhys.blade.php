<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', "Hendhys Brownies") — Sistem Bisnis Terpadu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#faf7f5] text-gray-800 font-sans antialiased flex h-screen overflow-hidden selection:bg-amber-200 selection:text-amber-900">

    @php
        $isPusat = auth()->user()->branch->type === 'pusat';
    @endphp

    {{-- Sidebar --}}
    <aside class="w-64 bg-[#4a2e15] text-[#faeadd] flex flex-col transition-all duration-300 shadow-xl z-20">
        {{-- Logo Area --}}
        <div class="h-16 flex items-center px-6 border-b border-[#5e3b1c] bg-[#3a2310]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-[#d97706] text-white rounded-lg flex items-center justify-center font-bold text-xl shadow-inner">
                    🧁
                </div>
                <div>
                    <span class="font-bold text-lg tracking-wide text-white block leading-none mt-1">Hendhys</span>
                    <span class="text-[10px] text-amber-200 font-medium tracking-wider uppercase">{{ $isPusat ? 'Pusat Bakery' : 'Cabang ' . auth()->user()->branch->name }}</span>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto py-4 px-3 custom-scrollbar">
            <nav class="space-y-1">
                <a href="{{ route('hendhys.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.dashboard') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </a>

                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-semibold text-amber-500/70 uppercase tracking-wider">Kasir & Penjualan</p>
                </div>
                
                <a href="{{ route('hendhys.pos.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.pos.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    POS Kasir
                </a>
                
                <a href="{{ route('hendhys.pending.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.pending.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Transaksi Pending
                </a>

                @if($isPusat)
                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-semibold text-amber-500/70 uppercase tracking-wider">Pusat Manufaktur</p>
                </div>

                <a href="{{ route('hendhys.productions.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.productions.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Produksi Bakery
                </a>
                
                <a href="{{ route('hendhys.transfer-to-branch.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.transfer-to-branch.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Distribusi ke Cabang
                </a>
                @endif

                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-semibold text-amber-500/70 uppercase tracking-wider">Inventory & Logistik</p>
                </div>

                <a href="{{ route('hendhys.stock.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.stock.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Stok Tersedia
                </a>

                @if($isPusat)
                <a href="{{ route('hendhys.transfer-requests.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.transfer-requests.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Request Bahan Baku
                </a>
                
                <a href="{{ route('hendhys.branch-requests.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.branch-requests.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Daftar Request Cabang
                </a>
                @else
                <a href="{{ route('hendhys.branch-requests.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.branch-requests.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Request Stok ke Pusat
                </a>
                <a href="{{ route('hendhys.transfer-to-branch.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.transfer-to-branch.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Penerimaan Barang
                </a>
                @endif
                
                <a href="{{ route('hendhys.returns.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hendhys.returns.*') ? 'bg-[#5e3b1c] text-white font-medium' : 'text-[#d7c4b3] hover:bg-[#5e3b1c]/60' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                    Return Barang Cacat
                </a>
            </nav>
        </div>

        {{-- User Menu --}}
        <div class="p-4 border-t border-[#5e3b1c] bg-[#3a2310]" x-data="{ open: false }">
            <div class="relative">
                <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 w-full hover:bg-[#4a2e15] p-2 rounded-lg transition-colors">
                    <div class="w-9 h-9 rounded-full bg-[#d97706] text-white flex items-center justify-center font-bold shadow-sm">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="text-left flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-amber-200 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                
                {{-- Dropdown --}}
                <div x-show="open" x-transition.opacity
                     class="absolute bottom-full left-0 w-full mb-2 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 flex flex-col h-screen overflow-hidden relative">
        {{-- Topbar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shadow-sm z-10 print:hidden">
            <h1 class="text-xl font-bold text-gray-800">@yield('page-title', "Dashboard")</h1>
            
            <div class="flex items-center gap-4">
                {{-- Notifications --}}
                <button class="relative p-2 text-gray-400 hover:text-[#d97706] transition-colors rounded-full hover:bg-amber-50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full"></span>
                </button>
            </div>
        </header>

        {{-- Page Content --}}
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
            @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 shadow-sm" role="alert">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 shadow-sm" role="alert">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            @endif

            @yield('content')
        </div>
    </main>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d6d3d1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8a29e; }
        aside.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
        aside.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
</body>
</html>
