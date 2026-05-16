<x-guest-layout>
    <!-- Status Sesi -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Alamat Email -->
        <div class="group">
            <label for="email" class="block text-sm font-medium text-gray-700 transition-colors group-focus-within:text-blue-600">
                {{ __('Alamat Email') }}
            </label>
            <div class="mt-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input id="email" 
                       class="block w-full pl-10 pr-3 py-3 border-gray-200 rounded-xl bg-white/50 border focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 placeholder-gray-400" 
                       type="email" 
                       name="email" 
                       :value="old('email')" 
                       placeholder="nama@email.com"
                       required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Kata Sandi -->
        <div class="group" x-data="{ show: false }">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-gray-700 transition-colors group-focus-within:text-blue-600">
                    {{ __('Kata Sandi') }}
                </label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors" href="{{ route('password.request') }}">
                        {{ __('Lupa kata sandi?') }}
                    </a>
                @endif
            </div>
            <div class="mt-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input id="password" 
                       class="block w-full pl-10 pr-12 py-3 border-gray-200 rounded-xl bg-white/50 border focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 placeholder-••••••••" 
                       :type="show ? 'text' : 'password'"
                       name="password"
                       required autocomplete="current-password" />
                
                <!-- Toggle Ikon Mata -->
                <button type="button" 
                        @click="show = !show"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 transition-colors focus:outline-none">
                    <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.024 10.024 0 014.132-5.403m11.856 2.828A10.053 10.053 0 0021.542 12c-1.274 4.057-5.064 7-9.542 7-1.31 0-2.54-.311-3.623-.866m3.623-8.866V5m-4.774 2.338L4.114 4.34M10.402 7.111l2.446 2.446m4.832 4.832l2.774 2.774M21 21l-2-2m-4.99-4.99l5 5m-7.699-15.3l3.699 3.699M10.4 10.4L6.7 6.7" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Ingat Saya -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 bg-white/50" name="remember">
            <label for="remember_me" class="ml-2 block text-sm text-gray-600 select-none">
                {{ __('Ingat saya di perangkat ini') }}
            </label>
        </div>

        <div>
            <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">
                {{ __('Masuk ke Dashboard') }}
            </button>
        </div>

        <div class="relative py-4">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-200/50"></div>
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="px-2 bg-transparent text-gray-500 uppercase tracking-widest font-semibold">Integrasi Bisnis</span>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="flex flex-col items-center p-2 rounded-lg bg-white/30 backdrop-blur-sm border border-white/20">
                <span class="text-[10px] font-bold text-blue-700">GUDANG</span>
            </div>
            <div class="flex flex-col items-center p-2 rounded-lg bg-white/30 backdrop-blur-sm border border-white/20">
                <span class="text-[10px] font-bold text-orange-600">JIHAN'S</span>
            </div>
            <div class="flex flex-col items-center p-2 rounded-lg bg-white/30 backdrop-blur-sm border border-white/20">
                <span class="text-[10px] font-bold text-red-600">HENDHYS</span>
            </div>
        </div>
    </form>
</x-guest-layout>
