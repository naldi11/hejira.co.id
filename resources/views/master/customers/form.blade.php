@extends($layout ?? 'layouts.gudang')
@section('title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')
@section('page-title', 'Master Data — ' . (isset($customer) ? 'Edit Customer' : 'Tambah Customer'))

@section('content')
    <div class="p-margin-mobile md:p-margin-desktop w-full bg-surface">

        @if ($errors->any())
            <div class="mb-md bg-error-container text-on-error-container p-sm rounded-lg shadow-sm border border-error/20">
                <div class="flex items-start gap-sm">
                    <span class="material-symbols-outlined text-error mt-[2px]">error</span>
                    <div>
                        <h4 class="font-bold text-sm mb-xs">Terdapat beberapa kesalahan:</h4>
                        <ul class="list-disc pl-md text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @php
            $defaultScope = ($currentScope ?? 'gudang') === 'gudang' ? 'all' : ($currentScope ?? 'all');
        @endphp

        <form method="POST"
            action="{{ isset($customer) ? route(($routePrefix ?? 'master.') . 'customers.update', $customer) : route(($routePrefix ?? 'master.') . 'customers.store') }}"
            class="space-y-lg">
            @csrf
            @if(isset($customer)) @method('PUT') @endif

            {{-- Informasi Utama --}}
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
                <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant">
                    <h3 class="font-label-lg text-label-lg font-semibold text-on-surface-variant uppercase tracking-wider">Informasi Customer</h3>
                </div>
                <div class="p-md grid grid-cols-1 md:grid-cols-2 gap-md">

                    {{-- Nama --}}
                    <div class="md:col-span-2">
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Nama Customer <span class="text-error">*</span></label>
                        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required
                                placeholder="cth: Toko Maju Jaya"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                        </div>
                        @error('name') <p class="text-error font-label-sm text-label-sm mt-xs">{{ $message }}</p> @enderror
                    </div>

                    {{-- Tipe --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Tipe Customer <span class="text-error">*</span></label>
                        <select name="type" class="w-full border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 focus:border-primary outline-none">
                            <option value="Pelanggan Individual" {{ old('type', $customer->type ?? 'Pelanggan Individual') === 'Pelanggan Individual' ? 'selected' : '' }}>Pelanggan Individual</option>
                            <option value="Pelanggan Retail"     {{ old('type', $customer->type ?? '') === 'Pelanggan Retail'     ? 'selected' : '' }}>Pelanggan Retail</option>
                            <option value="Pelanggan Agen"       {{ old('type', $customer->type ?? '') === 'Pelanggan Agen'       ? 'selected' : '' }}>Pelanggan Agen</option>
                        </select>
                        @error('type') <p class="text-error font-label-sm text-label-sm mt-xs">{{ $message }}</p> @enderror
                    </div>

                    {{-- Visibilitas Entitas --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">
                            Tampilkan di Entitas
                            <span class="text-outline font-normal normal-case"> — otomatis dari login, centang untuk multi-entitas</span>
                        </label>
                        @include('master.partials.visibility-checkboxes', [
                            'scope' => $defaultScope,
                            'model' => $customer ?? null,
                            'isNew' => !isset($customer),
                        ])
                    </div>

                    {{-- Telepon --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Telepon</label>
                        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
                                placeholder="cth: 08123456789"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                        </div>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Email</label>
                        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                                placeholder="cth: toko@email.com"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="md:col-span-2">
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Alamat</label>
                        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <textarea name="address" rows="2" placeholder="Alamat lengkap (opsional)"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none resize-none">{{ old('address', $customer->address ?? '') }}</textarea>
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div class="md:col-span-2">
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Catatan</label>
                        <div class="bg-surface-container-low rounded-t-lg border-b-2 border-outline-variant focus-within:border-primary transition-colors">
                            <input type="text" name="notes" value="{{ old('notes', $customer->notes ?? '') }}"
                                placeholder="Catatan tambahan (opsional)"
                                class="bg-transparent border-none focus:ring-0 w-full font-body-md text-body-md text-on-surface placeholder-on-surface-variant py-sm px-sm outline-none">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-xs">Status</label>
                        <select name="is_active" class="w-full border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface font-body-md py-sm px-sm focus:ring-0 focus:border-primary outline-none">
                            <option value="1" {{ old('is_active', $customer->is_active ?? 1) == 1 ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ old('is_active', $customer->is_active ?? 1) == 0 ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-md pb-lg">
                <button type="submit"
                    class="inline-flex items-center gap-sm px-lg py-sm bg-primary text-on-primary rounded-lg font-label-lg text-label-lg shadow-sm hover:bg-on-primary-fixed-variant transition-all">
                    <span class="material-symbols-outlined text-[18px]">{{ isset($customer) ? 'save' : 'add' }}</span>
                    {{ isset($customer) ? 'Simpan Perubahan' : 'Tambah Customer' }}
                </button>
                <a href="{{ route(($routePrefix ?? 'master.') . 'customers.index') }}"
                    class="inline-flex items-center gap-sm px-md py-sm bg-surface-container border border-outline-variant text-on-surface-variant rounded-lg font-label-lg text-label-lg hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection
