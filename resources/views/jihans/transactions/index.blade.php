@extends('layouts.jihans')
@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi Kasir')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
    <a href="{{ route('jihans.pos.index') }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali ke Kasir
    </a>

    <form action="{{ route('jihans.transactions.index') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
        <div class="relative flex-1 sm:w-64">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Transaksi atau Pelanggan..." 
                   class="w-full pl-8 pr-4 py-2 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-900">
            Cari
        </button>
        @if(request()->filled('search'))
            <a href="{{ route('jihans.transactions.index') }}" class="px-4 py-2 bg-gray-100 text-red-600 rounded-lg text-sm font-medium hover:bg-gray-200">Reset</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 font-medium">Tanggal</th>
                    <th class="px-6 py-4 font-medium">No. Transaksi</th>
                    <th class="px-6 py-4 font-medium">Pelanggan</th>
                    <th class="px-6 py-4 font-medium text-right">Total Tagihan</th>
                    <th class="px-6 py-4 font-medium">Kasir</th>
                    <th class="px-6 py-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transactions as $t)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y') }}
                        <br><span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($t->created_at)->format('H:i') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-mono font-semibold text-gray-800">{{ $t->transaction_number }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800">{{ $t->customer_name }}</p>
                        <p class="text-xs text-gray-500 capitalize">{{ $t->customer_type }}</p>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <p class="font-bold text-gray-900">Rp {{ number_format($t->grand_total, 0, ',', '.') }}</p>
                        <p class="text-xs text-green-600 capitalize mt-0.5">{{ $t->status }}</p>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $t->creator->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('jihans.transactions.show', $t->id) }}" class="text-orange-600 hover:text-orange-900 font-medium text-sm inline-flex items-center gap-1 border border-orange-200 px-3 py-1.5 rounded bg-orange-50 hover:bg-orange-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Struk
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p>Belum ada riwayat transaksi.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $transactions->links() }}
    </div>
    @endif
</div>
@endsection
