<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Gudang Tempua</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet"/>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js CDN sebagai backup jika Vite gagal load -->
    <script>
        // Hanya load Alpine CDN jika belum ada dari Vite
        if (typeof window.Alpine === 'undefined') {
            document.write('<scr'+'ipt defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"><\/scr'+'ipt>');
            document.write('<scr'+'ipt defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"><\/scr'+'ipt>');
        }
    </script>

    <!-- Backup Tailwind CDN for shared views using arbitrary MD3 classes -->
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
<body class="bg-gray-50 h-screen font-sans antialiased overflow-hidden selection:bg-indigo-200 selection:text-indigo-900" 
      x-data="{ sidebarOpen: window.innerWidth >= 1024, isMobile: window.innerWidth < 1024 }" 
      @resize.window="isMobile = window.innerWidth < 1024; if(!isMobile) sidebarOpen = true;">

    <div class="flex h-full w-full relative">

        {{-- Mobile Overlay --}}
        <div x-show="sidebarOpen && isMobile" x-cloak 
             class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden transition-opacity" 
             @click="sidebarOpen = false"></div>

        {{-- SIDEBAR --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-[#1e1b4b] text-white flex flex-col shadow-2xl lg:static lg:shrink-0 transition-all duration-300 ease-in-out border-r border-indigo-900/50"
               :class="sidebarOpen ? 'translate-x-0 lg:ml-0' : '-translate-x-full lg:-ml-64'">

            {{-- Logo --}}
            <div class="flex items-center gap-3 px-6 py-5 border-b border-white/5 bg-white/5 shrink-0">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl flex items-center justify-center shadow-inner">
                    <span class="text-sm font-bold text-white tracking-widest">GT</span>
                </div>
                <div>
                    <h1 class="font-bold text-[15px] leading-tight text-white">Gudang Tempua</h1>
                    <p class="text-[10px] text-indigo-300 font-medium tracking-wider uppercase">Manajemen Bisnis</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar">

                <a href="{{ route('gudang.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('gudang.dashboard') ? 'bg-indigo-600 shadow-md text-white font-medium' : 'text-indigo-200/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-sm">Dashboard</span>
                </a>

                {{-- Master Data Dropdown --}}
                <div x-data="{ open: {{ request()->routeIs('master.*') ? 'true' : 'false' }} }" class="space-y-1 mt-6">
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-indigo-200/80 hover:bg-white/10 transition-all duration-200 focus:outline-none">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span class="font-medium text-sm">Master Data</span>
                        </div>
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-300" :class="open ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    
                    <div x-show="open" x-collapse x-cloak class="pt-1 pb-2">
                        @foreach([
                            ['route' => 'master.suppliers.index',   'label' => 'Supplier'],
                            ['route' => 'master.customers.index',   'label' => 'Customer'],
                            ['route' => 'master.products.index',    'label' => 'Produk'],
                            ['route' => 'master.categories.index',  'label' => 'Kategori'],
                            ['route' => 'master.units.index',       'label' => 'Satuan'],
                            ['route' => 'master.brands.index',      'label' => 'Brand'],
                            ['route' => 'master.branches.index',    'label' => 'Cabang'],
                        ] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 pl-10 pr-3 py-2 rounded-lg text-[13px] transition-all duration-200 {{ request()->routeIs($item['route']) ? 'text-indigo-100 font-semibold bg-white/10 relative before:absolute before:left-4 before:top-1/2 before:-translate-y-1/2 before:w-1.5 before:h-1.5 before:bg-indigo-300 before:rounded-full' : 'text-indigo-300/60 hover:text-indigo-100 hover:bg-white/5' }}">
                            {{ $item['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Gudang Operasional --}}
                <div class="pt-5 pb-2">
                    <p class="px-3 text-[10px] font-bold text-indigo-400/60 uppercase tracking-widest">Operasional</p>
                </div>

                @foreach([
                    ['route' => 'gudang.po.index',               'label' => 'Purchase Order'],
                    ['route' => 'gudang.receiving.index',         'label' => 'Penerimaan Barang'],
                    ['route' => 'gudang.stock.index',             'label' => 'Stok Gudang'],
                    ['route' => 'gudang.transfer-requests.index', 'label' => 'Transfer Request', 'badge' => 'gudang_pending_count'],
                    ['route' => 'gudang.transfer-out.index',      'label' => 'Transfer Keluar'],
                ] as $item)
                @php 
                    $badgeName = $item['badge'] ?? null;
                    $badgeVal = $badgeName ? ($$badgeName ?? 0) : 0; 
                @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs(str_replace('.index', '', $item['route']).'*') ? 'bg-indigo-600 shadow-md text-white font-medium' : 'text-indigo-200/80 hover:bg-white/10 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span class="text-sm">{{ $item['label'] }}</span>
                    </div>
                    @if(isset($item['badge']) && $badgeVal > 0)
                        <span id="{{ str_replace('_count', '_badge', $item['badge']) }}" class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center shadow-sm">
                            {{ $badgeVal }}
                        </span>
                    @endif
                </a>
                @endforeach

                {{-- User Management --}}
                <div class="pt-5 pb-2">
                    <p class="px-3 text-[10px] font-bold text-indigo-400/60 uppercase tracking-widest">Pengaturan</p>
                </div>
                <a href="{{ route('master.users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('master.users.*') ? 'bg-indigo-600 shadow-md text-white font-medium' : 'text-indigo-200/80 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-sm">Manajemen User</span>
                </a>
            </nav>

            {{-- User info at bottom --}}
            <div class="p-4 border-t border-white/5 bg-white/5 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-200 text-indigo-900 flex items-center justify-center font-bold text-sm shadow-inner shrink-0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-indigo-300 truncate">Admin Gudang</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-gray-50 relative z-10 transition-all duration-300">

            {{-- Top bar --}}
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 shrink-0 z-20 print:hidden">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 p-2 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                    </button>
                    <h1 class="text-lg font-bold text-gray-800 tracking-tight truncate max-w-[200px] sm:max-w-none">@yield('page-title', 'Dashboard')</h1>
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
            <div class="absolute top-20 right-6 z-50 flex flex-col gap-2 max-w-sm w-full">
                @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-x-4"
                     class="flex items-center gap-3 bg-white border-l-4 border-green-500 shadow-xl rounded-lg p-4">
                    <div class="bg-green-100 rounded-full p-1.5 shrink-0"><svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div>
                    <p class="text-sm font-medium text-gray-700">{{ session('success') }}</p>
                </div>
                @endif
                @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-x-4"
                     class="flex items-center gap-3 bg-white border-l-4 border-red-500 shadow-xl rounded-lg p-4">
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

            if (Notification.permission !== "granted") {
                Notification.requestPermission();
            }

            setInterval(fetchNotificationCounts, 30000);

            if (typeof window.Echo !== 'undefined') {
                window.Echo.private('gudang.notifications')
                    .listen('TransferRequestCreated', () => fetchNotificationCounts());
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
