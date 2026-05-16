@extends('layouts.gudang')
@section('title', 'Produk')
@section('page-title', 'Master Data — Produk')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Produk</h2>
        <p class="text-sm text-gray-400">{{ $products->total() }} data</p>
    </div>
    <a href="{{ route('master.products.create') }}"
       class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Produk
    </a>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, barcode..."
           class="flex-1 min-w-48 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
    <select name="jenis" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">Semua Jenis</option>
        @foreach(['frozen','tortilla','bakery','bahan_baku','aksesoris','minuman','snack','selai','property','lainnya'] as $j)
        <option value="{{ $j }}" {{ request('jenis') === $j ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$j)) }}</option>
        @endforeach
    </select>
    <select name="entity_scope" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">Semua Entitas</option>
        <option value="gudang"  {{ request('entity_scope') === 'gudang'  ? 'selected' : '' }}>Gudang</option>
        <option value="jihans"  {{ request('entity_scope') === 'jihans'  ? 'selected' : '' }}>Jihan's</option>
        <option value="hendhys" {{ request('entity_scope') === 'hendhys' ? 'selected' : '' }}>Hendhys</option>
        <option value="all"     {{ request('entity_scope') === 'all'     ? 'selected' : '' }}>Semua</option>
    </select>
    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">Semua Status</option>
        <option value="active"       {{ request('status') === 'active'       ? 'selected' : '' }}>Aktif</option>
        <option value="discontinued" {{ request('status') === 'discontinued' ? 'selected' : '' }}>Discontinue</option>
    </select>
    <button type="submit" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg">Cari</button>
    @if(request()->hasAny(['search','jenis','entity_scope','status']))
    <a href="{{ route('master.products.index') }}" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-500 text-sm px-3 py-2 rounded-lg">Reset</a>
    @endif
</form>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Jenis</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">HPP</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Harga Jual</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $product->code }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $product->name }}</p>
                    <p class="text-xs text-gray-400">{{ $product->unit->abbreviation }} · {{ $product->brand->name ?? '-' }}</p>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $product->category->name }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                        {{ ucwords(str_replace('_',' ', $product->jenis)) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($product->hpp, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-right font-medium text-gray-800">{{ number_format($product->selling_price, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $product->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                        {{ $product->status === 'active' ? 'Aktif' : 'Discontinue' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('master.products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                        <form method="POST" action="{{ route('master.products.destroy', $product) }}"
                              onsubmit="return confirm('Hapus produk {{ $product->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Tidak ada data produk.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
