@extends('layouts.app')

@php
    $isEdit = isset($record);
    $pageTitle = $isEdit ? 'Edit Prive' : 'Tambah Prive';
    $formAction = $isEdit
        ? route('pengeluaran-lain.record.update', $record->id)
        : route('pengeluaran-lain.prive.store');
    $selectedMethod = old('payment_method', $isEdit ? ($record->payment_method ?? ($record->rekening_tujuan ? 'transfer' : 'cash')) : 'transfer');
    $recipientValue = $isEdit ? trim((string) $record->recipient_name) : '';
    if (strcasecmp($recipientValue, 'Penerima Default') === 0) {
        $recipientValue = '';
    }
@endphp

@section('page-title', $pageTitle)

@section('content')
<div class="p-4 md:p-8 min-h-screen bg-slate-50/60">
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('pengeluaran-lain.prive.index') }}"
                class="w-11 h-11 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-all shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900">{{ $pageTitle }}</h1>
                <p class="text-sm text-slate-500 mt-1">Pengajuan Prive akan masuk antrean approval Owner.</p>
            </div>
        </div>

        @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-xl text-sm font-semibold">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data"
            class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8 space-y-7">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                        <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                        Tanggal <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal"
                        value="{{ old('tanggal', $isEdit ? $record->tanggal->format('Y-m-d') : date('Y-m-d')) }}"
                        required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all">
                </div>

                <div>
                    <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                        <i data-lucide="banknote" class="w-4 h-4 text-slate-400"></i>
                        Nominal <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500 font-bold">Rp</span>
                        <input type="text" id="nominal_display" inputmode="numeric" autocomplete="off" required placeholder="0"
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all">
                        <input type="number" name="nominal" id="nominal_value"
                            value="{{ old('nominal', $isEdit ? $record->nominal : '') }}"
                            min="1" required class="hidden">
                    </div>
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                    <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                    Dari Cabang <span class="text-rose-500">*</span>
                </label>
                <select name="dari_cabang_id" required
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all">
                    <option value="">Pilih Cabang Asal</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('dari_cabang_id', $isEdit ? $record->dari_cabang_id : '') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                    <i data-lucide="wallet-cards" class="w-4 h-4 text-slate-400"></i>
                    Metode Prive <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="transfer" class="peer sr-only" {{ $selectedMethod === 'transfer' ? 'checked' : '' }}>
                        <span class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm font-black text-slate-600 peer-checked:bg-purple-50 peer-checked:border-purple-500 peer-checked:text-purple-700 transition-all">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Transfer
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" class="peer sr-only" {{ $selectedMethod === 'cash' ? 'checked' : '' }}>
                        <span class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-sm font-black text-slate-600 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition-all">
                            <i data-lucide="banknote" class="w-4 h-4"></i>
                            Cash
                        </span>
                    </label>
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                    <i data-lucide="user-round" class="w-4 h-4 text-slate-400"></i>
                    Nama Penerima <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="recipient_name"
                    value="{{ old('recipient_name', $recipientValue) }}"
                    required placeholder="Contoh: Andi / Owner / Nama penerima dana"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all">
            </div>

            <div id="transfer-destination-field">
                <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                    <i data-lucide="landmark" class="w-4 h-4 text-slate-400"></i>
                    Tujuan Transfer <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="rekening_tujuan"
                    value="{{ old('rekening_tujuan', $isEdit ? $record->rekening_tujuan : '') }}"
                    placeholder="Contoh: BCA 1234567890 a/n Andi"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all">
            </div>

            <div>
                <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                    <i data-lucide="align-left" class="w-4 h-4 text-slate-400"></i>
                    Keterangan
                </label>
                <textarea name="keterangan" rows="3" placeholder="Contoh: Prive owner Mei 2026, pengambilan dana dari cabang..."
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 outline-none transition-all resize-none">{{ old('keterangan', $isEdit ? $record->keterangan : '') }}</textarea>
            </div>

            @if(!$isEdit)
            <div>
                <label class="flex items-center gap-2 text-xs font-black text-slate-600 uppercase mb-2">
                    <i data-lucide="image" class="w-4 h-4 text-slate-400"></i>
                    Bukti Transfer / Cash <span class="text-slate-400 normal-case">(opsional)</span>
                </label>
                <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-purple-300 hover:bg-purple-50/30 transition-all"
                    onclick="document.getElementById('bukti_transfer').click()">
                    <div id="preview-container" class="hidden mb-3">
                        <img id="preview-img" class="max-h-48 mx-auto rounded-xl shadow">
                    </div>
                    <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                    <p class="text-sm font-bold text-slate-600">Klik untuk upload bukti</p>
                    <p class="text-xs text-slate-400 mt-1">JPG, JPEG, atau PNG maksimal 2MB</p>
                </div>
                <input type="file" id="bukti_transfer" name="bukti_transfer" accept="image/*" class="hidden" onchange="previewImage(this)">
            </div>
            @elseif($record->bukti_transfer)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black text-slate-500 uppercase">Bukti saat ini</p>
                    <p class="text-sm font-semibold text-slate-700 mt-1">{{ basename($record->bukti_transfer) }}</p>
                </div>
                <a href="{{ route('pengeluaran-lain.record.image', $record->id) }}" target="_blank"
                    class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-100 transition-all">
                    Lihat Bukti
                </a>
            </div>
            @endif

            <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                <a href="{{ route('pengeluaran-lain.prive.index') }}"
                    class="w-full sm:flex-1 px-6 py-3 border border-slate-200 rounded-xl text-center font-bold text-slate-600 hover:bg-slate-50 transition-all">
                    Batal
                </a>
                <button type="submit"
                    class="w-full sm:flex-[2] px-6 py-3 bg-linear-to-r from-sky-600 to-sky-500 text-white font-black rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    {{ $isEdit ? 'Simpan Perubahan' : 'Ajukan Prive' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (event) => {
            document.getElementById('preview-img').src = event.target.result;
            document.getElementById('preview-container').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

(function () {
    const display = document.getElementById('nominal_display');
    const hidden = document.getElementById('nominal_value');

    function formatRupiah(value) {
        const digits = String(value || '').replace(/\D/g, '');
        return digits ? parseInt(digits, 10).toLocaleString('id-ID') : '';
    }

    display.value = formatRupiah(hidden.value);

    display.addEventListener('input', function () {
        const raw = this.value.replace(/\D/g, '');
        this.value = formatRupiah(raw);
        hidden.value = raw;
    });

    display.addEventListener('keydown', function (event) {
        const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
        if (allowed.includes(event.key) || event.ctrlKey || event.metaKey) return;
        if (!/^\d$/.test(event.key)) event.preventDefault();
    });
}());

(function () {
    const destinationWrapper = document.getElementById('transfer-destination-field');
    const destinationInput = document.querySelector('input[name="rekening_tujuan"]');
    const methodInputs = document.querySelectorAll('input[name="payment_method"]');

    function syncMethodFields() {
        const selected = document.querySelector('input[name="payment_method"]:checked')?.value || 'transfer';
        const isTransfer = selected === 'transfer';

        destinationWrapper.classList.toggle('hidden', !isTransfer);
        destinationInput.required = isTransfer;
        if (!isTransfer) {
            destinationInput.value = '';
        }
    }

    methodInputs.forEach((input) => input.addEventListener('change', syncMethodFields));
    syncMethodFields();
}());
</script>
@endsection
