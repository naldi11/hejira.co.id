@extends($layout ?? 'layouts.gudang')
@section('title', isset($method) ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran')
@section('page-title', 'Konfigurasi Pembayaran')

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
        <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Batal & Kembali
        </a>
        <h2 class="text-xl font-black text-slate-800 font-headline tracking-tight">{{ isset($method) ? 'Edit Opsi Pembayaran' : 'Metode Pembayaran Baru' }}</h2>
    </div>

    <form method="POST" action="{{ isset($method) ? route(($routePrefix ?? 'master.') . 'payment-methods.update', $method) : route(($routePrefix ?? 'master.') . 'payment-methods.store') }}"
          enctype="multipart/form-data" class="space-y-8">
        @csrf
        @if(isset($method)) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Left: Form --}}
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-8 sm:p-10 space-y-8">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nama Metode <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $method->name ?? '') }}" required placeholder="cth: Tunai, Transfer Mandiri, QRIS..."
                                   class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Tipe Sistem <span class="text-rose-500">*</span></label>
                            <select name="type" required class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none">
                                @foreach(['tunai' => 'Tunai / Cash', 'kredit' => 'Piutang / Kredit', 'kartu_debit' => 'Kartu Debit / Transfer', 'kartu_kredit' => 'Kartu Kredit'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ old('type', $method->type ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-slate-100">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nama Bank / Provider</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $method->bank_name ?? '') }}" placeholder="cth: Bank Mandiri"
                                   class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Nomor Rekening</label>
                            <input type="text" name="account_number" value="{{ old('account_number', $method->account_number ?? '') }}" placeholder="xxxx-xxxx-xxxx"
                                   class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none font-mono">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Atas Nama</label>
                            <input type="text" name="account_name" value="{{ old('account_name', $method->account_name ?? '') }}" placeholder="cth: CV. Jihan Food"
                                   class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-4 pt-6 border-t border-slate-100">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Ikon / Logo Metode</label>
                        <div class="flex items-center gap-6">
                            @if(isset($method) && $method->image)
                                <img src="{{ asset('storage/'.$method->image) }}" class="w-20 h-12 object-contain rounded-xl border border-slate-200 p-2 bg-slate-50">
                            @endif
                            <input type="file" name="image" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:bg-{{ $accentColor }}-50 file:text-{{ $accentColor }}-600 hover:file:bg-{{ $accentColor }}-100 transition-all">
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-3xl text-sm font-black uppercase tracking-widest hover:bg-{{ $accentColor }}-600 transition-all shadow-xl shadow-slate-900/10 active:scale-[0.98]">
                        {{ isset($method) ? 'Simpan Perubahan' : 'Aktifkan Metode Pembayaran' }}
                    </button>
                </div>
            </div>

            {{-- Right: Sidebar --}}
            <div class="space-y-8">
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8 space-y-8">
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Scope Penggunaan</label>
                        <select name="entity_scope" class="w-full px-5 py-3.5 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none">
                            <option value="all" {{ old('entity_scope', $method->entity_scope ?? '') === 'all' ? 'selected' : '' }}>Semua Entitas</option>
                            <option value="jihans" {{ old('entity_scope', $method->entity_scope ?? '') === 'jihans' ? 'selected' : '' }}>Khusus Jihan's Food</option>
                            <option value="hendhys" {{ old('entity_scope', $method->entity_scope ?? '') === 'hendhys' ? 'selected' : '' }}>Khusus Hendhys</option>
                        </select>
                    </div>

                    <div class="flex items-center px-2">
                        <label class="relative inline-flex items-center cursor-pointer group">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $method->is_active ?? true) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500 transition-all"></div>
                            <span class="ms-3 text-xs font-black text-slate-500 uppercase tracking-widest group-hover:text-slate-700 transition-colors">Metode Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="bg-{{ $accentColor }}-600 rounded-[2rem] p-8 text-white shadow-xl shadow-{{ $accentColor }}-600/20 relative overflow-hidden group">
                    <div class="relative z-10">
                        <span class="material-symbols-outlined text-[32px] text-{{ $accentColor }}-300 mb-4">security</span>
                        <h3 class="text-sm font-black uppercase tracking-[0.2em] mb-4">Catatan Keamanan</h3>
                        <p class="text-xs font-medium leading-relaxed italic opacity-80">
                            Pastikan data nomor rekening dan tipe sistem sudah benar. Perubahan pada tipe sistem dapat mempengaruhi perhitungan laporan keuangan otomatis.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection
