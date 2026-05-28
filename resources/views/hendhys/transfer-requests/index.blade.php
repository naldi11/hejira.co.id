@extends('layouts.hendhys')
@section('title', 'Request ke Gudang')
@section('page-title', 'Request Stok ke Gudang')

@section('content')

@if($incomingTransfers->isNotEmpty())
<div class="mb-8 bg-amber-50 border border-amber-200 rounded-xl overflow-hidden shadow-sm">
    <div class="px-6 py-4 bg-amber-100 border-b border-amber-200 flex justify-between items-center">
        <h3 class="font-bold text-amber-950 flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px] animate-pulse">local_shipping</span>
            Pengiriman Masuk Belum Diterima
        </h3>
        <span class="bg-[#d97706] text-white text-xs px-2.5 py-1 rounded-full font-bold">{{ $incomingTransfers->count() }} Pengiriman</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-amber-50/50 text-amber-900 border-b border-amber-200 text-xs uppercase font-bold">
                <tr>
                    <th class="p-4">No. Transfer (DO)</th>
                    <th class="p-4">Tanggal Kirim</th>
                    <th class="p-4">Referensi Request</th>
                    <th class="p-4">Pengirim</th>
                    <th class="p-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-amber-100 text-sm">
                @foreach($incomingTransfers as $do)
                <tr class="hover:bg-amber-100/30 transition-colors">
                    <td class="p-4 font-mono font-bold text-amber-950">{{ $do->transfer_number }}</td>
                    <td class="p-4 text-amber-900">{{ $do->date->format('d/m/Y') }}</td>
                    <td class="p-4">
                        @if($do->request)
                            <span class="font-mono text-amber-850 font-semibold">{{ $do->request->request_number }}</span>
                        @else
                            <span class="text-amber-600 italic text-xs font-semibold">Transfer Langsung</span>
                        @endif
                    </td>
                    <td class="p-4 text-amber-900">{{ $do->creator->name ?? 'Gudang' }}</td>
                    <td class="p-4 text-right">
                        <a href="{{ route('hendhys.transfer-requests.receive-form-gudang', $do->id) }}" class="inline-flex items-center gap-1.5 bg-[#d97706] hover:bg-[#b45309] text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-sm">
                            <span class="material-symbols-outlined text-[14px]">check_box</span>
                            Konfirmasi Terima
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('hendhys.transfer-requests.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <select name="status" class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Request..." class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] min-w-[200px]">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['status', 'search']))
                <a href="{{ route('hendhys.transfer-requests.index') }}" class="text-sm text-red-500 hover:text-red-700">Reset</a>
            @endif
        </form>
        <a href="{{ route('hendhys.transfer-requests.create') }}" class="bg-[#d97706] hover:bg-[#b45309] text-white px-5 py-2 rounded-lg transition-colors text-sm font-medium whitespace-nowrap flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Request Baru
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="p-4 font-medium">Tanggal</th>
                    <th class="p-4 font-medium">No. Request</th>
                    <th class="p-4 font-medium">Pemohon</th>
                    <th class="p-4 font-medium">Status</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($requests as $req)
                <tr class="hover:bg-amber-50/50 transition-colors">
                    <td class="p-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</td>
                    <td class="p-4 font-medium text-[#d97706]">{{ $req->request_number }}</td>
                    <td class="p-4">{{ $req->creator->name }}</td>
                    <td class="p-4">
                        @if($req->status == 'pending')
                            <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Pending</span>
                        @elseif($req->status == 'approved')
                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider">Approved</span>
                        @elseif($req->status == 'completed')
                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Completed</span>
                        @elseif($req->status == 'partial')
                            <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-bold uppercase tracking-wider">Partial</span>
                        @else
                            <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold uppercase tracking-wider">Rejected</span>
                        @endif
                    </td>
                    <td class="p-4 text-right">
                        <a href="{{ route('hendhys.transfer-requests.show', $req->id) }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-xs bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-md transition-colors">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p>Belum ada pengajuan request ke Gudang Pusat.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $requests->links() }}
    </div>
    @endif
</div>
@endsection
