@extends($layout ?? 'layouts.jihans')
@section('title', isset($karyawan) ? 'Edit Karyawan' : 'Tambah Karyawan')
@section('page-title', 'Master Data — ' . (isset($karyawan) ? 'Edit' : 'Tambah') . ' Karyawan')

@section('content')
<div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

    @if($errors->any())
    <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg border border-error/20">
        <div class="flex items-start gap-sm">
            <span class="material-symbols-outlined text-error mt-[2px]">error</span>
            <ul class="list-disc pl-md text-sm space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <form method="POST"
        action="{{ isset($karyawan) ? route(($routePrefix ?? 'master.') . 'karyawan.update', $karyawan) : route(($routePrefix ?? 'master.') . 'karyawan.store') }}"
        class="space-y-lg">
        @csrf
        @if(isset($karyawan)) @method('PUT') @endif

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant rounded-t-xl">
                <h3 class="font-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Data Karyawan</h3>
            </div>
            <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">

                <div class="md:col-span-2">
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Nama Karyawan <span class="text-error">*</span></label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="name" value="{{ old('name', $karyawan->name ?? '') }}" required
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                    </div>
                    @error('name')<p class="text-error font-label-sm mt-xs">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Telepon</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <input type="text" name="phone" value="{{ old('phone', $karyawan->phone ?? '') }}"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface py-sm px-sm outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-sm mt-auto pb-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $karyawan->is_active ?? true) ? 'checked' : '' }}
                        class="w-4 h-4 accent-primary">
                    <label for="is_active" class="font-label-md text-on-surface cursor-pointer">Aktif</label>
                </div>

                <div class="md:col-span-2">
                    <label class="block font-label-sm text-on-surface-variant mb-xs">Alamat</label>
                    <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                        <textarea name="address" rows="3"
                            class="bg-transparent border-none focus:ring-0 w-full font-body-md text-on-surface py-sm px-sm outline-none resize-none">{{ old('address', $karyawan->address ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-md pb-lg">
            <a href="{{ route(($routePrefix ?? 'master.') . 'karyawan.index') }}"
                class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-colors">
                <span class="material-symbols-outlined text-[18px]">save</span>
                {{ isset($karyawan) ? 'Perbarui' : 'Simpan' }}
            </button>
        </div>
    </form>
</div>
@endsection
