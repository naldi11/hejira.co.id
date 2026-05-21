@extends('layouts.hendhys')
@section('title', auth()->user()->branch->type === 'pusat' ? 'Request Cabang' : 'Request Stok')
@section('page-title', auth()->user()->branch->type === 'pusat' ? 'Daftar Request dari Cabang' : 'Daftar Pengajuan Request Stok')

@section('content')
    @php
        $isPusat = auth()->user()->branch->type === 'pusat';
    @endphp

    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface space-y-md">
        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant overflow-hidden">
            <div
                class="p-md bg-surface-container-low border-b border-outline-variant flex flex-col sm:flex-row sm:items-center justify-between gap-md">
                <form action="{{ route('hendhys.branch-requests.index') }}" method="GET"
                    class="flex flex-wrap items-center gap-sm w-full sm:w-auto">
                    <select name="status"
                        class="px-sm py-sm border border-outline-variant rounded-lg font-body-sm text-body-sm focus:ring-0 focus:border-primary outline-none bg-surface-container-lowest text-on-surface">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    <div class="relative min-w-[200px]">
                        <span
                            class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Request..."
                            class="w-full pl-xl pr-sm py-sm border border-outline-variant rounded-lg focus:ring-0 focus:border-primary outline-none bg-surface-container-lowest text-on-surface font-body-sm text-sm">
                    </div>
                    <button type="submit"
                        class="bg-primary text-on-primary px-md py-sm rounded-lg font-label-lg hover:bg-on-primary-fixed-variant transition-colors shadow-sm">Filter</button>
                    @if(request()->anyFilled(['status', 'search']))
                        <a href="{{ route('hendhys.branch-requests.index') }}"
                            class="text-label-sm text-error hover:text-error/80 ml-xs">Reset</a>
                    @endif
                </form>
                @if(!$isPusat)
                    <div class="shrink-0">
                        <a href="{{ route('hendhys.branch-requests.create') }}"
                            class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Buat Request Baru
                        </a>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">
                                TANGGAL</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">NO.
                                REQUEST</th>
                            @if($isPusat)
                                <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">CABANG
                            PEMOHON</th> @endif
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">STATUS
                            </th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">
                                AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @forelse($requests as $req)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-md py-sm text-on-surface-variant font-mono text-[13px] whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}
                                </td>
                                <td class="px-md py-sm font-bold text-on-surface">{{ $req->request_number }}</td>
                                @if($isPusat)
                                <td class="px-md py-sm font-semibold text-on-surface">{{ $req->branch->name }}</td> @endif
                                <td class="px-md py-sm">
                                    @if($req->status == 'pending')
                                        <span
                                            class="px-xs py-[2px] rounded uppercase text-[11px] font-bold tracking-wider w-max bg-surface-container-high text-on-surface-variant border border-outline-variant">PENDING</span>
                                    @elseif($req->status == 'completed')
                                        <span
                                            class="px-xs py-[2px] rounded uppercase text-[11px] font-bold tracking-wider w-max bg-tertiary-container text-on-tertiary-container">COMPLETED</span>
                                    @elseif($req->status == 'partial')
                                        <span
                                            class="px-xs py-[2px] rounded uppercase text-[11px] font-bold tracking-wider w-max bg-primary-container text-on-primary-container">PARTIAL</span>
                                    @else
                                        <span
                                            class="px-xs py-[2px] rounded uppercase text-[11px] font-bold tracking-wider w-max bg-error-container text-on-error-container">REJECTED</span>
                                    @endif
                                </td>
                                <td class="px-md py-sm text-right whitespace-nowrap">
                                    @if($isPusat && $req->status === 'pending')
                                        <a href="{{ route('hendhys.transfer-to-branch.create', ['request_id' => $req->id]) }}"
                                            class="inline-flex py-1 px-sm bg-primary text-on-primary hover:bg-on-primary-fixed-variant rounded font-label-sm text-[12px] font-bold transition-colors mr-2">Proses
                                            Pengiriman</a>
                                    @endif
                                    <a href="{{ route('hendhys.branch-requests.show', $req->id) }}"
                                        class="inline-flex py-1 px-sm border border-outline border-opacity-50 text-on-surface hover:bg-surface-container rounded font-label-sm text-[12px] font-bold transition-colors">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isPusat ? 5 : 4 }}" class="p-xl text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-xs block">assignment</span>
                                    <p class="font-body-md text-sm">Belum ada data request stok.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
                <div class="p-md border-t border-outline-variant">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection