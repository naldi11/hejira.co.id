@extends($layout ?? 'layouts.gudang')
@section('title', isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier')
@section('page-title', 'Master Data — ' . (isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier'))

@section('content')
    <div class="max-w-4xl mx-auto">
        <form method="POST"
            action="{{ isset($supplier) ? route(($routePrefix ?? 'master.') . 'suppliers.update', $supplier) : route(($routePrefix ?? 'master.') . 'suppliers.store') }}"
            class="space-y-8">
            @csrf
            @if(isset($supplier)) @method('PUT') @endif

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-10 py-8 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-900 font-headline uppercase tracking-widest">Informasi Supplier</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-tighter">Detail kontak dan operasional mitra</p>
                </div>
                
                <div class="p-10 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Nama Supplier --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Nama Supplier <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}" required
                                placeholder="Masukkan nama perusahaan/supplier..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none w-full font-bold text-slate-900">
                            @error('name') <p class="text-rose-500 text-[10px] font-black uppercase tracking-widest mt-2 ml-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Contact Person --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Contact Person</label>
                            <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}"
                                placeholder="Nama orang yang bisa dihubungi..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Nomor Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}"
                                placeholder="Contoh: 08123456789"
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>

                        {{-- Email --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Email</label>
                            <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}"
                                placeholder="alamat@email.com"
                                class="bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none w-full font-bold text-slate-900">
                        </div>

                        {{-- Address --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Alamat Lengkap</label>
                            <textarea name="address" rows="3" placeholder="Masukkan alamat lengkap supplier..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-3xl px-6 py-4 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none w-full font-medium text-slate-700">{{ old('address', $supplier->address ?? '') }}</textarea>
                        </div>

                        {{-- Notes --}}
                        <div class="md:col-span-2">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Catatan Tambahan</label>
                            <textarea name="notes" rows="2" placeholder="Catatan internal mengenai supplier ini..."
                                class="bg-slate-50 border-2 border-slate-100 rounded-3xl px-6 py-4 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none w-full font-medium text-slate-700">{{ old('notes', $supplier->notes ?? '') }}</textarea>
                        </div>

                        {{-- Is Active --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-4 block">Status Kemitraan</label>
                            <div class="flex items-center gap-6">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="radio" name="is_active" value="1" {{ old('is_active', $supplier->is_active ?? 1) == 1 ? 'checked' : '' }} class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                    <span class="text-sm font-bold text-slate-600 group-hover:text-slate-900 transition-colors">Aktif</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="radio" name="is_active" value="0" {{ old('is_active', $supplier->is_active ?? 1) == 0 ? 'checked' : '' }} class="w-5 h-5 text-rose-600 focus:ring-rose-500 border-slate-300">
                                    <span class="text-sm font-bold text-slate-600 group-hover:text-slate-900 transition-colors">Nonaktif</span>
                                </label>
                            </div>
                        </div>

                        {{-- Entity Scope --}}
                        <div>
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1 mb-2 block">Cakupan Entitas</label>
                            <select name="entity_scope" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-3.5 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none font-bold text-slate-900 appearance-none cursor-pointer">
                                <option value="all" {{ old('entity_scope', $supplier->entity_scope ?? 'all') === 'all' ? 'selected' : '' }}>Semua Entitas</option>
                                <option value="gudang" {{ old('entity_scope', $supplier->entity_scope ?? '') === 'gudang' ? 'selected' : '' }}>Gudang Tempua</option>
                                <option value="jihans" {{ old('entity_scope', $supplier->entity_scope ?? '') === 'jihans' ? 'selected' : '' }}>Jihan's Food</option>
                                <option value="hendhys" {{ old('entity_scope', $supplier->entity_scope ?? '') === 'hendhys' ? 'selected' : '' }}>Hendhys Brownies</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center gap-4 pt-4 pb-12">
                <button type="submit"
                    class="flex-1 px-8 py-4 bg-indigo-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20 flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined">{{ isset($supplier) ? 'save' : 'add_circle' }}</span>
                    {{ isset($supplier) ? 'Simpan Perubahan' : 'Daftarkan Supplier' }}
                </button>
                <a href="{{ route(($routePrefix ?? 'master.') . 'suppliers.index') }}"
                    class="px-10 py-4 bg-white border-2 border-slate-200 text-slate-500 rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-50 transition-all flex items-center justify-center gap-3">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection
