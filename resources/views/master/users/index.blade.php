@extends($layout ?? 'layouts.gudang')
@section('title', 'Manajemen User')
@section('page-title', 'Keamanan & Akses')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 font-headline tracking-tight">Manajemen Pengguna</h2>
            <p class="text-sm text-slate-500 font-medium">Kelola hak akses, entitas bisnis, dan kredensial pengguna sistem.</p>
        </div>
        <a href="{{ route(($routePrefix ?? 'master.') . 'users.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[20px]">person_add</span>
            Tambah Pengguna
        </a>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                Total: <strong class="text-slate-900 tabular-nums">{{ $users->count() }}</strong> Pengguna Terdaftar
            </span>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Profil Pengguna</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Penempatan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Level Akses</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-black text-indigo-600 border border-slate-200 shadow-inner group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-slate-800 tracking-tight">{{ $user->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 truncate max-w-[150px]">{{ $user->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-tighter w-fit border
                                    @if($user->entity == 'gudang') bg-blue-50 text-blue-700 border-blue-100
                                    @elseif($user->entity == 'jihans') bg-orange-50 text-orange-700 border-orange-100
                                    @elseif($user->entity == 'hendhys') bg-amber-50 text-amber-700 border-amber-100
                                    @elseif($user->entity == 'owner') bg-violet-50 text-violet-700 border-violet-100
                                    @else bg-slate-50 text-slate-700 border-slate-100 @endif">
                                    {{ $user->entity }}
                                </span>
                                @if($user->branch)
                                    <span class="text-[10px] font-bold text-slate-400 italic">@ {{ $user->branch->name }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                    <span class="text-[10px] font-black text-slate-500 uppercase bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-widest border {{ $user->is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100' }}">
                                {{ $user->is_active ? 'Aktif' : 'Off' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2 opacity-40 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route(($routePrefix ?? 'master.') . 'users.edit', $user) }}" 
                                   class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-slate-200">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <form action="{{ route(($routePrefix ?? 'master.') . 'users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus pengguna {{ $user->name }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center bg-slate-50 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all border border-slate-200">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
