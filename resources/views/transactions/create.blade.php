@extends('layouts.app')

@section('page-title', 'Buat Pengajuan')

@section('content')
    <div>
        {{-- Stepper --}}
        <div class="flex items-center justify-center mb-4 md:mb-5 lg:mb-6">
            <div class="flex flex-col items-center">
                <div
                    class="w-7 h-7 md:w-8 md:h-8 lg:w-9 lg:h-9 rounded-lg md:rounded-xl flex items-center justify-center border-2 border-blue-600 bg-blue-600 text-white">
                    <i data-lucide="upload" class="w-3 h-3 md:w-3.5 md:h-3.5 lg:w-4 lg:h-4"></i>
                </div>
                <span
                    class="mt-1 md:mt-1.5 text-[7px] md:text-[8px] lg:text-[9px] font-bold uppercase tracking-wider text-blue-600">1.
                    Scan</span>
            </div>
            <div class="w-8 md:w-12 lg:w-16 h-0.5 mx-1.5 md:mx-2 rounded-full bg-gray-200"></div>
            <div class="flex flex-col items-center">
                <div
                    class="w-7 h-7 md:w-8 md:h-8 lg:w-9 lg:h-9 rounded-lg md:rounded-xl flex items-center justify-center border-2 border-gray-200 bg-white text-gray-300">
                    <i data-lucide="calculator" class="w-3 h-3 md:w-3.5 md:h-3.5 lg:w-4 lg:h-4"></i>
                </div>
                <span
                    class="mt-1 md:mt-1.5 text-[7px] md:text-[8px] lg:text-[9px] font-bold uppercase tracking-wider text-gray-400">2.
                    Alokasi</span>
            </div>
        </div>

        {{-- Upload Zone --}}
        <div class="max-w-lg mx-auto py-3 md:py-4 lg:py-6 px-3 md:px-0">
            <form action="{{ route('transactions.upload') }}" method="POST" enctype="multipart/form-data" id="upload-form">
                @csrf
                    <div class="bg-white rounded-xl md:rounded-2xl lg:rounded-3xl border-2 border-dashed border-gray-200 
                        p-6 md:p-8 lg:p-12 text-center hover:border-blue-400 hover:bg-blue-50/20 
                        transition-all group cursor-pointer relative shadow-sm"
                        id="upload-area">

                        {{-- Loading state --}}
                        <div id="upload-loading" class="hidden flex-col items-center">
                            <i data-lucide="loader-2"
                                class="w-7 h-7 md:w-8 md:h-8 lg:w-10 lg:h-10 text-blue-600 animate-spin mb-2 md:mb-3"></i>
                            <h3 class="text-sm md:text-base lg:text-lg font-bold text-slate-800">Memproses...</h3>
                            <p class="text-slate-400 text-[9px] md:text-[10px] font-bold uppercase tracking-wider mt-1">
                                Mengunggah Nota
                            </p>
                        </div>

                        {{-- Default state --}}
                        <div id="upload-default">
                            <div
                                class="bg-gradient-to-br from-blue-500 to-indigo-600 w-12 h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 
                                rounded-lg md:rounded-xl lg:rounded-2xl flex items-center justify-center 
                                mx-auto mb-3 md:mb-4 lg:mb-5 group-hover:scale-105 transition-transform 
                                shadow-lg shadow-blue-500/30">
                                <i data-lucide="upload" class="w-5 h-5 md:w-6 md:h-6 lg:w-7 lg:h-7 text-white"></i>
                            </div>

                            <h3 class="text-base md:text-lg lg:text-xl font-bold mb-1.5 md:mb-2 text-slate-800">
                                Unggah Nota
                            </h3>

                            <p class="text-slate-400 mb-4 md:mb-5 lg:mb-6 text-xs md:text-sm font-medium leading-relaxed max-w-xs mx-auto">
                                Letakkan file nota digital Anda di sini
                            </p>

                            <button type="button"
                                class="bg-slate-900 hover:bg-blue-600 text-white px-5 md:px-6 lg:px-8 
                                py-2.5 md:py-3 rounded-lg md:rounded-xl font-bold 
                                transition-all shadow-lg inline-block text-xs md:text-sm active:scale-95"
                                id="select-file-btn">
                                Pilih Berkas Nota
                            </button>

                            <input type="file" id="file-input" name="file" class="hidden" accept="image/*,.pdf">

                            <p class="text-slate-300 text-[9px] md:text-[10px] font-medium mt-3 md:mt-4">
                                Format: JPG, PNG, PDF • Maksimal 10MB
                            </p>
                        </div>
                    </div>
                @error('file')
                    <div class="mt-2 md:mt-3 text-center">
                        <p class="text-red-500 font-bold text-[10px] md:text-xs">{{ $message }}</p>
                    </div>
                @enderror
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.getElementById('file-input');
        const form = document.getElementById('upload-form');
        const loading = document.getElementById('upload-loading');
        const defaultView = document.getElementById('upload-default');
        const uploadArea = document.getElementById('upload-area');
        const selectBtn = document.getElementById('select-file-btn');

        // Klik area upload
        uploadArea.addEventListener('click', function (e) {
            if (e.target !== selectBtn) {
                fileInput.click();
            }
        });

        // Klik tombol
        selectBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            if (!this.files.length) return;

            const file = this.files[0];
            const maxSize = 10 * 1024 * 1024;
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung. Gunakan JPG, PNG, atau PDF.');
                this.value = '';
                return;
            }

            if (file.size > maxSize) {
                alert('Ukuran file terlalu besar. Maksimal 10MB.');
                this.value = '';
                return;
            }

            // Tampilkan loading
            defaultView.classList.add('hidden');
            loading.classList.remove('hidden');
            loading.classList.add('flex');

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            form.submit();
        });
    });
    </script>
    @endpush

@endsection