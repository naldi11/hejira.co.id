@extends($layout ?? 'layouts.gudang')
@section('title', 'Cabang')
@section('page-title', 'Master Data — Cabang')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Cabang Hendhys</h2>
        <p class="text-sm text-gray-400">{{ $branches->total() }} cabang</p>
    </div>
    <a href="{{ route(($routePrefix ?? 'master.') . 'branches.create') }}"
       class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Cabang
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden max-w-2xl">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipe</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Telepon</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">User</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($branches as $branch)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $branch->code }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $branch->name }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $branch->type === 'pusat' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ ucfirst($branch->type) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $branch->phone ?? '-' }}</td>
                <td class="px-4 py-3 text-center text-gray-500">{{ $branch->users_count }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $branch->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route(($routePrefix ?? 'master.') . 'branches.edit', $branch) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                        <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'branches.destroy', $branch) }}"
                              onsubmit="return confirm('Hapus cabang {{ $branch->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada cabang.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $branches->links() }}</div>
@endsection
