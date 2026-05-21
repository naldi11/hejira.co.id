@extends('layouts.hendhys')
@section('title', 'Kartu Stok')
@section('page-title', 'Mutasi Stok ' . (auth()->user()->branch->type === 'pusat' ? 'Pusat' : 'Cabang'))

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full overflow-y-auto h-full bg-surface">

        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant overflow-hidden">
            <div
                class="p-md bg-surface-container-low border-b border-outline-variant flex flex-col md:flex-row md:items-center justify-between gap-md">
                <div class="flex items-center gap-sm">
                    <a href="{{ route('hendhys.stock.index') }}"
                        class="w-8 h-8 rounded-full bg-surface border border-outline-variant flex items-center justify-center text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    </a>
                    <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface">Kartu Stok (Log Pergerakan)</h3>
                </div>
                <form action="{{ route('hendhys.stock.movements') }}" method="GET"
                    class="flex flex-wrap items-center gap-sm w-full md:w-auto">
                    <select name="product_id"
                        class="pl-sm pr-md py-sm font-body-sm text-body-sm border border-outline-variant rounded-lg focus:ring-0 focus:border-primary outline-none bg-surface-container-lowest text-on-surface">
                        <option value="">- Pilih Produk -</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-xs">
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="font-body-sm text-body-sm border border-outline-variant rounded-lg focus:ring-0 focus:border-primary outline-none bg-surface-container-lowest text-on-surface px-sm py-sm">
                        <span class="text-on-surface-variant text-label-sm font-medium uppercase mt-[2px]">s.d</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="font-body-sm text-body-sm border border-outline-variant rounded-lg focus:ring-0 focus:border-primary outline-none bg-surface-container-lowest text-on-surface px-sm py-sm">
                    </div>

                    <button type="submit"
                        class="bg-primary text-on-primary px-lg py-sm rounded-lg font-label-lg hover:bg-on-primary-fixed-variant transition-colors shadow-sm flex items-center">Filter</button>
                    @if(request()->anyFilled(['product_id', 'date_from', 'date_to']))
                        <a href="{{ route('hendhys.stock.movements') }}"
                            class="text-label-sm text-error hover:text-error/80 px-xs mt-[2px]">Reset</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold w-40">
                                Waktu Transaksi</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold w-40">
                                No. Referensi</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">Produk
                            </th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold w-24">
                                Tipe</th>
                            <th
                                class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold text-right w-24">
                                Kuantitas</th>
                            <th class="px-md py-sm font-label-lg text-label-lg text-on-surface-variant font-semibold">
                                Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @forelse($movements as $m)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-md py-sm text-on-surface-variant font-mono text-[12px] whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($m->created_at)->format('d/m/y H:i') }}</td>
                                <td class="px-md py-sm font-mono text-[13px] text-on-surface">{{ $m->reference_number }}</td>
                                <td class="px-md py-sm font-bold text-on-surface">{{ $m->product->name }}</td>
                                <td class="px-md py-sm">
                                    @if($m->type == 'in')
                                        <span
                                            class="px-xs py-[2px] bg-tertiary-container text-on-tertiary-container rounded text-[11px] font-bold uppercase tracking-wider block w-max">+
                                            Masuk</span>
                                    @else
                                        <span
                                            class="px-xs py-[2px] bg-error-container text-on-error-container rounded text-[11px] font-bold uppercase tracking-wider block w-max">-
                                            Keluar</span>
                                    @endif
                                </td>
                                <td
                                    class="px-md py-sm text-right font-black {{ $m->type == 'in' ? 'text-on-surface' : 'text-error' }}">
                                    {{ (float) $m->quantity }}
                                </td>
                                <td class="px-md py-sm text-on-surface-variant text-[12px] opacity-80">{{ $m->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-xl text-center text-on-surface-variant">
                                    <span
                                        class="material-symbols-outlined text-4xl text-outline mb-xs block">receipt_long</span>
                                    <p class="font-body-md text-sm">Tidak ada log pergerakan stok yang ditemukan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())
                <div class="p-md border-t border-outline-variant">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection