@extends('layouts.app')

@section('page-title', 'Edit Gaji — ' . $salary->invoice_number)

@section('content')
<div class="p-4 md:p-6">
    <div class="max-w-3xl mx-auto space-y-6">

        <div class="flex items-center gap-4">
            <a href="{{ route('pengeluaran-lain.gaji.show', $salary->id) }}"
                class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-all shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-900">Edit Gaji</h1>
                <p class="text-sm font-mono text-slate-400">{{ $salary->invoice_number }}</p>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-3.5 flex items-center gap-3">
            <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-amber-800">Data gaji hanya bisa diedit selama masih berstatus <strong>Draft</strong>.</p>
        </div>

        @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-xl text-sm font-semibold">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('pengeluaran-lain.gaji.update', $salary->id) }}" class="space-y-6" id="gajiForm">
            @csrf @method('PUT')

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-black text-slate-500 uppercase tracking-wider border-b border-slate-100 pb-3">Informasi Dasar</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Karyawan <span class="text-rose-500">*</span></label>
                        <select name="user_id" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-green-500 focus:ring-4 focus:ring-green-500/10 transition-all font-semibold text-slate-800 bg-white">
                            <option value="">— Pilih Karyawan —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ (old('user_id', $salary->user_id) == $emp->id) ? 'selected' : '' }}>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Periode <span class="text-rose-500">*</span></label>
                        <input type="text" name="periode" value="{{ old('periode', $salary->periode) }}" required placeholder="Contoh: Maret 2026"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-green-500 focus:ring-4 focus:ring-green-500/10 transition-all font-semibold text-slate-800 placeholder:text-slate-300">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-black text-slate-500 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-green-500"></i> Komponen Gaji
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @php
                    $komponents = ['gaji_pokok' => 'Gaji Pokok', 'bonus_1' => 'Bonus 1', 'bonus_2' => 'Bonus 2',
                        'tunjangan' => 'Tunjangan', 'lembur' => 'Lembur', 'bensin' => 'Bensin', 'lebih_hari' => 'Lebih Hari'];
                    @endphp
                    @foreach($komponents as $field => $label)
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">{{ $label }}</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">Rp</span>
                            <input type="text" name="{{ $field }}" id="{{ $field }}"
                                value="{{ old($field, $salary->{$field}) }}" placeholder="0"
                                class="salary-input w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-green-500 focus:ring-4 focus:ring-green-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300"
                                oninput="formatRupiah(this)" onfocus="handleFocus(this)" onblur="handleBlur(this)">
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-rose-100 shadow-sm p-6 space-y-5">
                <h2 class="text-sm font-black text-slate-500 uppercase tracking-wider border-b border-rose-100 pb-3 flex items-center gap-2">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-rose-500"></i> Potongan
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Potongan Absensi</label>
                        <p class="text-[10px] text-slate-400 mb-2">Absen, cuti, mangkir, telat</p>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-rose-400 font-bold text-sm">Rp</span>
                            <input type="text" name="potongan_absen" id="potongan_absen" value="{{ old('potongan_absen', $salary->potongan_absen) }}" placeholder="0"
                                class="salary-input w-full pl-10 pr-4 py-3 rounded-xl border border-rose-200 focus:border-rose-400 focus:ring-4 focus:ring-rose-400/10 transition-all font-bold text-slate-800 placeholder:text-slate-300" oninput="formatRupiah(this)" onfocus="handleFocus(this)" onblur="handleBlur(this)">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Potongan Bon/Angsuran</label>
                        <p class="text-[10px] text-slate-400 mb-2">Bon atau angsuran karyawan</p>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-rose-400 font-bold text-sm">Rp</span>
                            <input type="text" name="potongan_bon" id="potongan_bon" value="{{ old('potongan_bon', $salary->potongan_bon) }}" placeholder="0"
                                class="salary-input w-full pl-10 pr-4 py-3 rounded-xl border border-rose-200 focus:border-rose-400 focus:ring-4 focus:ring-rose-400/10 transition-all font-bold text-slate-800 placeholder:text-slate-300" oninput="formatRupiah(this)" onfocus="handleFocus(this)" onblur="handleBlur(this)">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
                <p class="text-sm font-bold text-green-100 mb-1">Total Gaji Bersih</p>
                <p id="total-display" class="text-4xl font-black tracking-tight">Rp 0</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Catatan Atasan</label>
                <textarea name="catatan_atasan" rows="3" placeholder="Catatan tambahan (opsional)..."
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-slate-400 transition-all font-semibold text-slate-800 placeholder:text-slate-300 resize-none">{{ old('catatan_atasan', $salary->catatan_atasan) }}</textarea>
            </div>

            <div class="flex items-center gap-3 pb-4">
                <a href="{{ route('pengeluaran-lain.gaji.show', $salary->id) }}"
                    class="flex-1 px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all text-center">Batal</a>
                <button type="submit"
                    class="flex-[2] px-6 py-3 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 shadow-lg transition-all flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const KOMPONEN = ['gaji_pokok','bonus_1','bonus_2','tunjangan','lembur','bensin','lebih_hari'];
const POTONGAN = ['potongan_absen','potongan_bon'];

function parseVal(val) {
    if (!val) return 0;
    return parseInt(val.toString().replace(/\./g, ''), 10) || 0;
}

function val(id) { 
    return parseVal(document.getElementById(id)?.value); 
}

function formatRupiah(el) {
    let num = el.value.replace(/\D/g, '');
    if (num === '') {
        el.value = '';
    } else {
        el.value = new Intl.NumberFormat('id-ID').format(parseInt(num, 10));
    }
    calculateTotal();
}

function handleFocus(el) {
    if (parseVal(el.value) === 0) el.value = '';
}

function handleBlur(el) {
    if (el.value === '') {
        el.value = '0';
        calculateTotal();
    }
}

function calculateTotal() {
    const totalKomponen = KOMPONEN.reduce((s, id) => s + val(id), 0);
    const totalPotongan = POTONGAN.reduce((s, id) => s + val(id), 0);
    const total = Math.max(0, totalKomponen - totalPotongan);
    document.getElementById('total-display').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.salary-input').forEach(el => {
        if (el.value !== '') {
            el.value = new Intl.NumberFormat('id-ID').format(parseVal(el.value));
        }
    });
    calculateTotal();
});

document.getElementById('gajiForm').addEventListener('submit', function() {
    document.querySelectorAll('.salary-input').forEach(input => {
        input.value = parseVal(input.value);
    });
});
</script>
@endsection
