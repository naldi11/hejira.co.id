@extends('layouts.owner')
@section('title', 'Dashboard Utama')
@section('page-title', 'Dashboard Konsolidasi Utama')

@section('content')

<div class="mb-8 bg-gradient-to-r from-blue-900 to-indigo-800 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
    <div class="relative z-10">
        <h2 class="text-3xl font-black mb-1">Selamat Datang, {{ auth()->user()->name }}</h2>
        <p class="text-blue-200 font-medium max-w-xl">Anda berada di Panel Eksekutif. Dashboard ini memberikan Anda pantauan penuh ("Helicopter View") terhadap performa seluruh entitas bisnis Anda secara real-time.</p>
    </div>
</div>

<h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-200 pb-2">Performa Hari Ini (Today)</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    {{-- Hari Ini Jihans --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between hover:shadow-md transition-shadow">
        <div>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Jihan's Food</p>
            <p class="text-3xl font-black text-slate-800">Rp {{ number_format($jihansToday, 0, ',', '.') }}</p>
            <p class="text-xs text-green-600 font-medium mt-2 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Realtime updates
            </p>
        </div>
        <div class="w-16 h-16 rounded-full bg-orange-50 border border-orange-100 text-orange-500 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>

    {{-- Hari Ini Hendhys --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between hover:shadow-md transition-shadow">
        <div>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Hendhys Brownies</p>
            <p class="text-3xl font-black text-slate-800">Rp {{ number_format($hendhysToday, 0, ',', '.') }}</p>
            <p class="text-xs text-green-600 font-medium mt-2 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Semua cabang
            </p>
        </div>
        <div class="w-16 h-16 rounded-full bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>
</div>

<h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-200 pb-2">Total Akumulasi & Aset</h3>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Akumulasi Jihan's --}}
    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-md p-6 text-white relative overflow-hidden">
        <svg class="w-24 h-24 absolute -bottom-4 -right-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        <p class="text-orange-100 font-medium text-sm mb-1 uppercase tracking-wider">Total Pendapatan Jihan's</p>
        <p class="text-3xl font-black mb-2">Rp {{ number_format($jihansRevenue, 0, ',', '.') }}</p>
        <a href="{{ route('owner.jihans') }}" class="text-xs font-bold text-white hover:text-orange-200 uppercase tracking-widest inline-flex items-center gap-1">Detail <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
    </div>

    {{-- Akumulasi Hendhys --}}
    <div class="bg-gradient-to-br from-amber-600 to-amber-700 rounded-xl shadow-md p-6 text-white relative overflow-hidden">
        <svg class="w-24 h-24 absolute -bottom-4 -right-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        <p class="text-amber-100 font-medium text-sm mb-1 uppercase tracking-wider">Total Pendapatan Hendhys</p>
        <p class="text-3xl font-black mb-2">Rp {{ number_format($hendhysRevenue, 0, ',', '.') }}</p>
        <a href="{{ route('owner.hendhys') }}" class="text-xs font-bold text-white hover:text-amber-200 uppercase tracking-widest inline-flex items-center gap-1">Detail <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
    </div>

    {{-- Total Fisik Gudang --}}
    <div class="bg-gradient-to-br from-teal-600 to-teal-700 rounded-xl shadow-md p-6 text-white relative overflow-hidden">
        <svg class="w-24 h-24 absolute -bottom-4 -right-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <p class="text-teal-100 font-medium text-sm mb-1 uppercase tracking-wider">Total Fisik Gudang Tempua</p>
        <p class="text-3xl font-black mb-2">{{ number_format($totalItemsInGudang, 2, ',', '.') }} <span class="text-lg font-medium opacity-80">Unit/Qty</span></p>
        <a href="{{ route('owner.gudang') }}" class="text-xs font-bold text-white hover:text-teal-200 uppercase tracking-widest inline-flex items-center gap-1">Detail <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
    </div>
</div>

@endsection
