<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FinanceOps</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-gradient-to-br from-slate-50 to-slate-100 antialiased">

@if(Auth::user()->role === 'teknisi')
    {{-- ===================== LAYOUT TEKNISI: TOP NAVBAR ===================== --}}
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-9xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                {{-- 1. Brand / Logo --}}
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                        <i data-lucide="receipt" class="w-4 h-4 text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-slate-800 tracking-tight">FinanceOps</span>
                </div>

                {{-- 2. Menu & Profile Area --}}
                <div class="flex items-center gap-4">
                    
                    {{-- === MENU NAVIGASI === --}}
                    
                    {{-- A. Tampilan Desktop/Tablet (Teks + Icon) --}}
                    {{-- Class: hidden sm:flex (Sembunyi di HP, Muncul di Tablet ke atas) --}}
                    <div class="hidden sm:flex items-center gap-2 mr-2 border-r border-slate-200 pr-4">
                        <a href="{{ route('transactions.create') }}"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-colors
                            {{ request()->routeIs('transactions.create') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                            <i data-lucide="home" class="w-4 h-4"></i> Beranda
                        </a>
                        <a href="{{ route('transactions.index') }}"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-colors
                            {{ request()->routeIs('transactions.index') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                            <i data-lucide="clock" class="w-4 h-4"></i> Riwayat
                        </a>
                    </div>

                    {{-- B. Tampilan Mobile/Smartphone (Hanya Icon) --}}
                    {{-- Class: flex sm:hidden (Muncul di HP, Sembunyi di Tablet ke atas) --}}
                    <div class="flex sm:hidden items-center gap-2 mr-1">
                        {{-- Icon Beranda --}}
                        <a href="{{ route('transactions.create') }}"
                            class="flex flex-col items-center justify-center w-10 h-10 rounded-xl transition-colors relative
                            {{ request()->routeIs('transactions.create') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-500 hover:bg-slate-100' }}">
                            <i data-lucide="home" class="w-5 h-5"></i>
                            {{-- Indikator titik kecil jika aktif (opsional) --}}
                            @if(request()->routeIs('transactions.create'))
                                <span class="absolute bottom-1.5 w-1 h-1 bg-indigo-600 rounded-full"></span>
                            @endif
                        </a>

                        {{-- Icon Riwayat --}}
                        <a href="{{ route('transactions.index') }}"
                            class="flex flex-col items-center justify-center w-10 h-10 rounded-xl transition-colors relative
                            {{ request()->routeIs('transactions.index') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-500 hover:bg-slate-100' }}">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                             {{-- Indikator titik kecil jika aktif (opsional) --}}
                             @if(request()->routeIs('transactions.index'))
                                <span class="absolute bottom-1.5 w-1 h-1 bg-indigo-600 rounded-full"></span>
                            @endif
                        </a>
                    </div>

                    {{-- 3. Profile Dropdown --}}
                    <div class="relative">
                        <button id="profileBtn" class="flex items-center gap-2 p-1 pr-2 rounded-full hover:bg-slate-100 transition-all outline-none border border-transparent hover:border-slate-200">
                            {{-- Nama User: Sembunyi di HP, Muncul di Tablet --}}
                            <div class="hidden sm:block text-right">
                                <p class="text-sm font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</p>
                                <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Teknisi</p>
                            </div>
                            
                            {{-- Avatar --}}
                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold shadow-inner text-sm border-2 border-white">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        </button>

                        <div id="profileDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-3xl shadow-2xl border border-slate-100 py-4 px-4 origin-top-right z-50">
                            <div class="flex flex-col items-center pb-4 border-b border-slate-100 mb-4">
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-black mb-3 shadow-lg">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <h4 class="font-bold text-slate-800">{{ Auth::user()->name }}</h4>
                                <p class="text-xs text-slate-400">{{ Auth::user()->email }}</p>
                            </div>
                            <div class="space-y-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold text-red-500 hover:bg-red-50 rounded-2xl transition-colors">
                                        <i data-lucide="log-out" class="w-4 h-4"></i> Logout Akun
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- Konten Halaman --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

@else
    {{-- ===================== LAYOUT ADMIN/ATASAN/OWNER: SIDEBAR ===================== --}}
    <div class="flex h-screen overflow-hidden">
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" onclick="toggleMobileSidebar()"></div>

        <aside id="mobile-sidebar"
            class="fixed md:static inset-y-0 left-0 w-72 bg-white border-r border-slate-200 
            text-slate-700 flex flex-col shrink-0 z-50 
            transform -translate-x-full md:translate-x-0 transition-transform duration-300">

            <div class="p-6 flex items-center gap-3 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                    <i data-lucide="receipt" class="w-5 h-5 text-white"></i>
                </div>
                <h1 class="font-bold text-lg text-slate-800">FinanceOps</h1>
            </div>

            <nav class="flex-1 p-6 space-y-3">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Menu Utama</p>

                <a href="{{ route('transactions.create') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('transactions.create') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="home" class="w-4 h-4"></i> Beranda
                </a>

                <a href="{{ route('transactions.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('transactions.index') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="clock" class="w-4 h-4"></i> Riwayat
                </a>

                {{-- Tambah menu khusus Admin/Atasan/Owner di sini --}}
                @if(in_array(Auth::user()->role, ['admin', 'atasan', 'owner']))
                <a href="{{ route('users.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('users.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="users" class="w-4 h-4"></i> Kelola Pengguna
                </a>
                @endif
            </nav>

            <div class="p-4 md:p-6">
                <div class="bg-slate-800/40 backdrop-blur-sm rounded-[2rem] p-5 border border-white/5">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-lg font-black text-white shadow-lg flex-shrink-0">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="overflow-hidden flex-1 min-w-0">
                            <p class="text-sm font-black truncate text-white">{{ Auth::user()->name }}</p>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">
                                    {{ ucfirst(Auth::user()->role) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center justify-center gap-3 py-3.5 rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all text-xs font-black uppercase tracking-widest cursor-pointer">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Logout Akun
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 bg-gray-50/50 overflow-hidden">
            <header class="bg-white/80 backdrop-blur-xl sticky top-0 z-20 px-4 md:px-8 py-4 md:py-5 flex justify-between items-center border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-3 md:gap-6 min-w-0 flex-1">
                    <button onclick="toggleMobileSidebar()" class="md:hidden p-2 hover:bg-slate-100 rounded-lg transition-colors">
                        <i data-lucide="menu" class="w-5 h-5 text-slate-600"></i>
                    </button>
                    <h2 class="text-xl lg:text-2xl font-black text-slate-900 tracking-tight truncate">
                        @yield('page-title', 'Dashboard')
                    </h2>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-6">
                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
@endif

{{-- Flash Notification (shared) --}}
@if(session('notification'))
    <div id="flash-notification"
        class="fixed top-20 right-4 md:right-8 left-4 md:left-auto md:max-w-md bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 z-50 font-black border border-slate-700 text-sm">
        <div class="w-2 h-2 rounded-full bg-blue-500 animate-ping flex-shrink-0"></div>
        <span>{{ session('notification') }}</span>
    </div>
@endif

<script>
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!sidebar) return;
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
        document.body.style.overflow = sidebar.classList.contains('-translate-x-full') ? '' : 'hidden';
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();

        // Profile dropdown (khusus teknisi)
        const profileBtn = document.getElementById('profileBtn');
        const dropdown = document.getElementById('profileDropdown');
        if (profileBtn && dropdown) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', () => dropdown.classList.add('hidden'));
        }

        // Auto-dismiss notification
        const notif = document.getElementById('flash-notification');
        if (notif) {
            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transition = 'opacity 0.5s';
                setTimeout(() => notif.remove(), 500);
            }, 3000);
        }
    });
</script>
@stack('scripts')
</body>
</html>
