@extends('layouts.app')

@section('page-title', 'Tambah Pengguna')

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Back Link --}}
    <a href="{{ route('users.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors mb-6">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Daftar
    </a>

    {{-- Form Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="user-plus" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900">Daftarkan Pengguna Baru</h3>
                    <p class="text-xs text-slate-500 font-medium">Isi data untuk mendaftarkan pengguna baru</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-5">
            @csrf

            {{-- Nama --}}
            <div class="space-y-1.5">
                <label for="name" class="block text-sm font-bold text-slate-700">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       placeholder="Masukkan nama lengkap"
                       class="w-full px-4 py-3 rounded-xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50' }} focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 text-sm font-medium outline-none transition-all">
                @error('name')
                    <p class="text-xs font-bold text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="space-y-1.5">
                <label for="email" class="block text-sm font-bold text-slate-700">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       placeholder="contoh@financeops.com"
                       class="w-full px-4 py-3 rounded-xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50' }} focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 text-sm font-medium outline-none transition-all">
                @error('email')
                    <p class="text-xs font-bold text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-bold text-slate-700">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Minimal 6 karakter"
                           class="w-full px-4 py-3 rounded-xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50' }} focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 text-sm font-medium outline-none transition-all">
                    @error('password')
                        <p class="text-xs font-bold text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-sm font-bold text-slate-700">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           placeholder="Ulangi password"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 text-sm font-medium outline-none transition-all">
                </div>
            </div>

            {{-- Role --}}
            <div class="space-y-1.5">
                <label for="role" class="block text-sm font-bold text-slate-700">Role / Peran</label>
                <select id="role" name="role" required
                        class="w-full px-4 py-3 rounded-xl border {{ $errors->has('role') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50' }} focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 text-sm font-medium outline-none transition-all">
                    <option value="">-- Pilih Role --</option>
                    @foreach($availableRoles as $key => $label)
                        <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')
                    <p class="text-xs font-bold text-red-500 mt-1">{{ $message }}</p>
                @enderror
                @if(count($availableRoles) === 1)
                    <p class="text-xs text-slate-400 font-medium mt-1">
                        <i data-lucide="info" class="w-3 h-3 inline"></i> Anda hanya dapat mendaftarkan role Teknisi.
                    </p>
                @endif
            </div>
            {{-- Rekening Info (Optional) --}}
            <div id="bankInfoSection" class="space-y-4 pt-4 border-t border-slate-100 hidden">
                <h4 class="text-sm font-bold text-slate-800">Informasi Rekening (Opsional)</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="rekening_bank" class="block text-sm font-bold text-slate-700">Nama Bank / E-Wallet</label>
                        <div class="flex gap-2">
                            <select id="rekening_bank" name="rekening_bank"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 text-sm font-medium outline-none transition-all">
                                <option value="">-- Pilih Bank / E-Wallet --</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->name }}" {{ old('rekening_bank') == $bank->name ? 'selected' : '' }}>{{ $bank->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" onclick="addNewBank()" title="Tambah Bank Baru"
                                    class="px-4 py-3 rounded-xl border border-slate-200 bg-white text-indigo-600 hover:bg-indigo-50 font-bold transition-all flex-shrink-0">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-1.5">
                        <label for="rekening_nomor" class="block text-sm font-bold text-slate-700">Nomor Rekening</label>
                        <input type="text" id="rekening_nomor" name="rekening_nomor" value="{{ old('rekening_nomor') }}"
                               placeholder="Contoh: 1234567890"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 text-sm font-medium outline-none transition-all">
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="rekening_nama" class="block text-sm font-bold text-slate-700">Atas Nama</label>
                        <input type="text" id="rekening_nama" name="rekening_nama" value="{{ old('rekening_nama') }}"
                               placeholder="Contoh: Budi Santoso"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 text-sm font-medium outline-none transition-all">
                    </div>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('users.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-500/20 hover:shadow-xl hover:shadow-indigo-500/30 transition-all active:scale-[0.98]">
                    <i data-lucide="send" class="w-4 h-4 inline mr-1"></i> Daftarkan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const bankSection = document.getElementById('bankInfoSection');

        function toggleBankSection() {
            if (roleSelect.value === 'teknisi') {
                bankSection.classList.remove('hidden');
            } else {
                bankSection.classList.add('hidden');
            }
        }

        roleSelect.addEventListener('change', toggleBankSection);
        toggleBankSection(); // Initialize on load
    });

    async function addNewBank() {
        const bankName = prompt("Masukkan nama Bank / E-Wallet baru:");
        if (!bankName || bankName.trim() === '') return;

        try {
            const formData = new FormData();
            formData.append('name', bankName.trim());

            const response = await fetch('/api/v1/banks', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Gagal menambahkan bank');
            }

            // Append to select
            const select = document.getElementById('rekening_bank');
            const option = document.createElement('option');
            option.value = data.name;
            option.text = data.name;
            option.selected = true;
            select.add(option);

            if(typeof showToast !== 'undefined') {
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">Bank berhasil ditambahkan</span></div></div>`, 'success');
            } else {
                alert("Bank berhasil ditambahkan!");
            }
        } catch (error) {
            alert(error.message);
        }
    }
</script>
@endpush
