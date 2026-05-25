@extends('layouts.hendhys')
@section('title', 'Detail Request')
@section('page-title', 'Detail Request: ' . $transferRequest->request_number)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('hendhys.transfer-requests.index') }}" class="text-[#d97706] hover:text-[#b45309] font-medium text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-100 flex flex-wrap gap-6 items-center justify-between bg-[#faf7f5]">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $transferRequest->request_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">Tanggal: {{ \Carbon\Carbon::parse($transferRequest->date)->format('d F Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Status Permintaan</p>
                @if($transferRequest->status == 'pending')
                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold uppercase tracking-wider">Pending</span>
                @elseif($transferRequest->status == 'approved')
                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider">Approved</span>
                @elseif($transferRequest->status == 'completed')
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase tracking-wider">Completed</span>
                @elseif($transferRequest->status == 'partial')
                    <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-bold uppercase tracking-wider">Partial</span>
                @else
                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold uppercase tracking-wider">Rejected</span>
                @endif
            </div>
        </div>
        
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-100">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Pemohon</p>
                <p class="font-semibold text-gray-800">{{ $transferRequest->creator->name }}</p>
                <p class="text-sm text-gray-500">Pusat Hendhys Bakery</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-1">Catatan Pemohon</p>
                <p class="font-medium text-gray-800">{{ $transferRequest->notes ?: '-' }}</p>
            </div>
        </div>
        
        @if($transferRequest->approval_notes)
        <div class="p-6 bg-blue-50 border-b border-blue-100">
            <p class="text-xs text-blue-500 font-medium uppercase tracking-wider mb-1">Catatan Admin Gudang ({{ $transferRequest->approver->name ?? 'Admin' }})</p>
            <p class="font-medium text-blue-800">{{ $transferRequest->approval_notes }}</p>
        </div>
        @endif

        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Rincian Barang</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-y border-gray-200">
                            <th class="py-3 px-4 font-medium">No</th>
                            <th class="py-3 px-4 font-medium">Nama Bahan Baku</th>
                            <th class="py-3 px-4 font-medium text-right">Qty Diminta</th>
                            <th class="py-3 px-4 font-medium text-right">Qty Disetujui</th>
                            <th class="py-3 px-4 font-medium">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @foreach($transferRequest->details as $index => $detail)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-gray-500">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-medium text-gray-800">{{ $detail->product->name }}</td>
                            <td class="py-3 px-4 text-right font-bold text-gray-800">{{ (float) $detail->quantity_requested }}</td>
                            <td class="py-3 px-4 text-right font-bold {{ $detail->quantity_approved < $detail->quantity_requested ? 'text-red-500' : 'text-green-600' }}">
                                {{ $detail->quantity_approved !== null ? (float) $detail->quantity_approved : '-' }}
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $detail->unit->code }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TransferOut / BAST section --}}
    @if($transferRequest->transferOuts->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-bold text-gray-800 text-sm">Pengiriman dari Gudang</h3>
        </div>
        <div class="p-6 space-y-3">
            @foreach($transferRequest->transferOuts as $do)
            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3 border border-gray-200">
                <div>
                    <span class="text-sm font-mono font-bold text-gray-700">{{ $do->transfer_number }}</span>
                    <span class="ml-2 text-xs text-gray-500">{{ $do->date->format('d M Y') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($do->status === 'sent')
                    <a href="{{ route('hendhys.transfer-requests.receive-form-gudang', $do->id) }}"
                       class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700 font-medium">Konfirmasi Terima</a>
                    @elseif($do->status === 'received')
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded font-medium">Diterima</span>
                    <a href="{{ route('hendhys.transfer-requests.print-gudang', $do->id) }}" target="_blank"
                       class="text-xs bg-gray-700 text-white px-2 py-1 rounded hover:bg-gray-800 font-medium">Cetak BAST</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
