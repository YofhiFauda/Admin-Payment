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
    <meta name="notif-unread-url" content="{{ route('notifications.unreadCount') }}">
    <link href="https://unpkg.com/nprogress@0.2.0/nprogress.css" rel="stylesheet">
    <script src="https://unpkg.com/nprogress@0.2.0/nprogress.js"></script>
    <style>
        /* NProgress Override */
        #nprogress .bar { background: #3b82f6 !important; height: 3px !important; }
        #nprogress .peg { box-shadow: 0 0 10px #3b82f6, 0 0 5px #3b82f6 !important; }
        #nprogress .spinner-icon { border-top-color: #3b82f6 !important; border-left-color: #3b82f6 !important; }
        
        /* Page Transition Animations */
        .page-enter { opacity: 0; animation: pageFadeIn 0.3s ease-out forwards; }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    
    <style>
        /* Profile Card Styles */
        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .profile-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: #cbd5e1;
        }
        
        .profile-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
        }
        
        .profile-info {
            flex: 1;
            min-width: 0;
        }
        
        .profile-name {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .profile-email {
            font-size: 13px;
            color: #64748b;
            margin: 2px 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Logout Button Styles */
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 16px;
            margin-top: 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
            /* Blue Gradient Outline */
            border: 2px solid transparent;
            background: linear-gradient(#ffffff, #ffffff) padding-box,
                        linear-gradient(135deg, #3b82f6, #8b5cf6) border-box;
            color: #3b82f6;
        }
        
        .logout-btn:hover,
        .logout-btn.active {
            background: #eef2ff;
            color: #4f46e5;
            border-color: #eef2ff;
        }
        
        .logout-btn:active {
            transform: scale(0.98);
        }
        
        .logout-btn i {
            width: 18px;
            height: 18px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 640px) {
            .profile-card {
                padding: 12px 16px;
                gap: 12px;
            }
            
            .profile-avatar {
                width: 40px;
                height: 40px;
            }
            
            .profile-name {
                font-size: 14px;
            }
            
            .profile-email {
                font-size: 12px;
            }
            
            .logout-btn {
                padding: 10px 14px;
                font-size: 13px;
                margin-top: 10px;
            }
            
            .logout-btn i {
                width: 16px;
                height: 16px;
            }
        }
        
        /* Extra Small Mobile */
        @media (max-width: 380px) {
            .profile-card {
                padding: 10px 14px;
                gap: 10px;
            }
            
            .profile-avatar {
                width: 36px;
                height: 36px;
            }
            
            .profile-name {
                font-size: 13px;
            }
            
            .profile-email {
                font-size: 11px;
            }
            
            .logout-btn {
                padding: 9px 12px;
                font-size: 12px;
            }
        }

        /* Notification Badge Styles */
        .notif-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #ef4444, #f43f5e);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            animation: badgePulse 2s ease-in-out infinite;
            transition: all 0.3s ease;
        }
        .notif-badge.badge-hidden {
            display: none;
        }
        .notif-badge-mobile {
            position: absolute;
            top: 2px;
            right: 2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #ef4444, #f43f5e);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            animation: badgePulse 2s ease-in-out infinite;
            transition: all 0.3s ease;
        }
        .notif-badge-mobile.badge-hidden {
            display: none;
        }
        .notif-badge-sidebar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #ef4444, #f43f5e);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
            margin-left: auto;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            animation: badgePulse 2s ease-in-out infinite;
            transition: all 0.3s ease;
        }
        .notif-badge-sidebar.badge-hidden {
            display: none;
        }
        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
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
                    <div class="hidden sm:flex items-center gap-2 mr-2 border-r border-slate-200 pr-4">
                        <a href="{{ route('transactions.create') }}"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-colors
                            {{ request()->routeIs('transactions.create') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                            <i data-lucide="file-up" class="w-4 h-4"></i> Input Pengeluaran
                        </a>
                        <a href="{{ route('transactions.index') }}"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-colors
                            {{ request()->routeIs('transactions.index') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                            <i data-lucide="clock" class="w-4 h-4"></i> Daftar Transaksi
                        </a>
                        <a href="{{ route('notifications.index') }}"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-colors relative
                            {{ request()->routeIs('notifications.index') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50' }}">
                            <i data-lucide="bell" class="w-4 h-4"></i> Notifikasi
                            <span id="notif-count-desktop" class="notif-badge badge-hidden">0</span>
                        </a>
                    </div>

                    {{-- B. Tampilan Mobile/Smartphone (Hanya Icon) --}}
                    <div class="flex sm:hidden items-center gap-2 mr-1">
                        <a href="{{ route('transactions.create') }}"
                            class="flex flex-col items-center justify-center w-10 h-10 rounded-xl transition-colors relative
                            {{ request()->routeIs('transactions.create') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-500 hover:bg-slate-100' }}">
                            <i data-lucide="file-up" class="w-5 h-5"></i>
                            @if(request()->routeIs('transactions.create'))
                                <span class="absolute bottom-1.5 w-1 h-1 bg-indigo-600 rounded-full"></span>
                            @endif
                        </a>

                        <a href="{{ route('transactions.index') }}"
                            class="flex flex-col items-center justify-center w-10 h-10 rounded-xl transition-colors relative
                            {{ request()->routeIs('transactions.index') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-500 hover:bg-slate-100' }}">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                             @if(request()->routeIs('transactions.index'))
                                <span class="absolute bottom-1.5 w-1 h-1 bg-indigo-600 rounded-full"></span>
                            @endif
                        </a>

                        <a href="{{ route('notifications.index') }}" 
                            class="flex flex-col items-center justify-center w-10 h-10 rounded-xl transition-colors relative
                            {{ request()->routeIs('notifications.index') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-500 hover:bg-slate-100' }}">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span id="notif-count-mobile" class="notif-badge-mobile badge-hidden">0</span>
                        </a>
                    </div>

                    {{-- 3. Profile Dropdown dengan Card Style --}}
                    <div class="relative">
                        <button id="profileBtn" class="flex items-center gap-2 p-1 pr-2 rounded-full hover:bg-slate-100 transition-all outline-none border border-transparent hover:border-slate-200">
                            <div class="hidden sm:block text-right">
                                <p class="text-sm font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</p>
                                <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Teknisi</p>
                            </div>
                            
                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold shadow-inner text-sm border-2 border-white">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        </button>

                        {{-- DROPDOWN PROFILE CARD --}}
                        <div id="profileDropdown" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 py-4 px-4 origin-top-right z-50">
                            
                            {{-- Profile Card Component --}}
                            <div class="profile-card">
                                {{-- Avatar --}}
                                @if(Auth::user()->avatar)
                                    <img src="{{ asset('storage/' . Auth::user()->avatar) }}" 
                                         alt="{{ Auth::user()->name }}" 
                                         class="profile-avatar">
                                @else
                                    <div class="profile-avatar bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                
                                {{-- Info --}}
                                <div class="profile-info">
                                    <h4 class="profile-name">{{ Auth::user()->name }}</h4>
                                    <p class="profile-email">{{ Auth::user()->email }}</p>
                                </div>
                            </div>
                            
                            {{-- Logout Button dengan Gradient Outline --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="logout-btn">
                                    <i data-lucide="log-out"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>  
        </div>
    </nav>

    {{-- Konten Halaman --}}
    <main class="max-w-7xl mx-auto px-1 sm:px-6 lg:px-8 py-1 page-enter">
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

                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="home" class="w-4 h-4"></i> Dashboard
                </a>

                <a href="{{ route('transactions.create') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('transactions.create') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="file-up" class="w-4 h-4"></i> Input Pengeluaran
                </a>

                <a href="{{ route('transactions.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('transactions.index') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="clock" class="w-4 h-4"></i> Daftar Transaksi
                </a>

                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Administrasi</p>
                @if(in_array(Auth::user()->role, ['admin', 'atasan', 'owner']))
                <a href="{{ route('users.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('users.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="users" class="w-4 h-4"></i> Kelola Pengguna
                </a>

                <a href="{{ route('branches.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('branches.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="building-2" class="w-4 h-4"></i> Kelola Cabang
                </a>

                <a href="{{ route('activity-logs.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('activity-logs.index') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Log Aktivitas
                </a>

                <a href="{{ route('notifications.index') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                    {{ request()->routeIs('notifications.index') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' : 'hover:bg-slate-100 text-slate-600' }}">
                    <i data-lucide="bell" class="w-4 h-4"></i> Notifikasi
                    <span id="notif-count-sidebar" class="notif-badge-sidebar badge-hidden">0</span>
                </a>
                @endif
            </nav>

            <div class="p-4 md:p-6">
                {{-- Profile Card untuk Sidebar --}}
                <div class="profile-card mb-3">
                    @if(Auth::user()->avatar)
                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" 
                             alt="{{ Auth::user()->name }}" 
                             class="profile-avatar">
                    @else
                        <div class="profile-avatar bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif
                    
                    <div class="profile-info">
                        <h4 class="profile-name">{{ Auth::user()->name }}</h4>
                        <p class="profile-email">{{ ucfirst(Auth::user()->role) }}</p>
                    </div>
                </div>
                
                {{-- Logout Button untuk Sidebar --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i data-lucide="log-out"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        @php
            $hideHeaderOnPc = request()->routeIs('transactions.create') 
                || request()->routeIs('notifications.index') 
                || request()->routeIs('activity-logs.index') 
                || request()->routeIs('transactions.confirm');
        @endphp

        <main class="flex-1 flex flex-col min-w-0 bg-gray-50/50 overflow-hidden page-enter">
            @if($hideHeaderOnPc)
                {{-- Mobile-only: minimal bar with just hamburger --}}
                <div class="md:hidden bg-white/80 backdrop-blur-xl sticky top-0 z-20 px-4 py-3 border-b border-gray-100 shrink-0">
                    <button onclick="toggleMobileSidebar()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                        <i data-lucide="menu" class="w-5 h-5 text-slate-600"></i>
                    </button>
                </div>
            @else
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
            @endif

            <div class="flex-1 overflow-y-auto lg:p-6">
                <div class="max-w-8xl mx-auto">
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
    // ─────────────────────────────────────────────────────────
    // NOTIFICATION BADGE COUNTER
    // ─────────────────────────────────────────────────────────
    function updateNotificationBadge() {
        const url = document.querySelector('meta[name="notif-unread-url"]')?.content;
        if (!url) return;
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            const count = data.count || 0;
            const badges = [
                document.getElementById('notif-count-desktop'),
                document.getElementById('notif-count-mobile'),
                document.getElementById('notif-count-sidebar')
            ];
            badges.forEach(badge => {
                if (!badge) return;
                
                // Update text content
                badge.textContent = count > 99 ? '99+' : count;
                
                // Show/Hide badge based on count
                if (count > 0) {
                    badge.classList.remove('badge-hidden');
                } else {
                    badge.classList.add('badge-hidden');
                }
            });
        })
        .catch(() => {});
    }
    // Call on page load
    document.addEventListener('DOMContentLoaded', () => updateNotificationBadge());

    function toggleMobileSidebar() {
        const sidebar = document.getElementById('mobile-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!sidebar) return;
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
        document.body.style.overflow = sidebar.classList.contains('-translate-x-full') ? '' : 'hidden';
    }

    document.addEventListener('DOMContentLoaded', () => {
        if(typeof NProgress !== 'undefined'){
            NProgress.configure({ showSpinner: false, minimum: 0.1 });
            NProgress.done();
        }
        lucide.createIcons();

        // Profile dropdown
        const profileBtn = document.getElementById('profileBtn');
        const dropdown = document.getElementById('profileDropdown');
        if (profileBtn && dropdown) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', () => dropdown.classList.add('hidden'));
        }

        // Auto-dismiss initial flash notification
        const notif = document.getElementById('flash-notification');
        if (notif) {
            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transition = 'opacity 0.5s';
                setTimeout(() => notif.remove(), 500);
            }, 3000);
        }
    });

    window.addEventListener('beforeunload', () => {
        if(typeof NProgress !== 'undefined') NProgress.start();
    });

    // ─────────────────────────────────────────────────────────
    // REAL-TIME BROADCASTING LISTENERS (LARAVEL ECHO)
    // ─────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.Echo !== 'undefined') {
            const userId = {{ Auth::id() }};
            
            const showRealtimeToast = (title, message, colorClasses, iconName) => {
                const toastId = 'toast-' + Date.now();
                
                let container = document.getElementById('toast-container-stack');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'toast-container-stack';
                    container.className = 'fixed top-24 right-4 md:right-8 z-[60] flex flex-col gap-3 pointer-events-none items-end w-full max-w-sm';
                    document.body.appendChild(container);
                }
                
                const html = `
                    <div id="${toastId}" class="pointer-events-auto flex items-start w-full p-4 space-x-4 bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-slate-100 opacity-0 transform translate-x-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]">
                        <div class="inline-flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-xl ${colorClasses}">
                            <i data-lucide="${iconName}" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1 min-w-0 pt-0.5">
                            <h3 class="text-sm font-extrabold text-slate-800 mb-1 leading-tight">${title}</h3>
                            <p class="text-[13px] text-slate-500 font-medium leading-snug">${message}</p>
                        </div>
                        <button type="button" class="flex-shrink-0 ms-auto -mx-1.5 -my-1.5 bg-white text-slate-400 hover:text-slate-900 rounded-xl p-1.5 hover:bg-slate-50 inline-flex items-center justify-center h-8 w-8 transition-colors" onclick="const el = document.getElementById('${toastId}'); if(el){ el.style.opacity='0'; el.style.transform='translateX(100%)'; setTimeout(()=>el.remove(), 500); }">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                `;
                
                container.insertAdjacentHTML('beforeend', html);
                const el = document.getElementById(toastId);
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        el.classList.remove('opacity-0', 'translate-x-full');
                        el.classList.add('opacity-100', 'translate-x-0');
                    });
                });
                
                setTimeout(() => {
                    if(document.getElementById(toastId)) {
                        el.classList.remove('opacity-100', 'translate-x-0');
                        el.classList.add('opacity-0', 'translate-x-full');
                        setTimeout(() => { if(document.getElementById(toastId)) el.remove() }, 500);
                    }
                }, 5000);
            };

            window.Echo.private(`ocr.${userId}`)
                .listen('.ocr.updated', (e) => {
                    console.log('Real-time OCR Update:', e);
                    
                    const isCompleted = e.payload.ai_status === 'completed';
                    const colorClasses = isCompleted 
                        ? 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg shadow-emerald-500/30' 
                        : 'bg-gradient-to-br from-rose-400 to-rose-600 text-white shadow-lg shadow-rose-500/30';
                    const iconName = isCompleted ? 'sparkles' : 'alert-triangle';
                    const titleStr = isCompleted ? 'Pemrosesan OCR Selesai' : 'Pemrosesan OCR Gagal';
                        
                    showRealtimeToast(titleStr, e.payload.message || `Sistem AI telah selesai memindai nota Anda.`, colorClasses, iconName);
                    updateNotificationBadge();

                    // Refresh transaction grid so AI badge updates in real-time
                    if (typeof window.handleRealtimeTransactionUpdate === 'function') {
                        window.handleRealtimeTransactionUpdate(e.payload);
                    }
                    
                    if (window.location.href.includes(`/loading/${e.payload.upload_id}`)) {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                });

            window.Echo.private(`transactions.${userId}`)
                .listen('.transaction.updated', (e) => {
                    console.log('Personal Real-time Transaction Update (Toast):', e);
                    
                    const statusConfig = {
                        'approved': { color: 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg shadow-blue-500/30', icon: 'check-circle-2', title: 'Transaksi Disetujui' },
                        'rejected': { color: 'bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-lg shadow-rose-500/30', icon: 'x-circle', title: 'Transaksi Ditolak' },
                        'completed': { color: 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/30', icon: 'badge-check', title: 'Transaksi Selesai' },
                        'pending': { color: 'bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg shadow-amber-500/30', icon: 'clock', title: 'Menunggu Persetujuan' },
                    };
                    
                    const config = statusConfig[e.transaction.status] || { color: 'bg-gradient-to-br from-slate-500 to-slate-700 text-white shadow-lg', icon: 'bell', title: 'Pembaruan Transaksi' };
                    
                    showRealtimeToast(
                        config.title,
                        `Nota dengan ID <b>${e.transaction.invoice_number}</b> saat ini menjadi <span class="uppercase font-bold">${e.transaction.status}</span>.`,
                        config.color,
                        config.icon
                    );
                    updateNotificationBadge();
                });

            window.Echo.private(`transactions`)
                .listen('.transaction.created', (e) => {
                    console.log('Global Real-time Transaction Created (Grid):', e);
                    if (typeof window.handleRealtimeTransactionCreation === 'function') {
                        window.handleRealtimeTransactionCreation(e.transaction);
                    }
                })
                .listen('.transaction.updated', (e) => {
                    console.log('Global Real-time Transaction Update (Grid):', e);
                    if (typeof window.handleRealtimeTransactionUpdate === 'function') {
                        window.handleRealtimeTransactionUpdate(e.transaction);
                    }
                });
                
            window.Echo.private(`activities`)
                .listen('.activity.logged', (e) => {
                    if (typeof window.handleRealtimeActivityLog === 'function') {
                        window.handleRealtimeActivityLog(e.activityLog);
                    }
                });

            // ── Listener untuk update badge notifikasi secara real-time ──
            window.Echo.private(`notifications.${userId}`)
                .listen('.notification.received', (e) => {
                    // Update badge counter immediately
                    updateNotificationBadge();

                    // Show toast to the recipient
                    const colorClasses = 'bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/30';
                    showRealtimeToast(e.title, e.message, colorClasses, 'bell');
                });
        }
    });
</script>
@stack('scripts')
</body>
</html>