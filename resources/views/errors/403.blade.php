<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>403 - Akses Ditolak</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Outfit', sans-serif; }
            @keyframes blob {
                0% { transform: translate(0px, 0px) scale(1); }
                33% { transform: translate(30px, -50px) scale(1.1); }
                66% { transform: translate(-20px, 20px) scale(0.9); }
                100% { transform: translate(0px, 0px) scale(1); }
            }
            .animate-blob { animation: blob 7s infinite; }
            .animation-delay-2000 { animation-delay: 2s; }
            .animation-delay-4000 { animation-delay: 4s; }
            .glass-card {
                @apply bg-white/70 backdrop-blur-lg border border-white/20 shadow-2xl;
            }
            .bg-animated-gradient {
                background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
                background-size: 400% 400%;
                animation: gradient 15s ease infinite;
            }
            @keyframes gradient {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-animated-gradient relative overflow-hidden">
             <!-- Decorative Elements (Fixed with pointer-events-none to prevent click blocking) -->
            <div class="absolute top-0 -left-4 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob pointer-events-none" style="pointer-events: none;"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000 pointer-events-none" style="pointer-events: none;"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000 pointer-events-none" style="pointer-events: none;"></div>

            <div class="relative z-10 w-full sm:max-w-lg mt-6 px-8 py-12 bg-white/70 backdrop-blur-lg border border-white/20 shadow-2xl overflow-hidden sm:rounded-3xl transition-all duration-500 text-center" style="position: relative; z-index: 10;">
                
                <div class="flex justify-center mb-6">
                    <div class="p-4 bg-red-100/50 rounded-full drop-shadow-md">
                        <svg class="w-16 h-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>

                <h1 class="text-6xl font-bold text-gray-800 mb-2">403</h1>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4 tracking-tight">Akses Ditolak</h2>
                
                <p class="text-gray-600 mb-8 leading-relaxed px-4">
                    {{ $exception->getMessage() ?: 'Anda tidak memiliki izin yang cukup untuk mengakses halaman ini atau melakukan tindakan ini.' }}
                </p>

                <div class="flex justify-center space-x-4">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}" onclick="event.preventDefault(); try { if (window.frameElement) { let el = window.frameElement; while (el.parentNode && el.parentNode !== window.parent.document.body) { el = el.parentNode; } el.remove(); } else if (window.parent && window.parent !== window) { window.parent.history.back(); } else { window.history.back(); } } catch(e) { window.history.back(); }" class="px-6 py-3 bg-white text-gray-700 border border-gray-200 font-semibold rounded-xl hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-sm cursor-pointer">
                        Kembali
                    </a>
                    <a href="{{ route('dashboard') }}" target="_top" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl shadow-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all transform hover:scale-105 cursor-pointer">
                        Ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
