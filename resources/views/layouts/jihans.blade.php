<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', "Jihan's Food") — Sistem Bisnis Terpadu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased flex h-screen overflow-hidden selection:bg-orange-200 selection:text-orange-900">

    {{-- Sidebar --}}
    <aside class="w-64 bg-orange-700 text-orange-50 flex flex-col transition-all duration-300 shadow-xl z-20">
        {{-- Logo Area --}}
        <div class="h-16 flex items-center px-6 border-b border-orange-600/50 bg-orange-800/30">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white text-orange-700 rounded-lg flex items-center justify-center font-bold text-xl shadow-inner">
                    🫓
                </div>
                <span class="font-bold text-lg tracking-wide text-white">Jihan's Food</span>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto py-4 px-3 custom-scrollbar">
            <nav class="space-y-1">
                <a href="{{ route('jihans.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('jihans.dashboard') ? 'bg-orange-800 text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard
                </a>

                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-semibold text-orange-300 uppercase tracking-wider">Kasir & Penjualan</p>
                </div>
                
                <a href="{{ route('jihans.pos.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('jihans.pos.*') ? 'bg-orange-800 text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    POS Kasir
                </a>
                
                <a href="{{ route('jihans.pending.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('jihans.pending.*') ? 'bg-orange-800 text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Transaksi Pending
                </a>

                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-semibold text-orange-300 uppercase tracking-wider">Manufaktur</p>
                </div>

                <a href="{{ route('jihans.productions.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('jihans.productions.*') ? 'bg-orange-800 text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Input Produksi Tortilla
                </a>

                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-semibold text-orange-300 uppercase tracking-wider">Inventory Jihan's</p>
                </div>

                <a href="{{ route('jihans.stock.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('jihans.stock.*') ? 'bg-orange-800 text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Stok Tersedia
                </a>

                <a href="{{ route('jihans.transfer-requests.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('jihans.transfer-requests.*') ? 'bg-orange-800 text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Request ke Gudang
                </a>
            </nav>
        </div>

        {{-- User Menu --}}
        <div class="p-4 border-t border-orange-600/50 bg-orange-800/20" x-data="{ open: false }">
            <div class="relative">
                <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 w-full hover:bg-orange-700/50 p-2 rounded-lg transition-colors">
                    <div class="w-9 h-9 rounded-full bg-orange-200 text-orange-800 flex items-center justify-center font-bold shadow-sm">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="text-left flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-orange-200 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                    <svg class="w-4 h-4 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-gray-50/50">
        {{-- Topbar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shadow-sm z-10 print:hidden">
            <h1 class="text-xl font-bold text-gray-800">@yield('page-title', "Dashboard Jihan's Food")</h1>
            
            <div class="flex items-center gap-4">
                {{-- Notifications (Placeholder) --}}
                <button class="relative p-2 text-gray-400 hover:text-orange-600 transition-colors rounded-full hover:bg-orange-50">
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
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        aside.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); }
        aside.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
    </style>
</body>
</html>
