@extends($layout ?? 'layouts.gudang')
@section('title', 'Tarif Produksi Tortilla')
@section('page-title', 'Master Data — Tarif Produksi Tortilla')

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

        @if (session('success'))
            <div class="mb-md bg-tertiary-container text-on-tertiary-container p-sm rounded-lg shadow-sm border border-tertiary/20 flex items-center gap-sm">
                <span class="material-symbols-outlined text-tertiary">check_circle</span>
                <p class="text-sm font-medium">{{ session('success') }}</p>
            </div>
        @endif

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

        <div class="max-w-4xl">
            <form method="POST" action="{{ route($routePrefix . 'production-rates.update') }}" class="space-y-lg">
                @csrf
                @method('PUT')

                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                    <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant">
                        <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">
                            Pengaturan Tarif Upah Karyawan</h3>
                    </div>
                    
                    <div class="p-md space-y-md">
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            Tentukan tarif upah yang dibayarkan kepada karyawan untuk setiap satuan produk tortilla yang dihasilkan. 
                            Tarif ini akan digunakan sebagai dasar perhitungan gaji otomatis pada modul produksi tortilla.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                            {{-- TB Rate --}}
                            <div>
                                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tarif TB (Tortilla Besar) <span class="text-error">*</span></label>
                                <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors flex items-center">
                                    <span class="pl-sm text-on-surface-variant font-body-md">Rp</span>
                                    <input type="number" name="tb_rate" value="{{ old('tb_rate', $rate->tb_rate ?? 0) }}" required min="0" step="0.01"
                                        class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                                </div>
                            </div>

                            {{-- TS Rate --}}
                            <div>
                                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tarif TS (Tortilla Sedang) <span class="text-error">*</span></label>
                                <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors flex items-center">
                                    <span class="pl-sm text-on-surface-variant font-body-md">Rp</span>
                                    <input type="number" name="ts_rate" value="{{ old('ts_rate', $rate->ts_rate ?? 0) }}" required min="0" step="0.01"
                                        class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                                </div>
                            </div>

                            {{-- TK Rate --}}
                            <div>
                                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tarif TK (Tortilla Kecil) <span class="text-error">*</span></label>
                                <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors flex items-center">
                                    <span class="pl-sm text-on-surface-variant font-body-md">Rp</span>
                                    <input type="number" name="tk_rate" value="{{ old('tk_rate', $rate->tk_rate ?? 0) }}" required min="0" step="0.01"
                                        class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                                </div>
                            </div>

                            {{-- TC Rate --}}
                            <div>
                                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tarif TC <span class="text-error">*</span></label>
                                <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors flex items-center">
                                    <span class="pl-sm text-on-surface-variant font-body-md">Rp</span>
                                    <input type="number" name="tc_rate" value="{{ old('tc_rate', $rate->tc_rate ?? 0) }}" required min="0" step="0.01"
                                        class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                                </div>
                            </div>

                            {{-- KRIBAB Rate --}}
                            <div>
                                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tarif KRIBAB <span class="text-error">*</span></label>
                                <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors flex items-center">
                                    <span class="pl-sm text-on-surface-variant font-body-md">Rp</span>
                                    <input type="number" name="kribab_rate" value="{{ old('kribab_rate', $rate->kribab_rate ?? 0) }}" required min="0" step="0.01"
                                        class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface py-sm px-sm outline-none">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Catatan / Keterangan</label>
                            <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                                <textarea name="notes" rows="3" placeholder="Catatan tambahan mengenai tarif ini..."
                                    class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none resize-none">{{ old('notes', $rate->notes ?? '') }}</textarea>
                            </div>
                        </div>

                        {{-- Mapping Produk ke Stok — hanya tampil untuk Jihans --}}
                        @if($currentScope === 'jihans')
                        <div class="border-t border-outline-variant pt-md mt-md">
                            <h4 class="font-label-md text-label-md font-semibold text-on-surface-variant mb-xs uppercase tracking-wider">
                                Mapping Varian ke Produk Stok
                            </h4>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mb-md">
                                Pilih produk yang stoknya bertambah otomatis saat produksi tortilla disimpan.
                            </p>

                            @if($producedProducts->isEmpty())
                                <div class="bg-surface-container-low rounded-lg p-sm flex items-center gap-sm border border-outline-variant">
                                    <span class="material-symbols-outlined text-outline">info</span>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                                        Belum ada produk Jihans dengan Sumber Stok "Produksi Sendiri".
                                        <a href="{{ route($routePrefix . 'products.create') }}" class="underline font-medium text-primary">Buat produk</a> terlebih dahulu.
                                    </p>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                                    @foreach([
                                        ['tb_product_id',     'TB — Tortilla Besar'],
                                        ['ts_product_id',     'TS — Tortilla Sedang'],
                                        ['tk_product_id',     'TK — Tortilla Kecil'],
                                        ['tc_product_id',     'TC — Tortilla Catering'],
                                        ['kribab_product_id', 'KRIBAB — Sisa Potongan'],
                                    ] as [$field, $label])
                                        <div>
                                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">{{ $label }}</label>
                                            <select name="{{ $field }}"
                                                class="w-full border border-outline-variant rounded-lg text-body-md bg-surface-container-lowest py-sm px-sm">
                                                <option value="">— Belum di-mapping —</option>
                                                @foreach($producedProducts as $prod)
                                                    <option value="{{ $prod->id }}"
                                                        {{ old($field, $rate?->{$field}) == $prod->id ? 'selected' : '' }}>
                                                        {{ $prod->name }}{{ $prod->unit ? ' ('.$prod->unit->abbreviation.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endif
                    </div>

                    <div class="px-md py-sm bg-surface-container-low border-t border-outline-variant flex justify-between items-center">
                        <p class="text-xs text-on-surface-variant italic">
                            @if(isset($rate) && $rate->updated_at)
                                Terakhir diperbarui: {{ $rate->updated_at->format('d M Y H:i') }}
                            @endif
                        </p>
                        <button type="submit" class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all">
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            Simpan Tarif & Mapping
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
