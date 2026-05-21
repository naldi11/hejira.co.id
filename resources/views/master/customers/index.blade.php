@extends($layout ?? 'layouts.gudang')
@section('title', 'Customer')
@section('page-title', 'Master Data — Customer')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-background">Data Customer</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-xs">{{ $customers->total() }} customer
                    terdaftar</p>
            </div>
            <a href="{{ route(($routePrefix ?? 'master.') . 'customers.create') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant  transition-all self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Customer
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-sm mb-lg">
            <div class="relative flex-1 min-w-[200px]">
                <span
                    class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, telepon..."
                    class="w-full pl-xl pr-sm py-sm bg-surface-container-low border-b border-outline-variant focus:border-primary focus:border-b-2 focus:ring-0 font-body-md text-body-md text-on-surface placeholder-on-surface-variant rounded-t-lg transition-colors outline-none">
            </div>
            <select name="type"
                class="px-sm py-sm border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-label-lg text-label-lg focus:ring-0 focus:border-primary outline-none">
                <option value="">Semua Tipe</option>
                <option value="retail" {{ request('type') === 'retail' ? 'selected' : '' }}>Retail (Umum)</option>
                <option value="agen" {{ request('type') === 'agen' ? 'selected' : '' }}>B2B / Agen</option>
            </select>
            <select name="status"
                class="px-sm py-sm border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-label-lg text-label-lg focus:ring-0 focus:border-primary outline-none">
                <option value="">Semua Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <button type="submit"
                class="px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors  flex items-center gap-xs">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Filter
            </button>
            @if(request()->hasAny(['search', 'type', 'status']))
                <a href="{{ route(($routePrefix ?? 'master.') . 'customers.index') }}"
                    class="px-sm py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors  flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                    Reset
                </a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Kode
                            </th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Nama
                            </th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Tipe
                            </th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">
                                Telepon</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-center">
                                Status</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr
                                class="border-b border-surface-container hover:bg-surface-container-lowest/80 transition-colors">
                                <td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ $customer->code }}</td>
                                <td class="px-md py-sm">
                                    <div class="flex items-center gap-sm">
                                        <div
                                            class="w-9 h-9 rounded-full bg-primary-fixed flex items-center justify-center shrink-0">
                                            <span
                                                class="font-bold text-on-primary-fixed-variant text-sm">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                        </div>
                                        <span
                                            class="font-label-lg text-label-lg font-bold text-on-surface">{{ $customer->name }}</span>
                                    </div>
                                </td>
                                <td class="px-md py-sm">
                                    @if(strtolower($customer->type) === 'agen')
                                        <span
                                            class="inline-flex items-center px-sm py-xs rounded-full font-label-sm text-label-sm bg-tertiary-fixed text-on-tertiary-fixed-variant border border-tertiary-fixed-dim">Agen</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-sm py-xs rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary-fixed-dim">Retail</span>
                                    @endif
                                </td>
                                <td class="px-md py-sm font-body-md text-body-md text-on-surface-variant">
                                    {{ $customer->phone ?? '-' }}</td>
                                <td class="px-md py-sm text-center">
                                    @if($customer->is_active)
                                        <span
                                            class="inline-flex items-center gap-xs px-sm py-xs rounded-full font-label-sm text-label-sm bg-tertiary-fixed text-on-tertiary-fixed-variant border border-tertiary-fixed-dim">
                                            <span class="w-1.5 h-1.5 rounded-full bg-tertiary inline-block"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-xs px-sm py-xs rounded-full font-label-sm text-label-sm bg-surface-container text-on-surface-variant border border-outline-variant">
                                            <span class="w-1.5 h-1.5 rounded-full bg-outline inline-block"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-md py-sm text-right">
                                    <div class="flex items-center justify-end gap-sm">
                                        <a href="{{ route(($routePrefix ?? 'master.') . 'customers.edit', $customer) }}"
                                            class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-sm text-label-sm hover:bg-surface-container-high transition-colors  shadow-sm">
                                            <span class="material-symbols-outlined text-[14px]">edit</span>
                                            Edit
                                        </a>
                                        <form method="POST"
                                            action="{{ route(($routePrefix ?? 'master.') . 'customers.destroy', $customer) }}"
                                            onsubmit="return confirm('Hapus customer {{ $customer->name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container border border-outline-variant text-error rounded-lg font-label-sm text-label-sm hover:bg-error-container transition-colors  shadow-sm">
                                                <span class="material-symbols-outlined text-[14px]">delete</span>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-md py-xl text-center text-on-surface-variant">
                                    <span
                                        class="material-symbols-outlined text-[48px] text-outline opacity-40 mb-sm block">group</span>
                                    <p class="font-label-lg text-label-lg font-medium">Tidak ada data customer.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->hasPages())
                <div class="bg-surface-container-low border-t border-outline-variant px-md py-sm">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
