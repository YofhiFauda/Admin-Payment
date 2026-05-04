@extends('layouts.app')

@section('page-title', 'Tambah Pengguna')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
    {{-- Back Link --}}
    <div class="mb-6 sm:mb-8">
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-2 text-xs sm:text-sm font-black text-slate-400 hover:text-sky-600 transition-all group">
            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center group-hover:bg-sky-50 group-hover:text-sky-600 transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </div>
            KEMBALI KE DAFTAR
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">
        <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50/30">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-[1.25rem] bg-linear-to-br from-sky-600 to-sky-500 flex items-center justify-center shadow-lg shadow-sky-500/20 shrink-0">
                    <i data-lucide="user-plus" class="w-7 h-7 sm:w-8 sm:h-8 text-white"></i>
                </div>
                <div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">Daftarkan Pengguna Baru</h3>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-bold uppercase tracking-[0.2em] mt-1.5">Lengkapi data akun untuk akses sistem</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="p-6 sm:p-10 space-y-8" id="userForm">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Nama --}}
                <div class="space-y-3 group">
                    <label for="name" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-1 group-focus-within:text-sky-600 transition-colors">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4.5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-sky-500 transition-colors">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </div>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               placeholder="Contoh: Budi Santoso"
                               style="text-transform: capitalize;"
                               class="w-full pl-12 pr-4 py-4 rounded-2xl border-2 {{ $errors->has('name') ? 'border-red-100 bg-red-50/30 text-red-900 focus:border-red-400' : 'border-slate-100 bg-slate-50/50 text-slate-800 focus:border-sky-500 focus:bg-white' }} text-sm font-black outline-none transition-all placeholder:text-slate-300 focus:ring-4 focus:ring-sky-500/10">
                    </div>
                    @error('name')
                        <p class="text-[10px] font-black text-red-500 mt-2 ml-1 uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="space-y-3 group">
                    <label for="email" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-1 group-focus-within:text-sky-600 transition-colors">Email Perusahaan</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4.5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-sky-500 transition-colors">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                               placeholder="budi@whusnet.com"
                               class="w-full pl-12 pr-4 py-4 rounded-2xl border-2 {{ $errors->has('email') ? 'border-red-100 bg-red-50/30 text-red-900 focus:border-red-400' : 'border-slate-100 bg-slate-50/50 text-slate-800 focus:border-sky-500 focus:bg-white' }} text-sm font-black outline-none transition-all placeholder:text-slate-300 focus:ring-4 focus:ring-sky-500/10">
                    </div>
                    @error('email')
                        <p class="text-[10px] font-black text-red-500 mt-2 ml-1 uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Password --}}
                <div class="space-y-3 group">
                    <label for="password" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-1 group-focus-within:text-sky-600 transition-colors">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4.5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-sky-500 transition-colors">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               placeholder="Minimal 6 karakter"
                               class="w-full pl-12 pr-12 py-4 rounded-2xl border-2 {{ $errors->has('password') ? 'border-red-100 bg-red-50/30 text-red-900 focus:border-red-400' : 'border-slate-100 bg-slate-50/50 text-slate-800 focus:border-sky-500 focus:bg-white' }} text-sm font-black outline-none transition-all placeholder:text-slate-300 focus:ring-4 focus:ring-sky-500/10">
                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-300 hover:text-sky-600 transition-colors">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-[10px] font-black text-red-500 mt-2 ml-1 uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="space-y-3 group">
                    <label for="password_confirmation" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-1 group-focus-within:text-sky-600 transition-colors">Konfirmasi Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4.5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-sky-500 transition-colors">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               placeholder="Ulangi password"
                               class="w-full pl-12 pr-4 py-4 rounded-2xl border-2 border-slate-100 bg-slate-50/50 text-slate-800 focus:border-sky-500 focus:bg-white text-sm font-black outline-none transition-all placeholder:text-slate-300 focus:ring-4 focus:ring-sky-500/10">
                    </div>
                </div>
            </div>

            {{-- Role --}}
            <div class="space-y-3 group">
                <label for="role" class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-1 group-focus-within:text-sky-600 transition-colors">Role / Peran Akses</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4.5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-sky-500 transition-colors">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                    </div>
                    <select id="role" name="role" required
                            class="w-full pl-12 pr-12 py-4 rounded-2xl border-2 {{ $errors->has('role') ? 'border-red-100 bg-red-50/30 text-red-900 focus:border-red-400' : 'border-slate-100 bg-slate-50/50 text-slate-800 focus:border-sky-500 focus:bg-white' }} text-sm font-black outline-none transition-all appearance-none cursor-pointer focus:ring-4 focus:ring-sky-500/10">
                        <option value="">-- Pilih Akses Pengguna --</option>
                        @foreach($availableRoles as $key => $label)
                            <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-300">
                        <i data-lucide="chevron-down" class="w-5 h-5"></i>
                    </div>
                </div>
                @error('role')
                    <p class="text-[10px] font-black text-red-500 mt-2 ml-1 uppercase tracking-wider flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}
                    </p>
                @enderror
                @if(count($availableRoles) === 1)
                    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-amber-50/50 text-amber-700 border border-amber-100 mt-3">
                        <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                        <p class="text-[10px] font-black uppercase tracking-[0.05em]">Anda hanya dapat mendaftarkan role <span class="underline decoration-2 underline-offset-2">{{ current($availableRoles) }}</span>.</p>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-8 border-t border-slate-100">
                <a href="{{ route('users.index') }}"
                   class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-4 rounded-2xl border-2 border-slate-100 text-sm font-black text-slate-400 hover:bg-slate-50 hover:text-slate-600 hover:border-slate-200 transition-all">
                    BATAL
                </a>
                <button type="submit" id="submitBtn"
                        class="w-full sm:w-auto flex items-center justify-center gap-3 px-10 py-4.5 bg-linear-to-r from-sky-600 to-sky-500 text-white rounded-2xl font-black text-sm shadow-xl shadow-sky-500/30 hover:shadow-2xl hover:shadow-sky-500/40 hover:-translate-y-1 transition-all active:scale-[0.98]">
                    <span id="btnText" class="flex items-center gap-2">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        DAFTARKAN PENGGUNA
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
