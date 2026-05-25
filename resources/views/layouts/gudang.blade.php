<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Gudang Tempua</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "primary": "#4f46e5",
                    "indigo": {
                        "50": "#eef2ff", "100": "#e0e7ff", "200": "#c7d2fe", "300": "#a5b4fc", "400": "#818cf8", "500": "#6366f1", "600": "#4f46e5", "700": "#4338ca", "800": "#3730a3", "900": "#312e81", "950": "#1e1b4b",
                    },
                    "slate": {
                        "50": "#f8fafc", "100": "#f1f5f9", "200": "#e2e8f0", "300": "#cbd5e1", "400": "#94a3b8", "500": "#64748b", "600": "#475569", "700": "#334155", "800": "#1e293b", "900": "#0f172a", "950": "#020617",
                    }
                },
                "borderRadius": { "2xl": "1rem", "3xl": "1.5rem" },
                "fontFamily": {
                    "sans": ["Inter", "ui-sans-serif", "system-ui"],
                    "headline": ["Montserrat", "sans-serif"],
                },
            },
        },
    }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-outlined.fill { font-variation-settings: 'FILL' 1; }
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
        
        aside .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
        aside .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

        .nav-link-active { @apply bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 font-semibold; }
        .nav-link-inactive { @apply text-slate-400 hover:bg-slate-800/50 hover:text-slate-100; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 h-screen font-sans antialiased overflow-hidden" 
      x-data="{ sidebarOpen: window.innerWidth >= 1024, isMobile: window.innerWidth < 1024 }" 
      @resize.window="isMobile = window.innerWidth < 1024; if(!isMobile) sidebarOpen = true;">

    <div class="flex h-full w-full relative">

        {{-- Mobile Overlay --}}
        <div x-show="sidebarOpen && isMobile" x-cloak 
             class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden transition-opacity duration-300" 
             @click="sidebarOpen = false"></div>

        {{-- SIDEBAR --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0f172a] text-white flex flex-col shadow-2xl lg:static lg:shrink-0 transition-all duration-300 ease-in-out border-r border-slate-800"
               :class="sidebarOpen ? 'translate-x-0 lg:ml-0' : '-translate-x-full lg:-ml-72'">

            {{-- Logo --}}
            <div class="flex items-center gap-4 px-8 py-8 shrink-0">
                <div class="w-12 h-12 bg-gradient-to-tr from-indigo-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-500/20 rotate-3 transition-transform hover:rotate-0 duration-300">
                    <span class="text-xl font-black text-white tracking-tighter font-headline">GT</span>
                </div>
                <div>
                    <h1 class="font-black text-xl leading-none tracking-tight text-white font-headline">Gudang<span class="text-indigo-400">Tempua</span></h1>
                    <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-1">Management System</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto custom-scrollbar">

                <a href="{{ route('gudang.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('gudang.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                    <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('gudang.dashboard') ? 'fill' : '' }}">dashboard</span>
                    <span class="text-sm tracking-wide">Dashboard</span>
                </a>

                {{-- Master Data Dropdown --}}
                <div x-data="{ open: {{ request()->routeIs('master.*') ? 'true' : 'false' }} }" class="space-y-1 pt-4">
                    <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-[0.2em] mb-2">Core Data</p>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-800/50 hover:text-slate-100 transition-all duration-300 focus:outline-none">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[22px]">database</span>
                            <span class="font-medium text-sm tracking-wide">Master Data</span>
                        </div>
                        <span class="material-symbols-outlined text-[20px] transition-transform duration-500" :class="open ? 'rotate-180 text-indigo-400' : ''">expand_more</span>
                    </button>
                    
                    <div x-show="open" x-collapse x-cloak class="space-y-1 mt-1">
                        @foreach([
                            ['route' => 'master.suppliers.index',   'label' => 'Supplier', 'icon' => 'local_shipping'],
                            ['route' => 'master.customers.index',   'label' => 'Customer', 'icon' => 'groups'],
                            ['route' => 'master.products.index',    'label' => 'Produk', 'icon' => 'inventory_2'],
                            ['route' => 'master.categories.index',  'label' => 'Kategori', 'icon' => 'category'],
                            ['route' => 'master.units.index',       'label' => 'Satuan', 'icon' => 'straighten'],
                            ['route' => 'master.brands.index',      'label' => 'Brand', 'icon' => 'verified'],
                            ['route' => 'master.branches.index',    'label' => 'Cabang', 'icon' => 'store'],
                        ] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-xl text-[13px] transition-all duration-300 {{ request()->routeIs($item['route']) ? 'text-indigo-400 font-bold bg-indigo-500/10' : 'text-slate-500 hover:text-slate-200 hover:bg-slate-800/30' }}">
                            <span class="material-symbols-outlined text-[18px]">{{ $item['icon'] }}</span>
                            {{ $item['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Gudang Operasional --}}
                <div class="pt-6 space-y-1.5">
                    <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-[0.2em] mb-2">Inventory Logic</p>
                    
                    @foreach([
                        ['route' => 'gudang.po.index',               'label' => 'Purchase Order',    'icon' => 'shopping_cart_checkout'],
                        ['route' => 'gudang.receiving.index',         'label' => 'Penerimaan Barang', 'icon' => 'input'],
                        ['route' => 'gudang.stock.index',             'label' => 'Stok Gudang',       'icon' => 'inventory'],
                        ['route' => 'gudang.transfer-requests.index', 'label' => 'Transfer Request',  'icon' => 'move_to_inbox', 'badge' => 'gudang_pending_count'],
                        ['route' => 'gudang.transfer-out.index',      'label' => 'Transfer Keluar',   'icon' => 'output'],
                    ] as $item)
                    @php 
                        $badgeName = $item['badge'] ?? null;
                        $badgeVal = $badgeName ? ($$badgeName ?? 0) : 0; 
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs(str_replace('.index', '', $item['route']).'*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs(str_replace('.index', '', $item['route']).'*') ? 'fill' : '' }}">{{ $item['icon'] }}</span>
                            <span class="text-sm tracking-wide">{{ $item['label'] }}</span>
                        </div>
                        @if(isset($item['badge']) && $badgeVal > 0)
                            <span id="{{ str_replace('_count', '_badge', $item['badge']) }}" class="bg-rose-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full min-w-[22px] text-center shadow-lg animate-bounce">
                                {{ $badgeVal }}
                            </span>
                        @endif
                    </a>
                    @endforeach
                </div>

                {{-- User Management --}}
                <div class="pt-6 space-y-1.5">
                    <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-[0.2em] mb-2">Access</p>
                    <a href="{{ route('master.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('master.users.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-100' }}">
                        <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('master.users.*') ? 'fill' : '' }}">manage_accounts</span>
                        <span class="text-sm tracking-wide">Manajemen User</span>
                    </a>
                </div>
            </nav>

            {{-- User info at bottom --}}
            <div class="p-6 shrink-0 border-t border-slate-800 bg-slate-900/30">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-black text-sm border border-indigo-500/20 shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-black text-white truncate uppercase tracking-tight">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-500 truncate font-bold uppercase tracking-widest mt-0.5">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-slate-50 relative z-10">

            {{-- Top bar --}}
            <header class="bg-white/70 backdrop-blur-xl border-b border-slate-200 h-20 flex items-center justify-between px-8 shrink-0 z-20 print:hidden">
                <div class="flex items-center gap-6">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 w-11 h-11 flex items-center justify-center rounded-2xl transition-all duration-300 focus:outline-none ring-1 ring-slate-200 hover:ring-indigo-200 shadow-sm">
                        <span class="material-symbols-outlined" x-text="sidebarOpen ? 'menu_open' : 'menu'">menu</span>
                    </button>
                    <div class="h-10 w-[1px] bg-slate-200"></div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight font-headline truncate max-w-[200px] sm:max-w-none">@yield('page-title', 'Dashboard')</h1>
                </div>
                
                <div class="flex items-center gap-5">
                    <div class="hidden md:flex flex-col text-right">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{{ now()->translatedFormat('l') }}</span>
                        <span class="text-xs font-bold text-slate-800">{{ now()->translatedFormat('d F Y') }}</span>
                    </div>
                    <div class="h-10 w-[1px] bg-slate-200 hidden md:block"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center justify-center px-4 py-2.5 bg-slate-900 text-white hover:bg-rose-600 rounded-xl transition-all duration-300 font-bold text-xs uppercase tracking-widest group shadow-lg shadow-slate-900/10" title="Logout">
                            <span class="hidden sm:inline mr-2">Keluar</span>
                            <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">logout</span>
                        </button>
                    </form>
                </div>
            </header>

            {{-- Flash messages (Floating) --}}
            <div class="fixed top-24 right-8 z-50 flex flex-col gap-3 max-w-sm w-full">
                @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-x-4"
                     class="flex items-center gap-4 bg-white border border-emerald-100 shadow-2xl shadow-emerald-500/10 rounded-2xl p-5">
                    <div class="bg-emerald-500 rounded-xl p-2 shrink-0 text-white shadow-lg shadow-emerald-500/20"><span class="material-symbols-outlined text-[20px] block">check_circle</span></div>
                    <p class="text-sm font-bold text-slate-800">{{ session('success') }}</p>
                </div>
                @endif
                @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-x-4"
                     class="flex items-center gap-4 bg-white border border-rose-100 shadow-2xl shadow-rose-500/10 rounded-2xl p-5">
                    <div class="bg-rose-500 rounded-xl p-2 shrink-0 text-white shadow-lg shadow-rose-500/20"><span class="material-symbols-outlined text-[20px] block">error</span></div>
                    <p class="text-sm font-bold text-slate-800">{{ session('error') }}</p>
                </div>
                @endif
            </div>

            {{-- Main Scrollable Area --}}
            <div class="flex-1 overflow-auto custom-scrollbar p-8">
                @yield('content')
            </div>
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let lastGudangCount = {{ $gudang_pending_count ?? 0 }};

            function fetchNotificationCounts() {
                axios.get('{{ route('api.notifications.counts') }}')
                    .then(response => {
                        const data = response.data;
                        const badge = document.getElementById('gudang_pending_badge');
                        if (badge) {
                            if (data.gudang_pending > 0) {
                                badge.innerText = data.gudang_pending;
                                badge.style.display = 'inline-block';
                            } else {
                                badge.style.display = 'none';
                            }
                        }

                        if (data.gudang_pending > lastGudangCount && Notification.permission === "granted") {
                            new Notification("Permintaan Transfer Baru", {
                                body: "Ada " + (data.gudang_pending - lastGudangCount) + " permintaan transfer baru.",
                                icon: "/logo/gudang-logo.png"
                            });
                        }
                        lastGudangCount = data.gudang_pending;
                    })
                    .catch(error => console.error('Error fetching notifications:', error));
            }

            if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                Notification.requestPermission();
            }

            setInterval(fetchNotificationCounts, 30000);
        });
    </script>
    @stack('scripts')
</body>
</html>
