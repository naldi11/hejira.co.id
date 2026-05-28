<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', "Owner Dashboard") — Executive Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        *, body, input, select, textarea, button {
            font-family: 'Poppins', sans-serif !important;
        }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; font-family: 'Material Symbols Outlined' !important; }
    </style>
</head>
<body class="bg-[#f8fafc] text-gray-800 font-sans antialiased flex h-screen overflow-hidden selection:bg-slate-200 selection:text-slate-900">

    {{-- Sidebar (Executive Theme - Dark Slate) --}}
    <aside class="w-64 bg-[#0f172a] text-[#cbd5e1] flex flex-col transition-all duration-300 shadow-xl z-20">
        {{-- Logo Area --}}
        <div class="h-16 flex items-center px-6 border-b border-[#1e293b] bg-[#020617]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-tr from-blue-600 to-indigo-500 text-white rounded-lg flex items-center justify-center font-bold text-xl shadow-inner">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div>
                    <span class="font-bold text-lg tracking-wide text-white block leading-none mt-1">Executive</span>
                    <span class="text-[10px] text-blue-400 font-medium tracking-wider uppercase">Owner Panel</span>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto py-4 px-3 custom-scrollbar">
            <nav class="space-y-1">
                <a href="{{ route('owner.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('owner.dashboard') ? 'bg-[#1e293b] text-white font-medium border-l-4 border-blue-500' : 'text-slate-400 hover:bg-[#1e293b]/60 border-l-4 border-transparent' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Konsolidasi Utama
                </a>

                <div class="pt-5 pb-2">
                    <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Dashboard Entitas</p>
                </div>
                
                <a href="{{ route('owner.gudang') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('owner.gudang') ? 'bg-[#1e293b] text-white font-medium border-l-4 border-teal-500' : 'text-slate-400 hover:bg-[#1e293b]/60 border-l-4 border-transparent' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Gudang Tempua
                </a>
                
                <a href="{{ route('owner.jihans') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('owner.jihans') ? 'bg-[#1e293b] text-white font-medium border-l-4 border-orange-500' : 'text-slate-400 hover:bg-[#1e293b]/60 border-l-4 border-transparent' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Jihan's Food
                </a>

                <a href="{{ route('owner.hendhys') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('owner.hendhys') ? 'bg-[#1e293b] text-white font-medium border-l-4 border-amber-600' : 'text-slate-400 hover:bg-[#1e293b]/60 border-l-4 border-transparent' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Hendhys Brownies
                </a>

                <div class="pt-5 pb-2">
                    <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Reporting & Log</p>
                </div>

                <a href="{{ route('owner.reports') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('owner.reports*') ? 'bg-[#1e293b] text-white font-medium border-l-4 border-purple-500' : 'text-slate-400 hover:bg-[#1e293b]/60 border-l-4 border-transparent' }}">
                    <svg class="w-5 h-5 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Data Reports (Eksport)
                </a>
            </nav>
        </div>

        {{-- User Menu --}}
        <div class="p-4 border-t border-[#1e293b] bg-[#020617]" x-data="{ open: false }">
            <div class="relative">
                <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 w-full hover:bg-[#0f172a] p-2 rounded-lg transition-colors">
                    <div class="w-9 h-9 rounded-full bg-slate-700 text-white flex items-center justify-center font-bold shadow-sm">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="text-left flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] uppercase tracking-wider font-bold text-blue-400 truncate">Big Boss / Owner</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
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
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shadow-sm z-10 print:hidden">
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">@yield('page-title', "Dashboard")</h1>
            
            <div class="flex items-center gap-4">
                <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold border border-blue-100 shadow-sm flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    READ ONLY MODE
                </span>
            </div>
        </header>

        {{-- Page Content --}}
        <div class="flex-1 overflow-y-auto p-6 lg:p-8 custom-scrollbar">
            @yield('content')
        </div>
    </main>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        aside.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
        aside.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
</body>
</html>
