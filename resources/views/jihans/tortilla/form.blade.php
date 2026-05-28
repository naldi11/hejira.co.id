@extends('layouts.jihans')
@section('title', 'Input Produksi Tortilla')
@section('page-title', 'Input Produksi Tortilla')

@section('content')
<div class="space-y-6" x-data="productionForm()">

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="flex gap-3">
            <span class="material-symbols-outlined text-red-500 text-[20px] shrink-0 mt-0.5">error</span>
            <div>
                <p class="text-sm font-bold text-red-700 mb-1">Terdapat kesalahan:</p>
                <ul class="text-sm text-red-600 list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('jihans.tortilla.store') }}" class="space-y-6">
        @csrf

        {{-- SEKSI 1 — INFORMASI SESI --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                <div class="w-9 h-9 bg-orange-100 rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-orange-600 text-[20px]">calendar_today</span>
                </div>
                <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider">Informasi Sesi</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Tanggal Produksi <span class="text-red-500">*</span></label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-400/10 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-2">Catatan Sesi</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Contoh: Produksi Pagi"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-400/10 transition-all">
                </div>
            </div>
        </div>

        {{-- SEKSI 2 — DATA PRODUKSI PER KARYAWAN --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-orange-100 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-orange-600 text-[20px]">group</span>
                    </div>
                    <h3 class="font-black text-slate-800 text-sm uppercase tracking-wider">Data Produksi per Karyawan</h3>
                </div>
                <button type="button" @click="addRow()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-xl text-xs font-black hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[16px]">person_add</span>
                    Tambah Karyawan
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left min-w-[680px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-6 py-3 text-xs font-black text-slate-500 uppercase tracking-wider w-1/3">Nama Karyawan</th>
                            <th class="px-3 py-3 text-xs font-black text-slate-500 uppercase tracking-wider text-center">TB</th>
                            <th class="px-3 py-3 text-xs font-black text-slate-500 uppercase tracking-wider text-center">TS</th>
                            <th class="px-3 py-3 text-xs font-black text-slate-500 uppercase tracking-wider text-center">TK</th>
                            <th class="px-3 py-3 text-xs font-black text-slate-500 uppercase tracking-wider text-center">TC</th>
                            <th class="px-3 py-3 text-xs font-black text-slate-500 uppercase tracking-wider text-center">Kribab</th>
                            <th class="px-3 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr class="hover:bg-orange-50/30 transition-colors">
                                <td class="px-6 py-3">
                                    <select :name="`details[${index}][karyawan_id]`"
                                            class="karyawan-select w-full" required
                                            x-model="row.karyawan_id" :data-id="row.id">
                                        <option value="">Pilih Karyawan...</option>
                                        @foreach($karyawans as $k)
                                        <option value="{{ $k->id }}">{{ $k->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                @foreach(['tb','ts','tk','tc','kribab'] as $v)
                                <td class="px-3 py-3">
                                    <input type="number"
                                           :name="`details[${index}][{{ $v }}_qty]`"
                                           x-model.number="row.{{ $v }}"
                                           min="0" value="0"
                                           class="w-20 text-center py-2 px-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-400/10 transition-all">
                                </td>
                                @endforeach
                                <td class="px-3 py-3">
                                    <button type="button" @click="removeRow(index)"
                                            class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                            :disabled="rows.length <= 1" :class="rows.length <= 1 ? 'opacity-30 cursor-not-allowed' : ''">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="bg-orange-50 border-t-2 border-orange-200">
                            <td class="px-6 py-4 font-black text-slate-700 text-xs uppercase tracking-wider">Total</td>
                            <td class="px-3 py-4 text-center font-black text-slate-700" x-text="totalQty('tb')"></td>
                            <td class="px-3 py-4 text-center font-black text-slate-700" x-text="totalQty('ts')"></td>
                            <td class="px-3 py-4 text-center font-black text-slate-700" x-text="totalQty('tk')"></td>
                            <td class="px-3 py-4 text-center font-black text-slate-700" x-text="totalQty('tc')"></td>
                            <td class="px-3 py-4 text-center font-black text-slate-700" x-text="totalQty('kribab')"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Tombol Submit --}}
        <div class="flex items-center gap-4 pb-4">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-8 py-3.5 bg-orange-600 text-white rounded-2xl font-black text-sm uppercase tracking-wider hover:bg-orange-700 transition-all shadow-xl shadow-orange-600/25 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[20px]">save</span>
                Simpan & Update Stok
            </button>
            <a href="{{ route('jihans.tortilla.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3.5 bg-white text-slate-600 border border-slate-200 rounded-2xl font-bold text-sm hover:bg-slate-50 transition-all">
                Batal
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function productionForm() {
        return {
            rows: [],
            nextId: 1,

            init() {
                this.addRow();
            },

            addRow() {
                const id = this.nextId++;
                this.rows.push({ id, karyawan_id: '', tb: 0, ts: 0, tk: 0, tc: 0, kribab: 0 });
                this.$nextTick(() => this.initTomSelect(id));
            },

            removeRow(index) {
                if (this.rows.length <= 1) return;
                const rowId = this.rows[index].id;
                const el = document.querySelector(`.karyawan-select[data-id="${rowId}"]`);
                if (el?.tomselect) el.tomselect.destroy();
                this.rows.splice(index, 1);
            },

            initTomSelect(id) {
                const el = document.querySelector(`.karyawan-select[data-id="${id}"]`);
                if (el && typeof TomSelect !== 'undefined') {
                    new TomSelect(el, {
                        create: false,
                        sortField: { field: 'text', direction: 'asc' },
                        placeholder: 'Pilih Karyawan...',
                    });
                }
            },

            totalQty(field) {
                return this.rows.reduce((sum, row) => sum + (parseInt(row[field]) || 0), 0);
            },
        }
    }
</script>
@endpush
@endsection
