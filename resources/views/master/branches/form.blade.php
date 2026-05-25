@extends($layout ?? 'layouts.gudang')
@section('title', isset($branch) ? 'Edit Cabang' : 'Tambah Cabang')
@section('page-title', 'Konfigurasi Cabang')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 pb-20">

    {{-- Header & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route(($routePrefix ?? 'master.') . 'branches.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Batal & Kembali
        </a>
        <h2 class="text-xl font-black text-slate-800 font-headline tracking-tight">{{ isset($branch) ? 'Edit Data Cabang' : 'Pendaftaran Cabang Baru' }}</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Left: Form --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ isset($branch) ? route(($routePrefix ?? 'master.') . 'branches.update', $branch) : route(($routePrefix ?? 'master.') . 'branches.store') }}"
                  class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-8 sm:p-10 space-y-8">
                @csrf
                @if(isset($branch)) @method('PUT') @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Kode Cabang <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $branch->code ?? '') }}" required placeholder="cth: HND-CB3"
                               class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none @error('code') border-rose-200 bg-rose-50 @enderror">
                        @error('code') <p class="text-rose-500 text-[10px] font-bold mt-1 ml-2 uppercase">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Tipe Unit <span class="text-rose-500">*</span></label>
                        <select name="type" required class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none">
                            <option value="cabang" {{ old('type', $branch->type ?? 'cabang') === 'cabang' ? 'selected' : '' }}>Outlet / Cabang</option>
                            <option value="pusat"  {{ old('type', $branch->type ?? '') === 'pusat'  ? 'selected' : '' }}>Kantor Pusat / Gudang</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nama Cabang <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $branch->name ?? '') }}" required placeholder="cth: Hendhys Brownies SM Raja"
                               class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none @error('name') border-rose-200 bg-rose-50 @enderror">
                        @error('name') <p class="text-rose-500 text-[10px] font-bold mt-1 ml-2 uppercase">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nomor Telepon</label>
                        <input type="text" name="phone" value="{{ old('phone', $branch->phone ?? '') }}" placeholder="0812xxxx"
                               class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-indigo-500 transition-all outline-none">
                    </div>
                    <div class="flex items-center pt-8 px-2">
                        <label class="relative inline-flex items-center cursor-pointer group">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 transition-all"></div>
                            <span class="ms-3 text-xs font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-700 transition-colors">Status Aktif</span>
                        </label>
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Alamat Lengkap</label>
                        <textarea name="address" rows="3" placeholder="Jl. Raya No. 123..."
                                  class="w-full px-5 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-indigo-500 transition-all outline-none resize-none">{{ old('address', $branch->address ?? '') }}</textarea>
                    </div>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row gap-3">
                    <button type="submit" class="flex-1 px-8 py-4 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl shadow-slate-900/10 active:scale-[0.98]">
                        {{ isset($branch) ? 'Simpan Perubahan' : 'Daftarkan Cabang' }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Right: Info --}}
        <div class="space-y-6">
            <div class="bg-indigo-600 rounded-[2rem] p-8 text-white shadow-xl shadow-indigo-600/20 relative overflow-hidden group">
                <div class="relative z-10">
                    <span class="material-symbols-outlined text-[32px] text-indigo-300 mb-4 group-hover:rotate-12 transition-transform">info</span>
                    <h3 class="text-sm font-black uppercase tracking-[0.2em] mb-4">Informasi Tipe</h3>
                    <ul class="space-y-4">
                        <li class="flex gap-3">
                            <span class="text-indigo-300 text-xs">●</span>
                            <p class="text-xs font-medium leading-relaxed"><strong class="text-white">Pusat:</strong> Digunakan untuk Gudang Utama dan Kantor Administrasi.</p>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-indigo-300 text-xs">●</span>
                            <p class="text-xs font-medium leading-relaxed"><strong class="text-white">Cabang:</strong> Digunakan untuk outlet penjualan retail (Hendhys).</p>
                        </li>
                    </ul>
                </div>
                <span class="material-symbols-outlined absolute -right-6 -bottom-6 text-white/5 text-[140px] rotate-12">storefront</span>
            </div>
        </div>

    </div>
</div>
@endsection
