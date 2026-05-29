@extends('layouts.app')

@php($isEdit = isset($record))

@section('page-title', $isEdit ? 'Edit Piutang Usaha' : 'Tambah Piutang Usaha')



@section('content')

<div class="p-3 sm:p-4 md:p-6 lg:p-8 min-h-screen bg-slate-50/50">
    <div class="max-w-3xl mx-auto space-y-4 sm:space-y-6 md:space-y-8">

        {{-- Header Section --}}
        <div class="flex items-center gap-3 sm:gap-4 md:gap-5">
            <a href="{{ route('pengeluaran-lain.piutang-usaha.index') }}"
                class="group w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12 rounded-xl sm:rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-x-1 flex-shrink-0">
                <i data-lucide="arrow-left" class="w-5 h-5 sm:w-5.5 sm:h-5.5 md:w-6 md:h-6 group-hover:scale-110 transition-transform"></i>
            </a>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight truncate">{{ $isEdit ? 'Edit Piutang Usaha' : 'Tambah Piutang Usaha' }}</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5 sm:mt-1 flex items-center gap-1.5 sm:gap-2">
                    <i data-lucide="info" class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0"></i>
                    <span class="truncate">Isi detail pencairan piutang dengan lengkap dan valid</span>
                </p>
            </div>
        </div>

        {{-- Error Alert --}}
        @if($errors->any())
        <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-3 sm:p-4 md:p-5 rounded-r-xl sm:rounded-r-2xl shadow-sm flex items-start gap-2 sm:gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-rose-500 shrink-0"></i>
            <div class="min-w-0 flex-1">
                <h3 class="font-bold text-rose-900 mb-1 text-sm sm:text-base">Terdapat kesalahan:</h3>
                <ul class="list-disc list-inside space-y-0.5 sm:space-y-1 text-xs sm:text-sm font-medium"> 
                    @foreach($errors->all() as $error) 
                        <li class="break-words">{{ $error }}</li> 
                    @endforeach 
                </ul>
            </div>
        </div>
        @endif

        {{-- Form Main Content --}}
        <form method="POST" action="{{ $isEdit ? route('pengeluaran-lain.record.update', $record->id) : route('pengeluaran-lain.piutang-usaha.store') }}" 
            class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/50 p-4 sm:p-6 md:p-8 lg:p-10 space-y-5 sm:space-y-6 md:space-y-8 relative overflow-hidden">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            {{-- Dekorasi Latar Belakang --}}
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 sm:w-40 sm:h-40 bg-indigo-50 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

            {{-- Cabang Section --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5 md:gap-6 relative z-10">
                <div class="group">
                    <label class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs font-black text-slate-600 uppercase mb-1.5 sm:mb-2">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 group-hover:text-indigo-500 transition-colors flex-shrink-0"></i>
                        <span class="truncate">Unit Kreditur</span> <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select name="dari_cabang_id" required class="w-full pl-3 sm:pl-4 pr-9 sm:pr-10 py-2.5 sm:py-3 md:py-3.5 bg-slate-50 border border-slate-200 rounded-lg sm:rounded-xl text-sm sm:text-base font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 appearance-none hover:border-slate-300 cursor-pointer">
                            <option value="">— Pilih Unit Penyalur —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('dari_cabang_id', $record->dari_cabang_id ?? null) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <div class="group">
                    <label class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs font-black text-slate-600 uppercase mb-1.5 sm:mb-2">
                        <i data-lucide="store" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 group-hover:text-indigo-500 transition-colors flex-shrink-0"></i>
                        <span class="truncate">Unit Debitur</span> <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select name="branch_id" required class="w-full pl-3 sm:pl-4 pr-9 sm:pr-10 py-2.5 sm:py-3 md:py-3.5 bg-slate-50 border border-slate-200 rounded-lg sm:rounded-xl text-sm sm:text-base font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 appearance-none hover:border-slate-300 cursor-pointer">
                            <option value="">— Pilih Unit Penerima —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id', $record->branch_id ?? null) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            {{-- Tanggal & Nominal --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5 md:gap-6 relative z-10">
                <div class="group">
                    <label class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs font-black text-slate-600 uppercase mb-1.5 sm:mb-2">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 group-hover:text-indigo-500 transition-colors flex-shrink-0"></i>
                        <span>Tanggal</span> <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', isset($record) ? $record->tanggal->format('Y-m-d') : date('Y-m-d')) }}" required 
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 md:py-3.5 bg-slate-50 border border-slate-200 rounded-lg sm:rounded-xl text-sm sm:text-base font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 hover:border-slate-300">
                </div>
                <div class="group">
                    <label class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs font-black text-slate-600 uppercase mb-1.5 sm:mb-2">
                        <i data-lucide="banknote" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 group-hover:text-emerald-500 transition-colors flex-shrink-0"></i>
                        <span>Nominal</span> <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 sm:pl-4 pointer-events-none">
                            <span class="text-slate-500 font-bold text-sm sm:text-base">Rp</span>
                        </div>
                        <input type="text" id="nominal_display" inputmode="numeric" required placeholder="0"
                            autocomplete="off"
                            class="w-full pl-10 sm:pl-12 pr-3 sm:pr-4 py-2.5 sm:py-3 md:py-3.5 bg-slate-50 border border-slate-200 rounded-lg sm:rounded-xl font-black text-slate-800 text-base sm:text-lg focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-300 hover:border-slate-300 placeholder:text-slate-300">
                        <input type="number" name="nominal" id="nominal_value" value="{{ old('nominal', $record->nominal ?? null) }}" required class="hidden" min="1">
                    </div>
                </div>
            </div>

            {{-- Keterangan --}}
            <div class="group relative z-10">
                <label class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs font-black text-slate-600 uppercase mb-1.5 sm:mb-2">
                    <i data-lucide="align-left" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 group-hover:text-indigo-500 transition-colors flex-shrink-0"></i>
                    <span>Keterangan</span>
                </label>
                <textarea name="keterangan" rows="3" placeholder="Tuliskan catatan tambahan jika ada..." 
                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 md:py-3.5 bg-slate-50 border border-slate-200 rounded-lg sm:rounded-xl text-sm sm:text-base font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 hover:border-slate-300 resize-none">{{ old('keterangan', $record->keterangan ?? null) }}</textarea>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4 pt-4 sm:pt-6 relative z-10 border-t border-slate-100">
                <a href="{{ route('pengeluaran-lain.piutang-usaha.index') }}" 
                    class="w-full sm:flex-1 px-4 sm:px-6 py-3 sm:py-3.5 md:py-4 border-2 border-slate-200 rounded-lg sm:rounded-xl text-center text-sm sm:text-base font-bold text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900 transition-all duration-300">
                    Batal
                </a>
                <button type="submit" 
                    class="group w-full sm:flex-[2] px-4 sm:px-6 py-3 sm:py-3.5 md:py-4 bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-700 hover:to-sky-600 text-white font-black rounded-lg sm:rounded-xl text-sm sm:text-base shadow-lg shadow-blue-200 hover:shadow-blue-300 transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4 sm:w-5 sm:h-5 group-hover:scale-110 transition-transform duration-300 flex-shrink-0"></i>
                    <span>{{ $isEdit ? 'Update Piutang Usaha' : 'Simpan Piutang Usaha' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const display = document.getElementById('nominal_display');
    const hidden  = document.getElementById('nominal_value');

    // Saat mengetik: format display, simpan nilai murni ke hidden
    display.addEventListener('input', function () {
        const raw = this.value.replace(/\D/g, '');
        const cursor = this.selectionStart;
        const prevLen = this.value.length;

        const formatted = raw ? parseInt(raw, 10).toLocaleString('id-ID') : '';
        this.value = formatted;
        hidden.value = raw || '';

        // Pertahankan posisi kursor
        const diff = formatted.length - prevLen;
        try { this.setSelectionRange(cursor + diff, cursor + diff); } catch (e) {}
    });

    // Tolak karakter non-numerik (kecuali navigasi keyboard)
    display.addEventListener('keydown', function (e) {
        const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
        if (allowed.includes(e.key)) return;
        if (e.ctrlKey || e.metaKey) return; // copy/paste/select-all
        if (!/^\d$/.test(e.key)) e.preventDefault();
    });

    // Inisialisasi: format nilai existing (old() atau edit mode)
    const initVal = hidden.value;
    if (initVal && !isNaN(initVal) && initVal !== '') {
        display.value = parseInt(initVal, 10).toLocaleString('id-ID');
    }

    // Validasi sebelum submit
    display.closest('form').addEventListener('submit', function (e) {
        const val = parseInt(hidden.value, 10);
        if (!val || val < 1) {
            e.preventDefault();
            display.focus();
            display.classList.add('border-rose-500', 'ring-2', 'ring-rose-500/20');
            display.placeholder = 'Nominal wajib diisi!';
        }
    });
}());
</script>
@endsection
