@extends('layouts.jihans')
@section('title', 'Input Produksi Tortilla')
@section('page-title', 'Form Input Produksi Tortilla')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-control { border-radius: 0.5rem; min-height: 42px; background-color: #fbf9f8; }
        .ts-dropdown { border-radius: 0.5rem; }
    </style>
@endpush

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full bg-surface" 
         x-data="productionForm({
            rates: {
                tb: {{ $rates->tb_rate }},
                ts: {{ $rates->ts_rate }},
                tk: {{ $rates->tk_rate }},
                tc: {{ $rates->tc_rate }},
                kribab: {{ $rates->kribab_rate }}
            }
         })">

        <form method="POST" action="{{ route('jihans.tortilla.store') }}" class="space-y-lg">
            @csrf

            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant">
                    <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Informasi Sesi</h3>
                </div>
                <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tanggal Produksi <span class="text-error">*</span></label>
                        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Catatan Sesi</label>
                        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Contoh: Produksi Pagi"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant flex justify-between items-center">
                    <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Data Produksi Karyawan</h3>
                    <button type="button" @click="addRow()"
                        class="inline-flex items-center gap-xs px-sm py-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:bg-on-primary-fixed-variant transition-colors">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        Tambah Karyawan
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant">
                                <th class="px-md py-sm font-label-sm text-on-surface-variant w-1/4">Nama Karyawan</th>
                                <th class="px-sm py-sm font-label-sm text-on-surface-variant text-center w-24">TB</th>
                                <th class="px-sm py-sm font-label-sm text-on-surface-variant text-center w-24">TS</th>
                                <th class="px-sm py-sm font-label-sm text-on-surface-variant text-center w-24">TK</th>
                                <th class="px-sm py-sm font-label-sm text-on-surface-variant text-center w-24">TC</th>
                                <th class="px-sm py-sm font-label-sm text-on-surface-variant text-center w-24">KRIBAB</th>
                                <th class="px-md py-sm font-label-sm text-on-surface-variant text-right">Total Upah</th>
                                <th class="px-md py-sm font-label-sm text-on-surface-variant text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container">
                            <template x-for="(row, index) in rows" :key="row.id">
                                <tr class="hover:bg-surface-container-lowest/50 transition-colors">
                                    <td class="px-md py-sm">
                                        <select :name="`details[${index}][karyawan_id]`" class="karyawan-select" required
                                            x-model="row.karyawan_id" :data-id="row.id">
                                            <option value="">Pilih Karyawan</option>
                                            @foreach($karyawans as $k)
                                                <option value="{{ $k->id }}">{{ $k->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-sm py-sm">
                                        <input type="number" :name="`details[${index}][tb_qty]`" x-model.number="row.tb" min="0"
                                            class="w-full text-center py-xs px-xs border border-outline-variant rounded-md focus:ring-1 focus:ring-primary outline-none">
                                    </td>
                                    <td class="px-sm py-sm">
                                        <input type="number" :name="`details[${index}][ts_qty]`" x-model.number="row.ts" min="0"
                                            class="w-full text-center py-xs px-xs border border-outline-variant rounded-md focus:ring-1 focus:ring-primary outline-none">
                                    </td>
                                    <td class="px-sm py-sm">
                                        <input type="number" :name="`details[${index}][tk_qty]`" x-model.number="row.tk" min="0"
                                            class="w-full text-center py-xs px-xs border border-outline-variant rounded-md focus:ring-1 focus:ring-primary outline-none">
                                    </td>
                                    <td class="px-sm py-sm">
                                        <input type="number" :name="`details[${index}][tc_qty]`" x-model.number="row.tc" min="0"
                                            class="w-full text-center py-xs px-xs border border-outline-variant rounded-md focus:ring-1 focus:ring-primary outline-none">
                                    </td>
                                    <td class="px-sm py-sm">
                                        <input type="number" :name="`details[${index}][kribab_qty]`" x-model.number="row.kribab" min="0"
                                            class="w-full text-center py-xs px-xs border border-outline-variant rounded-md focus:ring-1 focus:ring-primary outline-none">
                                    </td>
                                    <td class="px-md py-sm text-right font-bold text-on-surface" x-text="formatCurrency(calculateRowTotal(row))"></td>
                                    <td class="px-md py-sm text-center">
                                        <button type="button" @click="removeRow(index)" class="text-error hover:bg-error-container p-xs rounded-full transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-surface-container-low font-bold">
                                <td class="px-md py-sm">TOTAL KESELURUHAN</td>
                                <td class="px-sm py-sm text-center" x-text="totalQty('tb')"></td>
                                <td class="px-sm py-sm text-center" x-text="totalQty('ts')"></td>
                                <td class="px-sm py-sm text-center" x-text="totalQty('tk')"></td>
                                <td class="px-sm py-sm text-center" x-text="totalQty('tc')"></td>
                                <td class="px-sm py-sm text-center" x-text="totalQty('kribab')"></td>
                                <td class="px-md py-sm text-right text-primary text-lg" x-text="formatCurrency(grandTotal())"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="flex items-center gap-md pb-lg">
                <button type="submit" class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Data Produksi
                </button>
                <a href="{{ route('jihans.tortilla.index') }}" class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script>
            function productionForm(config) {
                return {
                    rates: config.rates,
                    rows: [],
                    nextId: 1,
                    
                    init() {
                        this.addRow();
                    },

                    addRow() {
                        const id = this.nextId++;
                        this.rows.push({
                            id: id,
                            karyawan_id: '',
                            tb: 0,
                            ts: 0,
                            tk: 0,
                            tc: 0,
                            kribab: 0
                        });
                        
                        this.$nextTick(() => {
                            this.initTomSelect(id);
                        });
                    },

                    removeRow(index) {
                        if (this.rows.length > 1) {
                            const rowId = this.rows[index].id;
                            const select = document.querySelector(`.karyawan-select[data-id="${rowId}"]`);
                            if (select && select.tomselect) {
                                select.tomselect.destroy();
                            }
                            this.rows.splice(index, 1);
                        }
                    },

                    initTomSelect(id) {
                        const el = document.querySelector(`.karyawan-select[data-id="${id}"]`);
                        if (el) {
                            new TomSelect(el, {
                                create: false,
                                sortField: { field: "text", direction: "asc" },
                                placeholder: 'Pilih Karyawan'
                            });
                        }
                    },

                    calculateRowTotal(row) {
                        return (row.tb * this.rates.tb) +
                               (row.ts * this.rates.ts) +
                               (row.tk * this.rates.tk) +
                               (row.tc * this.rates.tc) +
                               (row.kribab * this.rates.kribab);
                    },

                    grandTotal() {
                        return this.rows.reduce((sum, row) => sum + this.calculateRowTotal(row), 0);
                    },

                    totalQty(field) {
                        return this.rows.reduce((sum, row) => sum + (row[field] || 0), 0);
                    },

                    formatCurrency(value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                    }
                }
            }
        </script>
    @endpush
@endsection
