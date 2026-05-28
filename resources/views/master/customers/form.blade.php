@extends($layout ?? 'layouts.gudang')
@section('title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')
@section('page-title', 'Master Data — ' . (isset($customer) ? 'Edit Customer' : 'Tambah Customer'))

@section('content')
@php
    $accentColor = 'indigo';
    if (($currentScope ?? '') === 'jihans') {
        $accentColor = 'orange';
    } elseif (($currentScope ?? '') === 'hendhys') {
        $accentColor = 'amber';
    }
@endphp
    <div class="max-w-4xl mx-auto">
        <form method="POST"
            action="{{ isset($customer) ? route(($routePrefix ?? 'master.') . 'customers.update', $customer) : route(($routePrefix ?? 'master.') . 'customers.store') }}"
            class="space-y-8">
            @csrf
            @if(isset($customer)) @method('PUT') @endif

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-10 py-8 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 font-headline uppercase tracking-widest">Informasi Customer</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-tighter">Profil dan klasifikasi pelanggan</p>
                </div>
                
                <div class="p-10 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Nama --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Nama Lengkap / Instansi <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required
                                placeholder="Masukkan nama customer..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none w-full font-bold text-slate-900">
                            @error('name') <p class="text-rose-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Tipe --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Tipe Customer <span class="text-rose-500">*</span></label>
                            <select name="type" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none font-bold text-slate-900 appearance-none cursor-pointer">
                                <option value="Pelanggan Individual" {{ old('type', $customer->type ?? 'Pelanggan Individual') === 'Pelanggan Individual' ? 'selected' : '' }}>Individual</option>
                                <option value="Pelanggan Retail"     {{ old('type', $customer->type ?? '') === 'Pelanggan Retail'     ? 'selected' : '' }}>Retail</option>
                                <option value="Pelanggan Agen"       {{ old('type', $customer->type ?? '') === 'Pelanggan Agen'       ? 'selected' : '' }}>Agen</option>
                            </select>
                        </div>

                        {{-- Telepon --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Nomor Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
                                placeholder="Contoh: 08123456789"
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Alamat Email</label>
                            <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                                placeholder="alamat@email.com"
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Status Akun</label>
                            <select name="is_active" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none font-bold text-slate-900 appearance-none cursor-pointer">
                                <option value="1" {{ old('is_active', $customer->is_active ?? 1) == 1 ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ old('is_active', $customer->is_active ?? 1) == 0 ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>

                        {{-- Alamat --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Alamat Lengkap</label>
                            <textarea name="address" rows="3" placeholder="Masukkan alamat lengkap customer..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-3xl px-6 py-4 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none w-full font-medium text-slate-700">{{ old('address', $customer->address ?? '') }}</textarea>
                        </div>

                        {{-- Catatan --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Catatan Tambahan</label>
                            <input type="text" name="notes" value="{{ old('notes', $customer->notes ?? '') }}"
                                placeholder="Catatan internal (opsional)..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>
                    </div>

                    {{-- Visibilitas Entitas --}}
                    <div class="border-t border-slate-100 pt-10">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-6 block">Tampilkan di Entitas</label>
                        @php
                            $scope = $currentScope ?? 'gudang';
                            $isNew = !isset($customer);
                            $defGudang  = old('visible_gudang',  $isNew ? ($scope === 'gudang')                        : (bool)($customer->visible_gudang ?? false));
                            $defJihans  = old('visible_jihans',  $isNew ? in_array($scope, ['gudang','jihans'])        : (bool)($customer->visible_jihans ?? false));
                            $defHendhys = old('visible_hendhys', $isNew ? in_array($scope, ['gudang','hendhys'])       : (bool)($customer->visible_hendhys ?? false));
                        @endphp
                        <div class="flex flex-wrap gap-6">
                            @foreach([
                                ['visible_gudang',  'Gudang Tempua',   'warehouse',  $defGudang],
                                ['visible_jihans',  "Jihan's Food",    'storefront', $defJihans],
                                ['visible_hendhys', 'Hendhys Brownies','cake',       $defHendhys],
                            ] as [$fieldName, $label, $icon, $checked])
                                <label x-data="{ on: {{ $checked ? 'true' : 'false' }} }"
                                    :class="on ? 'border-{{ $accentColor }}-600 bg-{{ $accentColor }}-50 text-{{ $accentColor }}-600' : 'border-slate-100 bg-slate-50 text-slate-400 hover:border-slate-200'"
                                    class="flex-1 min-w-[200px] flex flex-col items-center justify-center p-6 rounded-[2rem] border-2 cursor-pointer transition-all select-none">
                                    <input type="checkbox" name="{{ $fieldName }}" value="1" x-model="on" class="hidden">
                                    <span class="material-symbols-outlined text-[32px] mb-3" :class="on ? 'fill' : ''">{{ $icon }}</span>
                                    <span class="text-xs font-black uppercase tracking-widest">{{ $label }}</span>
                                    <div class="mt-4 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all" :class="on ? 'bg-{{ $accentColor }}-600 border-{{ $accentColor }}-600' : 'border-slate-200'">
                                        <span x-show="on" class="material-symbols-outlined text-white text-[16px] font-black">check</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center gap-4 pt-4 pb-12">
                <button type="submit"
                    class="flex-1 px-8 py-4 bg-{{ $accentColor }}-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-{{ $accentColor }}-700 transition-all shadow-xl shadow-{{ $accentColor }}-600/20 flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined">{{ isset($customer) ? 'save' : 'person_add' }}</span>
                    {{ isset($customer) ? 'Simpan Perubahan' : 'Daftarkan Customer Baru' }}
                </button>
                <a href="{{ route(($routePrefix ?? 'master.') . 'customers.index') }}"
                    class="px-10 py-4 bg-white border-2 border-slate-200 text-slate-500 rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-50 transition-all flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection
