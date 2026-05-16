@extends('layouts.owner')
@section('title', 'Laporan & Export')
@section('page-title', 'Pusat Laporan & Data Export')

@section('content')
<div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-6 mb-8 flex items-start gap-4">
    <svg class="w-6 h-6 shrink-0 mt-0.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        <h4 class="font-bold text-lg mb-1">Informasi Fitur Laporan</h4>
        <p class="text-sm">Fitur Export (Excel/PDF) dan laporan terperinci akan sepenuhnya dikembangkan pada <strong>Fase 7: Finishing</strong>. Halaman ini dipersiapkan sebagai placeholder (*struktur awal*) untuk integrasi selanjutnya.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm opacity-60">
        <h3 class="font-bold text-slate-800 mb-2">Laporan Gudang Tempua</h3>
        <p class="text-sm text-slate-500 mb-4">Mutasi stok barang, histori penerimaan PO, dan histori distribusi ke cabang.</p>
        <button disabled class="w-full bg-slate-100 text-slate-400 py-2 rounded-lg font-medium text-sm cursor-not-allowed">Coming Soon</button>
    </div>
    
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm opacity-60">
        <h3 class="font-bold text-slate-800 mb-2">Laporan Jihan's Food</h3>
        <p class="text-sm text-slate-500 mb-4">Laporan omset harian/bulanan, histori produksi Tortilla, produk terlaris.</p>
        <button disabled class="w-full bg-slate-100 text-slate-400 py-2 rounded-lg font-medium text-sm cursor-not-allowed">Coming Soon</button>
    </div>
    
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm opacity-60">
        <h3 class="font-bold text-slate-800 mb-2">Laporan Hendhys Brownies</h3>
        <p class="text-sm text-slate-500 mb-4">Laporan omset per cabang, histori produksi bakery, data return produk.</p>
        <button disabled class="w-full bg-slate-100 text-slate-400 py-2 rounded-lg font-medium text-sm cursor-not-allowed">Coming Soon</button>
    </div>
</div>
@endsection
