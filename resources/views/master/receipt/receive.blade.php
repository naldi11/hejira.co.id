@extends($layout)
@section('title', 'Konfirmasi Penerimaan Barang')
@section('page-title', 'Penerimaan Stok')

@section('content')
@php
    $accentColor = 'indigo';
    if (($currentScope ?? '') === 'jihans') {
        $accentColor = 'orange';
    } elseif (($currentScope ?? '') === 'hendhys') {
        $accentColor = 'amber';
    }
@endphp
<div class="max-w-4xl mx-auto space-y-8 pb-20">

    {{-- Header & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route($info['transferRoute'] . 'index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Batal & Kembali
        </a>
        <h2 class="text-xl font-black text-slate-800 font-headline tracking-tight">Konfirmasi Penerimaan Barang</h2>
    </div>

    @if($errors->any())
    <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-2 text-rose-600">
            <span class="material-symbols-outlined">warning</span>
            <span class="text-sm font-black uppercase tracking-widest">Kesalahan Input</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-xs font-bold opacity-80">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route($info['receiveRoute'], $transferOut->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

        {{-- Header Info Card --}}
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 p-8 sm:p-10">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 rounded-2xl bg-{{ $accentColor }}-50 text-{{ $accentColor }}-600 flex items-center justify-center border border-{{ $accentColor }}-100 shadow-inner">
                    <span class="material-symbols-outlined text-[28px]">local_shipping</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Informasi Pengiriman</p>
                    <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">Dari Gudang Utama</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">No. Transfer</p>
                    <p class="text-sm font-black text-slate-800 mt-1 tabular-nums">{{ $transferOut->transfer_number }}</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tanggal Kirim</p>
                    <p class="text-sm font-black text-slate-800 mt-1 tabular-nums">{{ $transferOut->date->translatedFormat('d M Y') }}</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Dikirim Oleh</p>
                    <p class="text-sm font-black text-slate-800 mt-1 truncate">{{ $transferOut->creator->name }}</p>
                </div>
            </div>
        </div>

        {{-- Items Table Card --}}
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-8 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Daftar Barang — Verifikasi Fisik</h3>
            </div>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/30 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                            <th class="px-8 py-4">Produk</th>
                            <th class="px-6 py-4 text-center">Qty Kirim</th>
                            <th class="px-6 py-4 text-center w-32">Qty Diterima</th>
                            <th class="px-6 py-4 text-center">Satuan</th>
                            <th class="px-4 py-4 text-center w-32">Kondisi</th>
                            <th class="px-4 py-4 text-center w-40">Batch / Expired</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($transferOut->details as $detail)
                        <tr class="group hover:bg-slate-50/30 transition-colors">
                            <td class="px-8 py-5">
                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ $detail->product->name }}</span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="text-xs font-bold text-slate-400 tabular-nums bg-slate-100 px-2 py-1 rounded-lg border border-slate-200">{{ number_format($detail->quantity, 0) }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <input type="number" name="received_quantities[{{ $detail->id }}]" value="{{ floatval($detail->quantity) }}" min="0" max="{{ floatval($detail->quantity) }}" step="any" required
                                       class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-100 rounded-xl text-xs font-black text-center text-slate-900 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none tabular-nums shadow-inner">
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="text-[10px] font-black text-slate-500 uppercase">{{ $detail->unit->abbreviation ?? 'PCS' }}</span>
                            </td>
                            <td class="px-4 py-5">
                                <select name="kondisi[{{ $detail->id }}]" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none">
                                    <option value="baik">Baik</option>
                                    <option value="rusak">Rusak</option>
                                    <option value="kurang">Kurang</option>
                                </select>
                            </td>
                            <td class="px-4 py-5 space-y-2">
                                <input type="text" name="batch_number[{{ $detail->id }}]" placeholder="No Batch"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-[10px] font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none uppercase">
                                <input type="date" name="expired_date[{{ $detail->id }}]"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-[10px] font-bold text-slate-700 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="p-8 sm:p-10 bg-slate-900 border-t border-white/10">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="flex-1 space-y-4 w-full">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Unggah Foto Bukti / Surat Jalan</label>
                            <input type="file" name="photos[]" multiple accept="image/*" class="block w-full text-[11px] text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:bg-white/10 file:text-white hover:file:bg-white/20 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Catatan Penerimaan</label>
                            <textarea name="receive_notes" rows="2" placeholder="Tulis catatan jika ada barang rusak atau kurang..."
                                      class="w-full px-5 py-3 bg-white/5 border-2 border-white/10 rounded-2xl text-xs font-medium text-white placeholder:text-slate-500 focus:bg-white/10 focus:border-{{ $accentColor }}-400 transition-all outline-none resize-none"></textarea>
                        </div>
                    </div>
                    <div class="shrink-0 w-full md:w-auto">
                        <button type="submit" class="w-full px-10 py-5 bg-{{ $accentColor }}-600 text-white hover:bg-{{ $accentColor }}-500 rounded-3xl text-xs font-black uppercase tracking-[0.2em] transition-all shadow-2xl shadow-{{ $accentColor }}-600/30 active:scale-[0.98]">
                            Konfirmasi & Simpan Stok
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection
