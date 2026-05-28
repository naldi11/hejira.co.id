@extends($layout ?? 'layouts.gudang')
@section('title', 'Daftar Customer')
@section('page-title', 'Master Data — Customer')

@section('content')
@php
    $accentColor = 'indigo';
    if (($currentScope ?? '') === 'jihans') {
        $accentColor = 'orange';
    } elseif (($currentScope ?? '') === 'hendhys') {
        $accentColor = 'amber';
    }
@endphp
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 font-headline">Daftar Customer</h2>
            <p class="text-sm font-medium text-slate-500 mt-1">{{ $customers->total() }} customer terdaftar dalam sistem</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route(($routePrefix ?? 'master.') . 'customers.create') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-{{ $accentColor }}-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-{{ $accentColor }}-700 transition-all shadow-lg shadow-{{ $accentColor }}-600/20">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                Tambah Customer
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 mb-8">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[280px] relative">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, atau telepon..."
                    class="w-full pl-12 pr-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none text-sm">
            </div>
            
            <div class="min-w-[180px]">
                <select name="type"
                    class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none text-sm appearance-none cursor-pointer">
                    <option value="">Semua Tipe</option>
                    <option value="Pelanggan Individual" {{ request('type') === 'Pelanggan Individual' ? 'selected' : '' }}>Individual</option>
                    <option value="Pelanggan Retail" {{ request('type') === 'Pelanggan Retail' ? 'selected' : '' }}>Retail</option>
                    <option value="Pelanggan Agen" {{ request('type') === 'Pelanggan Agen' ? 'selected' : '' }}>Agen</option>
                </select>
            </div>

            <div class="min-w-[180px]">
                <select name="status"
                    class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-50 rounded-2xl focus:bg-white focus:border-{{ $accentColor }}-500 focus:ring-4 focus:ring-{{ $accentColor }}-500/10 transition-all outline-none text-sm appearance-none cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <button type="submit"
                class="px-6 py-3 bg-slate-900 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Filter
            </button>

            @if(request()->hasAny(['search', 'type', 'status']))
                <a href="{{ route(($routePrefix ?? 'master.') . 'customers.index') }}"
                    class="px-6 py-3 bg-rose-50 text-rose-600 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-rose-100 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kode</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Customer</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Tipe</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Telepon</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs font-bold text-slate-400 px-2 py-1 bg-slate-100 rounded-lg">{{ $customer->code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-{{ $accentColor }}-50 text-{{ $accentColor }}-600 flex items-center justify-center font-black text-sm border border-{{ $accentColor }}-100">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </div>
                                    <p class="text-sm font-black text-slate-900">{{ $customer->name }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if(str_contains(strtolower($customer->type), 'agen'))
                                    <span class="px-3 py-1 bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest rounded-xl border border-amber-100">Agen</span>
                                @elseif(str_contains(strtolower($customer->type), 'retail'))
                                    <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-widest rounded-xl border border-blue-100">Retail</span>
                                @else
                                    <span class="px-3 py-1 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest rounded-xl border border-slate-200">Individual</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-500">
                                {{ $customer->phone ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($customer->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route(($routePrefix ?? 'master.') . 'customers.edit', $customer) }}"
                                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition-all border border-slate-200">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'customers.destroy', $customer) }}"
                                        onsubmit="return confirm('Hapus customer {{ $customer->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-all border border-slate-200">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center bg-slate-50/30">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-100 rounded-3xl flex items-center justify-center mb-4">
                                        <span class="material-symbols-outlined text-[32px] text-slate-300">groups</span>
                                    </div>
                                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Tidak ada data customer</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
@endsection
