@extends('layouts.gudang')
@section('title', 'Buat Transfer Keluar (DO)')
@section('page-title', 'Gudang — Buat Transfer Keluar')

@section('content')
<div class="mt-4 max-w-5xl"
     x-data="{
        toEntity: '{{ old('to_entity', $transferRequest ? $transferRequest->from_entity : '') }}',
        branchId: '{{ old('branch_id', $transferRequest && $transferRequest->from_entity === 'hendhys' ? $transferRequest->branch_id : '') }}',
        
        @if($transferRequest)
        items: {{ $transferRequest->details->filter(fn($d) => $d->quantity_approved > $d->quantity_sent)->map(fn($d)=>['product_id'=>$d->product_id,'product_name'=>$d->product->name,'stock'=>$d->product->current_stock??0,'quantity_approved'=>$d->quantity_approved,'quantity_sent'=>$d->quantity_sent,'quantity'=>max(0,$d->quantity_approved - $d->quantity_sent),'unit_id'=>$d->unit_id,'unit_name'=>$d->unit->abbreviation,'hpp_price'=>$d->product->hpp])->values()->toJson() }},
        @else
        items: [],
        @endif
        
        products: {{ $products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'stock'=>$p->current_stock??0,'unit_id'=>$p->unit_id,'unit_name'=>$p->unit->abbreviation,'hpp'=>$p->hpp])->toJson() }},
        
        addItem() {
            this.items.push({ product_id:'', product_name:'', stock:0, quantity:1, unit_id:'', unit_name:'', hpp_price:0 });
        },
        removeItem(i) { this.items.splice(i,1); },
        onProductChange(i, productId) {
            const p = this.products.find(x=>x.id==productId);
            if (p) {
                this.items[i].product_name = p.name;
                this.items[i].stock = p.stock;
                this.items[i].unit_id = p.unit_id;
                this.items[i].unit_name = p.unit_name;
                this.items[i].hpp_price = p.hpp;
            }
        }
     }">

<form method="POST" action="{{ route('gudang.transfer-out.store') }}" class="space-y-4">
    @csrf
    
    @if($transferRequest)
        <input type="hidden" name="request_id" value="{{ $transferRequest->id }}">
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex justify-between items-center mb-3">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Informasi Pengiriman (DO)</p>
            @if($transferRequest)
            <span class="bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded font-medium border border-indigo-100">Berdasarkan Request: {{ $transferRequest->request_number }}</span>
            @else
            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded font-medium border border-gray-200">Transfer Langsung (Tanpa Request)</span>
            @endif
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Entitas Tujuan <span class="text-red-500">*</span></label>
                @if($transferRequest)
                    <input type="hidden" name="to_entity" :value="toEntity">
                    <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-700 capitalize">
                        {{ $transferRequest->from_entity === 'hendhys' ? 'Hendhys (Cabang)' : 'Jihans (Stok Produksi)' }}
                    </div>
                @else
                    <select name="to_entity" x-model="toEntity" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        <option value="">Pilih Tujuan</option>
                        <option value="hendhys">Hendhys (Cabang/Outlet)</option>
                        <option value="jihans">Jihans (Produksi)</option>
                    </select>
                @endif
            </div>
            
            <div x-show="toEntity === 'hendhys'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cabang Hendhys <span class="text-red-500" x-show="toEntity === 'hendhys'">*</span></label>
                @if($transferRequest && $transferRequest->from_entity === 'hendhys')
                    <input type="hidden" name="branch_id" :value="branchId">
                    <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-700">
                        {{ $transferRequest->branch->name ?? '-' }}
                    </div>
                @else
                    <select name="branch_id" x-model="branchId" :required="toEntity === 'hendhys'" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        <option value="">Pilih Cabang</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }} ({{ ucfirst($b->type) }})</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transfer <span class="text-red-500">*</span></label>
                <input type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>

            <div class="col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Catatan pengiriman (opsional)..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Item Produk yang Dikirim</p>
            @if(!$transferRequest)
            <button type="button" @click="addItem()"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Item
            </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 border-b border-gray-100">
                        <th class="pb-2 text-left w-64">Produk</th>
                        @if($transferRequest)
                        <th class="pb-2 text-center w-32">Sisa Disetujui</th>
                        @endif
                        <th class="pb-2 text-center w-32">Stok Gudang</th>
                        <th class="pb-2 text-center w-32">Qty Dikirim <span class="text-red-500">*</span></th>
                        <th class="pb-2 text-left w-20">Satuan</th>
                        @if(!$transferRequest)
                        <th class="pb-2 w-8"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, i) in items" :key="i">
                        <tr class="border-b border-gray-50">
                            <td class="py-2 pr-2">
                                @if($transferRequest)
                                    <input type="hidden" :name="`items[${i}][product_id]`" x-model="item.product_id">
                                    <div class="text-sm font-medium text-gray-800" x-text="item.product_name"></div>
                                @else
                                    <select :name="`items[${i}][product_id]`" x-model="item.product_id"
                                            @change="onProductChange(i, item.product_id)" required
                                            class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                                        <option value="">Pilih produk...</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="`${p.name} (Stok: ${p.stock})`"></option>
                                        </template>
                                    </select>
                                @endif
                                <input type="hidden" :name="`items[${i}][hpp_price]`" x-model="item.hpp_price">
                            </td>
                            @if($transferRequest)
                            <td class="py-2 px-2 text-center">
                                <span class="text-xs text-gray-600 font-medium" x-text="(item.quantity_approved - item.quantity_sent)"></span>
                            </td>
                            @endif
                            <td class="py-2 px-2 text-center">
                                <span class="text-xs font-medium px-2 py-0.5 rounded" 
                                      :class="item.stock < item.quantity ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'"
                                      x-text="item.stock"></span>
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" :name="`items[${i}][quantity]`" x-model.number="item.quantity"
                                       min="0.001" :max="item.stock" step="0.001" required
                                       class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-center focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                                       :class="item.quantity > item.stock ? 'border-red-500 ring-1 ring-red-500' : ''">
                            </td>
                            <td class="py-2 px-2">
                                <input type="hidden" :name="`items[${i}][unit_id]`" x-model="item.unit_id">
                                <span x-text="item.unit_name || '-'" class="text-xs text-gray-500 font-mono"></span>
                            </td>
                            @if(!$transferRequest)
                            <td class="py-2 pl-2">
                                <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                            @endif
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="{{ $transferRequest ? '5' : '5' }}" class="py-4 text-center text-gray-400 text-xs">Belum ada item produk.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-100 text-sm text-blue-700">
            <strong>Catatan Sistem:</strong> Menyimpan transfer keluar akan secara otomatis <strong>mengurangi stok Gudang Utama</strong> dan <strong>menambah stok di entitas tujuan</strong> (Hendhys atau Jihans) menggunakan harga HPP terakhir (Average/Terakhir).
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
            Proses Transfer Keluar
        </button>
        <a href="{{ route('gudang.transfer-out.index') }}" class="border border-gray-300 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50">Batal</a>
    </div>
</form>
</div>
@endsection
