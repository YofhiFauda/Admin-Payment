@extends('layouts.app')

@section('page-title', 'Langkah 3: Selesai')

@section('content')
    <div class="max-w-md mx-auto py-6 sm:py-10">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            {{-- Gradient Progress Bar --}}
            <div class="h-2 w-full bg-gradient-to-r from-green-400 via-blue-500 to-amber-400"></div>

            {{-- Content --}}
            <div class="p-6 sm:p-8 flex flex-col items-center text-center">
                {{-- Success Icon --}}
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-5">
                    <i data-lucide="party-popper" class="w-8 h-8 text-green-600"></i>
                </div>

                <h2 class="text-xl sm:text-2xl font-black text-slate-900 mb-1">Nota Berhasil Dikirim!</h2>
                <p class="text-slate-400 text-sm mb-6">ID Transaksi: <span
                        class="font-bold text-slate-700">{{ $transaction->invoice_number }}</span></p>

                {{-- Status Card --}}
                <div class="w-full bg-slate-50 rounded-xl border border-slate-100 p-5 mb-6">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Status Pengajuan Anda</p>
                    <div
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-100 text-amber-700 rounded-xl font-bold text-sm border border-amber-200">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        PENDING
                    </div>
                    <p class="text-slate-500 text-xs mt-4 leading-relaxed">
                        Nota Anda sedang dalam tahap peninjauan oleh tim finance.<br>
                        Terima kasih, <span class="font-bold">{{ Auth::user()->name }}</span>.
                    </p>
                </div>

                {{-- Detail (collapsible) --}}
                <div class="w-full text-left mb-5">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 text-center">
                        Detail Transaksi
                    </p>
                    <div class="space-y-1.5 border-t border-gray-100 pt-3">
                        <div class="flex justify-between py-1.5">
                            <span class="text-xs text-slate-400">Vendor</span>
                            <span class="text-xs font-bold text-slate-800">{{ $transaction->customer }}</span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-xs text-slate-400">Nominal</span>
                            <span class="text-xs font-bold text-blue-600">{{ $transaction->formatted_amount }}</span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-xs text-slate-400">Tanggal</span>
                            <span
                                class="text-xs font-bold text-slate-800">{{ $transaction->date ? $transaction->date->format('d M Y') : '-' }}</span>
                        </div>
                        @if($transaction->items)
                            <div class="flex justify-between py-1.5">
                                <span class="text-xs text-slate-400">Keterangan</span>
                                <span class="text-xs font-bold text-slate-800 text-right ml-4">{{ $transaction->items }}</span>
                            </div>
                        @endif
                        @if($transaction->branches->count() > 0)
                            <div class="pt-2 border-t border-gray-50">
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-2">Distribusi Cabang
                                </p>
                                @foreach($transaction->branches as $branch)
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-xs text-slate-600">{{ $branch->name }}</span>
                                        <span
                                            class="text-xs font-bold text-slate-800">{{ $branch->pivot->allocation_percent }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Action Button --}}
                <a href="{{ route('transactions.create') }}"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white py-3.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-600/20 text-sm active:scale-95">
                    <i data-lucide="plus" class="w-4 h-4"></i> Input Nota Lagi
                </a>
            </div>
        </div>
    </div>
@endsection