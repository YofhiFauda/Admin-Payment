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

        <form method="POST" action="{{ route('users.store') }}" class="p-8 space-y-6" id="userForm">
            @csrf

            {{-- Nama --}}
            <div class="space-y-2 group">
                <label for="name" class="block text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1 group-focus-within:text-indigo-600 transition-colors">Nama Lengkap</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </div>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           placeholder="Masukkan nama lengkap"
                           style="text-transform: capitalize;"
                           class="w-full pl-11 pr-4 py-3.5 rounded-2xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50' }} focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 text-sm font-bold text-slate-800 outline-none transition-all placeholder:text-slate-300">
                </div>
                @error('name')
                    <p class="text-[11px] font-black text-red-500 mt-1.5 ml-1 uppercase tracking-wider">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="space-y-2 group">
                <label for="email" class="block text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1 group-focus-within:text-indigo-600 transition-colors">Email Perusahaan</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </div>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           placeholder="contoh@financeops.com"
                           class="w-full pl-11 pr-4 py-3.5 rounded-2xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50' }} focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 text-sm font-bold text-slate-800 outline-none transition-all placeholder:text-slate-300">
                </div>
                @error('email')
                    <p class="text-[11px] font-black text-red-500 mt-1.5 ml-1 uppercase tracking-wider">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2 group">
                    <label for="password" class="block text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1 group-focus-within:text-indigo-600 transition-colors">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               placeholder="Minimal 6 karakter"
                               class="w-full pl-11 pr-12 py-3.5 rounded-2xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50' }} focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 text-sm font-bold text-slate-800 outline-none transition-all placeholder:text-slate-300">
                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-indigo-600 transition-colors">
                            <i data-lucide="eye" class="w-5 h-5 py-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-[11px] font-black text-red-500 mt-1.5 ml-1 uppercase tracking-wider">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-2 group">
                    <label for="password_confirmation" class="block text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1 group-focus-within:text-indigo-600 transition-colors">Konfirmasi Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               placeholder="Ulangi password"
                               class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 text-sm font-bold text-slate-800 outline-none transition-all placeholder:text-slate-300">
                    </div>
                </div>
            </div>

            {{-- Role --}}
            <div class="space-y-2 group">
                <label for="role" class="block text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1 group-focus-within:text-indigo-600 transition-colors">Role / Peran Akses</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <select id="role" name="role" required
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border {{ $errors->has('role') ? 'border-red-400 bg-red-50' : 'border-slate-200 bg-slate-50' }} focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 text-sm font-bold text-slate-800 outline-none transition-all appearance-none cursor-pointer">
                        <option value="">-- Pilih Akses Pengguna --</option>
                        @foreach($availableRoles as $key => $label)
                            <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="chevron-down" class="w-5 h-5"></i>
                    </div>
                </div>
                @error('role')
                    <p class="text-[11px] font-black text-red-500 mt-1.5 ml-1 uppercase tracking-wider">{{ $message }}</p>
                @enderror
                @if(count($availableRoles) === 1)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-50 text-amber-700 border border-amber-100 mt-2">
                        <i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i>
                        <p class="text-[10px] font-bold uppercase tracking-wide">Anda hanya dapat mendaftarkan role Teknisi.</p>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-2 px-6 py-3 rounded-2xl border border-slate-200 text-sm font-black text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition-all">
                    Batal
                </a>
                <button type="submit" id="submitBtn"
                        class="flex items-center justify-center gap-2 px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl font-black text-sm shadow-xl shadow-indigo-500/30 hover:shadow-2xl hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all active:scale-[0.98] min-w-[200px]">
                    <span id="btnText" class="flex items-center gap-2">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        Daftarkan Pengguna
                    </span>
                    <div id="btnLoader" class="hidden">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        lucide.createIcons();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const userForm = document.getElementById('userForm');
        if (userForm) {
            userForm.addEventListener('submit', function() {
                const btn = document.getElementById('submitBtn');
                const text = document.getElementById('btnText');
                const loader = document.getElementById('btnLoader');

                btn.disabled = true;
                btn.classList.add('opacity-80', 'cursor-not-allowed');
                text.classList.add('hidden');
                loader.classList.remove('hidden');
            });
        }
    });
</script>
@endpush
