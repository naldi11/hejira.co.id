@extends($layout ?? 'layouts.gudang')
@section('title', isset($user) ? 'Edit User' : 'Tambah User')
@section('page-title', isset($user) ? 'Edit Pengguna: ' . $user->name : 'Tambah Pengguna Baru')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="{{ isset($user) ? route(($routePrefix ?? 'master.') . 'users.update', $user) : route(($routePrefix ?? 'master.') . 'users.store') }}" method="POST">
            @csrf
            @if(isset($user)) @method('PUT') @endif
            
            <div class="p-6 md:p-8 space-y-6">
                
                {{-- Informasi Dasar --}}
                <div>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">Informasi Login</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
                                   class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#1e40af] focus:border-[#1e40af]">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                                   class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#1e40af] focus:border-[#1e40af]">
                            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Password {!! isset($user) ? '<span class="text-gray-400 font-normal">(Kosongkan jika tidak ingin diubah)</span>' : '<span class="text-red-500">*</span>' !!}</label>
                            <input type="password" name="password" {{ isset($user) ? '' : 'required' }} minlength="8"
                                   class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#1e40af] focus:border-[#1e40af]" placeholder="Minimal 8 karakter">
                            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Akses & Entitas --}}
                <div>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 mt-6">Hak Akses & Penempatan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5" x-data="{ entity: '{{ old('entity', $user->entity ?? 'gudang') }}' }">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Entitas Bisnis <span class="text-red-500">*</span></label>
                            <select name="entity" x-model="entity" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#1e40af] focus:border-[#1e40af]">
                                <option value="gudang">Gudang Tempua</option>
                                <option value="jihans">Jihan's Food</option>
                                <option value="hendhys">Hendhys Brownies</option>
                                <option value="owner">Owner / Manajemen</option>
                                <option value="all">Akses Semua (All)</option>
                            </select>
                            @error('entity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Role (Peran Sistem) <span class="text-red-500">*</span></label>
                            <select name="role" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#1e40af] focus:border-[#1e40af]">
                                <option value="">-- Pilih Role --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" 
                                        {{ (old('role') == $role->name || (isset($user) && $user->hasRole($role->name))) ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2" x-show="entity === 'jihans' || entity === 'hendhys'">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Penempatan Cabang <span class="text-gray-400 font-normal">(Opsional)</span></label>
                            <select name="branch_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-[#1e40af] focus:border-[#1e40af]">
                                <option value="">-- Pusat / Tidak Terikat Cabang --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }}) - {{ ucfirst($branch->type) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Kosongkan jika user bertugas di pusat entitas tersebut.</p>
                            @error('branch_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2 mt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" 
                                       {{ old('is_active', isset($user) ? $user->is_active : true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-[#1e40af] focus:ring-[#1e40af]">
                                <span class="text-sm font-medium text-gray-700">Akun Aktif (Dapat Login)</span>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-xl">
                <a href="{{ route(($routePrefix ?? 'master.') . 'users.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</a>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-[#1e40af] hover:bg-[#1e3a8a] rounded-lg transition-colors shadow-sm">
                    {{ isset($user) ? 'Simpan Perubahan' : 'Buat User Baru' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
