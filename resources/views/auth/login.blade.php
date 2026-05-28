<x-guest-layout>
    <!-- Header Form Login -->
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Selamat Datang</h2>
        <p class="text-slate-500 mt-1.5 text-xs leading-relaxed">Silakan masuk untuk mengakses akun Anda</p>
    </div>

    <!-- Status Sesi -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Alamat Email -->
        <div class="group">
            <label for="email" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest transition-colors group-focus-within:text-blue-600">
                {{ __('Alamat Email') }}
            </label>
            <div class="mt-2 relative rounded-2xl">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input id="email" 
                       class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all duration-200 placeholder-slate-400 text-sm text-slate-900 focus:bg-white" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       placeholder="nama@email.com"
                       required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-[11px] text-red-500 font-medium" />
        </div>

        <!-- Kata Sandi -->
        <div class="group" x-data="{ show: false }">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest transition-colors group-focus-within:text-blue-600">
                    {{ __('Kata Sandi') }}
                </label>
                @if (Route::has('password.request'))
                    <a class="text-[11px] font-semibold text-blue-600 hover:text-blue-800 transition-colors" href="{{ route('password.request') }}">
                        {{ __('Lupa?') }}
                    </a>
                @endif
            </div>
            <div class="mt-2 relative rounded-2xl">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input id="password" 
                       class="block w-full pl-11 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all duration-200 placeholder-slate-400 text-sm text-slate-900 focus:bg-white" 
                       :type="show ? 'text' : 'password'"
                       name="password"
                       placeholder="••••••••"
                       required autocomplete="current-password" />
                
                <!-- Toggle Ikon Mata -->
                <button type="button" 
                        @click="show = !show"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-blue-600 transition-colors focus:outline-none">
                    <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.024 10.024 0 014.132-5.403m11.856 2.828A10.053 10.053 0 0021.542 12c-1.274 4.057-5.064 7-9.542 7-1.31 0-2.54-.311-3.623-.866m3.623-8.866V5m-4.774 2.338L4.114 4.34M10.402 7.111l2.446 2.446m4.832 4.832l2.774 2.774M21 21l-2-2m-4.99-4.99l5 5m-7.699-15.3l3.699 3.699M10.4 10.4L6.7 6.7" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-[11px] text-red-500 font-medium" />
        </div>

        <!-- Ingat Saya -->
        <div class="flex items-center justify-between pt-1">
            <div class="flex items-center">
                <input id="remember_me" type="checkbox" class="w-4 h-4 text-blue-600 border-slate-300 rounded-md focus:ring-4 focus:ring-blue-500/10 bg-slate-50 transition-all duration-200" name="remember">
                <label for="remember_me" class="ml-2 block text-xs text-slate-500 select-none">
                    {{ __('Ingat perangkat ini') }}
                </label>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-blue-500/10 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/20 transition-all duration-300 transform active:scale-[0.98]">
                {{ __('Masuk ke Dashboard') }}
            </button>
        </div>

        <div class="relative py-3">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-100"></div>
            </div>
            <div class="relative flex justify-center text-[9px]">
                <span class="px-3 bg-white text-slate-400 uppercase tracking-widest font-extrabold">Ekosistem Terintegrasi</span>
            </div>
        </div>

        <!-- Sub-brands Showcase Minimalis -->
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col items-center justify-center p-1.5 rounded-xl bg-slate-50 border border-slate-100 shadow-sm">
                <span class="text-[8px] font-extrabold text-blue-600 tracking-wider">GUDANG</span>
            </div>
            <div class="flex flex-col items-center justify-center p-1.5 rounded-xl bg-slate-50 border border-slate-100 shadow-sm">
                <span class="text-[8px] font-extrabold text-orange-600 tracking-wider">JIHAN'S</span>
            </div>
            <div class="flex flex-col items-center justify-center p-1.5 rounded-xl bg-slate-50 border border-slate-100 shadow-sm">
                <span class="text-[8px] font-extrabold text-amber-700 tracking-wider">HENDHYS</span>
            </div>
        </div>
    </form>
</x-guest-layout>
