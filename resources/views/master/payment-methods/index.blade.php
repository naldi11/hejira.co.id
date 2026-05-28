@extends($layout ?? 'layouts.gudang')
@section('title', 'Metode Pembayaran')
@section('page-title', 'Master Data — Keuangan')

@section('content')
@php
    $accentColor = 'indigo';
    if (($currentScope ?? '') === 'jihans') {
        $accentColor = 'orange';
    } elseif (($currentScope ?? '') === 'hendhys') {
        $accentColor = 'amber';
    }
@endphp
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Metode Pembayaran</h2>
            <p class="text-sm text-slate-500 font-medium">Kelola opsi pembayaran untuk transaksi POS di semua entitas.</p>
        </div>
        <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-{{ $accentColor }}-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-{{ $accentColor }}-700 transition-all shadow-xl shadow-{{ $accentColor }}-600/20 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[20px]">add_card</span>
            Tambah Metode
        </a>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                Total: <strong class="text-slate-900 tabular-nums">{{ count($methods) }}</strong> Opsi Pembayaran
            </span>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Metode</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Tipe Sistem</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Informasi Akun</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Scope</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($methods as $method)
                    @php
                        $scopeColor = 'indigo';
                        if ($method->entity_scope === 'jihans') {
                            $scopeColor = 'orange';
                        } elseif ($method->entity_scope === 'hendhys') {
                            $scopeColor = 'amber';
                        }
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($method->image)
                                    <img src="{{ asset('storage/' . $method->image) }}" class="w-10 h-6 object-contain rounded border border-slate-100">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 border border-slate-200">
                                        <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                                    </div>
                                @endif
                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ $method->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest border bg-slate-100 text-slate-600 border-slate-200">
                                {{ str_replace('_', ' ', $method->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($method->account_number)
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-700">{{ $method->bank_name }}</span>
                                    <span class="text-[10px] font-black text-slate-400 font-mono">{{ $method->account_number }} a/n {{ $method->account_name }}</span>
                                </div>
                            @else
                                <span class="text-xs font-bold text-slate-300 italic">Langsung / Tunai</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs font-black text-{{ $scopeColor }}-500 uppercase tracking-tighter">
                            {{ $method->entity_scope }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest border {{ $method->is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100' }}">
                                {{ $method->is_active ? 'Aktif' : 'Off' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.edit', $method) }}" 
                                   class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-{{ $accentColor }}-600 hover:bg-{{ $accentColor }}-50 rounded-xl transition-all border border-slate-200">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'payment-methods.destroy', $method) }}"
                                      onsubmit="return confirm('Hapus metode {{ $method->name }}?')" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all border border-slate-200">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <span class="material-symbols-outlined text-slate-200 text-[64px] mb-4">payments</span>
                                <p class="text-slate-400 font-bold italic">Belum ada metode pembayaran.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
