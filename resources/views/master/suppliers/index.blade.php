@extends($layout ?? 'layouts.gudang')
@section('title', 'Supplier')
@section('page-title', 'Master Data — Supplier')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Supplier</h2>
        <p class="text-sm text-gray-400">{{ $suppliers->total() }} data</p>
    </div>
    <a href="{{ route(($routePrefix ?? 'master.') . 'suppliers.create') }}"
       class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Supplier
    </a>
</div>

{{-- Filter --}}
<form method="GET" class="flex gap-2 mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, telepon..."
           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none">
        <option value="">Semua Status</option>
        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
    </select>
    <button type="submit" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg">Cari</button>
    @if(request('search') || request('status') !== null)
    <a href="{{ route(($routePrefix ?? 'master.') . 'suppliers.index') }}" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-500 text-sm px-3 py-2 rounded-lg">Reset</a>
    @endif
</form>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kontak</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Telepon</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($suppliers as $supplier)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $supplier->code }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $supplier->name }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $supplier->contact_person ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $supplier->phone ?? '-' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $supplier->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route(($routePrefix ?? 'master.') . 'suppliers.edit', $supplier) }}"
                           class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                        <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'suppliers.destroy', $supplier) }}"
                              onsubmit="return confirm('Hapus supplier {{ $supplier->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada data supplier.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $suppliers->links() }}</div>
@endsection
