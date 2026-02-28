@extends('layouts.app')

@php
    $hideHeader = true;
    $isPengajuan = $transaction->type === 'pengajuan';
    $isRembush = $transaction->type === 'rembush';
@endphp

@section('content')
    {{-- Container utama --}}
    <div class="min-h-screen flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8 bg-slate-50 relative overflow-hidden font-sans">
        
        {{-- Background Orbs --}}
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-fuchsia-500/20 rounded-full mix-blend-multiply filter blur-[100px] pointer-events-none anim-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-violet-500/20 rounded-full mix-blend-multiply filter blur-[100px] pointer-events-none anim-pulse-slow delay-1000"></div>

        <div class="max-w-4xl w-full relative z-10 anim-fade-in">
            
            {{-- Main Glass Card --}}
            <div class="bg-white/70 backdrop-blur-2xl rounded-[2rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.07)] border border-white/60 p-6 sm:p-10 md:p-12 overflow-hidden relative">
                
                {{-- Header Section --}}
                <div class="flex flex-col items-center mb-10 anim-slide-down delay-100">
                    <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-fuchsia-500 rounded-full flex items-center justify-center mb-4 shadow-lg shadow-violet-500/30 anim-scale-pop delay-200">
                        <i data-lucide="check" class="w-7 h-7 text-white"></i>
                    </div>
                    <h2 class="text-3xl font-black text-slate-800 text-center tracking-tight mb-1">
                        {{ $isPengajuan ? 'Pengajuan Berhasil!' : 'Invoice Lunas!' }}
                    </h2>
                    <div class="px-4 py-1.5 bg-violet-100/60 border border-violet-200/60 text-violet-700 rounded-full text-xs font-bold tracking-wider backdrop-blur-sm">
                        REF: {{ $transaction->invoice_number }}
                    </div>
                </div>

                {{-- Payer & Receiver Info Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 anim-slide-up delay-300">
                    {{-- Dibayar Oleh --}}
                    <div class="bg-gradient-to-br from-violet-50/80 to-fuchsia-50/80 backdrop-blur-md rounded-2xl border border-violet-100/60 p-5 shadow-sm">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">DIBAYAR OLEH</p>
                        <p class="text-base font-bold text-slate-800 mb-2">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-slate-500 mb-1">{{ Auth::user()->email }}</p>
                        <p class="text-xs text-slate-500">{{ $isPengajuan ? 'Pengajuan Pembelian' : 'Klaim Reimbursement' }}</p>
                    </div>

                    {{-- Diterima Oleh / Vendor Info --}}
                    <div class="bg-gradient-to-br from-violet-50/80 to-fuchsia-50/80 backdrop-blur-md rounded-2xl border border-violet-100/60 p-5 shadow-sm">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                            {{ $isPengajuan ? 'VENDOR INFO' : 'DITERIMA OLEH' }}
                        </p>
                        <p class="text-base font-bold text-slate-800 mb-2">
                            {{ $isPengajuan ? ($transaction->vendor ?? 'Belum ditentukan') : 'PT Solusi Digital Kreatif' }}
                        </p>
                        @if($isPengajuan)
                            <p class="text-xs text-slate-500 mb-1">
                                {{ $transaction->customer ?? '-' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $transaction->specs['merk'] ?? '-' }} {{ $transaction->specs['tipe'] ?? '' }}
                            </p>
                        @else
                            <p class="text-xs text-slate-500 mb-1">Bank Mandiri 123.456.789.0</p>
                            <p class="text-xs text-slate-500">Gedung Cyber Lt.5, Jakarta Selatan</p>
                        @endif
                    </div>
                </div>

                {{-- Rincian Pembelian Header --}}
                <div class="flex justify-between items-center mb-4 anim-slide-up delay-400">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        {{ $isPengajuan ? 'Rincian Pengajuan' : 'Rincian Pembelian' }} 
                        ({{ $itemCount }})
                    </p>
                    <p class="text-xs text-slate-400 font-medium">
                        {{ $transaction->created_at ? $transaction->created_at->format('d M Y, H:i') : '-' }} WIB
                    </p>
                </div>

                {{-- UNIVERSAL Item List --}}
                <div class="bg-white/50 backdrop-blur-md rounded-2xl border border-white/60 shadow-sm mb-6 overflow-hidden anim-slide-up delay-500">
                    <div class="divide-y divide-violet-100/50">
                        @if($isPengajuan)
                            {{-- PENGAJUAN: Tampilkan sebagai single item --}}
                            <div class="flex justify-between items-center p-4 hover:bg-violet-50/30 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-violet-100/60 rounded-full flex items-center justify-center">
                                        <i data-lucide="package" class="w-4 h-4 text-violet-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">{{ $transaction->customer ?? 'Barang/Jasa' }}</p>
                                        <p class="text-[10px] text-slate-400">
                                            {{ $transaction->quantity ?? 1 }} {{ $transaction->unit ?? 'pcs' }} 
                                            @if($transaction->specs)
                                                • {{ $transaction->specs['merk'] ?? '' }} {{ $transaction->specs['tipe'] ?? '' }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <p class="text-sm font-bold text-slate-700">
                                    Rp {{ number_format($transaction->estimated_price ?? $transaction->amount ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                        @elseif($isRembush && $transaction->items && $transaction->items->count() > 0)
                            {{-- REMBUSH: Tampilkan semua items --}}
                            @foreach($transaction->items as $index => $item)
                                <div class="flex justify-between items-center p-4 hover:bg-violet-50/30 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-violet-100/60 rounded-full flex items-center justify-center">
                                            <i data-lucide="package" class="w-4 h-4 text-violet-600"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">{{ $item->name ?? 'Item ' . ($index + 1) }}</p>
                                            <p class="text-[10px] text-slate-400">
                                                {{ $item->qty ?? 1 }} {{ $item->unit ?? 'pcs' }} 
                                                @if($item->desc) • {{ Str::limit($item->desc, 30) }} @endif
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700">
                                        Rp {{ number_format(($item->price ?? 0) * ($item->qty ?? 1), 0, ',', '.') }}
                                    </p>
                                </div>
                            @endforeach
                        @else
                            {{-- FALLBACK: Tampilkan amount langsung --}}
                            <div class="flex justify-between items-center p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-violet-100/60 rounded-full flex items-center justify-center">
                                        <i data-lucide="receipt" class="w-4 h-4 text-violet-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">{{ $transaction->customer ?? 'Transaksi' }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $transaction->category ?? 'Umum' }} • 1 Qty</p>
                                    </div>
                                </div>
                                <p class="text-sm font-bold text-slate-700">
                                    Rp {{ number_format($transaction->amount ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Additional Info (Conditional) --}}
                @if($isPengajuan && $transaction->purchase_reason)
                <div class="bg-violet-50/50 backdrop-blur-md rounded-2xl border border-violet-100/60 p-5 mb-6 anim-slide-up delay-550">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Alasan Pengajuan</p>
                    <p class="text-sm text-slate-700 font-medium">
                        {{ \App\Models\Transaction::PURCHASE_REASONS[$transaction->purchase_reason] ?? $transaction->purchase_reason }}
                    </p>
                </div>
                @endif

                @if($transaction->description && $isRembush)
                <div class="bg-violet-50/50 backdrop-blur-md rounded-2xl border border-violet-100/60 p-5 mb-6 anim-slide-up delay-550">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Keterangan</p>
                    <p class="text-sm text-slate-700 font-medium">{{ $transaction->description }}</p>
                </div>
                @endif

                {{-- Branch Distribution (If exists) --}}
                @if($transaction->branches && $transaction->branches->count() > 0)
                <div class="bg-white/50 backdrop-blur-md rounded-2xl border border-white/60 p-5 mb-6 anim-slide-up delay-600">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Distribusi Cabang</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($transaction->branches as $branch)
                            <div class="flex items-center justify-between bg-white/60 px-3 py-2 rounded-xl border border-white/50 shadow-sm">
                                <span class="text-xs text-slate-700 font-medium">{{ $branch->name }}</span>
                                <span class="text-xs font-bold text-violet-700 bg-violet-100/50 px-2 py-0.5 rounded border border-violet-200/50">
                                    {{ $branch->pivot->allocation_percent ?? 0 }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Glassy Colorful Total Section --}}
                <div class="bg-gradient-to-br from-violet-600 to-fuchsia-600 p-6 sm:p-8 rounded-2xl shadow-lg text-white anim-slide-up delay-600 relative overflow-hidden mb-8">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-[20px] -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 relative z-10">
                        {{-- Subtotal --}}
                        <div class="text-center sm:text-left">
                            <p class="text-violet-200 font-medium text-[10px] mb-1 uppercase tracking-wider">Subtotal</p>
                            <p class="text-sm font-bold">Rp {{ number_format(($transaction->amount ?? 0) / 1.11, 0, ',', '.') }}</p>
                        </div>
                        
                        {{-- Pajak --}}
                        <div class="text-center sm:text-left">
                            <p class="text-violet-200 font-medium text-[10px] mb-1 uppercase tracking-wider">Pajak (11%)</p>
                            <p class="text-sm font-bold">Rp {{ number_format(($transaction->amount ?? 0) - (($transaction->amount ?? 0) / 1.11), 0, ',', '.') }}</p>
                        </div>
                        
                        {{-- Diskon --}}
                        <div class="text-center sm:text-left">
                            <p class="text-violet-200 font-medium text-[10px] mb-1 uppercase tracking-wider">Diskon</p>
                            <p class="text-sm font-bold text-violet-200">-Rp 0</p>
                        </div>
                        
                        {{-- Total Transaksi --}}
                        <div class="col-span-2 sm:col-span-1 text-center sm:text-right sm:border-l sm:border-white/20 sm:pl-4">
                            <p class="text-violet-200 font-medium text-[10px] mb-1 uppercase tracking-wider">Total Transaksi</p>
                            <p class="text-2xl sm:text-3xl font-black">Rp {{ number_format($transaction->amount ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap justify-center items-center gap-4 anim-slide-up delay-700">
                    {{-- Share Button --}}
                    <button class="w-12 h-12 bg-white/60 hover:bg-white/80 backdrop-blur-md border border-white rounded-full text-slate-700 shadow-sm transition-all hover:scale-110 active:scale-95 flex items-center justify-center" title="Bagikan">
                        <i data-lucide="share-2" class="w-5 h-5"></i>
                    </button>
                    
                    {{-- Download Button --}}
                    <button class="w-12 h-12 bg-white/60 hover:bg-white/80 backdrop-blur-md border border-white rounded-full text-slate-700 shadow-sm transition-all hover:scale-110 active:scale-95 flex items-center justify-center" title="Unduh Dokumen">
                        <i data-lucide="download" class="w-5 h-5"></i>
                    </button>
                    
                    {{-- Primary Button --}}
                    <a href="{{ route('transactions.index') }}" class="flex items-center gap-2 px-8 py-4 bg-slate-900 rounded-full text-white font-bold hover:bg-slate-800 shadow-xl shadow-slate-900/20 transition-all hover:scale-105 active:scale-95">
                        <i data-lucide="list" class="w-5 h-5"></i> Daftar Transaksi
                    </a>
                </div>

            </div>
            
            {{-- Footer Text --}}
            <p class="text-center text-slate-400 text-xs mt-8 font-medium anim-fade-in delay-1000">
                &copy; {{ date('Y') }} Finance System. Secure & Encrypted.
            </p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    @keyframes slideUpFade {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideDownFade {
        0% { opacity: 0; transform: translateY(-40px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes scaleInPop {
        0% { opacity: 0; transform: scale(0.5); }
        60% { transform: scale(1.1); }
        100% { opacity: 1; transform: scale(1); }
    }
    @keyframes fadeIn {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }
    @keyframes pulseSlow {
        0% { transform: scale(1); opacity: 0.3; }
        50% { transform: scale(1.1); opacity: 0.5; }
        100% { transform: scale(1); opacity: 0.3; }
    }
    
    .anim-slide-up { animation: slideUpFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .anim-slide-down { animation: slideDownFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .anim-scale-pop { animation: scaleInPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; opacity: 0; }
    .anim-fade-in { animation: fadeIn 0.8s ease-out forwards; opacity: 0; }
    .anim-pulse-slow { animation: pulseSlow 8s ease-in-out infinite; }
    
    .delay-100 { animation-delay: 100ms; }
    .delay-200 { animation-delay: 200ms; }
    .delay-300 { animation-delay: 300ms; }
    .delay-400 { animation-delay: 400ms; }
    .delay-500 { animation-delay: 500ms; }
    .delay-550 { animation-delay: 550ms; }
    .delay-600 { animation-delay: 600ms; }
    .delay-700 { animation-delay: 700ms; }
    .delay-1000 { animation-delay: 1000ms; }
</style>
@endpush

@push('scripts')
<script>
    if (window.lucide) {
        window.lucide.createIcons();
    }
</script>
@endpush