@extends('layouts.app')

@section('page-title', 'Tambah Piutang Usaha')

@section('content')
{{-- Tambahan custom animasi sederhana di Blade --}}
<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
    .stagger-1 { animation-delay: 0.1s; }
    .stagger-2 { animation-delay: 0.2s; }
    .stagger-3 { animation-delay: 0.3s; }
</style>

<div class="p-4 md:p-8 min-h-screen bg-slate-50/50">
    <div class="max-w-3xl mx-auto space-y-8">

        {{-- Header Section (Animasi Masuk) --}}
        <div class="flex items-center gap-5 opacity-0 animate-fade-in-up">
            <a href="{{ route('pengeluaran-lain.piutang-usaha.index') }}"
                class="group w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-x-1">
                <i data-lucide="arrow-left" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
            </a>
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Tambah Piutang Usaha</h1>
                <p class="text-sm font-medium text-slate-500 mt-1 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    Isi detail pencairan piutang dengan lengkap dan valid
                </p>
            </div>
        </div>

        {{-- Error Alert --}}
        @if($errors->any())
        <div class="opacity-0 animate-fade-in-up stagger-1 bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-5 rounded-r-2xl shadow-sm flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-6 h-6 text-rose-500 shrink-0"></i>
            <div>
                <h3 class="font-bold text-rose-900 mb-1">Terdapat kesalahan:</h3>
                <ul class="list-disc list-inside space-y-1 text-sm font-medium"> 
                    @foreach($errors->all() as $error) 
                        <li>{{ $error }}</li> 
                    @endforeach 
                </ul>
            </div>
        </div>
        @endif

        {{-- Form Main Content --}}
        <form method="POST" action="{{ route('pengeluaran-lain.piutang-usaha.store') }}" enctype="multipart/form-data" 
            class="opacity-0 animate-fade-in-up stagger-2 bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/50 p-6 md:p-10 space-y-8 relative overflow-hidden">
            @csrf

            {{-- Dekorasi Latar Belakang --}}
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-indigo-50 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

            {{-- Bukti Transfer --}}
            <div class="relative z-10">
                <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase tracking-widest mb-3">
                    <i data-lucide="image" class="w-4 h-4 text-indigo-500"></i>
                    Bukti Transfer <span class="text-indigo-400 normal-case font-semibold tracking-normal">(JPG/PNG max 2MB)</span>
                </label>
                
                <div class="group relative border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center cursor-pointer hover:border-indigo-500 hover:bg-indigo-50/50 transition-all duration-300" 
                     onclick="document.getElementById('bukti_transfer').click()">
                    
                    {{-- State Kosong --}}
                    <div id="upload-placeholder" class="transition-all duration-300">
                        <div class="w-16 h-16 mx-auto bg-slate-50 group-hover:bg-indigo-100 rounded-full flex items-center justify-center mb-4 transition-colors duration-300">
                            <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-400 group-hover:text-indigo-600 transition-colors duration-300"></i>
                        </div>
                        <p class="text-base font-bold text-slate-700 group-hover:text-indigo-700">Klik untuk mengunggah bukti</p>
                        <p class="text-sm text-slate-500 mt-1">atau seret dan lepas file di sini</p>
                    </div>

                    {{-- State Preview --}}
                    <div id="preview-container" class="hidden flex flex-col items-center">
                        <img id="preview-img" class="max-h-56 mx-auto rounded-xl shadow-md border border-slate-200 object-cover transform scale-95 transition-transform duration-500">
                        <div class="mt-4 flex items-center gap-2 text-sm font-semibold text-emerald-600 bg-emerald-50 px-4 py-2 rounded-lg">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                            <span id="file-name">Gambar berhasil dipilih</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-2 hover:text-indigo-500">Klik lagi untuk mengganti gambar</p>
                    </div>
                </div>
                <input type="file" id="bukti_transfer" name="bukti_transfer" accept="image/*" class="hidden" onchange="previewImage(this)">
            </div>

            <hr class="border-slate-100">

            {{-- Cabang Section --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                <div class="group">
                    <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                        <i data-lucide="building-2" class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors"></i>
                        Unit Kreditur <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select name="dari_cabang_id" required class="w-full pl-4 pr-10 py-3.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 appearance-none hover:border-slate-300 cursor-pointer">
                            <option value="">— Pilih Unit Penyalur —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('dari_cabang_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <div class="group">
                    <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                        <i data-lucide="store" class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors"></i>
                        Unit Debitur <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select name="branch_id" required class="w-full pl-4 pr-10 py-3.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 appearance-none hover:border-slate-300 cursor-pointer">
                            <option value="">— Pilih Unit Penerima —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            {{-- Tanggal & Nominal --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                <div class="group">
                    <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                        <i data-lucide="calendar" class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors"></i>
                        Tanggal <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required 
                        class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 hover:border-slate-300">
                </div>
                <div class="group">
                    <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                        <i data-lucide="banknote" class="w-4 h-4 text-slate-400 group-hover:text-emerald-500 transition-colors"></i>
                        Nominal <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <span class="text-slate-500 font-bold">Rp</span>
                        </div>
                        <input type="number" name="nominal" value="{{ old('nominal') }}" required placeholder="0"
                            class="w-full pl-12 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-800 text-lg focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-300 hover:border-slate-300 placeholder:text-slate-300">
                    </div>
                </div>
            </div>

            {{-- Keterangan --}}
            <div class="group relative z-10">
                <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                    <i data-lucide="align-left" class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors"></i>
                    Keterangan
                </label>
                <textarea name="keterangan" rows="3" placeholder="Tuliskan catatan tambahan jika ada..." 
                    class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 hover:border-slate-300 resize-none">{{ old('keterangan') }}</textarea>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 pt-6 relative z-10 border-t border-slate-100">
                <a href="{{ route('pengeluaran-lain.piutang-usaha.index') }}" 
                    class="w-full sm:w-auto flex-1 px-6 py-4 border-2 border-slate-200 rounded-xl text-center font-bold text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900 transition-all duration-300">
                    Batal
                </a>
                <button type="submit" 
                    class="group w-full sm:w-auto flex-[2] px-6 py-4 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-black rounded-xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5 group-hover:scale-110 transition-transform duration-300"></i>
                    Simpan Piutang Usaha
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Pastikan Lucide Icons di-render (Jika menggunakan CDN)
    // lucide.createIcons();

    function previewImage(input) {
        const previewContainer = document.getElementById('preview-container');
        const uploadPlaceholder = document.getElementById('upload-placeholder');
        const previewImg = document.getElementById('preview-img');
        const fileNameDisplay = document.getElementById('file-name');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) { 
                // Set sumber gambar
                previewImg.src = e.target.result; 
                
                // Tampilkan nama file
                fileNameDisplay.textContent = file.name;

                // Animasi pergantian state
                uploadPlaceholder.classList.add('hidden');
                previewContainer.classList.remove('hidden');
                
                // Efek scale-in gambar
                setTimeout(() => {
                    previewImg.classList.remove('scale-95');
                    previewImg.classList.add('scale-100');
                }, 50);
            };
            
            reader.readAsDataURL(file);
        }
    }
</script>
@endsection