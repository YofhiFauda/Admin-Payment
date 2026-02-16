<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminPay - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-slate-950 flex items-center justify-center p-6 relative overflow-hidden">
        {{-- Background Blurs --}}
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/20 blur-[120px] rounded-full"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-600/20 blur-[120px] rounded-full">
        </div>

        <div class="w-full max-w-md z-10">
            {{-- Logo --}}
            <div class="text-center mb-10 animate-fade-in-up">
                <div class="inline-flex p-4 rounded-3xl bg-blue-600 shadow-xl shadow-blue-500/20 mb-6">
                    <i data-lucide="receipt" class="w-10 h-10 text-white"></i>
                </div>
                <h1 class="text-4xl font-black text-white tracking-tight mb-2">AdminPay</h1>
                <p class="text-slate-400 font-medium tracking-tight">Financial Transaction Management System</p>
            </div>

            {{-- STEP 1: Role Selection --}}
            <div id="role-selection"
                class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-[2.5rem] p-8 shadow-2xl {{ request('role') ? 'hidden' : '' }}">
                <div class="mb-8 flex items-center gap-3 text-white/80">
                    <i data-lucide="lock" class="w-4 h-4 text-blue-500"></i>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]">Pilih Akses Role</span>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    @foreach($roles as $role)
                        <a href="{{ route('login', ['role' => $role['id']]) }}"
                            class="group relative flex items-center justify-between w-full p-5 rounded-2xl bg-white/5 border border-white/5 hover:border-blue-500/50 hover:bg-blue-500/10 transition-all text-left overflow-hidden">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <i data-lucide="user-circle" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-bold">{{ $role['label'] }}</h3>
                                    <p
                                        class="text-slate-500 text-[10px] font-black uppercase tracking-widest group-hover:text-blue-400 transition-colors">
                                        Akses {{ $role['label'] }}</p>
                                </div>
                            </div>
                            <i data-lucide="arrow-right"
                                class="w-5 h-5 text-slate-600 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- STEP 2: Login Form --}}
            @if(request('role'))
                @php
                    $selectedRole = collect($roles)->firstWhere('id', request('role'));
                @endphp
                <div id="login-form"
                    class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-[2.5rem] p-8 shadow-2xl animate-fade-in-up">
                    <div class="mb-8">
                        <a href="{{ route('login') }}"
                            class="flex items-center gap-2 text-slate-400 hover:text-blue-400 font-black text-[10px] uppercase tracking-[0.2em] transition-all mb-6">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali Pilih Role
                        </a>
                        <div class="flex items-center gap-4 mb-2">
                            <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                                <i data-lucide="shield-check" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-bold text-lg">Login {{ $selectedRole['label'] ?? '' }}</h3>
                                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest">Masukkan
                                    kredensial Anda</p>
                            </div>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="mb-6 bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-center gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-400 shrink-0"></i>
                            <p class="text-red-400 text-sm font-bold">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="role" value="{{ request('role') }}">

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-3 tracking-widest">Email
                                Address</label>
                            <div class="relative">
                                <i data-lucide="mail" class="w-5 h-5 absolute left-5 top-4.5 text-slate-600"></i>
                                <input type="email" name="email" required autofocus
                                    value="{{ old('email', $selectedRole['email'] ?? '') }}" placeholder="nama@email.com"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-14 pr-5 text-white font-bold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all placeholder:text-slate-600" />
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase mb-3 tracking-widest">Password</label>
                            <div class="relative">
                                <i data-lucide="key-round" class="w-5 h-5 absolute left-5 top-4.5 text-slate-600"></i>
                                <input type="password" name="password" required placeholder="Masukkan password"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl py-4 pl-14 pr-5 text-white font-bold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all placeholder:text-slate-600" />
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl transition-all shadow-2xl shadow-blue-600/30 active:scale-95 text-sm uppercase tracking-[0.2em] cursor-pointer flex items-center justify-center gap-3">
                            <i data-lucide="log-in" class="w-5 h-5"></i>
                            Masuk ke Sistem
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });
    </script>
</body>

</html>