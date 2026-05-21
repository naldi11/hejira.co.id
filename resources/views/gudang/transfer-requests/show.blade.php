@extends('layouts.gudang')
@section('title', 'Detail Transfer Request '.$transferRequest->request_number)
@section('page-title', 'Transfer Request — '.$transferRequest->request_number)

@section('content')
@php
    $statusClass = [
        'pending'   => 'bg-yellow-100 text-yellow-700',
        'approved'  => 'bg-blue-100 text-blue-700',
        'partial'   => 'bg-indigo-100 text-indigo-700',
        'completed' => 'bg-green-100 text-green-700',
        'rejected'  => 'bg-red-100 text-red-700',
    ];
@endphp

<div x-data="{ approveModalOpen: false, rejectModalOpen: false }">
    <div class="mt-4 max-w-5xl">
        <div class="flex justify-between items-start mb-6">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-2xl font-bold text-gray-800">{{ $transferRequest->request_number }}</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold uppercase tracking-wider {{ $statusClass[$transferRequest->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $transferRequest->status }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Diminta pada {{ \Carbon\Carbon::parse($transferRequest->date)->format('d F Y') }} oleh {{ $transferRequest->requester->name ?? '-' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('gudang.transfer-requests.index') }}" class="border border-gray-300 bg-white text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                    Kembali
                </a>
                @if($transferRequest->status === 'pending')
                    <button type="button" @click="rejectModalOpen = true" class="bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 px-4 py-2 rounded-lg text-sm font-medium">
                        Tolak Request
                    </button>
                    <button type="button" @click="approveModalOpen = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Proses Approval
                    </button>
                @elseif(in_array($transferRequest->status, ['approved', 'partial']))
                    <a href="{{ route('gudang.transfer-out.create', ['transfer_request_id' => $transferRequest->id]) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        Buat Transfer Keluar (DO)
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            {{-- Info Asal Request --}}
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Informasi Peminta</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Asal Request (Tujuan Kirim)</p>
                        <p class="text-sm font-medium text-gray-800">
                            @if($transferRequest->from_entity === 'hendhys')
                                Cabang Hendhys ({{ $transferRequest->branch->name ?? '-' }})
                            @else
                                Stok Gudang Jihans
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Catatan Permintaan</p>
                        <p class="text-sm text-gray-800">{{ $transferRequest->notes ?: '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Info Approval --}}
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Status Approval</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Diproses Oleh</p>
                        <p class="text-sm font-medium text-gray-800">{{ $transferRequest->approver->name ?? 'Belum diproses' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Tanggal Proses</p>
                        <p class="text-sm text-gray-800">{{ $transferRequest->approved_at ? \Carbon\Carbon::parse($transferRequest->approved_at)->format('d M Y H:i') : '-' }}</p>
                    </div>
                    @if($transferRequest->status === 'rejected')
                    <div>
                        <p class="text-xs text-red-500 font-semibold">Alasan Penolakan</p>
                        <p class="text-sm text-red-600 bg-red-50 p-2 rounded border border-red-100 mt-1">{{ $transferRequest->rejection_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Detail Items --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Detail Item Permintaan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Produk</th>
                            <th class="px-5 py-3 font-medium text-center">Qty Diminta</th>
                            <th class="px-5 py-3 font-medium text-center">Qty Disetujui</th>
                            <th class="px-5 py-3 font-medium text-center">Satuan</th>
                            <th class="px-5 py-3 font-medium text-center">Sisa Belum Dikirim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($transferRequest->details as $item)
                        @php
                            $sisaKirim = $item->quantity_approved - $item->quantity_sent;
                            $sisaKirim = max(0, $sisaKirim);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $item->product->name }}</td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ (int) $item->quantity_requested }}</td>
                            <td class="px-5 py-3 text-center font-bold {{ $item->quantity_approved > 0 ? 'text-indigo-600' : 'text-gray-400' }}">
                                {{ $transferRequest->status === 'pending' ? '?' : (int) $item->quantity_approved }}
                            </td>
                            <td class="px-5 py-3 text-center text-gray-500">{{ $item->unit->abbreviation ?? '-' }}</td>
                            <td class="px-5 py-3 text-center">
                                @if(in_array($transferRequest->status, ['approved', 'partial', 'completed']))
                                    <span class="inline-flex items-center justify-center px-2 py-1 rounded text-xs font-bold {{ $sisaKirim > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }}">
                                        {{ (int) $sisaKirim }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- List Transfer Out Terkait --}}
        @if($transferRequest->transferOuts->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Dokumen Transfer Keluar (DO) Terkait</h3>
            </div>
            <div class="p-3">
                <ul class="space-y-2">
                    @foreach($transferRequest->transferOuts as $to)
                    <li class="flex items-center justify-between p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-green-50 text-green-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                            </div>
                            <div>
                                <a href="{{ route('gudang.transfer-out.show', $to) }}" class="font-medium text-indigo-600 hover:underline">{{ $to->transfer_number }}</a>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($to->date)->format('d M Y') }} - Status: {{ $to->status }}</p>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    {{-- Modal Approval --}}
    <div x-show="approveModalOpen" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="approveModalOpen" x-transition.opacity></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="approveModalOpen" x-transition @click.away="approveModalOpen = false" class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <form method="POST" action="{{ route('gudang.transfer-requests.approve', $transferRequest) }}">
                        @csrf
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">Proses Approval Transfer Request</h3>
                            <p class="text-sm text-gray-500 mb-4">Silakan tentukan jumlah yang disetujui untuk masing-masing item. Sistem akan menyesuaikan status menjadi Partial jika ada item yang disetujui kurang dari yang diminta.</p>
                            
                            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg mb-4">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-gray-500 text-left sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 font-medium">Produk</th>
                                            <th class="px-3 py-2 font-medium text-center">Diminta</th>
                                            <th class="px-3 py-2 font-medium text-center">Disetujui</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($transferRequest->details as $idx => $item)
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-gray-800">
                                                {{ $item->product->name }}
                                                <input type="hidden" name="items[{{$idx}}][id]" value="{{ $item->id }}">
                                            </td>
                                            <td class="px-3 py-2 text-center text-gray-500">{{ (int) $item->quantity_requested }} {{ $item->unit->abbreviation ?? '' }}</td>
                                            <td class="px-3 py-2">
                                                <input type="number" name="items[{{$idx}}][quantity_approved]" value="{{ (int) $item->quantity_requested }}" min="0" step="1" required
                                                       class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none text-center">
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Approval (Opsional)</label>
                                <input type="text" name="notes" placeholder="Catatan tambahan..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:ml-3 sm:w-auto">
                                Simpan Approval
                            </button>
                            <button type="button" @click="approveModalOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Reject --}}
    <div x-show="rejectModalOpen" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="rejectModalOpen" x-transition.opacity></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="rejectModalOpen" x-transition @click.away="rejectModalOpen = false" class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <form method="POST" action="{{ route('gudang.transfer-requests.reject', $transferRequest) }}">
                        @csrf
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900">Tolak Transfer Request</h3>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                                        <textarea name="rejection_reason" required rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-300 focus:outline-none" placeholder="Masukkan alasan kenapa request ini ditolak..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 sm:ml-3 sm:w-auto">
                                Konfirmasi Tolak
                            </button>
                            <button type="button" @click="rejectModalOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
