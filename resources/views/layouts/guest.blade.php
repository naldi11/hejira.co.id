<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'HEJIRA') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            *, body, input, select, textarea, button {
                font-family: 'Poppins', sans-serif !important;
            }
        </style>
    </head>
    <body class="antialiased bg-slate-50 text-slate-900 min-h-screen flex flex-col justify-center items-center p-6 relative">
        <!-- Radial Gradient Latar Belakang yang Sangat Samar -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_120%,rgba(59,130,246,0.05),transparent_50%)] pointer-events-none"></div>

        <div class="w-full sm:max-w-md relative z-10">
            <!-- Card Utama -->
            <div class="bg-white p-8 sm:p-10 rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/40">
                {{ $slot }}
            </div>

            <!-- Footer Sederhana -->
            <div class="text-center mt-8 text-[11px] text-slate-400">
                <p>&copy; {{ date('Y') }} HEJIRA Systems. All rights reserved.</p>
            </div>
        </div>
    </body>
</html>
