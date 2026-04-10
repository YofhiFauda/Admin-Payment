<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanceOps - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-slate-900 bg-white min-h-screen flex">

    {{-- KIRI: Panel Informasi (Dark Navy) --}}
    <div class="hidden lg:flex w-3/4 
    bg-[radial-gradient(1200px_600px_at_10%_10%,rgba(99,102,241,0.25),transparent),
    radial-gradient(800px_500px_at_80%_20%,rgba(20,184,166,0.18),transparent),
    radial-gradient(600px_400px_at_50%_100%,rgba(139,92,246,0.15),transparent)]
    bg-[#0B1225]
    text-white flex-col justify-between p-12 lg:p-20 relative overflow-hidden">
        {{-- SOFT LIGHT ACCENT BLOBS --}}
        <div class="absolute -top-32 -left-32 w-[500px] h-[500px] bg-indigo-600/30 blur-[160px] rounded-full pointer-events-none"></div>
        <div class="absolute bottom-[-150px] right-[-100px] w-[500px] h-[500px] bg-teal-500/20 blur-[160px] rounded-full pointer-events-none"></div>

        {{-- OPTIONAL GLASS OVERLAY --}}
        <div class="absolute inset-0 backdrop-blur-[2px] bg-gradient-to-br from-white/5 to-transparent pointer-events-none"></div>
        
        {{-- Header Logo --}}
        <div class="relative z-10 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center">
                <i data-lucide="wallet" class="w-5 h-5 text-white"></i>
            </div>
            <span class="text-2xl font-bold tracking-tight">FinanceOps</span>
        </div>

        {{-- Main Content Kiri --}}
        <div class="relative z-10 mb-20">
            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#1A2342] text-indigo-300 text-[10px] font-bold tracking-widest uppercase mb-6 border border-indigo-500/20">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> DIDUKUNG AI
            </div>
            <h1 class="text-[3.5rem] leading-[1.1] font-black mb-6 tracking-tight">
                Cara Cerdas <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-teal-400">Kelola Operasional</span>
            </h1>
            <p class="text-slate-400 text-lg leading-relaxed max-w-md font-medium">
                Otomatisasi klaim reimbursement dan pengajuan pembelian menggunakan kekuatan AI. Lebih cepat, akurat, dan transparan untuk seluruh tim Anda.
            </p>
        </div>

        {{-- Footer Kiri --}}
        <div class="relative z-10 flex items-center gap-4 text-sm font-medium text-slate-500">
            <span>&copy; 2026 FinanceOps Inc.</span>
            <span class="w-1 h-1 rounded-full bg-slate-600"></span>
            <a href="#" class="hover:text-slate-300 transition-colors">Bantuan</a>
        </div>
    </div>

    {{-- KANAN: Panel Interaksi (Putih) --}}
    <div class="w-full lg:w-1/3 flex items-center justify-center p-8 min-h-screen relative overflow-hidden bg-[radial-gradient(600px_400px_at_90%_0%,rgba(99,102,241,0.08),transparent),radial-gradient(500px_300px_at_0%_100%,rgba(139,92,246,0.06),transparent)] bg-white">
        <div class="w-full max-w-sm relative z-10">

            {{-- STEP 1: Pilihan Peran / Role Selection --}}
            @if(!request('role'))
                <div id="role-selection" class="animate-fade-in-up">
                    <h2 class="text-[28px] font-black text-slate-900 mb-2 tracking-tight">Pilih Akses Anda</h2>
                    <p class="text-slate-500 font-medium mb-10">Siapa Anda? Pilih peran untuk masuk ke sistem.</p>

                    <div class="space-y-4">
                    @foreach($roles as $role)
                        <a href="{{ route('login', ['role' => $role['id']]) }}"
                        class="group flex items-center gap-5 p-5 rounded-3xl border border-slate-200 bg-white 
                                hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-500/10 
                                transition-all duration-300 cursor-pointer">

                            {{-- Icon --}}
                            <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center 
                                        text-slate-400 group-hover:bg-indigo-50 
                                        group-hover:text-indigo-600 transition-all duration-300">
                                <i data-lucide="{{ 
                                    $role['id'] == 'teknisi' ? 'wrench' : 
                                    ($role['id'] == 'admin' ? 'shield-check' : 
                                    ($role['id'] == 'atasan' ? 'user-cog' : 'briefcase')) 
                                }}" class="w-6 h-6"></i>
                            </div>

                            {{-- Text --}}
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900 text-base group-hover:text-indigo-600 transition-colors">
                                        {{ $role['label'] }}
                                    </span>

                                    <i data-lucide="arrow-right"
                                    class="w-5 h-5 text-slate-300 group-hover:text-indigo-500 
                                            group-hover:translate-x-1 transition-all duration-300"></i>
                                </div>

                                <p class="text-sm text-slate-500 mt-1">
                                    Login sebagai {{ strtolower($role['label']) }}
                                </p>
                            </div>

                        </a>
                    @endforeach
                </div>
                </div>
            @endif

            {{-- STEP 2: Form Login --}}
            @if(request('role'))
                @php
                    $selectedRole = collect($roles)->firstWhere('id', request('role'));
                @endphp
                
                <div id="login-form" class="animate-fade-in-up">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors mb-10">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Ganti Peran
                    </a>

                    <h2 class="text-[32px] font-black text-slate-900 mb-2 tracking-tight">Login {{ $selectedRole['label'] ?? '' }}</h2>
                    <p class="text-slate-500 font-medium mb-10">Masuk ke akun Anda untuk melanjutkan.</p>

                    @if($errors->any())
                        <div class="mb-6 bg-red-50 text-red-600 border border-red-200 rounded-2xl p-4 flex items-center gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                            <p class="text-sm font-bold">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-6" id="loginForm">
                        @csrf
                        <input type="hidden" name="role" value="{{ request('role') }}">

                        {{-- Input Email --}}
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-900">Email Perusahaan</label>
                            <input type="email" name="email" required autofocus
                                placeholder="nama@financeops.com"
                                class="w-full px-4 py-3.5 rounded-2xl border-2 border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 bg-white text-slate-900 outline-none transition-all placeholder:text-slate-400 font-medium" />
                        </div>

                        {{-- Input Password --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-bold text-slate-900">Password</label>
                            </div>
                            <input type="password" name="password" required placeholder="••••••••••"
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 text-slate-900 outline-none transition-all placeholder:text-slate-600 font-medium" />
                        </div>

                        {{-- Tombol Masuk --}}
                        <button type="submit" id="submitBtn"
                            class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-4 rounded-2xl transition-all shadow-lg shadow-violet-600/20 active:scale-[0.98] text-base cursor-pointer mt-2 flex items-center justify-center gap-3">
                            <span id="btnText">Masuk ke Dashboard</span>
                            <div id="btnLoader" class="hidden">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </button>
                    </form>

                    {{-- Footer Form --}}
                    <div class="text-center mt-10">
                        <p class="text-sm font-medium text-slate-500">
                            Belum punya akun? <a href="#" class="text-indigo-600 font-bold hover:text-indigo-700 transition-colors">Hubungi Admin</a>
                        </p>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => { 
            lucide.createIcons(); 

            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    const btn = document.getElementById('submitBtn');
                    const text = document.getElementById('btnText');
                    const loader = document.getElementById('btnLoader');

                    btn.disabled = true;
                    btn.classList.add('opacity-80', 'cursor-not-allowed');
                    text.textContent = 'Menghubungkan...';
                    loader.classList.remove('hidden');
                });
            }
        });
    </script>
</body>

</html>