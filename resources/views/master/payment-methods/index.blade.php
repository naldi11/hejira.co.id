@extends($layout ?? 'layouts.gudang')
@section('title', 'Metode Pembayaran')
@section('page-title', 'Master Data — Metode Pembayaran')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

    @if(session('success'))
        <div class="mb-md bg-primary-container text-on-primary-container p-sm rounded-lg border border-primary/20 flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
        <div>
            <h2 class="font-headline-md text-headline-md text-on-background">Metode Pembayaran</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">{{ $methods->count() }} metode terdaftar</p>
        </div>
        <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.create') }}"
            class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Tambah Metode
        </a>
    </div>

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">Nama</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">Bank</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">No. Rekening</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">Gambar</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant">Status</th>
                        <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($methods as $method)
                    <tr class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors">
                        <td class="px-md py-sm font-label-lg font-bold text-on-surface">{{ $method->name }}</td>
                        <td class="px-md py-sm font-body-md text-on-surface-variant">{{ $method->bank_name ?? '-' }}</td>
                        <td class="px-md py-sm font-body-md text-on-surface-variant">{{ $method->account_number ?? '-' }}</td>
                        <td class="px-md py-sm">
                            @if($method->image)
                                <img src="{{ Storage::url($method->image) }}" alt="{{ $method->name }}" class="h-10 w-16 object-contain rounded border border-outline-variant">
                            @else
                                <span class="text-on-surface-variant font-body-sm">-</span>
                            @endif
                        </td>
                        <td class="px-md py-sm">
                            <span class="inline-flex items-center px-sm py-xs rounded-full font-label-sm text-label-sm {{ $method->is_active ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                {{ $method->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-md py-sm text-right">
                            <div class="flex items-center justify-end gap-sm">
                                <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.edit', $method) }}"
                                    class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm hover:bg-surface-container-high transition-colors shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>Edit
                                </a>
                                <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'payment-methods.destroy', $method) }}"
                                    onsubmit="return confirm('Hapus {{ $method->name }}?')">
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
                    <tr>
                        <td colspan="6" class="px-md py-lg text-center text-on-surface-variant font-body-md">Belum ada metode pembayaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
