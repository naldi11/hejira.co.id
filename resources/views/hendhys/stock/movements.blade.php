@extends('layouts.hendhys')
@section('title', 'Kartu Stok')
@section('page-title', 'Histori Mutasi Stok ' . (auth()->user()->branch->type === 'pusat' ? 'Pusat' : 'Cabang'))

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-[#faf7f5]">
        <div class="flex items-center gap-3">
            <a href="{{ route('hendhys.stock.index') }}" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-gray-800 hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h3 class="font-bold text-gray-800">Kartu Stok (Log Pergerakan)</h3>
        </div>
        <form action="{{ route('hendhys.stock.movements') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <select name="product_id" class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
                <option value="">Pilih Produk spesifik...</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
            <span class="text-gray-500 text-xs font-medium uppercase">s.d</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
            
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition-colors text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['product_id', 'date_from', 'date_to']))
                <a href="{{ route('hendhys.stock.movements') }}" class="text-sm text-red-500 hover:text-red-700">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="py-3 px-4 font-medium w-40">Waktu Transaksi</th>
                    <th class="py-3 px-4 font-medium w-40">No. Referensi</th>
                    <th class="py-3 px-4 font-medium">Produk</th>
                    <th class="py-3 px-4 font-medium w-24">Tipe</th>
                    <th class="py-3 px-4 font-medium text-right w-24">Kuantitas</th>
                    <th class="py-3 px-4 font-medium">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($movements as $m)
                <tr class="hover:bg-amber-50/50 transition-colors">
                    <td class="py-3 px-4 text-gray-500 text-xs whitespace-nowrap">{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4 font-medium text-gray-800">{{ $m->reference_number }}</td>
                    <td class="py-3 px-4 font-semibold text-gray-800">{{ $m->product->name }}</td>
                    <td class="py-3 px-4">
                        @if($m->type == 'in')
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold uppercase tracking-wider">+ Masuk</span>
                        @else
                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-bold uppercase tracking-wider">- Keluar</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right font-black {{ $m->type == 'in' ? 'text-green-600' : 'text-red-500' }}">
                        {{ (float) $m->quantity }}
                    </td>
                    <td class="py-3 px-4 text-gray-600 text-xs">{{ $m->notes }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p>Tidak ada log pergerakan stok yang ditemukan.</p>
                    </td>
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
