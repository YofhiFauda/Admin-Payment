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
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i data-lucide="user-plus" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-900 leading-tight">Daftarkan Pengguna Baru</h3>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-0.5">Lengkapi data akun untuk akses sistem</p>
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
                       oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase()"
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
    // Bank info script removed as it's now managed via UserBankAccount feature
</script>
@endpush
