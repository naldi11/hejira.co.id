@extends($layout ?? 'layouts.jihans')
@section('title', isset($karyawan) ? 'Edit Karyawan' : 'Tambah Karyawan')
@section('page-title', 'Profil Karyawan')

@section('content')
@php
    $accentColor = 'indigo';
    if (($currentScope ?? '') === 'jihans') {
        $accentColor = 'orange';
    } elseif (($currentScope ?? '') === 'hendhys') {
        $accentColor = 'amber';
    }
@endphp
<div class="max-w-4xl mx-auto space-y-8 pb-20">

    {{-- Header & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Batal & Kembali
        </a>
        <h2 class="text-xl font-black text-slate-800 font-headline tracking-tight">{{ isset($karyawan) ? 'Ubah Profil Karyawan' : 'Pendaftaran Karyawan Baru' }}</h2>
    </div>

    <form method="POST" action="{{ isset($karyawan) ? route(($routePrefix ?? 'master.') . 'karyawan.update', $karyawan) : route(($routePrefix ?? 'master.') . 'karyawan.store') }}"
          class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @csrf
        @if(isset($karyawan)) @method('PUT') @endif

        {{-- Left Column: Main Form --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-8 sm:p-10 space-y-8">
                
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $accentColor }}-50 text-{{ $accentColor }}-600 flex items-center justify-center border border-{{ $accentColor }}-100 shadow-inner">
                        <span class="material-symbols-outlined text-[28px]">person</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">Biodata Diri</h3>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Identitas resmi karyawan</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $karyawan->name ?? '') }}" required placeholder="Masukkan nama sesuai KTP..."
                               class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nomor Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone', $karyawan->phone ?? '') }}" placeholder="0812xxxx"
                                   class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none">
                        </div>
                        <div class="flex items-center pt-8 px-2">
                            <label class="relative inline-flex items-center cursor-pointer group">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $karyawan->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500 transition-all"></div>
                                <span class="ms-3 text-xs font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-700 transition-colors">Status Aktif</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-2 pt-4 border-t border-slate-100">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Alamat Domisili</label>
                        <textarea name="address" rows="3" placeholder="Jl. Raya No. 123..."
                                  class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none resize-none">{{ old('address', $karyawan->address ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-3xl text-sm font-black uppercase tracking-widest hover:bg-{{ $accentColor }}-600 transition-all shadow-xl shadow-slate-900/10 active:scale-[0.98]">
                    {{ isset($karyawan) ? 'Update Profil Karyawan' : 'Simpan Data Karyawan' }}
                </button>
            </div>
        </div>

        {{-- Right Column: Secondary Info --}}
        <div class="space-y-8">
            <div class="bg-{{ $accentColor }}-600 rounded-[2rem] p-8 text-white shadow-xl shadow-{{ $accentColor }}-600/20 relative overflow-hidden group">
                <div class="relative z-10">
                    <span class="material-symbols-outlined text-[32px] text-{{ $accentColor }}-300 mb-4 group-hover:rotate-12 transition-transform">info</span>
                    <h3 class="text-sm font-black uppercase tracking-[0.2em] mb-4">Informasi Sistem</h3>
                    <p class="text-xs font-medium leading-relaxed italic opacity-90">
                        Data karyawan ini akan muncul sebagai opsi pada sesi produksi borongan (Jihan's Food) untuk perhitungan gaji borongan secara otomatis.
                    </p>
                </div>
                <span class="material-symbols-outlined absolute -right-6 -bottom-6 text-white/5 text-[140px] rotate-12">groups</span>
            </div>
        </div>

    </form>
</div>
@endsection
