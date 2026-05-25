<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', "Jihan's Food") — Sistem Bisnis Terpadu</title>
    
    <!-- Fonts & Icons 100% vitales -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Backup Tailwind CDN in case local Vite compile isn't picking up Jihan's views -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "primary-fixed": "#ffdbc9",
                    "surface-container-low": "#f5f3f3",
                    "surface-container-highest": "#e4e2e2",
                    "surface-container": "#efeded",
                    "on-secondary-container": "#6e5c00",
                    "secondary": "#705d00",
                    "primary-fixed-dim": "#ffb68c",
                    "surface-container-high": "#eae8e7",
                    "primary": "#6c2f00",
                    "on-surface-variant": "#54433a",
                    "inverse-on-surface": "#f2f0f0",
                    "error-container": "#ffdad6",
                    "on-secondary-fixed-variant": "#544600",
                    "secondary-fixed": "#ffe16d",
                    "secondary-fixed-dim": "#e9c400",
                    "inverse-surface": "#303030",
                    "outline": "#877369",
                    "on-primary-fixed": "#321200",
                    "on-error-container": "#93000a",
                    "surface": "#fbf9f8",
                    "tertiary-container": "#5a5a38",
                    "on-background": "#1b1c1c",
                    "outline-variant": "#dac2b6",
                    "surface-container-lowest": "#ffffff",
                    "on-tertiary-container": "#d3d1a7",
                    "background": "#fbf9f8",
                    "surface-bright": "#fbf9f8",
                    "on-primary-fixed-variant": "#753401",
                    "inverse-primary": "#ffb68c",
                    "tertiary-fixed": "#e6e5b9",
                    "on-tertiary-fixed-variant": "#484828",
                    "primary-container": "#8b4513",
                    "tertiary-fixed-dim": "#cac99f",
                    "on-secondary-fixed": "#221b00",
                    "on-primary": "#ffffff",
                    "on-surface": "#1b1c1c",
                    "tertiary": "#424223",
                    "surface-dim": "#dbd9d9",
                    "error": "#ba1a1a",
                    "on-tertiary": "#ffffff",
                    "on-tertiary-fixed": "#1d1d03",
                    "surface-variant": "#e4e2e2",
                    "on-error": "#ffffff",
                    "secondary-container": "#fcd400",
                    "on-secondary": "#ffffff",
                    "on-primary-container": "#ffc29f",
                    "surface-tint": "#934b19"
                },
                "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                "spacing": { "xl": "64px", "lg": "40px", "xs": "4px", "sm": "12px", "md": "24px", "margin-mobile": "16px", "gutter": "16px", "base": "8px", "margin-desktop": "32px" },
                "fontFamily": {
                    "headline-lg": ["Montserrat"], "label-lg": ["Inter"], "display-lg": ["Montserrat"], "headline-md": ["Montserrat"], 
                    "body-lg": ["Inter"], "label-sm": ["Inter"], "body-md": ["Inter"], "title-lg": ["Inter"], "headline-lg-mobile": ["Montserrat"]
                },
            },
        },
    }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-family: 'Material Symbols Outlined'; }
        .material-symbols-outlined.fill { font-variation-settings: 'FILL' 1; }
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        aside.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); }
        aside.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 h-screen font-sans antialiased overflow-hidden selection:bg-orange-200 selection:text-orange-900" 
      x-data="{ sidebarOpen: window.innerWidth >= 1024, isMobile: window.innerWidth < 1024 }" 
      @resize.window="isMobile = window.innerWidth < 1024; if(!isMobile) sidebarOpen = true;">

    <div class="flex h-full w-full relative">

        {{-- Mobile Overlay --}}
        <div x-show="sidebarOpen && isMobile" x-cloak 
             class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden transition-opacity" 
             @click="sidebarOpen = false"></div>

        {{-- SIDEBAR --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-orange-700 text-orange-50 flex flex-col shadow-2xl lg:static lg:shrink-0 transition-all duration-300 ease-in-out border-r border-orange-800"
               :class="sidebarOpen ? 'translate-x-0 lg:ml-0' : '-translate-x-full lg:-ml-64'">

            {{-- Logo Area --}}
            <div class="flex items-center gap-3 px-6 py-5 border-b border-orange-600/50 bg-orange-800/30 shrink-0">
                <img src="{{ asset('logo/jihans-logo.png') }}" alt="Jihan's Logo" class="h-10 w-auto object-contain drop-shadow-md">
                <span class="font-bold text-[18px] tracking-wide text-white">Jihan's Food</span>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar">

                <a href="{{ route('jihans.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('jihans.dashboard') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="text-sm">Dashboard</span>
                </a>

                <div class="pt-5 pb-2">
                    <p class="px-3 text-[10px] font-bold text-orange-300 uppercase tracking-widest">Kasir & Penjualan</p>
                </div>
                
                @foreach([
                    ['route' => 'jihans.pos.index',          'label' => 'POS Kasir',         'icon' => 'pos'],
                    ['route' => 'jihans.pending.index',      'label' => 'Transaksi Pending', 'icon' => 'time'],
                    ['route' => 'jihans.transactions.index', 'label' => 'Riwayat Transaksi', 'icon' => 'history'],
                ] as $item)
                <a href="{{ route($item['route']) }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs(str_replace('.index', '', $item['route']).'*') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
                    @if($item['icon'] === 'pos')
                        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    @elseif($item['icon'] === 'time')
                        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif($item['icon'] === 'history')
                        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    @endif
                    <span class="text-sm">{{ $item['label'] }}</span>
                </a>
                @endforeach

                <a href="{{ route('jihans.reports.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('jihans.reports.*') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m11 2a4 4 0 014 4v2m-3-3l3 3m0 0l-3 3M3 7h18M3 12h18M3 17h18"/></svg>
                    <span class="text-sm">Laporan</span>
                </a>

                <div class="pt-5 pb-2">
                    <p class="px-3 text-[10px] font-bold text-orange-300 uppercase tracking-widest">Manufaktur</p>
                </div>

                <a href="{{ route('jihans.tortilla.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('jihans.tortilla.*') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="text-sm">Produksi Tortilla</span>
                </a>

                <div class="pt-5 pb-2">
                    <p class="px-3 text-[10px] font-bold text-orange-300 uppercase tracking-widest">Inventory Jihan's</p>
                </div>

                <a href="{{ route('jihans.stock.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('jihans.stock.*') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="text-sm">Stok Tersedia</span>
                </a>

                <a href="{{ route('jihans.transfer-requests.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('jihans.transfer-requests.*') ? 'bg-orange-800 shadow-md text-white font-medium' : 'text-orange-100 hover:bg-orange-600/50 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span class="text-sm">Request ke Gudang</span>
                </a>

                {{-- Master Data Dropdown --}}
                <div class="pt-5 pb-2">
                    <p class="px-3 text-[10px] font-bold text-orange-200/90 uppercase tracking-widest">Master Data</p>
                </div>
                
                <div x-data="{ open: {{ request()->routeIs('jihans.master.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-orange-100 hover:bg-orange-600/50 transition-all duration-200 focus:outline-none">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span class="font-medium text-sm">Master Data</span>
                        </div>
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-300" :class="open ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    
                    <div x-show="open" x-collapse x-cloak class="pt-1 pb-2">
                        @foreach([
                            ['route' => 'jihans.master.products.index',          'label' => 'Daftar Produk'],
                            ['route' => 'jihans.master.categories.index',        'label' => 'Kategori'],
                            ['route' => 'jihans.master.units.index',             'label' => 'Satuan'],
                            ['route' => 'jihans.master.brands.index',            'label' => 'Brand'],
                            ['route' => 'jihans.master.customers.index',         'label' => 'Pelanggan'],
                            ['route' => 'jihans.master.karyawan.index',          'label' => 'Karyawan'],
                            ['route' => 'jihans.master.payment-methods.index',   'label' => 'Metode Bayar'],
                            ['route' => 'jihans.master.production-rates.edit',   'label' => 'Tarif Produksi'],
                        ] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 pl-10 pr-3 py-2 rounded-lg text-[13px] transition-all duration-200 {{ request()->routeIs($item['route']) ? 'text-white font-semibold bg-orange-800/80 relative before:absolute before:left-4 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-orange-300 before:rounded-full' : 'text-orange-200 hover:text-white hover:bg-orange-800/40' }}">
                            {{ $item['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>

            </nav>

            {{-- User info at bottom --}}
            <div class="p-4 border-t border-orange-600/50 bg-orange-800/20 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-orange-200 text-orange-800 flex items-center justify-center font-bold text-sm shadow-inner shrink-0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-orange-200 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-gray-50 relative z-10 transition-all duration-300">

            {{-- Top bar --}}
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 shrink-0 z-20 print:hidden">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-orange-600 hover:bg-orange-50 p-2 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-orange-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                    </button>
                    <h1 class="text-lg font-bold text-gray-800 tracking-tight truncate max-w-[200px] sm:max-w-none">@yield('page-title', "Dashboard Jihan's Food")</h1>
                </div>
                
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 px-4 py-2 rounded-xl transition-all flex items-center gap-2">
                            <span class="hidden sm:inline">Logout</span>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </header>

            {{-- Flash messages (Floating) --}}
            <div class="absolute top-20 right-6 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
                @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-x-4"
                     class="flex items-center gap-3 bg-white border-l-4 border-green-500 shadow-xl rounded-lg p-4 pointer-events-auto">
                    <div class="bg-green-100 rounded-full p-1.5 shrink-0"><svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div>
                    <p class="text-sm font-medium text-gray-700">{{ session('success') }}</p>
                </div>
                @endif
                @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-x-4"
                     class="flex items-center gap-3 bg-white border-l-4 border-red-500 shadow-xl rounded-lg p-4 pointer-events-auto">
                    <div class="bg-red-100 rounded-full p-1.5 shrink-0"><svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></div>
                    <p class="text-sm font-medium text-gray-700">{{ session('error') }}</p>
                </div>
                @endif
            </div>

            {{-- Main Scrollable Area --}}
            <div class="flex-1 overflow-auto custom-scrollbar p-6">
                @yield('content')
            </div>
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function fetchNotificationCounts() {
                axios.get('{{ route('api.notifications.counts') }}')
                    .then(response => {
                        // Jihans currently doesn't have a menu badge for incoming requests
                        // but this polling keeps the session alive.
                    })
                    .catch(error => console.error('Error fetching notifications:', error));
            }

            if (Notification.permission !== "granted") {
                Notification.requestPermission();
            }

            setInterval(fetchNotificationCounts, 30000);

            if (typeof window.Echo !== 'undefined') {
                window.Echo.private('user.{{ auth()->id() }}.notifications')
                    .listen('TransferRequestStatusChanged', (e) => {
                        if (Notification.permission === "granted") {
                            new Notification("Update Permintaan Stok", {
                                body: e.message,
                                icon: "/logo/jihans-logo.png"
                            });
                        }
                    });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
