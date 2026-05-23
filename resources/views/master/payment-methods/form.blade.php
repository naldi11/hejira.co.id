@extends($layout ?? 'layouts.gudang')
@section('title', isset($method) ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran')
@section('page-title', 'Master Data — ' . (isset($method) ? 'Edit' : 'Tambah') . ' Metode Pembayaran')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

    @if($errors->any())
    <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg border border-error/20">
        <div class="flex items-start gap-sm">
            <span class="material-symbols-outlined text-error mt-[2px]">error</span>
            <ul class="list-disc pl-md text-sm space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST"
        action="{{ isset($method) ? route(($routePrefix ?? 'master.') . 'payment-methods.update', $method) : route(($routePrefix ?? 'master.') . 'payment-methods.store') }}"
        enctype="multipart/form-data"
        class="space-y-lg">
        @csrf
        @if(isset($method)) @method('PUT') @endif

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl">
                <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Informasi Metode Pembayaran</h3>
            </div>
            <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">

                <div class="md:col-span-2">
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Nama Metode <span class="text-error">*</span></label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="name" value="{{ old('name', $method->name ?? '') }}" required
                            placeholder="cth: BCA Transfer, QRIS Jihan's, Tunai"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                    @error('name')<p class="text-error font-label-sm mt-xs">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Nama Bank</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="bank_name" value="{{ old('bank_name', $method->bank_name ?? '') }}"
                            placeholder="cth: BCA, Mandiri, BRI (kosongkan untuk Tunai)"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Nomor Rekening</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="account_number" value="{{ old('account_number', $method->account_number ?? '') }}"
                            placeholder="cth: 1234567890"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Atas Nama</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="account_name" value="{{ old('account_name', $method->account_name ?? '') }}"
                            placeholder="cth: Jihan Santoso"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Gambar QR / Logo Bank <span class="text-on-surface-variant/60 font-label-sm">(opsional, max 2MB)</span></label>
                    @if(isset($method) && $method->image)
                        <div class="mb-xs">
                            <img src="{{ Storage::url($method->image) }}" class="h-16 object-contain border border-outline-variant rounded-lg p-xs" alt="Gambar saat ini">
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*"
                        class="w-full text-sm text-on-surface-variant file:mr-sm file:py-xs file:px-sm file:rounded-lg file:border-0 file:text-sm file:bg-primary-container file:text-on-primary-container hover:file:bg-primary hover:file:text-on-primary transition-colors">
                    @error('image')<p class="text-error font-label-sm mt-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $method->is_active ?? true) ? 'checked' : '' }}
                        class="w-4 h-4 accent-primary">
                    <label for="is_active" class="font-label-md text-on-surface cursor-pointer">Aktif</label>
                </div>

            </div>
        </div>

        <div class="flex items-center justify-end gap-md pb-lg">
            <a href="{{ route(($routePrefix ?? 'master.') . 'payment-methods.index') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                <span class="material-symbols-outlined text-[18px]">save</span>
                {{ isset($method) ? 'Perbarui' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
@endsection
