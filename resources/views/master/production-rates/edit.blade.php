@extends($layout ?? 'layouts.jihans')
@section('title', 'Tarif Produksi')
@section('page-title', 'Konfigurasi Operasional')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 pb-20">

    {{-- Header & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route(($routePrefix ?? 'master.') . 'products.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Kembali ke Produk
        </a>
        <h2 class="text-xl font-black text-slate-800 font-headline tracking-tight text-right">Tarif Borongan Produksi</h2>
    </div>

    <form method="POST" action="{{ route(($routePrefix ?? 'master.') . 'production-rates.update') }}" class="space-y-8">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Left: List --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-8 sm:p-10 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shadow-inner">
                                <span class="material-symbols-outlined text-[28px]">payments</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">Konfigurasi Tarif per Pack</h3>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Ubah tarif borongan karyawan produksi</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-0">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    <th class="px-8 py-4">Nama Produk</th>
                                    <th class="px-8 py-4 text-right">Tarif (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($rates as $rate)
                                <tr class="hover:bg-slate-50/30 transition-colors">
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-800 tracking-tight">{{ $rate->product_name }}</span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Produksi Jihan's</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <div class="relative inline-block w-48">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">Rp</span>
                                            <input type="number" name="rates[{{ $rate->id }}]" value="{{ floatval($rate->rate_per_pack) }}" step="any" required
                                                   class="w-full pl-10 pr-4 py-3 bg-slate-50 border-2 border-slate-100 rounded-2xl text-sm font-black text-right text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none tabular-nums">
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-3xl text-sm font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl shadow-slate-900/10 active:scale-[0.98]">
                        Simpan Semua Perubahan Tarif
                    </button>
                </div>
            </div>

            {{-- Right: Sidebar --}}
            <div class="space-y-6">
                <div class="bg-indigo-600 rounded-[2rem] p-8 text-white shadow-xl shadow-indigo-600/20 relative overflow-hidden group">
                    <div class="relative z-10">
                        <span class="material-symbols-outlined text-[32px] text-indigo-300 mb-4 group-hover:rotate-12 transition-transform">help_outline</span>
                        <h3 class="text-sm font-black uppercase tracking-[0.2em] mb-4">Cara Kerja</h3>
                        <p class="text-xs font-medium leading-relaxed italic opacity-90">
                            Tarif ini digunakan sebagai dasar perhitungan gaji borongan karyawan pada setiap sesi produksi Tortilla. Perubahan tarif akan langsung berlaku pada sesi produksi berikutnya.
                        </p>
                    </div>
                    <span class="material-symbols-outlined absolute -right-6 -bottom-6 text-white/5 text-[140px] rotate-12">trending_up</span>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection
