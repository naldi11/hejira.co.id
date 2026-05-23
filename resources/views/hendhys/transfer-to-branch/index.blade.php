@extends('layouts.hendhys')
@section('title', 'Distribusi ke Cabang')
@section('page-title', 'Distribusi Barang ke Cabang')

@section('content')
    @php
        $isPusat = auth()->user()->branch->type === 'pusat';
    @endphp

    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface space-y-md">
        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant overflow-hidden">
            <div
                class="p-md bg-surface-container-low border-b border-outline-variant flex flex-col sm:flex-row sm:items-center justify-between gap-md">
                <form action="{{ route('hendhys.transfer-to-branch.index') }}" method="GET"
                    class="flex flex-wrap items-center gap-sm w-full sm:w-auto">
                    <select name="status"
                        class="pl-sm pr-8 py-sm border border-outline-variant rounded-lg font-body-sm text-body-sm focus:ring-0 focus:border-primary outline-none bg-surface-container-lowest text-on-surface">
                        <option value="">Semua Status</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Dalam Perjalanan (Sent)
                        </option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Diterima (Received)
                        </option>
                    </select>
                    <div class="relative min-w-[200px]">
                        <span
                            class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Pengiriman..."
                            class="w-full pl-xl pr-sm py-sm border border-outline-variant rounded-lg focus:ring-0 focus:border-primary outline-none bg-surface-container-lowest text-on-surface font-body-sm text-sm">
                    </div>
                    <button type="submit"
                        class="bg-primary text-on-primary px-md py-sm rounded-lg font-label-lg hover:bg-on-primary-fixed-variant transition-colors shadow-sm">Filter</button>
                    @if(request()->anyFilled(['status', 'search']))
                        <a href="{{ route('hendhys.transfer-to-branch.index') }}"
                            class="text-label-sm text-error hover:text-error/80 ml-xs">Reset</a>
                    @endif
                </form>

                @if($isPusat)
                    <div class="shrink-0">
                        <a href="{{ route('hendhys.transfer-to-branch.create') }}"
                            class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Distribusikan Stok
                        </a>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">TGL.
                                PENGIRIMAN</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">NO.
                                PENGIRIMAN</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">NO.
                                REQUEST</th>
                            @if($isPusat)
                                <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">CABANG
                            TUJUAN</th> @endif
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">STATUS
                            </th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @forelse($transfers as $trf)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-md py-sm text-on-surface-variant font-mono text-[13px] whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($trf->date)->format('d M Y') }}</td>
                                <td class="px-md py-sm font-bold text-on-surface">{{ $trf->transfer_number }}</td>
                                <td class="px-md py-sm font-medium text-on-surface-variant">
                                    {{ $trf->branchRequest->request_number ?? '-' }}</td>
                                @if($isPusat)
                                <td class="px-md py-sm font-semibold text-on-surface">{{ $trf->branch->name }}</td> @endif
                                <td class="px-md py-sm">
                                    @if($trf->status == 'sent')
                                        <span
                                            class="px-xs py-[2px] rounded uppercase text-[11px] font-bold tracking-wider w-max inline-flex items-center gap-1 bg-surface-container-high text-on-surface-variant border border-outline-variant">
                                            <span class="material-symbols-outlined text-[14px]">local_shipping</span>
                                            Dikirim
                                        </span>
                                    @elseif($trf->status == 'received')
                                        <span
                                            class="px-xs py-[2px] rounded uppercase text-[11px] font-bold tracking-wider w-max inline-flex items-center gap-1 bg-tertiary-container text-on-tertiary-container">
                                            <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                            Diterima
                                        </span>
                                    @endif
                                </td>
                                <td class="px-md py-sm text-right">
                                    <a href="{{ route('hendhys.transfer-to-branch.show', $trf->id) }}"
                                        class="inline-flex py-1 px-sm border border-outline border-opacity-50 text-on-surface hover:bg-surface-container rounded font-label-sm text-[12px] font-bold transition-colors">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isPusat ? 6 : 5 }}" class="p-xl text-center text-on-surface-variant">
                                    <span
                                        class="material-symbols-outlined text-4xl text-outline mb-xs block">local_shipping</span>
                                    <p class="font-body-md text-sm">Belum ada data distribusi barang.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transfers->hasPages())
                <div class="p-md border-t border-outline-variant">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection