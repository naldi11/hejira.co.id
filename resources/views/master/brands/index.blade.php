@extends('layouts.gudang')
@section('title', 'Brand')
@section('page-title', 'Master Data — Brand')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Brand</h2>
        <p class="text-sm text-gray-400">{{ $brands->total() }} data</p>
    </div>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Brand
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden max-w-lg">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama Brand</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Produk</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100" x-data="{}">
            @forelse($brands as $brand)
            <tr class="hover:bg-gray-50" x-data="{ editOpen: false }">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $brand->name }}</td>
                <td class="px-4 py-3 text-center text-gray-500">{{ $brand->products_count }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button @click="editOpen = !editOpen" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</button>
                        <form method="POST" action="{{ route('master.brands.destroy', $brand) }}"
                              onsubmit="return confirm('Hapus brand {{ $brand->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Hapus</button>
                        </form>
                    </div>
                    <div x-show="editOpen" x-cloak class="mt-2">
                        <form method="POST" action="{{ route('master.brands.update', $brand) }}" class="flex gap-2">
                            @csrf @method('PUT')
                            <input type="text" name="name" value="{{ $brand->name }}" required
                                   class="border border-gray-300 rounded-lg px-2 py-1 text-sm flex-1 focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            <button type="submit" class="bg-indigo-600 text-white text-xs px-3 py-1.5 rounded-lg">Simpan</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Belum ada brand.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $brands->links() }}</div>

<div id="modal-add" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
        <h3 class="font-semibold text-gray-800 mb-4">Tambah Brand</h3>
        <form method="POST" action="{{ route('master.brands.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Brand <span class="text-red-500">*</span></label>
                <input type="text" name="name" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 rounded-lg">Tambah</button>
                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')"
                        class="flex-1 border border-gray-300 text-gray-600 text-sm py-2 rounded-lg hover:bg-gray-50">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
