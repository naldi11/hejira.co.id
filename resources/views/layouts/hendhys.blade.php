<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hendhys Brownies') — HEJIRA</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    {{-- NOTE: To avoid conflicts, we use Vite for js, but Tailwind CDN for this specific layout --}}
    <!-- TomSelect CSS & JS (Loaded globally in head to prevent AlpineJS race conditions) -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
 
    @vite(['resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
 
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
                    "sans": ["Poppins", "sans-serif"],
                    "headline-lg": ["Poppins"], "label-lg": ["Poppins"], "display-lg": ["Poppins"], "headline-md": ["Poppins"], 
                    "body-lg": ["Poppins"], "label-sm": ["Poppins"], "body-md": ["Poppins"], "title-lg": ["Poppins"], "headline-lg-mobile": ["Poppins"]
                },
                "fontSize": {
                    "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "500" }],
                    "label-lg": ["14px", { "lineHeight": "20px", "letterSpacing": "0.02em", "fontWeight": "500" }],
                    "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "500" }],
                    "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "500" }],
                    "body-lg": ["18px", { "lineHeight": "26px", "fontWeight": "400" }],
                    "label-sm": ["12px", { "lineHeight": "16px", "fontWeight": "500" }],
                    "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                    "title-lg": ["20px", { "lineHeight": "28px", "fontWeight": "500" }],
                    "headline-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "500" }]
                },
                "fontWeight": {
                    "semibold": "500",
                    "bold": "500",
                    "extrabold": "500",
                    "black": "500",
                }
            },
        },
    }
    </script>
    <style>
        *, body, input, select, textarea, button {
            font-family: 'Poppins', sans-serif !important;
        }
        body { background-color: theme('colors.background'); color: theme('colors.on-background'); }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-family: 'Material Symbols Outlined' !important; }
        .material-symbols-outlined.fill { font-variation-settings: 'FILL' 1; }
        [x-cloak] { display: none !important; }
        /* Scrollbar customization */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #dac2b6; border-radius: 4px; }
        
        .cart-collapsed { width: 0 !important; opacity: 0; border-left-width: 0 !important; }
        .cart-wrapper { width: 20rem; }
        @media (min-width: 1024px) { .cart-wrapper { width: 24rem; } }
    </style>
    @stack('styles')
</head>
<body class="flex h-screen overflow-hidden antialiased bg-background text-on-background font-body-md print:block print:h-auto print:overflow-visible print:bg-white" x-data="{ sidebarOpen: {{ request()->routeIs('hendhys.pos.*') ? 'false' : 'window.innerWidth >= 768' }}, isMobile: window.innerWidth < 768 }" @resize.window="isMobile = window.innerWidth < 768;">

    @php
        $isPusat = auth()->user()->branch->type === 'pusat';
    @endphp

    {{-- Mobile Overlay --}}
    <div x-show="sidebarOpen && isMobile" x-cloak class="fixed inset-0 z-40 bg-inverse-surface bg-opacity-40 backdrop-blur-sm md:hidden transition-opacity print:hidden" @click="sidebarOpen = false"></div>

    <!-- SideNavBar -->
    <nav class="fixed inset-y-0 left-0 z-50 w-64 bg-surface-container flex flex-col h-full py-md px-sm border-r border-outline-variant shadow-lg transition-transform duration-300 ease-in-out print:hidden"
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="mb-sm px-sm flex items-center gap-2">
            <img src="{{ asset('logo/hendhys-logo.png') }}" alt="Logo Hendhys" class="w-14 h-14 object-contain drop-shadow-sm rounded" onerror="this.style.display='none'">
            <div>
                <h1 class="text-[14px] font-black text-primary-container leading-tight">Hendhy's Brownies</h1>
                <p class="text-[10px] font-bold text-on-surface-variant mt-[2px] tracking-wide uppercase">
                    {{ $isPusat ? 'PUSAT' : 'CABANG ' . auth()->user()->branch->name }}
                </p>
            </div>
        </div>
        
        <ul class="flex flex-col gap-xs flex-grow overflow-y-auto custom-scrollbar">
            <li>
                <a href="{{ route('hendhys.dashboard') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.dashboard') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.dashboard') ? 'fill' : '' }}" data-icon="dashboard">dashboard</span>
                    Dashboard
                </a>
            </li>

            <li class="mt-md text-[10px] font-bold text-outline uppercase tracking-widest px-sm">Penjualan</li>
            <li>
                <a href="{{ route('hendhys.pos.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.pos.index') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.pos.index') ? 'fill' : '' }}">point_of_sale</span>
                    POS Kasir
                </a>
            </li>
            <li>
                <a href="{{ route('hendhys.transactions.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.transactions.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.transactions.*') ? 'fill' : '' }}" data-icon="receipt_long">receipt_long</span>
                    Riwayat Transaksi
                </a>
            </li>
            <li>
                <a href="{{ route('hendhys.pending.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.pending.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.pending.*') ? 'fill' : '' }}">pause_circle</span>
                    Transaksi Pending
                </a>
            </li>
            <li>
                <a href="{{ route('hendhys.reports.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.reports.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.reports.*') ? 'fill' : '' }}">assessment</span>
                    Laporan
                </a>
            </li>

            <li class="mt-md text-[10px] font-bold text-outline uppercase tracking-widest px-sm">Inventory</li>
            <li>
                <a href="{{ route('hendhys.stock.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.stock.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.stock.*') ? 'fill' : '' }}" data-icon="inventory_2">inventory_2</span>
                    Stok Produk
                </a>
            </li>
            
            @if($isPusat)
            <li>
                <a href="{{ route('hendhys.productions.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.productions.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.productions.*') ? 'fill' : '' }}">factory</span>
                    Produksi Bakery
                </a>
            </li>
            <li>
                <a href="{{ route('hendhys.transfer-to-branch.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.transfer-to-branch.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.transfer-to-branch.*') ? 'fill' : '' }}">local_shipping</span>
                    Distribusi
                </a>
            </li>
            <li>
                <a href="{{ route('hendhys.transfer-requests.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.transfer-requests.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.transfer-requests.*') ? 'fill' : '' }}">warehouse</span>
                    Request Stok (Gudang)
                </a>
            </li>
            @else
            <li>
                <a href="{{ route('hendhys.transfer-to-branch.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.transfer-to-branch.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('hendhys.transfer-to-branch.*') ? 'fill' : '' }}">call_received</span>
                    Penerimaan Barang
                </a>
            </li>
            @endif

            <li>
                <a href="{{ route('hendhys.branch-requests.index') }}" class="flex items-center gap-sm px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all  duration-200 {{ request()->routeIs('hendhys.branch-requests.*') ? 'bg-secondary-container text-on-secondary-container font-bold' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    @if($isPusat)
                        <span class="material-symbols-outlined {{ request()->routeIs('hendhys.branch-requests.*') ? 'fill' : '' }}">inbox</span>
                        Permintaan Cabang
                    @else
                        <span class="material-symbols-outlined {{ request()->routeIs('hendhys.branch-requests.*') ? 'fill' : '' }}">add_shopping_cart</span>
                        Minta Stok
                    @endif
                </a>
            </li>

            @if($isPusat)
            <li class="mt-md text-[10px] font-bold text-outline uppercase tracking-widest px-sm">Master Data</li>
            <li x-data="{ masterOpen: {{ request()->routeIs('hendhys.master.*') ? 'true' : 'false' }} }">
                <button @click="masterOpen = !masterOpen" class="w-full flex items-center justify-between px-sm py-sm rounded-lg font-label-lg text-label-lg transition-all text-on-surface-variant hover:bg-surface-container-high">
                    <span class="flex items-center gap-sm"><span class="material-symbols-outlined" data-icon="settings">settings</span>Master Data</span>
                    <span class="material-symbols-outlined transition-transform" :class="masterOpen ? 'rotate-180' : ''">expand_more</span>
                </button>
                <div x-show="masterOpen" x-collapse x-cloak class="mt-1 space-y-1">
                    @foreach([
                        ['route' => 'hendhys.master.products.index',          'label' => 'Produk'],
                        ['route' => 'hendhys.master.payment-methods.index',   'label' => 'Metode Bayar'],
                    ] as $item)
                        <a href="{{ route($item['route']) }}" class="block pl-[44px] pr-sm py-[8px] rounded-lg text-[13px] transition-colors {{ request()->routeIs($item['route']) ? 'text-on-secondary-container font-bold bg-secondary-container' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </li>
            @endif
        </ul>

        <div class="mt-auto">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-sm px-sm py-sm text-on-surface-variant hover:bg-error-container hover:text-error transition-all rounded-lg font-label-lg text-label-lg  duration-200">
                    <span class="material-symbols-outlined" data-icon="logout">logout</span>
                    Logout
                </button>
            </form>
        </div>
    </nav>
    {{-- Sidebar spacer --}}
    <div class="shrink-0 transition-all duration-300 ease-in-out h-full print:hidden" :class="sidebarOpen ? 'w-64' : 'w-0'"></div>
    <!-- Main Flex Area after Nav -->
    <div class="flex-1 flex min-w-0 h-full w-full relative transition-all duration-300 print:block print:w-full print:h-auto" @yield('wrapper-attributes', '')>
        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col min-w-0 bg-surface overflow-auto relative z-10 h-full w-full print:block print:w-full print:h-auto print:overflow-visible print:bg-white">
        
        <header class="flex justify-between items-center w-full px-margin-mobile md:px-margin-desktop h-20 z-40 bg-surface border-b border-outline-variant shrink-0 print:hidden">
            <div class="flex items-center gap-md">
                {{-- Sidebar Toggle - visible always --}}
                <button @click="sidebarOpen = !sidebarOpen"
                        class="text-primary hover:bg-surface-container-low p-xs rounded-lg transition-colors  duration-150 shrink-0"
                        title="Toggle Sidebar">
                    <span class="material-symbols-outlined" x-text="sidebarOpen ? 'menu_open' : 'menu'">menu</span>
                </button>
                <div class="font-headline-md text-[18px] md:text-headline-md font-bold text-primary truncate max-w-[200px] md:max-w-none">@yield('page-title', 'Hendhys POS')</div>
            </div>
            {{-- Auth Profile --}}
            <div class="flex items-center gap-sm shrink-0">
                <div class="text-right leading-tight">
                    <div class="font-label-lg text-label-lg text-on-background">{{ auth()->user()->name }}</div>
                    <div class="font-label-sm text-label-sm text-on-surface-variant">{{ auth()->user()->getRoleNames()->first() }}</div>
                </div>
                <div class="w-9 h-9 rounded-full bg-primary-fixed flex items-center justify-center shrink-0">
                    <span class="font-bold text-on-primary-fixed-variant text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
            </div>
        </header>

        <!-- Flash messages -->
        <div class="absolute top-24 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2 max-w-md w-full pointer-events-none px-4">
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="flex items-center gap-3 bg-tertiary-fixed border border-tertiary-fixed-dim text-on-tertiary-fixed-variant shadow-lg rounded-xl p-4 pointer-events-auto">
                <span class="material-symbols-outlined text-tertiary">check_circle</span>
                <p class="font-body-md text-sm font-bold">{{ session('success') }}</p>
            </div>
            @endif
            @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="flex items-center gap-3 bg-error-container border border-error text-on-error-container shadow-lg rounded-xl p-4 pointer-events-auto">
                <span class="material-symbols-outlined text-error">error</span>
                <p class="font-body-md text-sm font-bold">{{ session('error') }}</p>
            </div>
            @endif
        </div>

        <div class="@if(!request()->routeIs('hendhys.pos.*')) p-6 md:p-8 @endif flex-1 flex flex-col min-w-0">
            @yield('content')
        </div>
    </main>
    
    @yield('right-sidebar')

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let lastHendhysCount = {{ $hendhys_pusat_pending_count ?? 0 }};

            function fetchNotificationCounts() {
                axios.get('{{ route('api.notifications.counts') }}')
                    .then(response => {
                        const data = response.data;
                        lastHendhysCount = data.hendhys_pusat_pending;
                    }).catch(error => console.error('Error fetching notifications:', error));
            }
            setInterval(fetchNotificationCounts, 30000);
        });
    </script>
    @stack('scripts')
</body>
</html>
