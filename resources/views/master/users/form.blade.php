@extends($layout ?? 'layouts.gudang')
@section('title', isset($user) ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('page-title', 'Manajemen Akses')

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
        <a href="{{ route(($routePrefix ?? 'master.') . 'users.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Batal & Kembali
        </a>
        <h2 class="text-xl font-black text-slate-800 font-headline tracking-tight">{{ isset($user) ? 'Ubah Profil Pengguna' : 'Registrasi User Baru' }}</h2>
    </div>

    <form method="POST" action="{{ isset($user) ? route(($routePrefix ?? 'master.') . 'users.update', $user) : route(($routePrefix ?? 'master.') . 'users.store') }}"
          class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @csrf
        @if(isset($user)) @method('PUT') @endif

        {{-- Left: Credentials --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-8 sm:p-10 space-y-8">
                
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $accentColor }}-50 text-{{ $accentColor }}-600 flex items-center justify-center border border-{{ $accentColor }}-100 shadow-inner">
                        <span class="material-symbols-outlined text-[28px]">badge</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">Informasi Dasar</h3>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Identitas dan kredensial login</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required placeholder="cth: Ahmad Suherman"
                               class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Alamat Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required placeholder="email@perusahaan.com"
                               class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Password {{ isset($user) ? '(Kosongkan jika tetap)' : '*' }}</label>
                            <input type="password" name="password" {{ isset($user) ? '' : 'required' }}
                                   class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" {{ isset($user) ? '' : 'required' }}
                                   class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-3xl text-sm font-black uppercase tracking-widest hover:bg-{{ $accentColor }}-600 transition-all shadow-xl shadow-slate-900/10 active:scale-[0.98]">
                    {{ isset($user) ? 'Update Data Pengguna' : 'Daftarkan Pengguna' }}
                </button>
            </div>
        </div>

        {{-- Right: Permissions --}}
        <div class="space-y-8">
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8 space-y-8">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Entitas Bisnis <span class="text-rose-500">*</span></label>
                        <select name="entity" required class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none">
                            <option value="gudang" {{ old('entity', $user->entity ?? '') === 'gudang' ? 'selected' : '' }}>Gudang Utama</option>
                            <option value="jihans" {{ old('entity', $user->entity ?? '') === 'jihans' ? 'selected' : '' }}>Jihan's Food</option>
                            <option value="hendhys" {{ old('entity', $user->entity ?? '') === 'hendhys' ? 'selected' : '' }}>Hendhys Brownies</option>
                            <option value="owner" {{ old('entity', $user->entity ?? '') === 'owner' ? 'selected' : '' }}>Pemilik / Owner</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Cabang Penempatan</label>
                        <select name="branch_id" class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none">
                            <option value="">Tidak Terikat Cabang</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id', $user->branch_id ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Hak Akses (Role)</label>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($roles as $role)
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50 hover:bg-slate-100 cursor-pointer transition-all">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                           {{ in_array($role->name, old('roles', isset($user) ? $user->roles->pluck('name')->toArray() : [])) ? 'checked' : '' }}
                                           class="w-5 h-5 rounded-lg border-slate-300 text-{{ $accentColor }}-600 focus:ring-{{ $accentColor }}-500 transition-all">
                                    <span class="text-xs font-bold text-slate-700 uppercase tracking-tight">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-4 px-2">
                        <label class="relative inline-flex items-center cursor-pointer group">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500 transition-all"></div>
                            <span class="ms-3 text-xs font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-700 transition-colors">Akses Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
