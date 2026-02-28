@extends('layouts.app')

@php
    $hideHeader = true;
@endphp

@section('content')
    {{-- Container dengan background dekoratif --}}
    <div class="min-h-screen flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8 bg-slate-50 relative overflow-hidden">
        
        {{-- Background Blobs (Animasi halus) --}}
        <div class="absolute top-0 left-0 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 -translate-x-1/2 -translate-y-1/2 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 translate-x-1/2 -translate-y-1/2 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="max-w-md w-full relative z-10">
            
            {{-- Main Card --}}
            <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/50 overflow-hidden transition-all duration-300 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)]">
                
                {{-- Gradient Top Border --}}
                <div class="h-1.5 w-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>

                <div class="p-8 flex flex-col items-center text-center">
                    
                    {{-- Success Icon with Glow --}}
                    <div class="relative mb-6 group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-green-400 to-emerald-600 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                        <div class="relative w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm border border-slate-100">
                            <i data-lucide="check-circle-2" class="w-10 h-10 text-emerald-500"></i>
                        </div>
                    </div>

                    <h2 class="text-2xl font-extrabold text-slate-800 mb-1 tracking-tight">Nota Berhasil Dikirim!</h2>
                    <p class="text-slate-500 text-sm mb-8 font-medium">
                        ID Transaksi: <span class="font-mono text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-xs border border-slate-200">{{ $transaction->invoice_number }}</span>
                    </p>

                    {{-- Status Card (Floating Style) --}}
                    <div class="w-full bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-100 p-5 mb-6 shadow-sm relative overflow-hidden">
                        {{-- Subtle pattern overlay --}}
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-slate-100 rounded-full opacity-50 blur-2xl"></div>
                        
                        <div class="relative z-10">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 text-left">Status Pengajuan Anda</p>
                            
                            <div class="text-left bg-white/60 p-3 rounded-lg border border-slate-50 backdrop-blur-sm">
                                <p id="status-description" class="text-slate-500 text-xs leading-relaxed">
                                    Nota Anda sedang dalam tahap peninjauan oleh tim finance. Kami akan menghubungi Anda segera setelah proses selesai.
                                </p>
                                <p class="text-slate-400 text-xs mt-2 font-medium">
                                    Terima kasih, <span class="text-slate-600">{{ Auth::user()->name }}</span>.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Detail Section --}}
                    <div class="w-full text-left mb-8">
                        <div class="flex items-center justify-between mb-4 px-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Detail Transaksi</p>
                            <div class="h-px bg-slate-200 flex-grow ml-4"></div>
                        </div>

                        <div class="space-y-4">
                            {{-- Type Badge Row --}}
                            <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-100 shadow-sm">
                                <span class="text-xs font-medium text-slate-500">Jenis Transaksi</span>
                                @if($transaction->type === 'pengajuan')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-100">
                                        <i data-lucide="file-plus" class="w-3 h-3"></i> Pengajuan
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <i data-lucide="refresh-cw" class="w-3 h-3"></i> Rembush
                                    </span>
                                @endif
                            </div>

                            {{-- Key Metrics Grid --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold">{{ $transaction->isPengajuan() ? 'Nama Barang' : 'Vendor' }}</span>
                                    <p class="text-sm font-bold text-slate-800 truncate" title="{{ $transaction->customer }}">{{ $transaction->customer }}</p>
                                </div>
                                <div class="space-y-1 text-right">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold">Total Nominal</span>
                                    <p class="text-sm font-bold text-blue-600">Rp {{ number_format($transaction->amount ?? 0, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            {{-- Dynamic Fields Container --}}
                            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-100 space-y-3">
                                @if($transaction->isRembush() && $transaction->category)
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-slate-500">Kategori</span>
                                        <span class="text-xs font-semibold text-slate-700">{{ \App\Models\Transaction::CATEGORIES[$transaction->category] ?? $transaction->category }}</span>
                                    </div>
                                @endif

                                @if($transaction->isPengajuan())
                                    @if($transaction->vendor)
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-slate-500">Vendor</span>
                                            <span class="text-xs font-semibold text-slate-700">{{ $transaction->vendor }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-slate-500">Jumlah</span>
                                        <span class="text-xs font-semibold text-slate-700">{{ $transaction->quantity ?? 1 }} {{ $transaction->unit ?? 'pcs' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-slate-500">Estimasi Harga</span>
                                        <span class="text-xs font-semibold text-slate-700">Rp {{ number_format($transaction->estimated_price ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    @if($transaction->purchase_reason)
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-slate-500">Alasan</span>
                                            <span class="text-xs font-semibold text-slate-700">{{ \App\Models\Transaction::PURCHASE_REASONS[$transaction->purchase_reason] ?? $transaction->purchase_reason }}</span>
                                        </div>
                                    @endif
                                @endif

                                <div class="flex justify-between items-center pt-2 border-t border-slate-200/60 mt-2">
                                    <span class="text-xs text-slate-500">Tanggal</span>
                                    <span class="text-xs font-semibold text-slate-700">{{ $transaction->date ? $transaction->date->format('d M Y') : '-' }}</span>
                                </div>

                                @if($transaction->description)
                                    <div class="pt-2">
                                        <span class="text-[10px] text-slate-400 uppercase font-bold block mb-1">Keterangan</span>
                                        <p class="text-xs text-slate-600 bg-white p-2 rounded border border-slate-100 leading-relaxed">{{ $transaction->description }}</p>
                                    </div>
                                @endif
                            </div>

                            @if($transaction->branches->count() > 0)
                                <div class="pt-2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 pl-1">Distribusi Cabang</p>
                                    <div class="space-y-2">
                                        @foreach($transaction->branches as $branch)
                                            <div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-slate-100 shadow-sm">
                                                <span class="text-xs text-slate-600 font-medium">{{ $branch->name }}</span>
                                                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">{{ $branch->pivot->allocation_percent }}%</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <a href="{{ route('transactions.create') }}"
                        class="group w-full relative overflow-hidden bg-slate-900 hover:bg-slate-800 text-white py-4 rounded-xl font-bold transition-all flex items-center justify-center gap-2 shadow-lg shadow-slate-900/20 text-sm active:scale-[0.98]">
                        <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></div>
                        <i data-lucide="plus" class="w-4 h-4 transition-transform group-hover:rotate-90"></i> 
                        Input Nota Lagi
                    </a>
                </div>
            </div>
            
            {{-- Footer Text --}}
            <p class="text-center text-slate-400 text-xs mt-6 font-medium">
                &copy; {{ date('Y') }} Finance System. Secure & Encrypted.
            </p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Custom Animation for Shimmer Effect */
    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }
    /* Blob Animation */
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
</style>
@endpush

@push('scripts')
@endpush