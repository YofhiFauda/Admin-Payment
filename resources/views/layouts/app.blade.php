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
                            <i data-lucide="file-up" class="w-4 h-4"></i> Input Rembush
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
                            <div class="profile-card cursor-pointer hover:bg-slate-50 transition-colors rounded-xl p-2 -mx-2" 
                                 onclick="openBankAccountsModal({{ Auth::id() }})">
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
                                    <h4 class="profile-name group-hover:text-indigo-600 transition-colors">{{ Auth::user()->name }}</h4>
                                    <p class="profile-email">{{ Auth::user()->email }}</p>
                                </div>
                            </div>
                            
                            {{-- Manage Bank Accounts Button (Modern Style) --}}
                            <div class="mt-3 pt-3 border-t border-slate-100">
                                <button type="button" onclick="openBankAccountsModal({{ Auth::id() }})" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 group">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p class="leading-none mb-1">Rekening Saya</p>
                                        <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Kelola Bank & E-Wallet</p>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-indigo-400 transition-colors"></i>
                                </button>
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
    {{-- <main class="max-w-7xl mx-auto px-1 sm:px-6 lg:px-8 py-1 page-enter"> --}}
    <main class="w-full mx-auto px-1 sm:px-2 py-1 page-enter">
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

                {{-- ▼ Input Pengeluaran Lain (dropdown) - admin, atasan, owner --}}
                @if(in_array(Auth::user()->role, ['admin', 'atasan', 'owner']))
                @php
                    $isPengeluaranLain = request()->is('pengeluaran-lain/*');
                @endphp
                <div>
                    <button id="pengeluaran-lain-toggle" onclick="togglePengeluaranLain()"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all
                        {{ $isPengeluaranLain ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-slate-100 text-slate-600' }}">
                        <i data-lucide="wallet" class="w-4 h-4 flex-shrink-0"></i>
                        <span class="flex-1 text-left">Input Pengeluaran Lain</span>
                        <i data-lucide="chevron-down" id="pengeluaran-lain-chevron" class="w-4 h-4 transition-transform duration-200 {{ $isPengeluaranLain ? 'rotate-180' : '' }}"></i>
                    </button>
                    <div id="pengeluaran-lain-menu" class="mt-1 ml-4 pl-4 border-l-2 border-slate-200 space-y-1 {{ $isPengeluaranLain ? '' : 'hidden' }}">
                        <a href="{{ route('pengeluaran-lain.bayar-hutang.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                            {{ request()->is('pengeluaran-lain/bayar-hutang*') ? 'bg-red-50 text-red-600' : 'hover:bg-slate-100 text-slate-600' }}">
                            <i data-lucide="credit-card" class="w-4 h-4 flex-shrink-0"></i> Bayar Hutang
                        </a>
                        <a href="{{ route('pengeluaran-lain.piutang-usaha.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                            {{ request()->is('pengeluaran-lain/piutang-usaha*') ? 'bg-blue-50 text-blue-600' : 'hover:bg-slate-100 text-slate-600' }}">
                            <i data-lucide="trending-up" class="w-4 h-4 flex-shrink-0"></i> Piutang Usaha
                        </a>
                        @if(in_array(Auth::user()->role, ['atasan', 'owner']))
                        <a href="{{ route('pengeluaran-lain.prive.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                            {{ request()->is('pengeluaran-lain/prive*') ? 'bg-purple-50 text-purple-600' : 'hover:bg-slate-100 text-slate-600' }}">
                            <i data-lucide="user-check" class="w-4 h-4 flex-shrink-0"></i> Prive
                        </a>
                        @endif
                        <a href="{{ route('pengeluaran-lain.gaji.index') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
                            {{ request()->is('pengeluaran-lain/gaji*') ? 'bg-green-50 text-green-600' : 'hover:bg-slate-100 text-slate-600' }}">
                            <i data-lucide="banknote" class="w-4 h-4 flex-shrink-0"></i> Gaji
                        </a>
                    </div>
                </div>
                @endif

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

            {{-- <div class="flex-1 overflow-y-auto lg:p-6"> --}}
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-8xl mx-auto">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
    </div>
@endif

{{-- ===================== BANK ACCOUNTS MODAL (Shared for Technicians) ===================== --}}
<div id="bankAccountsModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeBankAccountsModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl pointer-events-auto transform transition-all duration-300 scale-95 opacity-0 flex flex-col max-h-[90vh]" id="bankAccountsModalContent">
            {{-- Header --}}
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i data-lucide="credit-card" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 leading-tight">Daftar Rekening</h3>
                        <p class="text-sm font-medium text-slate-500">Kelola rekening bank atau e-wallet Anda</p>
                    </div>
                </div>
                <button onclick="closeBankAccountsModal()" class="w-10 h-10 rounded-xl hover:bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            {{-- List Section --}}
            <div class="flex-1 overflow-y-auto px-8 py-6" id="bankAccountsListContainer">
                <div id="bankAccountsLoading" class="flex flex-col items-center justify-center py-12">
                    <div class="w-10 h-10 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin"></div>
                    <p class="mt-4 text-sm font-bold text-slate-400">Memuat data...</p>
                </div>
                <div id="bankAccountsList" class="space-y-4 hidden">
                    {{-- Dynamically populated --}}
                </div>
                <div id="bankAccountsEmpty" class="hidden flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                        <i data-lucide="wallet" class="w-8 h-8"></i>
                    </div>
                    <p class="text-slate-500 font-bold">Belum ada rekening</p>
                    <p class="text-slate-400 text-xs mt-1">Tambahkan rekening untuk mempermudah transaksi</p>
                </div>
            </div>

            {{-- Form Section (Hidden by default) --}}
            <div id="bankAccountFormContainer" class="hidden px-8 py-6 border-t border-slate-100 bg-slate-50/50">
                <h4 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4" id="formTitle">Tambah Rekening Baru</h4>
                <form id="bankAccountForm" onsubmit="saveBankAccount(event)" class="space-y-4">
                    <input type="hidden" id="bank_account_id">
                    <input type="hidden" id="target_user_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Bank / E-Wallet</label>
                            <input type="text" id="bank_name" required placeholder="Contoh: BCA, MANDIRI, DANA" 
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300 uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Nomor Rekening</label>
                            <input type="text" id="account_number" required placeholder="Nomor rekening/HP"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Atas Nama</label>
                        <input type="text" id="account_name" required placeholder="Nama pemilik rekening"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300 uppercase">
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" onclick="hideBankAccountForm()" class="flex-1 px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-white transition-all">
                            Batal
                        </button>
                        <button type="submit" id="saveAccountBtn" class="flex-[2] px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan Rekening</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Footer (Action Buttons) --}}
            <div class="px-8 py-6 border-t border-slate-100 flex items-center justify-between shrink-0" id="modalFooter">
                <button type="button" onclick="showBankAccountForm()" class="px-6 py-3 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 shadow-xl transition-all flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Rekening</span>
                </button>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">FinanceOps Secure Storage</p>
            </div>
        </div>
    </div>
</div>

{{-- Delete Reason Modal (Admin only) --}}
<div id="deleteAccountReasonModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDeleteReasonModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl pointer-events-auto p-8 text-center sm:text-left">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600 mx-auto sm:mx-0 mb-6">
                <i data-lucide="alert-triangle" class="w-8 h-8"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-2">Konfirmasi Hapus</h3>
            <p class="text-slate-500 font-medium mb-6">Penghapusan rekening oleh Admin/Owner memerlukan alasan penyingkatan.</p>
            
            <form onsubmit="confirmDeleteAccount(event)">
                <input type="hidden" id="delete_target_id">
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5 ml-1 text-left">Alasan Penghapusan</label>
                    <textarea id="delete_reason" required placeholder="Contoh: Rekening sudah tidak aktif / Salah input"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all font-bold text-slate-800 placeholder:text-slate-300 min-h-[100px]"></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="closeDeleteReasonModal()" class="flex-1 px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-6 py-3 rounded-xl bg-rose-600 text-white font-bold hover:bg-rose-700 shadow-lg shadow-rose-600/20 transition-all">
                        Hapus Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    function togglePengeluaranLain() {
        const menu    = document.getElementById('pengeluaran-lain-menu');
        const chevron = document.getElementById('pengeluaran-lain-chevron');
        if (!menu) return;
        menu.classList.toggle('hidden');
        chevron && chevron.classList.toggle('rotate-180');
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
    // BANK ACCOUNTS MANAGEMENT LOGIC
    // ─────────────────────────────────────────────────────────
    const bankAccountsModal = document.getElementById('bankAccountsModal');
    const bankAccountsContent = document.getElementById('bankAccountsModalContent');

    function openBankAccountsModal(userId) {
        document.getElementById('target_user_id').value = userId;
        bankAccountsModal.classList.remove('hidden');
        setTimeout(() => {
            bankAccountsContent.classList.remove('scale-95', 'opacity-0');
            bankAccountsContent.classList.add('scale-100', 'opacity-100');
        }, 10);
        fetchBankAccounts(userId);
    }

    function closeBankAccountsModal() {
        bankAccountsContent.classList.add('scale-95', 'opacity-0');
        bankAccountsContent.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            bankAccountsModal.classList.add('hidden');
            hideBankAccountForm();
        }, 300);
    }

    function fetchBankAccounts(userId) {
        const list = document.getElementById('bankAccountsList');
        const loading = document.getElementById('bankAccountsLoading');
        const empty = document.getElementById('bankAccountsEmpty');

        list.classList.add('hidden');
        loading.classList.remove('hidden');
        empty.classList.add('hidden');

        fetch(`/user-bank-accounts/${userId}`)
            .then(r => r.json())
            .then(accounts => {
                loading.classList.add('hidden');
                list.innerHTML = '';

                if (accounts.length === 0) {
                    empty.classList.remove('hidden');
                    return;
                }

                list.classList.remove('hidden');
                accounts.forEach(acc => {
                    const card = `
                        <div class="group bg-white border border-slate-200 rounded-2xl p-5 hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-500/5 transition-all flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 flex items-center justify-center transition-colors">
                                    <i data-lucide="landmark" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-black bg-slate-800 text-white px-1.5 py-0.5 rounded uppercase tracking-widest">${acc.bank_name}</span>
                                        <h4 class="text-sm font-black text-slate-800 tracking-tight">${acc.account_number}</h4>
                                    </div>
                                    <p class="text-xs font-bold text-slate-400 mt-1 uppercase">${acc.account_name}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 scale-90 sm:scale-100 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick='showBankAccountForm(${JSON.stringify(acc)})' class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-all">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <button onclick="deleteBankAccount(${acc.id})" class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-100 transition-all">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    list.insertAdjacentHTML('beforeend', card);
                });
                lucide.createIcons();
            });
    }

    function showBankAccountForm(data = null) {
        const container = document.getElementById('bankAccountFormContainer');
        const footer = document.getElementById('modalFooter');
        const list = document.getElementById('bankAccountsListContainer');
        const title = document.getElementById('formTitle');
        const submitBtnText = document.querySelector('#saveAccountBtn span');

        document.getElementById('bank_account_id').value = data ? data.id : '';
        document.getElementById('bank_name').value = data ? data.bank_name : '';
        document.getElementById('account_number').value = data ? data.account_number : '';
        document.getElementById('account_name').value = data ? data.account_name : '';

        title.textContent = data ? 'Edit Rekening' : 'Tambah Rekening Baru';
        submitBtnText.textContent = data ? 'Update Rekening' : 'Simpan Rekening';

        list.classList.add('hidden');
        footer.classList.add('hidden');
        container.classList.remove('hidden');
    }

    function hideBankAccountForm() {
        document.getElementById('bankAccountFormContainer').classList.add('hidden');
        document.getElementById('modalFooter').classList.remove('hidden');
        document.getElementById('bankAccountsListContainer').classList.remove('hidden');
        document.getElementById('bankAccountForm').reset();
    }

    function saveBankAccount(e) {
        e.preventDefault();
        const id = document.getElementById('bank_account_id').value;
        const userId = document.getElementById('target_user_id').value;
        const url = id ? `/user-bank-accounts/${id}` : '/user-bank-accounts';
        const method = id ? 'PUT' : 'POST';

        const btn = document.getElementById('saveAccountBtn');
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>`;

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                user_id: userId,
                bank_name: document.getElementById('bank_name').value,
                account_number: document.getElementById('account_number').value,
                account_name: document.getElementById('account_name').value
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                hideBankAccountForm();
                fetchBankAccounts(userId);
            } else {
                alert('Gagal menyimpan rekening: ' + (res.message || 'Error tidak diketahui'));
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            lucide.createIcons();
        });
    }

    function deleteBankAccount(id) {
        const isAdmin = {{ Auth::user()->canManageUsers() ? 'true' : 'false' }};
        const currentUserId = {{ Auth::id() }};
        const targetUserId = document.getElementById('target_user_id').value;

        // If admin and deleting someone else's account, ask for reason
        if (isAdmin && currentUserId != targetUserId) {
            document.getElementById('delete_target_id').value = id;
            document.getElementById('deleteAccountReasonModal').classList.remove('hidden');
        } else {
            if (confirm('Apakah Anda yakin ingin menghapus rekening ini?')) {
                executeDelete(id);
            }
        }
    }

    function closeDeleteReasonModal() {
        document.getElementById('deleteAccountReasonModal').classList.add('hidden');
        document.getElementById('delete_reason').value = '';
    }

    function confirmDeleteAccount(e) {
        e.preventDefault();
        const id = document.getElementById('delete_target_id').value;
        const reason = document.getElementById('delete_reason').value;
        executeDelete(id, reason);
    }

    function executeDelete(id, reason = null) {
        const userId = document.getElementById('target_user_id').value;
        const body = reason ? JSON.stringify({ reason }) : null;

        fetch(`/user-bank-accounts/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: body
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeDeleteReasonModal();
                fetchBankAccounts(userId);
            } else {
                alert('Gagal menghapus rekening');
            }
        });
    }

    // ─────────────────────────────────────────────────────────
    // REAL-TIME BROADCASTING LISTENERS (LARAVEL ECHO)
    // ─────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.Echo !== 'undefined') {
            const userId = {{ Auth::id() }};
            const userRole = "{{ Auth::user()->role }}";
            
            // ── Toast Queue System ──
            const toastQueue = [];
            let isProcessingQueue = false;
            const MAX_TOASTS = 3;

            const processToastQueue = () => {
                if (isProcessingQueue || toastQueue.length === 0) return;

                const currentToasts = document.querySelectorAll('.realtime-toast-item').length;
                if (currentToasts >= MAX_TOASTS) {
                    setTimeout(processToastQueue, 1000);
                    return;
                }

                isProcessingQueue = true;
                const { title, message, colorClasses, iconName } = toastQueue.shift();
                renderToast(title, message, colorClasses, iconName);

                setTimeout(() => {
                    isProcessingQueue = false;
                    processToastQueue();
                }, 3000); // 3 second interval
            };

            const showRealtimeToast = (title, message, colorClasses, iconName) => {
                toastQueue.push({ title, message, colorClasses, iconName });
                processToastQueue();
            };

            const renderToast = (title, message, colorClasses, iconName) => {
                const toastId = 'toast-' + Date.now();

                let container = document.getElementById('toast-container-stack');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'toast-container-stack';
                    container.className = 'fixed top-24 right-4 md:right-8 z-[60] flex flex-col gap-3 pointer-events-none items-end w-full max-w-sm';
                    document.body.appendChild(container);
                }

                const html = `
                    <div id="${toastId}" class="realtime-toast-item pointer-events-auto flex items-start w-full p-4 space-x-4 bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-slate-100 opacity-0 transform translate-x-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]">
                        <div class="inline-flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-xl ${colorClasses}">
                            <i data-lucide="${iconName || 'bell'}" class="w-6 h-6"></i>
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
                }, 6000);
            };

            // ── Listener untuk NOTIFIKASI SYSTEM (Real-time Toasts & Database Sync) ──
            window.Echo.private(`notifications.${userId}`)
                .listen('.notification.received', (e) => {
                    console.log('🔔 Notification Received:', e);

                    // Update badge counter
                    updateNotificationBadge();

                    // Determine colors based on type
                    let colorClasses = 'bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/30';
                    let iconName = 'bell';

                    if (e.type === 'ocr_status') {
                        // Check title for 'berhasil' or check status
                        const isSuccess = e.title.toLowerCase().includes('berhasil');
                        colorClasses = isSuccess 
                            ? 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg shadow-emerald-500/30' 
                            : 'bg-gradient-to-br from-rose-400 to-rose-600 text-white shadow-lg shadow-rose-500/30';
                        iconName = isSuccess ? 'sparkles' : 'alert-triangle';
                    } else if (e.type === 'transaction_status') {
                        if (e.title.includes('CASH SIAP')) {
                            colorClasses = 'bg-gradient-to-br from-amber-400 to-orange-600 text-white shadow-lg shadow-orange-500/30';
                            iconName = 'banknote';
                        } else if (e.title.toLowerCase().includes('disetujui owner') || e.title.includes('DISETUJUI OWNER')) {
                            colorClasses = 'bg-gradient-to-br from-emerald-500 to-green-700 text-white shadow-lg shadow-emerald-500/30';
                            iconName = 'check-circle';
                        } else if (e.title.toLowerCase().includes('disetujui')) {
                            colorClasses = 'bg-gradient-to-br from-cyan-400 to-blue-600 text-white shadow-lg shadow-blue-500/30';
                            iconName = 'clipboard-check';
                        } else if (e.title.toLowerCase().includes('diproses') || e.title.includes('SEDANG DIPROSES')) {
                            colorClasses = 'bg-gradient-to-br from-blue-400 to-indigo-600 text-white shadow-lg shadow-blue-500/30';
                            iconName = 'clock';
                        } else if (e.title.toLowerCase().includes('selesai')) {
                            colorClasses = 'bg-gradient-to-br from-blue-400 to-indigo-600 text-white shadow-lg shadow-indigo-500/30';
                            iconName = 'check-circle';
                        } else if (e.title.toLowerCase().includes('ditolak')) {
                            colorClasses = 'bg-gradient-to-br from-rose-500 to-red-700 text-white shadow-lg shadow-red-500/30';
                            iconName = 'x-circle';
                        }
                    } else if (e.type === 'owner_approval') {
                        colorClasses = 'bg-gradient-to-br from-purple-500 to-indigo-700 text-white shadow-lg shadow-purple-500/30';
                        iconName = 'shield-check';
                    }

                    showRealtimeToast(e.title, e.message, colorClasses, iconName);
                });

            // ── Listeners untuk GRID UPDATES (Tanpa Toasts - Toasts handled above) ──
            window.Echo.private(`ocr.${userId}`)
                .listen('.ocr.updated', (e) => {
                    if (typeof window.handleRealtimeTransactionUpdate === 'function' && e.payload.transaction) {
                        window.handleRealtimeTransactionUpdate(e.payload.transaction);
                    }
                });

            window.Echo.private(`transactions.${userId}`)
                .listen('.transaction.updated', (e) => {
                    if (typeof window.handleRealtimeTransactionUpdate === 'function') {
                        window.handleRealtimeTransactionUpdate(e.transaction);
                    }
                });

            window.Echo.private(`transactions`)
                .listen('.transaction.created', (e) => {
                    if (typeof window.handleRealtimeTransactionCreation === 'function') {
                        window.handleRealtimeTransactionCreation(e.transaction);
                    }
                })
                .listen('.transaction.updated', (e) => {
                    if (typeof window.handleRealtimeTransactionUpdate === 'function') {
                        window.handleRealtimeTransactionUpdate(e.transaction);
                    }
                });        }
    });
</script>
@stack('scripts')
</body>
</html>