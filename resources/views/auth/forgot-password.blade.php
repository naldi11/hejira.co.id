<x-guest-layout>
    <div class="mb-6 text-sm text-gray-600 leading-relaxed text-center">
        {{ __('Lupa kata sandi? Tidak masalah. Beri tahu kami alamat email Anda dan kami akan mengirimkan tautan pengaturan ulang kata sandi yang memungkinkan Anda membuat kata sandi baru.') }}
    </div>

    <!-- Status Sesi -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
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
                       required autofocus />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex flex-col space-y-4">
            <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 transform hover:scale-[1.02] ">
                {{ __('Kirim Tautan Atur Ulang') }}
            </button>
            
            <a href="{{ route('login') }}" class="text-center text-sm font-semibold text-gray-500 hover:text-blue-600 transition-colors">
                 Kembali ke Halaman Masuk
            </a>
        </div>
    </form>
</x-guest-layout>
