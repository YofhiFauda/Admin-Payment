@extends('layouts.app')

@section('content')
@php
    \Carbon\Carbon::setLocale('id');
@endphp

<style>
    @keyframes scaleIn {
        0% {
            transform: scale(0);
            opacity: 0;
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes checkmark {
        0% {
            stroke-dashoffset: 100;
        }
        100% {
            stroke-dashoffset: 0;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    .success-icon {
        animation: scaleIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .checkmark-path {
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
        animation: checkmark 0.6s 0.3s ease-out forwards;
    }

    .fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .fade-in {
        animation: fadeIn 0.8s ease-out forwards;
    }

    .slide-in-right {
        animation: slideInRight 0.5s ease-out forwards;
    }

    .detail-item {
        opacity: 0;
        animation: fadeInUp 0.5s ease-out forwards;
    }

    .detail-item:nth-child(1) { animation-delay: 0.6s; }
    .detail-item:nth-child(2) { animation-delay: 0.65s; }
    .detail-item:nth-child(3) { animation-delay: 0.7s; }
    .detail-item:nth-child(4) { animation-delay: 0.75s; }
    .detail-item:nth-child(5) { animation-delay: 0.8s; }
    .detail-item:nth-child(6) { animation-delay: 0.85s; }
    .detail-item:nth-child(7) { animation-delay: 0.9s; }
    .detail-item:nth-child(8) { animation-delay: 0.95s; }
    .detail-item:nth-child(9) { animation-delay: 1s; }

    .button-1 {
        opacity: 0;
        animation: fadeInUp 0.6s 1.1s ease-out forwards;
    }

    .button-2 {
        opacity: 0;
        animation: fadeInUp 0.6s 1.2s ease-out forwards;
    }
</style>

<div class="min-h-[80vh] flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-xl overflow-hidden p-8 flex flex-col items-center">
        
        <!-- Success Icon with Animation -->
        <div class="w-24 h-24 mb-6 flex items-center justify-center success-icon">
            <div class="w-20 h-20 bg-[#10b981] rounded-full flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path class="checkmark-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <!-- Title with Animation -->
        <h1 class="text-2xl font-extrabold text-[#1e293b] mb-3 fade-in-up" style="animation-delay: 0.3s; opacity: 0;">
            {{ $transaction->isPengajuan() ? 'Pengajuan' : 'Reimbursement' }} Berhasil!
        </h1>
        
        <!-- Description with Animation -->
        <p class="text-slate-500 text-sm text-center mb-8 px-4 leading-relaxed fade-in-up" style="animation-delay: 0.4s; opacity: 0;">
            @if($transaction->isPengajuan())
                Terima kasih, dokumen pengajuan dana Anda telah kami terima dan akan segera diproses.
            @else
                Bukti nota/struk reimbursement Anda telah berhasil disubmit. Kami akan melakukan verifikasi secepatnya.
            @endif
        </p>

        <!-- Transaction Details Box with Animation -->
        <div class="w-full bg-[#f8fafc] rounded-3xl p-6 mb-6 fade-in-up" style="animation-delay: 0.5s; opacity: 0;">
            <div class="space-y-3">
                <!-- Invoice Ref -->
                <div class="detail-item">
                    <div class="flex justify-between items-center py-2.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">NO. INVOICE / REF</span>
                        <span class="text-sm font-bold text-[#1e293b]">{{ $transaction->invoice_number }}</span>
                    </div>
                </div>
                <div class="detail-item border-b border-slate-200"></div>

                <!-- Jenis Transaksi -->
                <div class="detail-item">
                    <div class="flex justify-between items-center py-2.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">JENIS TRANSAKSI</span>
                        @if($transaction->isPengajuan())
                            <span class="px-3 py-1 bg-blue-50 text-blue-600 text-xs font-bold rounded-lg transition-all hover:bg-blue-100">Pengajuan</span>
                        @else
                            <span class="px-3 py-1 bg-purple-50 text-purple-600 text-xs font-bold rounded-lg transition-all hover:bg-purple-100">Reimbursement</span>
                        @endif
                    </div>
                </div>
                <div class="detail-item border-b border-slate-200"></div>

                <!-- Kategori -->
                <div class="detail-item">
                    <div class="flex justify-between items-center py-2.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">KATEGORI</span>
                        <span class="text-sm font-semibold text-[#1e293b]">{{ $transaction->category_label }}</span>
                    </div>
                </div>
                <div class="detail-item border-b border-slate-200"></div>

                <!-- Tanggal -->
                <div class="detail-item">
                    <div class="flex justify-between items-center py-2.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">TANGGAL</span>
                        <span class="text-sm font-semibold text-[#1e293b]">{{ $transaction->date ? $transaction->date->isoFormat('DD MMMM Y') : '-' }}</span>
                    </div>
                </div>
                <div class="detail-item border-b border-slate-200"></div>

                <!-- Total Nominal -->
                <div class="detail-item">
                    <div class="flex justify-between items-center py-2.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">TOTAL NOMINAL</span>
                        <span class="text-lg font-extrabold text-[#10b981]">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons with Animation -->
        <div class="w-full space-y-3">
            <a href="{{ route('transactions.index') }}" 
               class="button-1 w-full h-14 bg-[#10b981] hover:bg-[#059669] text-white rounded-2xl font-bold transition-all duration-300 flex items-center justify-center shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:scale-[1.02] active:scale-[0.98]">
                Lihat Status Transaksi
            </a>
            <a href="{{ route('transactions.create') }}" 
               class="button-2 w-full h-14 bg-white hover:bg-slate-50 text-[#1e293b] border-2 border-slate-200 rounded-2xl font-bold transition-all duration-300 flex items-center justify-center hover:border-slate-300 hover:scale-[1.02] active:scale-[0.98]">
                Kembali ke Beranda
            </a>
        </div>

    </div>
</div>
@endsection