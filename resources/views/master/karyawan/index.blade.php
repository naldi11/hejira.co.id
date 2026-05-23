@extends($layout ?? 'layouts.jihans')
@section('title', 'Karyawan')
@section('page-title', 'Master Data — Karyawan')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

    @if(session('success'))
    <div class="mb-md bg-primary-container text-on-primary-container p-sm rounded-lg border border-primary/20 flex items-center gap-sm">
        <span class="material-symbols-outlined text-primary">check_circle</span>{{ session('success') }}
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
        <div>
            <h2 class="font-headline-md text-headline-md text-on-background">Karyawan</h2>
            <p class="font-body-md text-on-surface-variant mt-xs">{{ $karyawans->total() }} karyawan terdaftar</p>
        </div>
        <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.create') }}"
            class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">add</span>Tambah Karyawan
        </a>
    </div>

    <form method="GET" class="flex flex-wrap gap-sm mb-md">
        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama karyawan..."
                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
        </div>
        <select name="status" class="border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 focus:border-primary outline-none">
            <option value="">Semua Status</option>
            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>
        <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg">Cari</button>
    </form>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Nama</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Telepon</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant">Status</th>
                        <th class="px-md py-sm font-label-lg text-on-surface-variant text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($karyawans as $k)
                    <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors">
                        <td class="px-md py-sm font-label-lg font-bold text-on-surface">{{ $k->name }}</td>
                        <td class="px-md py-sm font-body-md text-on-surface-variant">{{ $k->phone ?? '-' }}</td>
                        <td class="px-md py-sm">
                            <span class="inline-flex items-center px-sm py-xs rounded-full font-label-sm {{ $k->is_active ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                {{ $k->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-md py-sm text-right">
                            <div class="flex items-center justify-end gap-sm">
                                <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.edit', $k) }}"
                                    class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm hover:bg-surface-container-high transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>Edit
                                </a>
                                <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'karyawan.destroy', $k) }}"
                                    onsubmit="return confirm('Hapus {{ $k->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-error rounded-lg font-label-sm hover:bg-error-container transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-[14px]">delete</span>Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-md py-lg text-center text-on-surface-variant font-body-md">Belum ada karyawan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($karyawans->hasPages())
        <div class="px-md py-sm border-t border-outline-variant">{{ $karyawans->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
