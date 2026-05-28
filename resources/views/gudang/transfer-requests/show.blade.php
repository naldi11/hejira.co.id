@extends('layouts.gudang')
@section('title', 'Detail Transfer Request')
@section('page-title', 'Review Permintaan')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 pb-12">

    {{-- Validation Errors --}}
    @if ($errors->any())
    <div class="bg-rose-50 border border-rose-200 rounded-3xl p-6 text-rose-800 space-y-2">
        <div class="flex items-center gap-2 font-black text-sm uppercase tracking-wider">
            <span class="material-symbols-outlined text-[20px]">error</span>
            Terjadi Kesalahan
        </div>
        <ul class="list-disc pl-5 text-xs font-semibold space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Top Action Bar --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('gudang.transfer-requests.index') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 font-bold transition-colors group">
            <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
            Kembali ke Daftar
        </a>
        
        <div class="flex items-center gap-3">
            @if(in_array($transferRequest->status, ['approved', 'partial']))
                <a href="{{ route('gudang.transfer-out.create', ['request_id' => $transferRequest->id]) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-600/20">
                    <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                    Proses Pengiriman
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Left Column: Document Info --}}
        <div class="lg:col-span-2 space-y-8">
            
            {{-- Main Info Card --}}
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-8 sm:p-10">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h1 class="text-3xl font-black text-slate-900 font-headline tracking-tight">{{ $transferRequest->request_number }}</h1>
                                @php
                                    $statusStyles = [
                                        'pending'   => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'approved'  => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                        'partial'   => 'bg-violet-100 text-violet-700 border-violet-200',
                                        'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'rejected'  => 'bg-rose-100 text-rose-700 border-rose-200',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-[0.15em] border {{ $statusStyles[$transferRequest->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $transferRequest->status }}
                                </span>
                            </div>
                            <p class="text-slate-500 font-bold tracking-wide">Tanggal Permintaan: {{ \Carbon\Carbon::parse($transferRequest->date)->translatedFormat('d F Y') }}</p>
                        </div>
                        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-3xl border border-slate-100 min-w-[240px]">
                            <div class="w-12 h-12 rounded-2xl {{ $transferRequest->from_entity === 'hendhys' ? 'bg-amber-100 text-amber-600' : 'bg-orange-100 text-orange-600' }} flex items-center justify-center shrink-0 shadow-inner">
                                <span class="material-symbols-outlined text-[28px]">{{ $transferRequest->from_entity === 'hendhys' ? 'cake' : 'bakery_dining' }}</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Asal Permintaan</p>
                                <p class="text-sm font-black text-slate-800 leading-tight">{{ ucfirst($transferRequest->from_entity) }}</p>
                                <p class="text-xs font-bold text-slate-500">{{ $transferRequest->branch->name ?? 'Produksi Pusat' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Items Table --}}
                    <div class="mt-12 space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-black text-slate-900 font-headline tracking-tight">Daftar Barang</h3>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ count($transferRequest->details) }} Items</span>
                        </div>

                        <div class="overflow-hidden rounded-3xl border border-slate-100">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-[0.15em]">
                                        <th class="px-6 py-4">Produk</th>
                                        <th class="px-6 py-4 text-center">Jumlah Diminta</th>
                                        @if($transferRequest->status === 'pending')
                                            <th class="px-6 py-4 text-center">Stok Gudang</th>
                                            <th class="px-6 py-4 text-center">Qty Disetujui</th>
                                        @else
                                            <th class="px-6 py-4 text-center">Qty Disetujui</th>
                                        @endif
                                        <th class="px-6 py-4 text-center">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($transferRequest->details as $i => $item)
                                    @php
                                        $whStock  = \App\Models\GudangStock::where('product_id', $item->product_id)->value('quantity') ?? 0;
                                        $hasEnough = $whStock >= $item->quantity_requested;
                                    @endphp
                                    <tr class="group transition-colors hover:bg-slate-50/50">
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ $item->product->name ?? '-' }}</span>
                                                <span class="text-[10px] font-bold text-slate-400 font-mono uppercase mt-0.5">{{ $item->product->code ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <span class="text-base font-black text-slate-900 tabular-nums">{{ number_format($item->quantity_requested, 0) }}</span>
                                        </td>
                                        @if($transferRequest->status === 'pending')
                                            <td class="px-6 py-5 text-center">
                                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl font-bold text-xs {{ $hasEnough ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                                    <span class="tabular-nums">{{ number_format($whStock, 0) }}</span>
                                                    @if(!$hasEnough)
                                                        <span class="material-symbols-outlined text-[14px]">warning</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-5 text-center">
                                                <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}" form="approve-form">
                                                <input type="number" name="items[{{ $i }}][quantity_approved]" value="{{ (float) $item->quantity_requested }}" min="0.001" step="any" max="{{ (float) $item->quantity_requested }}" form="approve-form"
                                                    class="w-28 px-3 py-2 text-center text-sm font-bold text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none tabular-nums">
                                            </td>
                                        @else
                                            <td class="px-6 py-5 text-center">
                                                <span class="text-sm font-bold text-slate-700 tabular-nums">{{ $item->quantity_approved !== null ? (float) $item->quantity_approved : '-' }}</span>
                                            </td>
                                        @endif
                                        <td class="px-6 py-5 text-center">
                                            <span class="text-[10px] font-black text-slate-500 uppercase bg-slate-100 px-2 py-1 rounded-lg">{{ $item->unit->abbreviation ?? 'PCS' }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Approval Actions (Only for Pending) --}}
                @if($transferRequest->status === 'pending')
                <div class="p-8 sm:p-10 bg-slate-900 border-t border-white/10">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="flex-1">
                            <h4 class="text-lg font-black text-white font-headline tracking-tight">Keputusan Approval</h4>
                            <p class="text-slate-400 text-xs font-medium mt-1">Sesuaikan qty disetujui jika perlu, lalu klik Setujui.</p>
                        </div>
                        <div class="flex items-center gap-4 shrink-0 w-full md:w-auto">
                            <form id="reject-form" action="{{ route('gudang.transfer-requests.reject', $transferRequest) }}" method="POST" class="flex-1 md:flex-none" onsubmit="return handleReject(event)">
                                @csrf
                                <input type="hidden" name="rejection_reason" id="rejection_reason_input">
                                <button type="submit" class="w-full px-8 py-4 bg-rose-600/10 text-rose-500 hover:bg-rose-600 hover:text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all">
                                    Tolak Request
                                </button>
                            </form>
                            <form id="approve-form" action="{{ route('gudang.transfer-requests.approve', $transferRequest) }}" method="POST" class="flex-1 md:flex-none">
                                @csrf
                                <button type="submit" class="w-full px-10 py-4 bg-indigo-600 text-white hover:bg-indigo-500 rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-600/30">
                                    Setujui (Approve)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Notes Section --}}
            @if($transferRequest->notes)
            <div class="bg-indigo-50 border border-indigo-100 rounded-3xl p-8 flex items-start gap-5">
                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                    <span class="material-symbols-outlined">description</span>
                </div>
                <div>
                    <h4 class="text-sm font-black text-indigo-900 uppercase tracking-widest mb-1">Catatan Peminta</h4>
                    <p class="text-indigo-800/80 text-sm font-medium leading-relaxed italic">"{{ $transferRequest->notes }}"</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Right Column: Audit Trail & Details --}}
        <div class="space-y-8">
            
            {{-- Requester Info --}}
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8 space-y-6">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Audit Trail</h3>
                
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100 shrink-0">
                            <span class="material-symbols-outlined text-[20px]">person</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Diminta Oleh</p>
                            <p class="text-sm font-bold text-slate-800">{{ $transferRequest->requester->name ?? '-' }}</p>
                            <p class="text-[10px] font-medium text-slate-500 mt-0.5">{{ $transferRequest->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($transferRequest->approver)
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 shrink-0">
                            <span class="material-symbols-outlined text-[20px]">verified</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Disetujui Oleh</p>
                            <p class="text-sm font-bold text-slate-800">{{ $transferRequest->approver->name ?? '-' }}</p>
                            <p class="text-[10px] font-medium text-slate-500 mt-0.5">{{ $transferRequest->approved_at ? $transferRequest->approved_at->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                    </div>
                    @endif

                    @if($transferRequest->status === 'rejected')
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 border border-rose-100 shrink-0">
                            <span class="material-symbols-outlined text-[20px]">block</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ditolak Oleh</p>
                            <p class="text-sm font-bold text-slate-800">{{ $transferRequest->approver->name ?? 'Admin' }}</p>
                            <p class="text-[10px] font-medium text-slate-500 mt-0.5">Permintaan tidak disetujui.</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Summary Sidebar --}}
            <div class="bg-indigo-600 rounded-[2rem] shadow-xl shadow-indigo-600/20 p-8 text-white">
                <div class="flex items-center gap-3 mb-6">
                    <span class="material-symbols-outlined text-[24px]">info</span>
                    <h3 class="text-sm font-black uppercase tracking-widest">Ringkasan</h3>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-white/10">
                        <span class="text-xs font-bold text-indigo-200">Total Item</span>
                        <span class="text-sm font-black tabular-nums">{{ count($transferRequest->details) }} SKU</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-white/10">
                        <span class="text-xs font-bold text-indigo-200">Total Kuantitas</span>
                        <span class="text-sm font-black tabular-nums">{{ number_format($transferRequest->details->sum('quantity_requested')) }} Unit</span>
                    </div>
                    <div class="flex justify-between items-center py-3">
                        <span class="text-xs font-bold text-indigo-200">Estimasi Berat</span>
                        <span class="text-sm font-black">~ -- kg</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleReject(event) {
        event.preventDefault();
        const reason = prompt('Masukkan alasan penolakan transfer request ini:');
        if (reason === null) {
            // User click Batal
            return false;
        }
        const trimmedReason = reason.trim();
        if (!trimmedReason) {
            alert('Alasan penolakan wajib diisi!');
            return false;
        }
        document.getElementById('rejection_reason_input').value = trimmedReason;
        document.getElementById('reject-form').submit();
    }
</script>
@endpush
