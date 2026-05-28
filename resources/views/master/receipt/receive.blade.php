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
                            <th class="px-4 py-4 text-center w-32">Qty Bagus</th>
                            <th class="px-4 py-4 text-center w-32">Qty Rusak</th>
                            <th class="px-6 py-4 text-center">Satuan</th>
                            <th class="px-4 py-4 text-center w-48">Batch / Expired</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($transferOut->details as $detail)
                        @php $qtySent = (float) $detail->quantity; @endphp
                        <tr class="group hover:bg-slate-50/30 transition-colors"
                            x-data="{
                                qtySent: {{ $qtySent }},
                                qtyBagus: {{ $qtySent }},
                                qtyRusak: 0,
                                validateBagus() {
                                    if (this.qtyBagus === '' || isNaN(this.qtyBagus)) return;
                                    if (this.qtyBagus < 0) this.qtyBagus = 0;
                                    if (this.qtyBagus > this.qtySent) this.qtyBagus = this.qtySent;
                                    this.qtyRusak = parseFloat((this.qtySent - this.qtyBagus).toFixed(3));
                                },
                                validateRusak() {
                                    if (this.qtyRusak === '' || isNaN(this.qtyRusak)) return;
                                    if (this.qtyRusak < 0) this.qtyRusak = 0;
                                    if (this.qtyRusak > this.qtySent) this.qtyRusak = this.qtySent;
                                    this.qtyBagus = parseFloat((this.qtySent - this.qtyRusak).toFixed(3));
                                }
                            }">
                            <td class="px-8 py-5">
                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ $detail->product->name }}</span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="text-xs font-bold text-slate-400 tabular-nums bg-slate-100 px-2 py-1 rounded-lg border border-slate-200">{{ $qtySent }}</span>
                            </td>
                            <td class="px-4 py-5">
                                <input type="number" name="quantity_bagus[{{ $detail->id }}]" x-model.number="qtyBagus" @input="validateBagus()" min="0" :max="qtySent" step="any" required
                                       class="w-full px-3 py-2 bg-slate-50 border-2 border-slate-100 rounded-xl text-xs font-black text-center text-slate-900 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none tabular-nums shadow-inner">
                            </td>
                            <td class="px-4 py-5">
                                <input type="number" name="quantity_rusak[{{ $detail->id }}]" x-model.number="qtyRusak" @input="validateRusak()" min="0" :max="qtySent" step="any" required
                                       class="w-full px-3 py-2 bg-slate-50 border-2 border-slate-100 rounded-xl text-xs font-black text-center text-slate-900 focus:bg-white focus:border-{{ $accentColor }}-500 transition-all outline-none tabular-nums shadow-inner">
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="text-[10px] font-black text-slate-500 uppercase">{{ $detail->unit->abbreviation ?? 'PCS' }}</span>
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
                        <div x-data="{
                            files: [],
                            dragging: false,
                            addFiles(fileList) {
                                for (let i = 0; i < fileList.length; i++) {
                                    const file = fileList[i];
                                    if (file.type.startsWith('image/')) {
                                        const url = URL.createObjectURL(file);
                                        this.files.push({ file: file, url: url, name: file.name });
                                    }
                                }
                                this.updateFileInput();
                            },
                            removeFile(index) {
                                URL.revokeObjectURL(this.files[index].url);
                                this.files.splice(index, 1);
                                this.updateFileInput();
                            },
                            updateFileInput() {
                                const dataTransfer = new DataTransfer();
                                this.files.forEach(f => dataTransfer.items.add(f.file));
                                this.$refs.fileInput.files = dataTransfer.files;
                            }
                        }" class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Unggah Foto Bukti / Surat Jalan</label>
                            
                            <div 
                                @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false"
                                @drop.prevent="dragging = false; addFiles($event.dataTransfer.files)"
                                class="border-2 border-dashed rounded-3xl p-6 transition-all duration-200 flex flex-col items-center justify-center cursor-pointer text-center relative"
                                :class="dragging ? 'border-{{ $accentColor }}-500 bg-{{ $accentColor }}-500/10' : 'border-white/10 bg-white/5 hover:bg-white/10'"
                                @click="$refs.fileInput.click()"
                            >
                                <input type="file" name="photos[]" multiple accept="image/*" class="hidden" x-ref="fileInput"
                                       @change="addFiles($event.target.files)">
                                
                                <span class="material-symbols-outlined text-[32px] text-white/50 mb-2">cloud_upload</span>
                                <p class="text-xs text-white/70 font-semibold">Tarik & lepas foto di sini, atau <span class="text-{{ $accentColor }}-400 underline">klik untuk memilih</span></p>
                                <p class="text-[10px] text-slate-500 mt-1">Maksimal 10 file gambar (masing-masing maks 5MB)</p>
                            </div>

                            <!-- Previews Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-3" x-show="files.length > 0" x-cloak>
                                <template x-for="(f, idx) in files" :key="idx">
                                    <div class="relative group aspect-square rounded-2xl overflow-hidden border border-white/10 bg-white/5 shadow-inner">
                                        <img :src="f.url" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <button type="button" @click.stop="removeFile(idx)" class="w-8 h-8 rounded-xl bg-red-600 text-white flex items-center justify-center hover:bg-red-500 transition-colors">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </div>
                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 px-2 py-1 truncate text-[8px] text-white/80 font-mono" x-text="f.name"></div>
                                    </div>
                                </template>
                            </div>
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
