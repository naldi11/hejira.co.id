@extends('layouts.hendhys')
@section('title', 'Detail Request Cabang')
@section('page-title', 'Detail Request: ' . $branchRequest->request_number)

@push('styles')
<style>
@media print {
    aside, nav, header,
    [class*="sidebar"], [class*="navbar"], [class*="topbar"],
    .no-print { display: none !important; }
    body { background: white !important; margin: 0 !important; padding: 0 !important; }
    main, [class*="content"], #app {
        margin: 0 !important; padding: 0 !important;
        width: 100% !important; max-width: 100% !important;
    }
    .print-card {
        max-width: 100% !important; margin: 0 !important;
        box-shadow: none !important; border: 1.5px solid #000 !important;
        border-radius: 0 !important;
    }
    @page { margin: 10mm; size: A4 portrait; }
}
</style>
@endpush

@section('content')
@php
    $isPusat = auth()->user()->branch->type === 'pusat';
    $statusConfig = match($branchRequest->status) {
        'pending'   => ['label' => 'Pending',   'bg' => 'bg-secondary-fixed',      'text' => 'text-on-secondary-fixed-variant', 'dot' => 'bg-secondary',  'icon' => 'schedule'],
        'completed' => ['label' => 'Selesai',   'bg' => 'bg-tertiary-fixed',       'text' => 'text-on-tertiary-fixed-variant',  'dot' => 'bg-tertiary',   'icon' => 'check_circle'],
        'partial'   => ['label' => 'Parsial',   'bg' => 'bg-primary-fixed',        'text' => 'text-on-primary-fixed-variant',   'dot' => 'bg-primary',    'icon' => 'remove_done'],
        default     => ['label' => 'Ditolak',   'bg' => 'bg-error-container',      'text' => 'text-on-error-container',         'dot' => 'bg-error',      'icon' => 'cancel'],
    };
@endphp

<div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full space-y-md">

    {{-- Print-only header --}}
    <div class="hidden print:block mb-6 pb-4 border-b-2 border-gray-800">
        <h1 class="text-2xl font-bold uppercase tracking-wide">Faktur Permintaan Stok Cabang</h1>
        <p class="text-sm text-gray-600 mt-1">Hendhys Bakery — Sistem Bisnis Terpadu</p>
        <p class="text-sm text-gray-600">Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
    </div>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md">
        <div class="flex items-center gap-sm">
            <a href="{{ route('hendhys.branch-requests.index') }}"
                class="flex items-center justify-center w-9 h-9 rounded-full bg-surface-container border border-outline-variant text-on-surface-variant hover:bg-surface-container-high transition-colors active:scale-95">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <h2 class="font-headline-sm text-headline-sm font-bold text-on-surface">{{ $branchRequest->request_number }}</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ \Carbon\Carbon::parse($branchRequest->date)->translatedFormat('d F Y') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-sm flex-wrap">
            <button onclick="window.print()"
                class="no-print inline-flex items-center gap-xs px-md py-sm bg-surface-container border border-outline-variant text-on-surface rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors active:scale-95">
                <span class="material-symbols-outlined text-[18px]">print</span>
                Cetak Faktur
            </button>

            {{-- Status Badge --}}
            <span class="inline-flex items-center gap-xs px-sm py-xs rounded-full font-label-sm text-label-sm font-bold {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }} border border-outline-variant/30">
                <span class="w-2 h-2 rounded-full {{ $statusConfig['dot'] }} inline-block"></span>
                {{ $statusConfig['label'] }}
            </span>

            @if($isPusat && $branchRequest->status === 'pending')
                <a href="{{ route('hendhys.transfer-to-branch.create', ['request_id' => $branchRequest->id]) }}"
                    class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg font-bold shadow-sm hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all">
                    <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                    Proses Distribusi
                </a>
            @endif
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">

        {{-- Dari Cabang --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md flex items-start gap-sm shadow-sm">
            <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-[20px]">store</span>
            </div>
            <div class="min-w-0">
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Dari Cabang</p>
                <p class="font-label-lg text-label-lg font-bold text-on-surface truncate">{{ $branchRequest->branch->name }}</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-xs">{{ $branchRequest->creator->name }}</p>
            </div>
        </div>

        {{-- Tanggal --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md flex items-start gap-sm shadow-sm">
            <div class="w-10 h-10 rounded-full bg-secondary-fixed flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-secondary text-[20px]">calendar_today</span>
            </div>
            <div class="min-w-0">
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Tanggal Request</p>
                <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ \Carbon\Carbon::parse($branchRequest->date)->translatedFormat('d F Y') }}</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-xs">{{ \Carbon\Carbon::parse($branchRequest->date)->diffForHumans() }}</p>
            </div>
        </div>

        {{-- Catatan --}}
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md flex items-start gap-sm shadow-sm">
            <div class="w-10 h-10 rounded-full bg-tertiary-fixed flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-tertiary text-[20px]">notes</span>
            </div>
            <div class="min-w-0">
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Catatan</p>
                <p class="font-body-md text-body-md text-on-surface">{{ $branchRequest->notes ?: '—' }}</p>
            </div>
        </div>

    </div>

    {{-- Detail Tabel --}}
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">

        {{-- Table Header --}}
        <div class="px-md py-sm border-b border-outline-variant bg-surface-container-low flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary text-[20px]">inventory_2</span>
            <h3 class="font-label-lg text-label-lg font-bold text-on-surface">Rincian Stok Diminta</h3>
            <span class="ml-auto font-label-sm text-label-sm text-on-surface-variant">
                {{ $branchRequest->details->count() }} item
            </span>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant font-semibold uppercase tracking-wider w-12 text-center">No</th>
                        <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant font-semibold uppercase tracking-wider">Nama Produk</th>
                        <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant font-semibold uppercase tracking-wider text-right">Qty Diminta</th>
                        <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant font-semibold uppercase tracking-wider text-right">Qty Disetujui</th>
                        <th class="px-md py-sm font-label-sm text-label-sm text-on-surface-variant font-semibold uppercase tracking-wider">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container">
                    @forelse($branchRequest->details as $index => $detail)
                    <tr class="hover:bg-surface-container/50 transition-colors">
                        <td class="px-md py-sm text-center font-label-sm text-label-sm text-on-surface-variant">{{ $index + 1 }}</td>
                        <td class="px-md py-sm">
                            <p class="font-label-lg text-label-lg font-bold text-on-surface">{{ $detail->product->name }}</p>
                        </td>
                        <td class="px-md py-sm text-right">
                            <span class="font-label-lg text-label-lg font-bold text-on-surface">{{ number_format((float) $detail->quantity_requested, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-md py-sm text-right">
                            @if($detail->quantity_approved === null)
                                <span class="inline-flex items-center gap-xs px-sm py-xs rounded-full font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-fixed-variant">
                                    <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse inline-block"></span>
                                    Menunggu
                                </span>
                            @elseif((float)$detail->quantity_approved >= (float)$detail->quantity_requested)
                                <span class="font-label-lg text-label-lg font-bold text-tertiary">{{ number_format((float) $detail->quantity_approved, 0, ',', '.') }}</span>
                            @elseif((float)$detail->quantity_approved > 0)
                                <span class="font-label-lg text-label-lg font-bold text-primary">{{ number_format((float) $detail->quantity_approved, 0, ',', '.') }}</span>
                                <span class="font-label-sm text-label-sm text-on-surface-variant ml-xs">(parsial)</span>
                            @else
                                <span class="font-label-lg text-label-lg font-bold text-error">0</span>
                                <span class="font-label-sm text-label-sm text-error ml-xs">(ditolak)</span>
                            @endif
                        </td>
                        <td class="px-md py-sm">
                            <span class="font-label-sm text-label-sm text-on-surface-variant font-bold">{{ $detail->unit->code }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-md py-xl text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[40px] opacity-30 mb-sm block">inventory_2</span>
                            <p class="font-label-lg text-label-lg">Tidak ada item.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tanda Tangan (cetak) --}}
        <div class="mt-8 pt-6 border-t border-outline-variant grid grid-cols-2 gap-8 text-center text-sm no-print">
            <div>
                <p class="text-on-surface-variant mb-1">Pihak Peminta (Cabang)</p>
                <div class="h-16 border-b border-outline-variant mx-8 mb-2"></div>
                <p class="font-bold text-on-surface">{{ $branchRequest->creator->name }}</p>
                <p class="text-xs text-on-surface-variant">{{ $branchRequest->branch->name }}</p>
            </div>
            <div>
                <p class="text-on-surface-variant mb-1">Pihak Menyetujui (Pusat)</p>
                <div class="h-16 border-b border-outline-variant mx-8 mb-2"></div>
                <p class="font-bold text-on-surface">( ........................ )</p>
                <p class="text-xs text-on-surface-variant">Kasir Hendhys Pusat</p>
            </div>
        </div>
        <div class="mt-8 pt-6 border-t border-outline-variant grid grid-cols-2 gap-8 text-center text-sm hidden print:grid">
            <div>
                <p class="text-gray-600 mb-1">Pihak Peminta (Cabang)</p>
                <div class="h-16 border-b border-gray-400 mx-8 mb-2"></div>
                <p class="font-bold text-gray-800">{{ $branchRequest->creator->name }}</p>
                <p class="text-xs text-gray-500">{{ $branchRequest->branch->name }}</p>
            </div>
            <div>
                <p class="text-gray-600 mb-1">Pihak Menyetujui (Pusat)</p>
                <div class="h-16 border-b border-gray-400 mx-8 mb-2"></div>
                <p class="font-bold text-gray-800">( ........................ )</p>
                <p class="text-xs text-gray-500">Kasir Hendhys Pusat</p>
            </div>
        </div>

    </div>

</div>
@endsection
