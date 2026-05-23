@extends('layouts.hendhys')
@section('title', 'Catat Produksi')
@section('page-title', 'Catat Hasil Produksi Bakery')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full bg-surface" x-data="productionForm()">

        @if ($errors->any())
            <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg shadow-sm border border-error/20">
                <div class="flex items-start gap-sm">
                    <span class="material-symbols-outlined text-error mt-[2px]">error</span>
                    <div>
                        <h4 class="font-bold text-sm mb-xs">Terdapat beberapa kesalahan:</h4>
                        <ul class="list-disc pl-md text-sm text-on-error-container/90 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('hendhys.productions.store') }}" method="POST" class="space-y-lg">
            @csrf

            {{-- Header Info --}}
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
                <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl">
                    <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">
                        Informasi Produksi</h3>
                </div>

                <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tanggal Produksi
                            <span class="text-error">*</span></label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Catatan</label>
                        <div
                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="notes" value="{{ old('notes') }}"
                                placeholder="Opsional (misal: Sesi Siang)"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Daftar Produk Jadi --}}
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
                <div
                    class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl flex items-center justify-between">
                    <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">
                        Daftar Produk Jadi</h3>
                    <button type="button" @click="addItem"
                        class="text-sm bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        Tambah Baris
                    </button>
                </div>

                <div class="overflow-x-auto overflow-visible p-md">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-on-surface-variant text-label-sm font-bold uppercase tracking-wider border-b border-outline-variant">
                                <th class="pb-3 pt-2 px-2">Produk</th>
                                <th class="pb-3 pt-2 px-2 w-32">Kuantitas</th>
                                <th class="pb-3 pt-2 px-2 w-32">Satuan</th>
                                <th class="pb-3 pt-2 px-2 w-16 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr
                                    class="border-b border-outline-variant/30 last:border-0 hover:bg-surface-container-lowest transition-colors">
                                    <td class="py-3 px-2">
                                        <div
                                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                                            <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                                class="bg-transparent border-none focus:ring-0 w-full font-body-sm text-body-sm text-on-surface py-sm px-sm outline-none">
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    <td class="py-3 px-2">
                                        <div
                                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                                            <input type="number" step="1" min="1"
                                                :name="`items[${index}][quantity_produced]`" x-model="item.qty" @input="item.qty = Math.floor(item.qty)" required
                                                class="bg-transparent border-none focus:ring-0 w-full font-body-sm text-body-sm text-on-surface py-sm px-sm outline-none">
                                        </div>
                                    </td>
                                    <td class="py-3 px-2">
                                        <div
                                            class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                                            <select :name="`items[${index}][unit_id]`" x-model="item.unit_id" required
                                                class="bg-transparent border-none focus:ring-0 w-full font-body-sm text-body-sm text-on-surface py-sm px-sm outline-none">
                                                @foreach($units as $u)
                                                    <option value="{{ $u->id }}">{{ $u->abbreviation }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                            class="text-error hover:bg-error-container p-2 rounded-lg transition-colors flex items-center justify-center mx-auto">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-md pb-lg">
                <a href="{{ route('hendhys.productions.index') }}"
                    class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Produksi
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productionForm', () => ({
                items: [{ id: Date.now(), product_id: '', qty: '', unit_id: '' }],

                addItem() {
                    this.items.push({ id: Date.now(), product_id: '', qty: '', unit_id: '' });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                }
            }))
        })
    </script>
@endsection