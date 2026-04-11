@extends('layouts.app')

@section('page-title', 'Tambah Prive')

@section('content')
<div class="p-4 md:p-6">
    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('pengeluaran-lain.prive.index') }}"
                class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-all shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-900">Tambah Prive</h1>
                <p class="text-sm text-slate-500">Isi detail prive dengan lengkap</p>
            </div>
        </div>

        @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-xl text-sm font-semibold">
            <ul class="list-disc list-inside space-y-1"> @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach </ul>
        </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('pengeluaran-lain.prive.store') }}" enctype="multipart/form-data"
            class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8 space-y-6">
            @csrf

            {{-- Bukti --}}
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase mb-2">Bukti Transfer (Opsional)</label>
                <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer" onclick="document.getElementById('bukti_transfer').click()">
                    <div id="preview-container" class="hidden mb-3"><img id="preview-img" class="max-h-48 mx-auto rounded-xl shadow"></div>
                    <p class="text-sm font-semibold text-slate-500">Klik untuk upload bukti</p>
                </div>
                <input type="file" id="bukti_transfer" name="bukti_transfer" class="hidden" onchange="previewImage(this)">
            </div>

            {{-- Tujuan (Text) --}}
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase mb-2">Tujuan Transfer <span class="text-rose-500">*</span></label>
                <input type="text" name="rekening_tujuan" value="{{ old('rekening_tujuan') }}" required placeholder="Contoh: BCA 1234567890 a/n Andi"
                    class="w-full px-4 py-3 border rounded-xl font-semibold bg-white">
            </div>

            {{-- Dari Cabang --}}
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase mb-2">Dari Cabang <span class="text-rose-500">*</span></label>
                <select name="dari_cabang_id" required class="w-full px-4 py-3 border rounded-xl font-semibold bg-white">
                    <option value="">— Pilih Cabang Asal —</option>
                    @foreach($branches as $branch) <option value="{{ $branch->id }}" {{ old('dari_cabang_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option> @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Tanggal <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required class="w-full px-4 py-3 border rounded-xl font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Nominal <span class="text-rose-500">*</span></label>
                    <input type="number" name="nominal" value="{{ old('nominal') }}" required class="w-full px-4 py-3 border rounded-xl font-bold">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <a href="{{ route('pengeluaran-lain.prive.index') }}" class="flex-1 px-6 py-3 border rounded-xl text-center">Batal</a>
                <button type="submit" class="flex-[2] px-6 py-3 bg-slate-900 text-white font-bold rounded-xl shadow-lg">Simpan Prive</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(i) {
    if (i.files && i.files[0]) {
        const r = new FileReader();
        r.onload = (e) => { document.getElementById('preview-img').src = e.target.result; document.getElementById('preview-container').classList.remove('hidden'); };
        r.readAsDataURL(i.files[0]);
    }
}
</script>
@endsection
