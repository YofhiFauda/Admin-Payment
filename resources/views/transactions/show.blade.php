@extends('layouts.app')

@section('page-title', 'Detail Transaksi')

@section('content')
    <div class="max-w-4xl mx-auto space-y-4 md:space-y-6">

        {{-- Back Button --}}
        <a href="{{ route('transactions.index') }}"
            class="inline-flex items-center gap-1.5 text-slate-400 hover:text-blue-600 font-bold text-xs uppercase tracking-wider transition-all">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Kembali ke Riwayat
        </a>

        {{-- Header Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="h-1.5 w-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
            <div class="p-5 md:p-8">
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                    <div>
                        <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-1">
                            {{ $transaction->invoice_number }}
                        </p>
                        <h2 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">
                            {{ $transaction->customer }}
                        </h2>
                        @if($transaction->category)
                            <span class="inline-flex items-center gap-1 mt-2 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-wider">
                                <i data-lucide="tag" class="w-3 h-3"></i>
                                {{ \App\Models\Transaction::CATEGORIES[$transaction->category] ?? $transaction->category }}
                            </span>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Nominal</p>
                        <p class="text-2xl md:text-3xl font-black text-blue-600 tracking-tighter">
                            {{ $transaction->formatted_amount }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
            {{-- Left Column: Details --}}
            <div class="lg:col-span-2 space-y-4 md:space-y-6">

                {{-- Transaction Info --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-blue-500"></i> Informasi Transaksi
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-xs text-slate-400 font-bold">Tanggal Terbit</span>
                            <span class="text-xs font-bold text-slate-800">{{ $transaction->date ? $transaction->date->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-xs text-slate-400 font-bold">Diajukan oleh</span>
                            <span class="text-xs font-bold text-slate-800">{{ $transaction->submitter->name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-xs text-slate-400 font-bold">Tanggal Input</span>
                            <span class="text-xs font-bold text-slate-800">{{ $transaction->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        @if($transaction->items)
                            <div class="py-2">
                                <p class="text-xs text-slate-400 font-bold mb-1">Keterangan</p>
                                <p class="text-sm text-slate-700 leading-relaxed">{{ $transaction->items }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Branch Distribution --}}
                @if($transaction->branches->count() > 0)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-blue-500"></i>
                            Distribusi Cabang
                            <span class="ml-auto px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-black">
                                {{ $transaction->branches->count() }} Cabang
                            </span>
                        </h3>
                        <div class="space-y-2">
                            @foreach($transaction->branches as $branch)
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                                        </div>
                                        <span class="text-sm font-bold text-slate-700">{{ $branch->name }}</span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-slate-900">
                                            Rp {{ number_format($branch->pivot->allocation_amount, 0, ',', '.') }}
                                        </p>
                                        <p class="text-[10px] font-bold text-slate-400">
                                            {{ $branch->pivot->allocation_percent }}%
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Nota Image --}}
                @if($transaction->file_path)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <i data-lucide="image" class="w-4 h-4 text-blue-500"></i> Gambar Nota
                        </h3>
                        <div class="bg-slate-50 rounded-xl overflow-hidden text-center p-4">
                            <img src="{{ route('transactions.image', $transaction->id) }}"
                                 class="max-w-full max-h-96 object-contain mx-auto rounded-lg"
                                 alt="Nota {{ $transaction->invoice_number }}"
                                 onerror="this.parentElement.innerHTML='<p class=\'text-sm text-slate-400 py-8\'>Gambar tidak tersedia</p>'" />
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right Column: Status & Actions --}}
            <div class="space-y-4 md:space-y-6">

                {{-- Status Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-blue-500"></i> Status
                    </h3>
                    @php
                        $statusConfig = [
                            'pending' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'icon' => 'clock', 'label' => 'Pending'],
                            'approved' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'check-circle', 'label' => 'Disetujui'],
                            'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-200', 'icon' => 'check-circle-2', 'label' => 'Selesai'],
                            'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'x-circle', 'label' => 'Ditolak'],
                        ];
                        $sc = $statusConfig[$transaction->status] ?? $statusConfig['pending'];
                    @endphp
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 {{ $sc['bg'] }} {{ $sc['text'] }} {{ $sc['border'] }}">
                        <i data-lucide="{{ $sc['icon'] }}" class="w-5 h-5"></i>
                        <span class="font-black text-sm uppercase tracking-widest">{{ $sc['label'] }}</span>
                    </div>

                    {{-- Reviewer Info --}}
                    @if($transaction->reviewer)
                        <div class="mt-4 p-3 bg-slate-50 rounded-xl">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">
                                {{ $transaction->status === 'rejected' ? 'Ditolak oleh' : 'Diproses oleh' }}
                            </p>
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-black">
                                    {{ strtoupper(substr($transaction->reviewer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-700">{{ $transaction->reviewer->name }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold">
                                        {{ $transaction->reviewed_at ? $transaction->reviewed_at->format('d M Y, H:i') : '' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Rejection Reason --}}
                    @if($transaction->status === 'rejected' && $transaction->rejection_reason)
                        <div class="mt-3 p-3 bg-red-50 border border-red-100 rounded-xl">
                            <p class="text-[10px] font-black text-red-400 uppercase tracking-wider mb-1">Alasan Penolakan</p>
                            <p class="text-xs text-red-700 leading-relaxed">{{ $transaction->rejection_reason }}</p>
                        </div>
                    @endif
                </div>

                {{-- Admin Actions --}}
                @if(Auth::user()->canManageStatus() && $transaction->status === 'pending')
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <i data-lucide="settings" class="w-4 h-4 text-blue-500"></i> Tindakan
                        </h3>
                        <div class="space-y-2">
                            <form method="POST" action="{{ route('transactions.updateStatus', $transaction->id) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-green-50 text-green-700 hover:bg-green-600 hover:text-white font-bold text-xs uppercase tracking-wider transition-all border border-green-200 hover:border-green-600 cursor-pointer">
                                    <i data-lucide="check" class="w-4 h-4"></i> Setujui Nota
                                </button>
                            </form>
                            <button type="button" onclick="document.getElementById('reject-modal-detail').classList.remove('hidden')"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white font-bold text-xs uppercase tracking-wider transition-all border border-red-200 hover:border-red-600 cursor-pointer">
                                <i data-lucide="x" class="w-4 h-4"></i> Tolak Nota
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    @if(Auth::user()->canManageStatus())
    <div id="reject-modal-detail" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-900">Tolak Nota</h3>
                    <p class="text-xs text-slate-400">{{ $transaction->invoice_number }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('transactions.updateStatus', $transaction->id) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="rejected">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <textarea name="rejection_reason" rows="3" required placeholder="Tuliskan alasan penolakan..."
                        class="w-full border border-slate-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-red-100 resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('reject-modal-detail').classList.add('hidden')"
                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all cursor-pointer border border-slate-200">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Batal
                    </button>
                    <button type="submit"
                        style="background-color: #dc2626; color: #ffffff;"
                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all cursor-pointer shadow-lg">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        Konfirmasi Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection
