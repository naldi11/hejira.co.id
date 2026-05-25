@extends('layouts.gudang')
@section('title', 'Transfer Requests (Approval)')
@section('page-title', 'Permintaan Barang')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Permintaan Transfer Stok</h2>
            <p class="text-sm text-slate-500 font-medium">Review dan persetujuan permintaan barang dari unit bisnis</p>
        </div>
    </div>

    {{-- Dashboard Mini Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-inner">
                <span class="material-symbols-outlined text-[32px] animate-pulse">pending_actions</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Menunggu Review</p>
                <p class="text-2xl font-black text-slate-900 tabular-nums">{{ $counts['pending'] }} <span class="text-xs font-bold text-slate-400">Dokumen</span></p>
            </div>
        </div>
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-inner">
                <span class="material-symbols-outlined text-[32px]">task_alt</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Siap Dikirim</p>
                <p class="text-2xl font-black text-slate-900 tabular-nums">{{ $counts['approved'] }} <span class="text-xs font-bold text-slate-400">Dokumen</span></p>
            </div>
        </div>
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-inner">
                <span class="material-symbols-outlined text-[32px]">local_shipping</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Selesai / Terkirim</p>
                <p class="text-2xl font-black text-slate-900 tabular-nums">{{ $counts['completed'] }} <span class="text-xs font-bold text-slate-400">Bulan Ini</span></p>
            </div>
        </div>
    </div>

    {{-- List Card --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        {{-- Filter Area --}}
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <form method="GET" action="{{ route('gudang.transfer-requests.index') }}" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[250px] relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Request..." 
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                </div>
                
                <select name="status" class="px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                    <option value="">Semua Status</option>
                    @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'partial' => 'Sebagian', 'completed' => 'Selesai', 'rejected' => 'Ditolak'] as $v => $l)
                        <option value="{{ $v }}" {{ request('status') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                
                <select name="from_entity" class="px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:outline-none transition-all">
                    <option value="">Asal Request</option>
                    <option value="hendhys" {{ request('from_entity') == 'hendhys' ? 'selected' : '' }}>Hendhys Brownies</option>
                    <option value="jihans" {{ request('from_entity') == 'jihans' ? 'selected' : '' }}>Jihan's Food</option>
                </select>
                
                <button type="submit" class="px-8 py-3 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg shadow-slate-900/10">
                    Filter
                </button>

                @if(request()->anyFilled(['search', 'status', 'from_entity']))
                    <a href="{{ route('gudang.transfer-requests.index') }}" class="w-11 h-11 flex items-center justify-center bg-rose-50 text-rose-600 rounded-2xl hover:bg-rose-100 transition-all">
                        <span class="material-symbols-outlined">refresh</span>
                    </a>
                @endif
            </form>
        </div>

        {{-- Table Area --}}
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Data Dokumen</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Unit Bisnis</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Peminta</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $statusStyles = [
                            'pending'   => 'bg-amber-50 text-amber-600 border-amber-100',
                            'approved'  => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                            'partial'   => 'bg-violet-50 text-violet-600 border-violet-100',
                            'completed' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                            'rejected'  => 'bg-rose-50 text-rose-600 border-rose-100',
                        ];
                    @endphp
                    @forelse($requests as $req)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-slate-800 tracking-tight group-hover:text-indigo-600 transition-colors">{{ $req->request_number }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ \Carbon\Carbon::parse($req->date)->translatedFormat('d M Y') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $req->from_entity === 'hendhys' ? 'bg-amber-100 text-amber-700' : 'bg-orange-100 text-orange-700' }}">
                                    <span class="material-symbols-outlined text-[18px]">{{ $req->from_entity === 'hendhys' ? 'cake' : 'bakery_dining' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-700 uppercase tracking-tight">{{ ucfirst($req->from_entity) }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">{{ $req->branch->name ?? 'Produksi Pusat' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest border {{ $statusStyles[$req->status] ?? 'bg-slate-50 text-slate-500 border-slate-100' }}">
                                {{ $req->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-black text-slate-500">
                                    {{ substr($req->requester->name ?? '?', 0, 1) }}
                                </div>
                                <span class="text-xs font-bold text-slate-600">{{ $req->requester->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($req->status === 'pending')
                                <a href="{{ route('gudang.transfer-requests.show', $req) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/20">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    Review
                                </a>
                            @else
                                <a href="{{ route('gudang.transfer-requests.show', $req) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-600 border border-slate-200 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                                    Detail
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <span class="material-symbols-outlined text-slate-200 text-[64px] mb-4">move_to_inbox</span>
                                <p class="text-slate-400 font-bold italic">Belum ada dokumen Transfer Request.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="p-6 border-t border-slate-100 bg-slate-50/30">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
