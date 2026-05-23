@extends('layouts.hendhys')
@section('title', 'Proses Pengiriman ke Cabang')
@section('page-title', 'Form Distribusi Barang ke Cabang')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('hendhys.branch-requests.show', request('request_id')) }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Detail Request
        </a>
    </div>

    @if(!$branchRequest)
        <div class="bg-red-50 text-red-600 p-4 rounded-lg">Data request tidak ditemukan atau sudah diproses.</div>
    @else
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form action="{{ route('hendhys.transfer-to-branch.store') }}" method="POST">
            @csrf
            <input type="hidden" name="request_id" value="{{ $branchRequest->id }}">
            
            <div class="p-6 border-b border-gray-100 bg-amber-50/30 flex flex-wrap gap-6 justify-between">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">Referensi Request: {{ $branchRequest->request_number }}</h3>
                    <p class="text-sm text-gray-600 mt-1">Tujuan: <span class="font-bold text-gray-800">{{ $branchRequest->branch->name }}</span></p>
                </div>
            </div>

            <div class="p-6 border-b border-gray-100 bg-[#faf7f5]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengiriman <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
                        @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Pengiriman</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Misal: Dikirim via kurir internal"
                               class="w-full border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] bg-white">
                        @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Persetujuan & Pengiriman Item</h3>
                
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 text-sm text-blue-700">
                    <p>Ubah nilai <strong>Qty Disetujui (Kirim)</strong> jika stok pusat tidak mencukupi untuk memenuhi seluruh permintaan cabang. Isi "0" jika item ditolak seluruhnya.</p>
                </div>

                <div class="overflow-x-auto overflow-visible">
                    <table class="w-full text-left border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                                <th class="py-3 px-4 font-medium border-r border-gray-200">Produk Bakery</th>
                                <th class="py-3 px-4 font-medium border-r border-gray-200 text-right">Qty Diminta</th>
                                <th class="py-3 px-4 font-medium border-r border-gray-200 w-48 text-right bg-amber-50">Qty Disetujui (Kirim) <span class="text-red-500">*</span></th>
                                <th class="py-3 px-4 font-medium">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @foreach($branchRequest->details as $index => $detail)
                            <tr>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $detail->product_id }}">
                                <input type="hidden" name="items[{{ $index }}][unit_id]" value="{{ $detail->unit_id }}">
                                <input type="hidden" name="items[{{ $index }}][detail_id]" value="{{ $detail->id }}">
                                
                                <td class="py-3 px-4 font-medium text-gray-800 border-r border-gray-200">{{ $detail->product->name }}</td>
                                <td class="py-3 px-4 text-right font-bold text-gray-600 border-r border-gray-200">{{ (int) $detail->quantity_requested }}</td>
                                <td class="py-2 px-2 border-r border-gray-200 bg-amber-50/30">
                                    <input type="number" step="1" min="1" max="{{ (int) $detail->quantity_requested }}" name="items[{{ $index }}][quantity]" value="{{ (int) $detail->quantity_requested }}" required
                                           class="w-full text-right text-sm border-gray-300 rounded-lg focus:ring-[#d97706] focus:border-[#d97706] font-bold text-[#d97706]">
                                </td>
                                <td class="py-3 px-4 text-gray-600">{{ $detail->unit->code }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                <a href="{{ route('hendhys.branch-requests.show', request('request_id')) }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</a>
                <button type="submit" onclick="return confirm('Proses pengiriman ini akan memotong stok Pusat. Lanjutkan?')" class="px-5 py-2.5 text-sm font-medium text-white bg-[#d97706] hover:bg-[#b45309] rounded-lg transition-colors shadow-sm">
                    Proses & Kirim Barang
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
