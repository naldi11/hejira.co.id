@extends('layouts.hendhys')
@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi')

@section('content')
<div class="h-full w-full p-margin-mobile md:p-margin-desktop bg-surface-container-lowest overflow-y-auto">

    <!-- Page Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-lg gap-md">
        <div>
            <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background mb-xs">Riwayat Transaksi</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Pantau dan kelola penjualan harian.</p>
        </div>
        <div class="flex items-center gap-sm">
            <div class="flex items-center bg-surface-container-low rounded-lg p-xs border border-outline-variant">
                <button class="flex items-center gap-xs px-sm py-sm text-on-surface font-label-lg text-label-lg rounded-md hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-[18px]">calendar_today</span>
                    Hari Ini
                </button>
            </div>
            <button class="flex items-center justify-center bg-surface-container-low text-primary p-sm rounded-lg hover:bg-surface-container transition-colors border border-outline-variant shadow-sm" title="Export">
                <span class="material-symbols-outlined">download</span>
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md mb-lg">
        <div class="bg-surface p-md rounded-xl shadow-sm border border-outline-variant flex flex-col justify-between h-[120px]">
            <div class="flex items-center gap-sm text-on-surface-variant font-label-lg text-label-lg">
                <span class="material-symbols-outlined text-primary-container">point_of_sale</span>
                Total Penjualan Hari Ini
            </div>
            <div class="font-headline-md text-headline-md text-on-background">
                Rp {{ number_format(\App\Models\HendhysTransaction::where('branch_id', auth()->user()->branch_id)->whereDate('created_at', today())->where('status', 'sukses')->sum('grand_total'), 0, ',', '.') }}
            </div>
        </div>
        <div class="bg-surface p-md rounded-xl shadow-sm border border-outline-variant flex flex-col justify-between h-[120px]">
            <div class="flex items-center gap-sm text-on-surface-variant font-label-lg text-label-lg">
                <span class="material-symbols-outlined text-secondary-container">receipt</span>
                Jumlah Transaksi
            </div>
            <div class="font-headline-md text-headline-md text-on-background">
                {{ \App\Models\HendhysTransaction::where('branch_id', auth()->user()->branch_id)->whereDate('created_at', today())->count() }}
                <span class="font-body-md text-body-md text-on-surface-variant ml-xs">struk</span>
            </div>
        </div>
        <div class="bg-surface p-md rounded-xl shadow-sm border border-outline-variant flex flex-col justify-between h-[120px]">
            <div class="flex items-center gap-sm text-on-surface-variant font-label-lg text-label-lg">
                <span class="material-symbols-outlined text-error">cancel</span>
                Dibatalkan
            </div>
            <div class="font-headline-md text-headline-md text-on-background">
                {{ \App\Models\HendhysTransaction::where('branch_id', auth()->user()->branch_id)->whereDate('created_at', today())->where('status', 'batal')->count() }}
                <span class="font-body-md text-body-md text-on-surface-variant ml-xs">struk</span>
            </div>
        </div>
    </div>

    <!-- Transaction Table / List -->
    <div class="bg-surface rounded-xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="p-md font-label-lg text-label-lg text-on-surface-variant font-semibold">ID Transaksi</th>
                        <th class="p-md font-label-lg text-label-lg text-on-surface-variant font-semibold">Waktu</th>
                        <th class="p-md font-label-lg text-label-lg text-on-surface-variant font-semibold">Kasir</th>
                        <th class="p-md font-label-lg text-label-lg text-on-surface-variant font-semibold">Metode</th>
                        <th class="p-md font-label-lg text-label-lg text-on-surface-variant font-semibold">Total Bayar</th>
                        <th class="p-md font-label-lg text-label-lg text-on-surface-variant font-semibold">Status</th>
                        <th class="p-md font-label-lg text-label-lg text-on-surface-variant font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="font-body-md text-body-md">
                    @forelse($transactions as $t)
                        <tr class="border-b border-surface-container hover:bg-surface-container-lowest transition-colors group cursor-pointer {{ $t->status === 'batal' ? 'bg-error-container/20' : '' }}">
                            <td class="p-md font-medium {{ $t->status === 'batal' ? 'text-on-surface-variant opacity-70 line-through' : 'text-primary-container' }}">#{{ $t->transaction_number }}</td>
                            <td class="p-md text-on-surface {{ $t->status === 'batal' ? 'opacity-70' : '' }}">{{ $t->created_at->format('H:i') }} <span class="text-xs text-on-surface-variant block">{{ $t->created_at->format('d M') }}</span></td>
                            <td class="p-md text-on-surface {{ $t->status === 'batal' ? 'opacity-70' : '' }}">{{ $t->creator ? $t->creator->name : 'Sistem' }}</td>
                            <td class="p-md {{ $t->status === 'batal' ? 'opacity-70' : '' }}">
                                <div class="flex items-center gap-xs text-on-surface capitalize">
                                    <span class="material-symbols-outlined text-[18px] text-on-surface-variant">
                                        {{ $t->payment_method === 'cash' ? 'payments' : ($t->payment_method === 'transfer' ? 'qr_code_scanner' : 'credit_card') }}
                                    </span>
                                    {{ $t->payment_method }}
                                </div>
                            </td>
                            <td class="p-md font-medium text-on-background {{ $t->status === 'batal' ? 'opacity-70 line-through' : '' }}">Rp {{ number_format($t->grand_total, 0, ',', '.') }}</td>
                            <td class="p-md">
                                @if($t->status === 'batal')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-error-container text-on-error-container border border-error/20">Dibatalkan</span>
                                @elseif($t->status === 'sukses')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-tertiary-fixed text-on-tertiary-fixed-variant border border-tertiary/20">Berhasil</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary-fixed text-on-secondary-fixed-variant border border-secondary/20 capitalize">{{ $t->status }}</span>
                                @endif
                            </td>
                            <td class="p-md text-right">
                                @if($t->status === 'sukses')
                                    <div class="flex items-center justify-end gap-xs">
                                        <a href="{{ route('hendhys.pos.receipt', $t->id) }}" class="inline-flex items-center gap-xs text-primary hover:text-primary-container bg-surface-container-low hover:bg-surface-container px-sm py-xs rounded-md font-label-sm text-label-sm transition-colors border border-outline-variant shadow-sm">
                                            <span class="material-symbols-outlined text-[16px]">receipt</span>
                                            Struk
                                        </a>
                                        <a href="{{ route('hendhys.transactions.show', $t->id) }}" class="inline-flex items-center gap-xs text-secondary hover:text-secondary-container bg-surface-container-low hover:bg-surface-container px-sm py-xs rounded-md font-label-sm text-label-sm transition-colors border border-outline-variant shadow-sm">
                                            <span class="material-symbols-outlined text-[16px]">print</span>
                                            Faktur
                                        </a>
                                    </div>
                                @else
                                    <button class="text-on-surface-variant opacity-50 bg-surface-container px-sm py-xs rounded-md font-label-sm text-label-sm border border-outline-variant cursor-not-allowed flex items-center gap-xs ml-auto" disabled>
                                        <span class="material-symbols-outlined text-[16px]">print_disabled</span>
                                        Cetak
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-xl text-center text-on-surface-variant bg-surface-container-lowest">
                                <span class="material-symbols-outlined text-[48px] text-outline opacity-50 mb-sm block">receipt_long</span>
                                <p class="font-headline-md text-title-lg font-medium">Belum ada riwayat transaksi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($transactions->hasPages())
        <div class="bg-surface-container-low p-sm border-t border-outline-variant text-on-surface-variant font-label-sm text-label-sm">
            {{ $transactions->links('vendor.pagination.tailwind') }}
        </div>
        @endif
    </div>
</div>
@endsection
