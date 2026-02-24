@extends('layouts.app')

@php
    $hideHeader = true;
@endphp

@section('content')

    
<div class="min-h-screen flex items-start sm:items-center justify-center
            bg-gradient-to-br from-slate-50 to-slate-100
            px-4 sm:px-6 lg:px-8
            pt-16 sm:pt-0 pb-10">
    <div class="w-full max-w-xl sm:max-w-2xl lg:max-w-4xl mx-auto text-center py-10 sm:py-14 lg:py-20">

        {{-- TITLE --}}
        <h1 class="text-3xl sm:text-4xl lg:text-6xl font-extrabold tracking-tight mb-4 sm:mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 via-purple-600 to-teal-500 drop-shadow-sm pb-2">
            Mulai Pengajuan Anda
        </h1>

        <p class="text-sm sm:text-base text-slate-500 max-w-md sm:max-w-xl lg:max-w-2xl mx-auto mb-8 sm:mb-10">
            Upload foto nota atau referensi barang untuk memulai klaim reimbursement atau pengajuan beli. 
            <span class="text-indigo-600 font-semibold">
                ✨ Didukung oleh AI
            </span>
        </p>

        {{-- UPLOAD FORM --}}
        <form id="uploadForm"
              action="{{ route('rembush.upload') }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf

            <div id="uploadArea"
                class="relative w-full border-2 border-dashed border-indigo-300 rounded-3xl p-8 sm:p-12 lg:p-16 cursor-pointer transition hover:border-indigo-500 hover:bg-indigo-50/30 flex flex-col items-center justify-center gap-4 sm:gap-6">

                <div id="uploadDefault" class="text-center">
                    <div class="w-16 h-16 mx-auto bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="upload-cloud" class="w-8 h-8 text-indigo-600"></i>
                    </div>

                    <p class="font-semibold text-slate-700 mb-1">
                        Klik atau Drag File ke Sini
                    </p>
                    <p class="text-xs text-slate-400">
                        JPG, PNG (Max 1MB)
                    </p>
                </div>

                <div id="uploadPreview" class="hidden text-center">
                    <p class="text-green-600 font-semibold">File siap digunakan</p>
                    <p id="fileName" class="text-sm text-slate-600 mt-1"></p>
                </div>

                <button type="button" id="select-file-btn" class="mt-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                    Pilih Foto Dokumen
                </button>

                <input type="file" 
                    id="file-input" 
                    name="file" 
                    class="hidden" 
                    accept="image/*">
            </div>

        </form>
    </div>
</div>

{{-- MODAL PILIH TUJUAN --}}
<div id="choiceModal"
     class="fixed inset-0 hidden items-center justify-center
     bg-black/40 backdrop-blur-md z-50
     opacity-0 transition-opacity duration-300">

    <div id="modalBox"
        class="bg-white/80 backdrop-blur-xl rounded-3xl w-full max-w-md p-8
        transform scale-95 opacity-0
        transition-all duration-300 shadow-2xl">

        <h2 class="text-xl font-bold mb-6 text-slate-800">
            Pilih Tujuan
        </h2>

        <div class="space-y-4">

            {{-- PILIHAN REIMBURSEMENT --}}
            <button id="btnRembush"
                class="w-full group relative bg-white p-5 rounded-3xl border-2 border-slate-100 hover:border-transparent hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 transition-all duration-300 text-left flex items-center gap-5 shadow-sm hover:shadow-xl hover:shadow-indigo-500/20"
              >
                <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 group-hover:bg-white/20 group-hover:text-white transition-colors">
                  <i data-lucide="receipt" class="w-7 h-7"></i>
                </div>
                <div>
                  <h4 class="font-bold text-slate-900 group-hover:text-white text-lg transition-colors">Reimbursement</h4>
                  <p class="text-sm text-slate-500 group-hover:text-indigo-100 transition-colors">Klaim dana dengan nota</p>
                </div>
              </button>

              {{-- PILIHAN PENGAJUAN BELI --}}
              <button id="btnPengajuan"
                class="w-full group relative bg-white p-5 rounded-3xl border-2 border-slate-100 hover:border-transparent hover:bg-gradient-to-r hover:from-teal-400 hover:to-emerald-500 transition-all duration-300 text-left flex items-center gap-5 shadow-sm hover:shadow-xl hover:shadow-teal-500/20"
              >
                <div class="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600 group-hover:bg-white/20 group-hover:text-white transition-colors">
                  <i data-lucide="shopping-cart" class="w-7 h-7"></i>
                </div>
                <div>
                  <h4 class="font-bold text-slate-900 group-hover:text-white text-lg transition-colors">Pengajuan Beli</h4>
                  <p class="text-sm text-slate-500 group-hover:text-teal-100 transition-colors">Beli barang (Referensi)</p>
                </div>
              </button>
            

            <button id="btnCancel"
                class="mt-6 w-full py-4 text-sm font-bold text-slate-500 hover:text-slate-800 bg-slate-100/50 hover:bg-rose-500 rounded-2xl transition-colors">
                Batal
            </button>

        </div>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loadingOverlay"
     class="fixed inset-0 hidden items-center justify-center
     bg-white z-[60] opacity-0 transition-opacity duration-300">

    <div class="text-center">
        <div class="w-14 h-14 border-4 border-indigo-500
            border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>

        <p class="text-slate-700 font-semibold">
            Memproses Nota dengan AI...
        </p>
        <p class="text-sm text-slate-400">
            Mohon tunggu sebentar
        </p>
    </div>
</div>

{{-- GLOBAL DRAG OVERLAY --}}
<div id="globalDragOverlay"
     class="fixed inset-0 hidden items-center justify-center
     bg-indigo-600/10 backdrop-blur-sm z-[70]
     transition-opacity duration-200 opacity-0">

    <div class="bg-white rounded-3xl shadow-2xl px-12 py-10 text-center border border-indigo-100">
        <div class="w-20 h-20 mx-auto bg-indigo-100 rounded-2xl flex items-center justify-center mb-6">
            <i data-lucide="image-plus" class="w-10 h-10 text-indigo-600"></i>
        </div>

        <h3 class="text-2xl font-bold text-slate-800 mb-2">
            Drop file di sini
        </h3>
        <p class="text-slate-500">
            Maksimal ukuran 1MB
        </p>
    </div>
</div>

{{-- TOAST --}}
<div id="toast"
     class="fixed top-6 right-6 hidden z-[80]
     bg-red-500 text-white px-6 py-4 rounded-xl
     shadow-xl transition-all duration-300
     translate-y-[-20px] opacity-0">
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const fileInput = document.getElementById('file-input');
    const selectBtn = document.getElementById('select-file-btn');
    const uploadPreview = document.getElementById('uploadPreview');
    const uploadDefault = document.getElementById('uploadDefault');
    const fileName = document.getElementById('fileName');

    const modal = document.getElementById('choiceModal');
    const modalBox = document.getElementById('modalBox');

    const btnRembush = document.getElementById('btnRembush');
    const btnPengajuan = document.getElementById('btnPengajuan');
    const btnCancel = document.getElementById('btnCancel');

    const form = document.getElementById('uploadForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const uploadArea = document.getElementById('uploadArea');
    const globalOverlay = document.getElementById('globalDragOverlay');
    const toast = document.getElementById('toast');

    const MAX_SIZE = 1024 * 1024; // 1MB

    let selectedFile = null;

    // =============================
    // SHOW MODAL WITH ANIMATION
    // =============================
    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');

            modalBox.classList.remove('scale-95','opacity-0');
            modalBox.classList.add('scale-100','opacity-100');
        }, 10);
    }

    function closeModal() {
        modal.classList.add('opacity-0');
        modalBox.classList.add('scale-95','opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    // =============================
    // FILE SELECT
    // =============================
    fileInput.addEventListener('change', function() {

        if (!this.files[0]) return;

        const file = this.files[0];

        // 🔥 VALIDASI UKURAN
        if (file.size > MAX_SIZE) {
            showToast("Ukuran file maksimal 1MB!");
            fileInput.value = '';
            return;
        }

        selectedFile = file;

        uploadDefault.classList.add('hidden');
        uploadPreview.classList.remove('hidden');
        fileName.textContent = selectedFile.name;

        openModal();
    });

    // =============================
    // FILE SELECT BUTTON
    // =============================
    selectBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        fileInput.click();
    });

    // =============================
    // DRAG AND DROP
    // =============================
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('bg-indigo-100/40');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('bg-indigo-100/40');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('bg-indigo-100/40');

        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    // =============================
    // GLOBAL DRAG EVENTS
    // =============================

    let dragCounter = 0;

    window.addEventListener('dragenter', (e) => {
        e.preventDefault();
        dragCounter++;

        globalOverlay.classList.remove('hidden');
        globalOverlay.classList.add('flex');

        setTimeout(() => {
            globalOverlay.classList.remove('opacity-0');
            globalOverlay.classList.add('opacity-100');
        }, 10);
    });

    window.addEventListener('dragleave', (e) => {
        dragCounter--;

        if (dragCounter === 0) {
            globalOverlay.classList.add('opacity-0');
            setTimeout(() => {
                globalOverlay.classList.add('hidden');
                globalOverlay.classList.remove('flex');
            }, 200);
        }
    });

    window.addEventListener('dragover', (e) => {
        e.preventDefault();
    });

    window.addEventListener('drop', (e) => {
        e.preventDefault();
        dragCounter = 0;

        globalOverlay.classList.add('opacity-0');
        setTimeout(() => {
            globalOverlay.classList.add('hidden');
            globalOverlay.classList.remove('flex');
        }, 200);

        if (e.dataTransfer.files.length) {
            const file = e.dataTransfer.files[0];

            if (file.size > MAX_SIZE) {
                showToast("Ukuran file maksimal 1MB!");
                return;
            }

            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    // =============================
    // SHOW TOAST
    // =============================
    function showToast(message) {
        toast.textContent = message;
        toast.classList.remove('hidden');

        setTimeout(() => {
            toast.classList.remove('opacity-0','translate-y-[-20px]');
            toast.classList.add('opacity-100','translate-y-0');
        }, 10);

        setTimeout(() => {
            toast.classList.add('opacity-0','translate-y-[-20px]');
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 3000);
    }

    // =============================
    // REMBUSH CLICK
    // =============================
    btnRembush.addEventListener('click', function() {
        if (!selectedFile) return;

        closeModal();

        setTimeout(() => {

            // show loading overlay smooth
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');

            setTimeout(() => {
                loadingOverlay.classList.remove('opacity-0');
                loadingOverlay.classList.add('opacity-100');
            }, 10);

            // delay sedikit biar smooth
            setTimeout(() => {
                form.submit();
            }, 400);

        }, 300);
    });

    // =============================
    // PENGAJUAN CLICK
    // =============================
    btnPengajuan.addEventListener('click', function() {
        if (!selectedFile) return;

        closeModal();

        setTimeout(() => {
            showLoading(
                "Menyiapkan Form Pengajuan...",
                "Mohon tunggu sebentar"
            );

            setTimeout(() => {
                form.action = "{{ route('pengajuan.upload') }}";
                form.submit();
            }, 400);

        }, 300);
    });


    function showLoading(title, subtitle) {
        loadingOverlay.classList.remove('hidden');
        loadingOverlay.classList.add('flex');

        setTimeout(() => {
            loadingOverlay.classList.remove('opacity-0');
            loadingOverlay.classList.add('opacity-100');
        }, 10);

        loadingOverlay.querySelector('p.font-semibold').textContent = title;
        loadingOverlay.querySelector('p.text-sm').textContent = subtitle;
    }

    // =============================
    // CANCEL
    // =============================
    btnCancel.addEventListener('click', function() {

        closeModal();

        fileInput.value = '';
        uploadPreview.classList.add('hidden');
        uploadDefault.classList.remove('hidden');
    });

});
</script>
@endpush

@endsection