@extends('layouts.gudang')
@section('title', isset($branch) ? 'Edit Cabang' : 'Tambah Cabang')
@section('page-title', 'Master Data — ' . (isset($branch) ? 'Edit Cabang' : 'Tambah Cabang'))

@section('content')
<div class="max-w-lg mt-4">
    <form method="POST" action="{{ isset($branch) ? route('master.branches.update', $branch) : route('master.branches.store') }}"
          class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        @csrf
        @if(isset($branch)) @method('PUT') @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Cabang <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code', $branch->code ?? '') }}" required
                       placeholder="cth: HND-CB3"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('code') border-red-400 @enderror">
                @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    <option value="cabang" {{ old('type', $branch->type ?? 'cabang') === 'cabang' ? 'selected' : '' }}>Cabang</option>
                    <option value="pusat"  {{ old('type', $branch->type ?? '') === 'pusat'  ? 'selected' : '' }}>Pusat</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Cabang <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $branch->name ?? '') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('name') border-red-400 @enderror">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $branch->phone ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
            <div>
                <label class="flex items-center gap-2 cursor-pointer mt-6">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 rounded">
                    <span class="text-sm font-medium text-gray-700">Aktif</span>
                </label>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea name="address" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('address', $branch->address ?? '') }}</textarea>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
                {{ isset($branch) ? 'Simpan Perubahan' : 'Tambah Cabang' }}
            </button>
            <a href="{{ route('master.branches.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
