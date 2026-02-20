<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AdminPay - Finance ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans bg-gradient-to-br from-slate-50 to-slate-100 antialiased">
    <div class="flex h-screen overflow-hidden">
        {{-- Mobile Sidebar Overlay --}}
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity" onclick="toggleMobileSidebar()"></div>
        
        {{-- Sidebar --}}
        <aside id="mobile-sidebar" class="fixed md:static inset-y-0 left-0 w-72 bg-slate-900 text-white flex flex-col shrink-0 z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
            {{-- Sidebar Header --}}
            <div class="p-6 md:p-8 flex items-center justify-between border-b border-slate-800/50">
                <div class="flex items-center gap-3 md:gap-4">
                    <div class="bg-blue-600 p-2 md:p-2.5 rounded-xl md:rounded-2xl text-white shadow-lg shadow-blue-900/40">
                        <i data-lucide="receipt" class="w-6 h-6 md:w-7 md:h-7"></i>
                    </div>
                    <div>
                        <h1 class="font-black text-lg md:text-xl tracking-tight leading-tight">AdminPay</h1>
                        <p class="text-[9px] md:text-[10px] font-black text-blue-400 uppercase tracking-[0.2em]">Finance ERP</p>
                    </div>
                </div>
                {{-- Close button for mobile --}}
                <button onclick="toggleMobileSidebar()" class="md:hidden p-2 hover:bg-slate-800 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 p-4 md:p-6 space-y-2 md:space-y-3 mt-2 md:mt-4 overflow-y-auto">
                @if(Auth::user()->canInput())
                    <a href="{{ route('transactions.create') }}" onclick="closeMobileSidebar()"
                        class="w-full flex items-center gap-3 md:gap-4 px-4 md:px-5 py-3 md:py-4 rounded-2xl md:rounded-3xl transition-all duration-300 font-bold
                               {{ request()->routeIs('transactions.create') || request()->routeIs('transactions.form') || request()->routeIs('transactions.upload') ? 'bg-blue-600 text-white shadow-xl shadow-blue-900/50 scale-[1.02]' : 'hover:bg-slate-800/50 text-slate-400 hover:text-white' }}">
                        <i data-lucide="plus" class="w-4 h-4 md:w-5 md:h-5"></i>
                        <span class="text-xs md:text-sm">Input Nota Baru</span>
                    </a>
                @endif

                @if(Auth::user()->canViewHistory())
                    <a href="{{ route('transactions.index') }}" onclick="closeMobileSidebar()"
                        class="w-full flex items-center gap-3 md:gap-4 px-4 md:px-5 py-3 md:py-4 rounded-2xl md:rounded-3xl transition-all duration-300 font-bold
                               {{ request()->routeIs('transactions.index') ? 'bg-blue-600 text-white shadow-xl shadow-blue-900/50 scale-[1.02]' : 'hover:bg-slate-800/50 text-slate-400 hover:text-white' }}">
                        <i data-lucide="file-text" class="w-4 h-4 md:w-5 md:h-5"></i>
                        <span class="text-xs md:text-sm">History Transaksi</span>
                    </a>
                @endif

                @if(!Auth::user()->isTeknisi())
                <div class="pt-4 md:pt-6 border-t border-slate-800/50 mt-4 md:mt-6">
                    <p class="text-[9px] md:text-[10px] font-black text-slate-600 uppercase tracking-widest px-4 md:px-5 mb-3 md:mb-4">Analytics</p>
                    <div
                        class="w-full flex items-center gap-3 md:gap-4 px-4 md:px-5 py-3 md:py-4 rounded-2xl md:rounded-3xl text-slate-400 hover:text-white transition-all font-bold cursor-pointer">
                        <i data-lucide="trending-up" class="w-4 h-4 md:w-5 md:h-5"></i>
                        <span class="text-xs md:text-sm">Laporan Grafik</span>
                    </div>
                </div>
                @endif
            </nav>

            {{-- User Profile & Logout --}}
            <div class="p-4 md:p-6">
                <div class="bg-slate-800/40 backdrop-blur-sm rounded-xl md:rounded-[2rem] p-4 md:p-5 border border-white/5">
                    <div class="flex items-center gap-3 md:gap-4 mb-4 md:mb-5">
                        <div
                            class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-base md:text-lg font-black shadow-lg shadow-blue-900/20 flex-shrink-0">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="overflow-hidden flex-1 min-w-0">
                            <p class="text-xs md:text-sm font-black truncate text-white">{{ Auth::user()->name }}</p>
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 md:w-2 md:h-2 rounded-full bg-green-500 animate-pulse"></div>
                                <p class="text-[9px] md:text-[10px] text-slate-400 font-black uppercase tracking-widest">
                                    {{ ucfirst(Auth::user()->role) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center justify-center gap-2 md:gap-3 py-3 md:py-3.5 rounded-lg md:rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition-all text-[10px] md:text-xs font-black uppercase tracking-widest cursor-pointer">
                            <i data-lucide="log-out" class="w-3 h-3 md:w-3.5 md:h-3.5"></i> Logout Akun
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 flex flex-col min-w-0 bg-gray-50/50 overflow-hidden">
            {{-- Header --}}
            <header
                class="bg-white/80 backdrop-blur-xl sticky top-0 z-20 px-4 md:px-6 lg:px-8 py-4 md:py-5 flex justify-between items-center border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-3 md:gap-6 min-w-0 flex-1">
                    {{-- Mobile menu button --}}
                    <button onclick="toggleMobileSidebar()" class="md:hidden p-2 hover:bg-slate-100 rounded-lg transition-colors flex-shrink-0">
                        <i data-lucide="menu" class="w-5 h-5 text-slate-600"></i>
                    </button>
                    
                    <h2 class="text-lg md:text-xl lg:text-2xl font-black text-slate-900 tracking-tight truncate">
                        @yield('page-title', 'Dashboard')
                    </h2>
                    <div class="h-5 md:h-6 w-px bg-gray-200 hidden lg:block"></div>
                    <div class="hidden lg:flex items-center gap-2">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-blue-500"></i>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.1em] whitespace-nowrap">Sesi Aktif:
                            {{ now()->translatedFormat('d M Y') }}</span>
                    </div>
                </div>

            </header>

            {{-- Flash Notification --}}
            @if(session('notification'))
                <div id="flash-notification"
                    class="fixed top-20 md:top-24 right-4 md:right-8 left-4 md:left-auto md:max-w-md bg-slate-900 text-white px-4 md:px-8 py-3 md:py-4 rounded-xl md:rounded-2xl shadow-2xl flex items-center gap-2 md:gap-3 z-50 font-black border border-slate-700 animate-slide-in text-xs md:text-sm">
                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-ping flex-shrink-0"></div>
                    <span class="flex-1">{{ session('notification') }}</span>
                </div>
            @endif

            {{-- Page Content --}}
            <div class="flex-1 overflow-y-auto p-2 md:p-2 lg:p-4 custom-scrollbar">
                <div class="max-w-7.5xl mx-auto">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
            
            // Prevent body scroll when sidebar is open
            if (!sidebar.classList.contains('-translate-x-full')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            if (window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            // Auto-dismiss notification
            const notification = document.getElementById('flash-notification');
            if (notification) {
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 500);
                }, 3000);
            }

            // Close sidebar on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeMobileSidebar();
                }
            });
        });
    </script>
    @stack('scripts')
</body>

</html>