@extends('layouts.app')

@section('page-title', 'Tambah ' . $config['label'])

@section('content')
<div class="p-4 md:p-6">
    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('pengeluaran-lain.' . str_replace('_', '-', $jenis) . '.index') }}"
                class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-all shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-900">Tambah {{ $config['label'] }}</h1>
                <p class="text-sm text-slate-500">Isi detail pengeluaran dengan lengkap</p>
            </div>
        </div>

        @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-xl text-sm font-semibold">
            <div class="flex items-center gap-2 mb-2"><i data-lucide="alert-circle" class="w-4 h-4"></i> Ada kesalahan:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Form --}}
        <form method="POST"
            action="{{ route('pengeluaran-lain.' . str_replace('_', '-', $jenis) . '.store') }}"
            enctype="multipart/form-data"
            class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8 space-y-6">
            @csrf

            {{-- Upload Bukti Transfer --}}
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">
                    Bukti Transfer
                    <span class="text-slate-400 font-normal capitalize">(opsional, JPG/PNG max 2MB)</span>
                </label>
                <div id="drop-zone" class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 transition-all" onclick="document.getElementById('bukti_transfer').click()">
                    <div id="preview-container" class="hidden mb-3">
                        <img id="preview-img" src="" alt="" class="max-h-48 mx-auto rounded-xl object-contain shadow">
                    </div>
                    <div id="drop-placeholder" class="flex flex-col items-center gap-2">
                        <i data-lucide="upload-cloud" class="w-10 h-10 text-slate-300"></i>
                        <p class="text-sm font-semibold text-slate-500">Klik atau drag & drop gambar di sini</p>
                        <p class="text-xs text-slate-400">JPG, PNG — Maks. 2MB</p>
                    </div>
                </div>
                <input type="file" id="bukti_transfer" name="bukti_transfer" accept="image/jpeg,image/png" class="hidden"
                    onchange="previewImage(this)">
            </div>

            {{-- Tujuan Transfer --}}
            @if(in_array($jenis, ['bayar_hutang', 'piutang_usaha']))
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">
                    Tujuan Transfer <span class="text-rose-500">*</span>
                </label>
                <select name="branch_id" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-semibold text-slate-800 bg-white">
                    <option value="">— Pilih Cabang —</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($jenis === 'prive')
            {{-- Tujuan Transfer (Prive) --}}
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">
                    Tujuan Transfer <span class="text-rose-500">*</span>
                    <span class="text-slate-400 font-normal">(rekening atau cash)</span>
                </label>
                <input type="text" name="rekening_tujuan" value="{{ old('rekening_tujuan') }}" required
                    placeholder="Contoh: BCA 1234567890 a/n Andi atau Cash"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all font-semibold text-slate-800 placeholder:text-slate-300">
            </div>

            {{-- Dari Cabang --}}
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">
                    Dari Cabang <span class="text-rose-500">*</span>
                </label>
                <select name="dari_cabang_id" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all font-semibold text-slate-800 bg-white">
                    <option value="">— Pilih Cabang Asal —</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('dari_cabang_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Tanggal --}}
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">
                        Tanggal <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-semibold text-slate-800">
                </div>

                {{-- Nominal --}}
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">
                        Nominal <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">Rp</span>
                        <input type="number" name="nominal" id="nominal" value="{{ old('nominal') }}" required min="1"
                            placeholder="0"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300">
                    </div>
                    <p id="nominal-preview" class="mt-1.5 text-xs font-bold text-indigo-600 hidden"></p>
                </div>
            </div>

            {{-- Keterangan --}}
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Keterangan</label>
                <textarea name="keterangan" rows="3" placeholder="Tambahkan catatan atau keterangan..."
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-semibold text-slate-800 placeholder:text-slate-300 resize-none">{{ old('keterangan') }}</textarea>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-3 pt-2">
                <a href="{{ route('pengeluaran-lain.' . str_replace('_', '-', $jenis) . '.index') }}"
                    class="flex-1 px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all text-center">
                    Batal
                </a>
                <button type="submit"
                    class="flex-[2] px-6 py-3 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 shadow-lg transition-all flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Simpan {{ $config['label'] }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('preview-container').classList.remove('hidden');
            document.getElementById('drop-placeholder').classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Nominal formatter
document.getElementById('nominal')?.addEventListener('input', function() {
    const val = parseInt(this.value);
    const preview = document.getElementById('nominal-preview');
    if (val > 0) {
        preview.textContent = '= Rp ' + val.toLocaleString('id-ID');
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
});
</script>
@endsection
