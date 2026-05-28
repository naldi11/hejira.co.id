@extends('layouts.jihans')
@section('title', 'Konfigurasi Produksi Tortilla')
@section('page-title', 'Konfigurasi Produksi Tortilla')

@section('content')
<div class="max-w-2xl space-y-6">

    <p class="text-sm text-slate-500">
        Tentukan produk mana yang mewakili setiap varian tortilla. Konfigurasi ini digunakan setiap kali ada input produksi baru.
    </p>

    <form method="POST" action="{{ route('jihans.master.production-config.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm divide-y divide-slate-100">

            @php
            $variants = [
                ['key' => 'tb', 'label' => 'TB — Tortilla Besar',    'color' => 'bg-orange-100 text-orange-700'],
                ['key' => 'ts', 'label' => 'TS — Tortilla Sedang',   'color' => 'bg-blue-100 text-blue-700'],
                ['key' => 'tk', 'label' => 'TK — Tortilla Kecil',    'color' => 'bg-green-100 text-green-700'],
                ['key' => 'tc', 'label' => 'TC — Tortilla Catering', 'color' => 'bg-purple-100 text-purple-700'],
                ['key' => 'kribab', 'label' => 'Kribab',             'color' => 'bg-rose-100 text-rose-700'],
            ];
            @endphp

            @foreach($variants as $v)
            @php $field = $v['key'] . '_product_id'; @endphp
            <div class="flex items-center gap-6 px-6 py-5">
                <div class="w-44 shrink-0">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black {{ $v['color'] }}">
                        {{ $v['label'] }}
                    </span>
                </div>
                <div class="flex-1">
                    <select name="{{ $field }}" id="{{ $field }}"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all">
                        <option value="">— Belum dikonfigurasi —</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                {{ old($field, $config->$field) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    @error($field)
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="w-6 shrink-0 flex items-center justify-center">
                    @if($config->$field)
                        <span class="material-symbols-outlined text-green-500 text-[20px]">check_circle</span>
                    @else
                        <span class="material-symbols-outlined text-slate-300 text-[20px]">radio_button_unchecked</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-bold hover:bg-orange-700 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[18px]">save</span>
                Simpan Konfigurasi
            </button>
        </div>
    </form>

    @if($config->updated_by && $config->updated_at)
    <p class="text-xs text-slate-400 text-right">
        Terakhir diubah: {{ $config->updated_at->format('d M Y H:i') }}
    </p>
    @endif

</div>
@endsection
