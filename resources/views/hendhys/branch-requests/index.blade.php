@extends('layouts.hendhys')
@section('title', 'Request Cabang')
@section('page-title', 'Daftar Request dari Cabang')

@section('content')
@php
    $isPusat = auth()->user()->branch->type === 'pusat';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('hendhys.branch-requests.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <select name="status" class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706]">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Request..." class="text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] min-w-[200px]">
            <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">Filter</button>
            @if(request()->anyFilled(['status', 'search']))
                <a href="{{ route('hendhys.branch-requests.index') }}" class="text-sm text-red-500 hover:text-red-700">Reset</a>
            @endif
        </form>
        @if(!$isPusat)
        <a href="{{ route('hendhys.branch-requests.create') }}" class="bg-[#d97706] hover:bg-[#b45309] text-white px-5 py-2 rounded-lg transition-colors text-sm font-medium whitespace-nowrap flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Request Baru
        </a>
        @endif
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <th class="p-4 font-medium">Tanggal</th>
                    <th class="p-4 font-medium">No. Request</th>
                    @if($isPusat) <th class="p-4 font-medium">Cabang Pemohon</th> @endif
                    <th class="p-4 font-medium">Status</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($requests as $req)
                <tr class="hover:bg-amber-50/50 transition-colors">
                    <td class="p-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</td>
                    <td class="p-4 font-medium text-[#d97706]">{{ $req->request_number }}</td>
                    @if($isPusat) <td class="p-4 font-semibold text-gray-800">{{ $req->branch->name }}</td> @endif
                    <td class="p-4">
                        @if($req->status == 'pending')
                            <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Pending</span>
                        @elseif($req->status == 'completed')
                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Completed</span>
                        @elseif($req->status == 'partial')
                            <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-bold uppercase tracking-wider">Partial</span>
                        @else
                            <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold uppercase tracking-wider">Rejected</span>
                        @endif
                    </td>
                    <td class="p-4 text-right">
                        @if($isPusat && $req->status === 'pending')
                            <a href="{{ route('hendhys.transfer-to-branch.create', ['request_id' => $req->id]) }}" class="text-white bg-[#d97706] hover:bg-[#b45309] font-medium text-xs px-3 py-1.5 rounded-md transition-colors inline-block mr-2">Proses Pengiriman</a>
                        @endif
                        <a href="{{ route('hendhys.branch-requests.show', $req->id) }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-xs bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-md transition-colors">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isPusat ? 5 : 4 }}" class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p>Belum ada data request stok.</p>
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
