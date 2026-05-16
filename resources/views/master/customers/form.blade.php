@extends('layouts.gudang')
@section('title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')
@section('page-title', 'Master Data — ' . (isset($customer) ? 'Edit Customer' : 'Tambah Customer'))

@section('content')
<div class="max-w-2xl mt-4">
    <form method="POST" action="{{ isset($customer) ? route('master.customers.update', $customer) : route('master.customers.store') }}"
          class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        @csrf
        @if(isset($customer)) @method('PUT') @endif

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Customer <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('name') border-red-400 @enderror">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    <option value="retail" {{ old('type', $customer->type ?? 'retail') === 'retail' ? 'selected' : '' }}>Retail</option>
                    <option value="agen"   {{ old('type', $customer->type ?? '') === 'agen' ? 'selected' : '' }}>Agen</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('email') border-red-400 @enderror">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea name="address" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('address', $customer->address ?? '') }}</textarea>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('notes', $customer->notes ?? '') }}</textarea>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 rounded">
                    <span class="text-sm font-medium text-gray-700">Aktif</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
                {{ isset($customer) ? 'Simpan Perubahan' : 'Tambah Customer' }}
            </button>
            <a href="{{ route('master.customers.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
