@extends('layouts.gudang')
@section('title', 'Histori Pergerakan Stok (Kartu Stok)')
@section('page-title', 'Gudang — Histori Pergerakan Stok')

@section('content')
<div class="flex items-center justify-between mt-4 mb-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Histori Pergerakan Stok</h2>
        <p class="text-sm text-gray-400">Log keluar masuk barang dan penyesuaian di Gudang Utama</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('gudang.stock.index') }}" class="border border-gray-300 bg-white text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
            Kembali ke Data Stok
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center gap-4">
        <form method="GET" action="{{ route('gudang.stock.movements') }}" class="flex-1 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk..." 
                   class="w-1/3 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Semua Tipe (In/Out)</option>
                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Masuk (In)</option>
                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Keluar (Out)</option>
            </select>
            <select name="source" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Semua Sumber Transaksi</option>
                <option value="purchase_receiving" {{ request('source') == 'purchase_receiving' ? 'selected' : '' }}>Penerimaan Barang (GRN)</option>
                <option value="transfer_out" {{ request('source') == 'transfer_out' ? 'selected' : '' }}>Transfer Keluar (DO)</option>
                <option value="adjustment" {{ request('source') == 'adjustment' ? 'selected' : '' }}>Penyesuaian (SO)</option>
            </select>
            <button type="submit" class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['search', 'type', 'source']))
                <a href="{{ route('gudang.stock.movements') }}" class="text-gray-400 hover:text-red-500 px-2 py-2 flex items-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Waktu Transaksi</th>
                    <th class="px-4 py-3 font-medium">Produk</th>
                    <th class="px-4 py-3 font-medium">Tipe</th>
                    <th class="px-4 py-3 font-medium text-right">Qty</th>
                    <th class="px-4 py-3 font-medium">Sumber (Dokumen)</th>
                    <th class="px-4 py-3 font-medium">Keterangan</th>
                    <th class="px-4 py-3 font-medium">Operator</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($movements as $m)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ \Carbon\Carbon::parse($m->created_at)->format('d M Y, H:i') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $m->product->name ?? 'Produk Dihapus' }}</td>
                    <td class="px-4 py-3">
                        @if($m->type === 'in')
                            <span class="inline-flex items-center gap-1 text-green-600 bg-green-50 px-2 py-0.5 rounded text-xs font-semibold">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                Masuk
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-red-600 bg-red-50 px-2 py-0.5 rounded text-xs font-semibold">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                Keluar
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-bold {{ $m->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $m->type === 'in' ? '+' : '-' }}{{ number_format($m->quantity, 3, ',', '.') }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $sourceLabels = [
                                'purchase_receiving' => 'Penerimaan (GRN)',
                                'transfer_out' => 'Transfer Keluar',
                                'adjustment' => 'Penyesuaian (SO)',
                            ];
                        @endphp
                        <span class="text-xs text-gray-500 font-mono block">{{ $sourceLabels[$m->source] ?? $m->source }}</span>
                        @if($m->reference_id)
                            <span class="text-xs text-indigo-600">ID: {{ $m->reference_id }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $m->notes ?: '-' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $m->creator->name ?? 'Sistem' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada histori pergerakan stok.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $movements->links() }}
    </div>
    @endif
</div>
@endsection
