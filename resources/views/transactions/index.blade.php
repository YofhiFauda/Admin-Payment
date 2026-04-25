@extends('layouts.app')

@section('page-title', 'Data Riwayat Transaksi')

@section('content')
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        .tabs-scroll-container {
            position: relative;
            mask-image: linear-gradient(to right, black 85%, transparent 100%);
            -webkit-mask-image: linear-gradient(to right, black 85%, transparent 100%);
        }

        @media (max-width: 768px) {
            .filter-trigger {
                justify-content: space-between;
            }
            .filter-group {
                width: 100%;
            }
        }
    </style>
    {{-- Main Content Card --}}
    <div class="bg-white shadow-sm border border-gray-100">
        {{-- Header Toolbar --}}
        <div class="p-3 sm:p-4 md:p-5 border-b border-gray-100 bg-white/80 backdrop-blur-sm">
            <!-- DESKTOP: Responsive Layout (1024px to 1439px: 2 Rows, >= 1440px: 1 Row) -->
            <div class="hidden lg:flex flex-wrap min-[1510px]:flex-nowrap items-center gap-3 md:gap-4 lg:gap-3">
            {{-- <div class="hidden lg:flex flex-wrap min-[1440px]:flex-nowrap items-center gap-3 md:gap-4 lg:gap-3"> --}}
                
                <!-- Group 1: Search, Multi Cabang, Multi Kategori, Date Range Picker -->
               <div class="flex items-center gap-3 w-full min-[1510px]:w-auto overflow-x-auto scrollbar-hide pb-1 min-[1510px]:pb-0">
                    <!-- Search -->
                    <div class="relative flex-1 min-[1510px]:flex-none min-[1510px]:w-72 group">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="text" id="instant-search" value="{{ request('search') }}" placeholder="Cari invoice, nama..." autocomplete="off" class="search-input-sync w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-semibold focus:outline-none focus:ring-4 focus:ring-blue-500/5 focus:border-blue-400 transition-all placeholder:text-gray-400">
                        <button type="button" id="search-clear" class="absolute right-3 top-1/2 -translate-y-1/2 hidden p-1 rounded-lg hover:bg-gray-200 transition-colors">
                            <i data-lucide="x" class="w-3 h-3 text-gray-400"></i>
                        </button>
                    </div>

                    @if(auth()->user()->role !== 'teknisi')
                    <!-- Branch Filter -->
                    <div class="relative filter-group flex-shrink-0" id="group-branch">
                        <button type="button" class="js-filter-branch-btn filter-trigger flex items-center gap-2 px-3 py-2.5 bg-white border border-gray-200 rounded-2xl text-[11px] font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all active:scale-95 group" data-target="menu-filter-branch">
                            <i data-lucide="git-branch" class="w-3.5 h-3.5 text-gray-400 group-[.active]:text-blue-600 transition-colors"></i>
                            <span class="js-filter-branch-label filter-label">Semua Cabang</span>
                            <div class="flex items-center">
                                <i data-lucide="chevron-down" class="w-3 h-3 ml-1 text-gray-400 group-[.active]:rotate-180 transition-transform"></i>
                                <span class="js-filter-branch-reset filter-clear hidden ml-1.5 p-0.5 rounded-md hover:bg-blue-100 text-blue-600 transition-colors" title="Bersihkan">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </span>
                            </div>
                        </button>
                    </div>

                    <!-- Category Filter -->
                    <div class="relative filter-group flex-shrink-0" id="group-category">
                        <button type="button" class="js-filter-category-btn filter-trigger flex items-center gap-2 px-3 py-2.5 bg-white border border-gray-200 rounded-2xl text-[11px] font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all active:scale-95 group" data-target="menu-filter-category">
                            <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-gray-400 group-[.active]:text-blue-600 transition-colors"></i>
                            <span class="js-filter-category-label filter-label">Semua Kategori</span>
                            <div class="flex items-center">
                                <i data-lucide="chevron-down" class="w-3 h-3 ml-1 text-gray-400 group-[.active]:rotate-180 transition-transform"></i>
                                <span class="js-filter-category-reset filter-clear hidden ml-1.5 p-0.5 rounded-md hover:bg-blue-100 text-blue-600 transition-colors" title="Bersihkan">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </span>
                            </div>
                        </button>
                    </div>

                    <!-- Date Filter -->
                    <div class="relative filter-group flex-shrink-0" id="group-date">
                        <button type="button" class="js-filter-date-btn filter-trigger flex items-center gap-2 px-3 py-2.5 bg-white border border-gray-200 rounded-2xl text-[11px] font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all active:scale-95 group" data-target="menu-filter-date">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400 group-[.active]:text-blue-600 transition-colors"></i>
                            <span class="js-filter-date-label filter-label">Pilih Tanggal</span>
                            <div class="flex items-center">
                                <i data-lucide="chevron-down" class="w-3 h-3 ml-1 text-gray-400 group-[.active]:rotate-180 transition-transform"></i>
                                <span class="js-filter-date-reset filter-clear hidden ml-1.5 p-0.5 rounded-md hover:bg-blue-100 text-blue-600 transition-colors" title="Bersihkan">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </span>
                            </div>
                        </button>
                    </div>
                    @endif
                </div>

                <!-- Spacer separating Groups (Hidden on 2-rows) -->
                <div class="flex-1 min-w-[20px] hidden min-[1510px]:block"></div>

                <!-- Group 2: Type Filters -->
                <!-- Group 2: Type Filters - Grid on 2-row mode, flex on 1-row mode -->
                <div class="w-full min-[1510px]:w-auto grid grid-cols-4 gap-2 min-[1510px]:flex min-[1510px]:items-center min-[1510px]:gap-2 overflow-x-auto scrollbar-hide pb-1 min-[1510px]:pb-0">
                    @php $currentType = request('type', 'all'); @endphp
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => null])) }}" class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap text-center border {{ $currentType === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50' }}">
                        Semua
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'rembush'])) }}" class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap text-center border {{ $currentType === 'rembush' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-indigo-50' }}">
                        <i data-lucide="receipt" class="w-3 h-3 inline mr-1"></i>Rembush
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'pengajuan'])) }}" class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap text-center border {{ $currentType === 'pengajuan' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-teal-50' }}">
                        <i data-lucide="shopping-bag" class="w-3 h-3 inline mr-1"></i>Pengajuan
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'gudang'])) }}" class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap text-center border {{ $currentType === 'gudang' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-amber-50' }}">
                        <i data-lucide="package" class="w-3 h-3 inline mr-1"></i>Gudang
                    </a>
                </div>
            </div>

            <!-- TABLET: 3 Rows Layout (md to lg screens) -->
            <div class="hidden md:flex lg:hidden flex-col gap-3">
                <!-- Row 1: Search + Date -->
                <div class="flex gap-3">
                    <div class="relative flex-1 group">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="text" id="instant-search-tablet" value="{{ request('search') }}" placeholder="Cari invoice, nama..." autocomplete="off" class="search-input-sync w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-semibold focus:outline-none focus:ring-4 focus:ring-blue-500/5 focus:border-blue-400 transition-all placeholder:text-gray-400">
                        <button type="button" class="search-clear absolute right-3 top-1/2 -translate-y-1/2 hidden p-1 rounded-lg hover:bg-gray-200 transition-colors">
                            <i data-lucide="x" class="w-3 h-3 text-gray-400"></i>
                        </button>
                    </div>
                    @if(auth()->user()->role !== 'teknisi')
                    <button type="button" class="js-filter-date-btn filter-trigger flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-[11px] font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all whitespace-nowrap group" data-target="menu-filter-date">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400 group-[.active]:text-blue-600"></i>
                        <span class="js-filter-date-label filter-label">Pilih Tanggal</span>
                        <i data-lucide="x" class="js-filter-date-reset hidden w-3 h-3 ml-1 text-blue-600"></i>
                    </button>
                    @endif
                </div>

                @if(auth()->user()->role !== 'teknisi')
                <!-- Row 2: Branch and Category -->
                <div class="flex gap-3">
                    <button type="button" class="js-filter-branch-btn filter-trigger flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all bg-white text-slate-500 border-2 border-slate-200 hover:border-blue-300 group" data-target="menu-filter-branch">
                        <i data-lucide="git-branch" class="w-4 h-4 group-[.active]:text-blue-600"></i>
                        <span class="js-filter-branch-label filter-label">Pilih Cabang</span>
                        <i data-lucide="x" class="js-filter-branch-reset hidden w-3 h-3 ml-1 text-blue-600"></i>
                    </button>
                    <button type="button" class="js-filter-category-btn filter-trigger flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all bg-white text-slate-500 border-2 border-slate-200 hover:border-blue-300 group" data-target="menu-filter-category">
                        <i data-lucide="layout-grid" class="w-4 h-4 group-[.active]:text-blue-600"></i>
                        <span class="js-filter-category-label filter-label">Pilih Kategori</span>
                        <i data-lucide="x" class="js-filter-category-reset hidden w-3 h-3 ml-1 text-blue-600"></i>
                    </button>
                </div>
                @endif

                <!-- Row 3: Type Filters -->
                <div class="bg-slate-50 rounded-2xl p-3 grid grid-cols-4 gap-2">
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => null])) }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'all' ? 'bg-slate-800 text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-100' }}">
                        Semua
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'rembush'])) }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'rembush' ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-100' }}">
                        <i data-lucide="receipt" class="w-3.5 h-3.5 inline mr-1"></i>Rembush
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'pengajuan'])) }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'pengajuan' ? 'bg-teal-600 text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-100' }}">
                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5 inline mr-1"></i>Pengajuan
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'gudang'])) }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'gudang' ? 'bg-amber-600 text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-100' }}">
                        <i data-lucide="package" class="w-3.5 h-3.5 inline mr-1"></i>Gudang
                    </a>
                </div>
            </div>

            <!-- MOBILE: 5 Rows Layout (screens below md) -->
            <div class="flex md:hidden flex-col gap-3">
                <!-- Row 1: Search -->
                <div class="relative group">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    <input type="text" id="instant-search-mobile" value="{{ request('search') }}" placeholder="Cari invoice, nama..." autocomplete="off" class="search-input-sync w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-semibold focus:outline-none focus:ring-4 focus:ring-blue-500/5 focus:border-blue-400 transition-all placeholder:text-gray-400">
                    <button type="button" class="search-clear absolute right-3 top-1/2 -translate-y-1/2 hidden p-1 rounded-lg hover:bg-gray-200 transition-colors">
                        <i data-lucide="x" class="w-3 h-3 text-gray-400"></i>
                    </button>
                </div>

                @if(auth()->user()->role !== 'teknisi')
                <!-- Row 2: Date -->
                <button type="button" class="js-filter-date-btn filter-trigger w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border-2 border-gray-200 rounded-2xl text-xs font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all group" data-target="menu-filter-date">
                    <i data-lucide="calendar" class="w-4 h-4 text-gray-400 group-[.active]:text-blue-600"></i>
                    <span class="js-filter-date-label filter-label">Pilih Tanggal</span>
                    <i data-lucide="x" class="js-filter-date-reset hidden w-4 h-4 ml-1 text-blue-600"></i>
                </button>

                <!-- Row 3: Branch -->
                <button type="button" class="js-filter-branch-btn filter-trigger w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all bg-white text-slate-500 border-2 border-slate-200 hover:border-blue-300 group" data-target="menu-filter-branch">
                    <i data-lucide="git-branch" class="w-4 h-4 group-[.active]:text-blue-600"></i>
                    <span class="js-filter-branch-label filter-label">Pilih Cabang</span>
                    <i data-lucide="x" class="js-filter-branch-reset hidden w-4 h-4 ml-1 text-blue-600"></i>
                </button>

                <!-- Row 4: Category -->
                <button type="button" class="js-filter-category-btn filter-trigger w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all bg-white text-slate-500 border-2 border-slate-200 hover:border-blue-300 group" data-target="menu-filter-category">
                    <i data-lucide="layout-grid" class="w-4 h-4 group-[.active]:text-blue-600"></i>
                    <span class="js-filter-category-label filter-label">Pilih Kategori</span>
                    <i data-lucide="x" class="js-filter-category-reset hidden w-4 h-4 ml-1 text-blue-600"></i>
                </button>
                @endif

                <!-- Row 5: Type Filters -->
                <div class="bg-slate-50 rounded-2xl p-2.5 grid grid-cols-2 gap-2">
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => null])) }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'all' ? 'bg-slate-800 text-white shadow-lg' : 'bg-white text-slate-500' }}">
                        Semua
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'rembush'])) }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'rembush' ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-slate-500' }}">
                        <i data-lucide="receipt" class="w-3.5 h-3.5 inline mr-1"></i>Rembush
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'pengajuan'])) }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'pengajuan' ? 'bg-teal-600 text-white shadow-lg' : 'bg-white text-slate-500' }}">
                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5 inline mr-1"></i>Pengajuan
                    </a>
                    <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'gudang'])) }}" class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'gudang' ? 'bg-amber-600 text-white shadow-lg' : 'bg-white text-slate-500' }}">
                        <i data-lucide="package" class="w-3.5 h-3.5 inline mr-1"></i>Gudang
                    </a>
                </div>
            </div>

            @if(auth()->user()->role !== 'teknisi')
            <!-- SINGLETON POPOVERS: Shared by all layouts (Fixed Positioning) -->
            <div id="popover-container">
                <!-- Branch Popover -->
                <div id="menu-filter-branch" class="filter-popover hidden fixed w-full md:w-72 bg-white border border-slate-200 rounded-2xl shadow-xl z-[100] p-3 animate-in fade-in slide-in-from-top-2 duration-200">
                    <div class="relative mb-3">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"></i>
                        <input type="text" placeholder="Cari cabang..." class="popover-search w-full pl-8 pr-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all placeholder:text-slate-400">
                    </div>
                    <div class="max-h-64 overflow-y-auto custom-scrollbar flex flex-col gap-1 pr-1" id="branch-list">
                        @foreach($branches as $b)
                        <label class="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all text-sm select-none hover:bg-slate-50 [&:has(input:checked)]:bg-blue-50 [&:has(input:checked)]:ring-1 [&:has(input:checked)]:ring-blue-200">
                            <div class="flex items-center justify-center relative w-4 h-4">
                                <input type="checkbox" name="branch_id[]" value="{{ $b->id }}" class="peer absolute w-full h-full opacity-0 cursor-pointer filter-checkbox z-10">
                                <div class="w-4 h-4 rounded border-2 border-slate-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 transition-all flex items-center justify-center">
                                    <i data-lucide="check" class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-slate-900 peer-checked:text-blue-700 transition-colors leading-none truncate">{{ $b->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Category Popover -->
                <div id="menu-filter-category" class="filter-popover hidden fixed w-full md:w-64 bg-white border border-slate-200 rounded-2xl shadow-xl z-[100] p-3 animate-in fade-in slide-in-from-top-2 duration-200">
                    <div class="relative mb-3">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"></i>
                        <input type="text" placeholder="Cari kategori..." class="popover-search w-full pl-8 pr-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all placeholder:text-slate-400">
                    </div>
                    <div class="max-h-64 overflow-y-auto custom-scrollbar flex flex-col gap-1 pr-1" id="category-list">
                        @foreach($categories as $key => $val)
                        <label class="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all text-sm select-none hover:bg-slate-50 [&:has(input:checked)]:bg-blue-50 [&:has(input:checked)]:ring-1 [&:has(input:checked)]:ring-blue-200">
                            <div class="flex items-center justify-center relative w-4 h-4">
                                <input type="checkbox" name="category[]" value="{{ $key }}" class="peer absolute w-full h-full opacity-0 cursor-pointer filter-checkbox z-10">
                                <div class="w-4 h-4 rounded border-2 border-slate-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 transition-all flex items-center justify-center">
                                    <i data-lucide="check" class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-600 group-hover:text-slate-900 peer-checked:text-blue-700 transition-colors leading-none truncate">{{ $val }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Date Popover -->
                <div id="menu-filter-date" class="filter-popover hidden fixed bg-white border border-slate-200 rounded-2xl shadow-xl z-[100] flex flex-col md:flex-row divide-y md:divide-y-0 md:divide-x divide-slate-100 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200 min-w-min w-full md:w-auto">
                    <!-- Presets -->
                    <div class="w-full md:w-40 bg-slate-50 p-2.5 flex flex-col gap-1 shrink-0">
                        <button type="button" class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all" data-range="today">Hari Ini</button>
                        <button type="button" class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all" data-range="yesterday">Kemarin</button>
                        <button type="button" class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all" data-range="last7">7 Hari Terakhir</button>
                        <button type="button" class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all" data-range="last30">30 Hari Terakhir</button>
                        <button type="button" class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all" data-range="thisMonth">Bulan Ini</button>
                        <button type="button" class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all" data-range="lastMonth">Bulan Lalu</button>
                        <div class="h-px bg-slate-200/60 my-1 mx-2"></div>
                        <button type="button" class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-bold text-left bg-blue-50 text-blue-700 ring-1 ring-blue-200" data-range="custom">Custom Tanggal</button>
                    </div>
                    <!-- Custom Range -->
                    <div class="p-4 flex-1 flex flex-col gap-4 bg-white md:w-[360px]">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="group">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5 ml-1 group-focus-within:text-blue-500 transition-colors">DARI TANGGAL</label>
                                <input type="date" id="filter-start-date" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                            <div class="group">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5 ml-1 group-focus-within:text-blue-500 transition-colors">SAMPAI TANGGAL</label>
                                <input type="date" id="filter-end-date" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" id="btn-cancel-date" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition-all active:scale-95">Batal</button>
                            <button type="button" id="btn-apply-date" class="flex-[2] py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]" disabled>Terapkan Filter</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>


        {{-- Status Tabs --}}
        <div id="search-results-container">
        <div class="tabs-scroll-container px-3 sm:px-5 pt-1 sm:pt-2 overflow-x-auto scrollbar-hide">
            <div class="flex items-center gap-0.5 sm:gap-1 md:gap-2 min-w-max border-b border-gray-100">
                @php
                    $tabs = [
                        'all'       => ['label' => 'Semua',      'count' => $stats['count']],
                        'pending'   => ['label' => 'Pending',  'count' => $stats['pending']],
                        'auto-reject'     => ['label' => 'Auto Reject', 'count' => $stats['auto_reject'] ?? 0],
                        'flagged'         => ['label' => 'Bermasalah',     'count' => $stats['flagged'] ?? 0],
                        'waiting_payment' => ['label' => 'Menunggu Pembayaran', 'count' => $stats['waiting_payment'] ?? 0],
                        'approved'  => ['label' => 'Menunggu Approve Owner', 'count' => $stats['approved'] ?? 0],
                        'completed' => ['label' => 'Selesai',     'count' => $stats['completed']],
                        'rejected'  => ['label' => 'Ditolak', 'count' => $stats['rejected']],
                    ];
                    $currentStatus = request('status', 'all');
                @endphp

                @foreach($tabs as $key => $tab)
                    <a href="{{ route('transactions.index', ['status' => $key === 'all' ? null : $key, 'search' => request('search')]) }}"
                       class="relative px-2.5 sm:px-3 md:px-4 py-2.5 sm:py-3 text-[11px] sm:text-xs md:text-sm font-medium transition-all whitespace-nowrap {{ $currentStatus === $key ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                        {{ $tab['label'] }}
                        <span class="ml-0.5 sm:ml-1 text-[10px] sm:text-xs opacity-70 status-count" data-status="{{ $key }}">({{ $tab['count'] }})</span>
                        @if($currentStatus === $key)
                            <div class="absolute bottom-0 left-0 w-full h-[2px] bg-blue-600 rounded-t-full"></div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Table View --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-400 font-semibold bg-gray-50/50 whitespace-nowrap">
                        <th class="px-5 py-4 text-center w-10 hidden xl:table-cell">No.</th>
                        <th class="px-5 py-4">Nama Pengaju</th>
                        <th class="px-5 py-4">Jenis</th>
                        <th class="px-5 py-4 hidden lg:table-cell">Kategori</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 hidden xl:table-cell">Tanggal</th>
                        <th class="px-5 py-4">Nominal</th>
                        <th class="px-5 py-4 text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody id="desktop-tbody" class="divide-y divide-gray-50 text-sm text-gray-600 transition-all duration-300">
                    {{-- Will be populated by JavaScript --}}
                </tbody>
            </table>
            {{-- No results message --}}
            <div id="table-no-results" class="hidden px-6 py-20 text-center">
                <div class="flex flex-col items-center justify-center opacity-40">
                    <div class="p-4 bg-gray-50 rounded-full mb-4">
                        <i data-lucide="search" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">Tidak Ditemukan</h3>
                    <p class="text-xs text-gray-500 mt-1">Tidak ada transaksi yang cocok dengan pencarian "<span id="no-result-query"></span>"</p>
                </div>
            </div>
        </div>

        {{-- Mobile/Tablet Card View --}}
        <div id="mobile-container" class="md:hidden divide-y divide-gray-50 transition-all duration-300">
            {{-- Will be populated by JavaScript --}}
        </div>

        {{-- No results message (mobile) --}}
        <div id="mobile-no-results" class="hidden md:hidden p-12 text-center">
            <div class="flex flex-col items-center justify-center opacity-40">
                <i data-lucide="search" class="w-12 h-12 text-gray-300 mb-3"></i>
                <h3 class="text-sm font-bold text-gray-900">Tidak Ditemukan</h3>
                <p class="text-xs text-gray-500">Tidak ada transaksi yang cocok dengan pencarian "<span id="mobile-no-result-query"></span>"</p>
            </div>
        </div>

        {{-- Footer / Pagination --}}
        <div class="p-3 sm:p-4 md:p-5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2 sm:gap-0">
            <p class="text-[11px] sm:text-xs text-gray-500 font-medium order-2 sm:order-1">
                Menampilkan <span id="showing-from">0</span> - <span id="showing-to">0</span> dari <span id="total-records">0</span> transaksi
            </p>
            <div class="flex items-center gap-2 order-1 sm:order-2">
                @if(auth()->user()->role !== 'teknisi')
                {{-- Export Button --}}
                <button type="button" id="btn-open-export"
                    onclick="openExportModal()"
                    class="flex items-center gap-2 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-emerald-600/20 active:scale-95">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Export Excel</span>
                    <span class="sm:hidden">Export</span>
                </button>
                @endif
                <div id="pagination-container" class="flex items-center gap-1 sm:gap-2">
                    {{-- Will be populated by JavaScript --}}
                </div>
            </div>
        </div>
        </div>{{-- end #search-results-container --}}
    </div>


@if(auth()->user()->role !== 'teknisi')
{{-- ══════════════════════════════════════════════════ --}}
{{-- EXPORT MODAL: Filter Laporan Bulanan              --}}
{{-- ══════════════════════════════════════════════════ --}}
<div id="export-modal"
     class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[70] flex items-center justify-center p-4 opacity-0 transition-opacity duration-200"
     role="dialog" aria-modal="true" aria-labelledby="export-modal-title">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform scale-95 transition-transform duration-200" id="export-modal-card">

        {{-- Header --}}
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 rounded-xl">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900" id="export-modal-title">Export Laporan Transaksi</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Format Excel (.xlsx) · Rumus Otomatis · Google Sheets Ready</p>
                </div>
            </div>
            <button type="button" onclick="closeExportModal()"
                class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-4">

            {{-- Period Row: Bulan + Tahun --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Bulan</label>
                    <select id="export-month" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        <option value="">Semua Bulan</option>
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Tahun</label>
                    <select id="export-year" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        @for($y = now()->year; $y >= now()->year - 4; $y--)
                            <option value="{{ $y }}" {{ $y === now()->year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Tipe Transaksi --}}
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Tipe Transaksi</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="export-type-option cursor-pointer">
                        <input type="radio" name="export_type" value="" class="sr-only" checked>
                        <div class="px-2 py-2.5 rounded-xl text-xs font-bold text-center transition-all border-2 border-slate-200 bg-white text-slate-500 hover:border-slate-400 peer-checked:border-slate-800 peer-checked:bg-slate-800 peer-checked:text-white" data-type="">
                            Semua
                        </div>
                    </label>
                    <label class="export-type-option cursor-pointer">
                        <input type="radio" name="export_type" value="rembush" class="sr-only">
                        <div class="px-2 py-2.5 rounded-xl text-xs font-bold text-center transition-all border-2 border-slate-200 bg-white text-slate-500 hover:border-indigo-400" data-type="rembush">
                            Rembush
                        </div>
                    </label>
                    <label class="export-type-option cursor-pointer">
                        <input type="radio" name="export_type" value="pengajuan" class="sr-only">
                        <div class="px-2 py-2.5 rounded-xl text-xs font-bold text-center transition-all border-2 border-slate-200 bg-white text-slate-500 hover:border-teal-400" data-type="pengajuan">
                            Pengajuan
                        </div>
                    </label>
                    <label class="export-type-option cursor-pointer">
                        <input type="radio" name="export_type" value="gudang" class="sr-only">
                        <div class="px-2 py-2.5 rounded-xl text-xs font-bold text-center transition-all border-2 border-slate-200 bg-white text-slate-500 hover:border-amber-400" data-type="gudang">
                            Gudang
                        </div>
                    </label>
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Status</label>
                <select id="export-status" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Menunggu Approve Owner</option>
                    <option value="waiting_payment">Menunggu Pembayaran</option>
                    <option value="completed">Selesai / Paid</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>

            {{-- Cabang --}}
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Cabang (Opsional)</label>
                <select id="export-branch" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Info callout --}}
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex gap-2.5">
                <i data-lucide="info" class="w-4 h-4 text-blue-400 shrink-0 mt-0.5"></i>
                <div class="text-[11px] font-medium text-blue-600 leading-relaxed">
                    File <strong>Excel (.xlsx)</strong> akan langsung terdownload. Kolom kalkulasi seperti <em>Total Estimasi</em> dan <em>Grand Total</em> menggunakan <strong>rumus Excel</strong>—klik selnya untuk melihat formula. Pengajuan multi-item di-<em>expand</em> per baris.
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="flex gap-3 p-5 pt-0">
            <button type="button" id="btn-cancel-export" onclick="closeExportModal()"
                class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                Batal
            </button>
            <button type="button" id="btn-do-export" onclick="doExport(this)"
                class="flex-[2] py-3 rounded-xl bg-emerald-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-emerald-700 disabled:opacity-70 disabled:cursor-not-allowed transition-all shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 active:scale-[0.98]">
                <span id="export-btn-idle" class="flex items-center gap-2">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Download Excel
                </span>
                <span id="export-btn-loading" class="hidden flex items-center gap-2">
                    <svg class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Menyiapkan file...
                </span>
            </button>
        </div>
    </div>
</div>

<script>
// ── Export Modal Logic ──────────────────────────────────
function openExportModal() {
    const modal = document.getElementById('export-modal');
    // Pre-fill: bulan saat ini
    const now = new Date();
    document.getElementById('export-month').value = now.getMonth() + 1;

    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        modal.classList.remove('opacity-0');
        document.getElementById('export-modal-card').classList.remove('scale-95');
    });
    
    // reinit lucide icons for newly shown modal
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeExportModal() {
    const modal = document.getElementById('export-modal');
    modal.classList.add('opacity-0');
    document.getElementById('export-modal-card').classList.add('scale-95');
    setTimeout(() => modal.classList.add('hidden'), 200);
}

// doExport menerima referensi button (this) agar tidak perlu getElementById
// yang bisa gagal jika elemen belum/sudah di-unmount dari DOM.
function doExport(btnEl) {
    const month    = document.getElementById('export-month')?.value ?? '';
    const year     = document.getElementById('export-year')?.value ?? '';
    const status   = document.getElementById('export-status')?.value ?? '';
    const branch   = document.getElementById('export-branch')?.value ?? '';
    const typeEl   = document.querySelector('input[name="export_type"]:checked');
    const type     = typeEl ? typeEl.value : '';

    const params = new URLSearchParams();
    if (month)    params.set('month', month);
    if (year)     params.set('year', year);
    if (type)     params.set('type', type);
    if (status)   params.set('status', status);
    if (branch)   params.set('branch_id', branch);

    // ✅ Gunakan window.location.origin agar kompatibel dengan
    // Cloudflare tunnel, localhost, dan domain apapun.
    const url = window.location.origin + '/transactions/export?' + params.toString();

    // ── Set loading state ──────────────────────────────
    // Gunakan parameter `btnEl` (this) — dijamin valid karena
    // dilewatkan langsung dari event handler.
    const btn       = btnEl || document.getElementById('btn-do-export');
    const btnCancel = document.getElementById('btn-cancel-export');
    const idleSpan    = document.getElementById('export-btn-idle');
    const loadingSpan = document.getElementById('export-btn-loading');

    if (btn)       btn.disabled = true;
    if (btnCancel) btnCancel.disabled = true;
    if (idleSpan)    idleSpan.classList.add('hidden');
    if (loadingSpan) loadingSpan.classList.remove('hidden');

    // ── Fetch as blob → trigger download ───────────────
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        },
        credentials: 'same-origin',
    })
    .then(response => {
        if (!response.ok) throw new Error('Server error ' + response.status);
        const disposition = response.headers.get('Content-Disposition') || '';
        const match = disposition.match(/filename="?([^"]+)"?/);
        const filename = match ? match[1] : 'Laporan_Transaksi.xlsx';
        return response.blob().then(blob => ({ blob, filename }));
    })
    .then(({ blob, filename }) => {
        const objectUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = objectUrl;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);

        closeExportModal();
        setTimeout(() => resetExportBtn(), 300);
    })
    .catch(err => {
        console.error('[Export] Error:', err);
        alert('Gagal mengunduh laporan. Silakan coba lagi.\n' + err.message);
        resetExportBtn();
    });
}

function resetExportBtn() {
    const btn         = document.getElementById('btn-do-export');
    const btnCancel   = document.getElementById('btn-cancel-export');
    const idleSpan    = document.getElementById('export-btn-idle');
    const loadingSpan = document.getElementById('export-btn-loading');

    if (btn)          btn.disabled = false;
    if (btnCancel)    btnCancel.disabled = false;
    if (idleSpan)     idleSpan.classList.remove('hidden');
    if (loadingSpan)  loadingSpan.classList.add('hidden');
}

// ── Export Type Radio Styling ─────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const typeColors = {
        ''          : { checked: 'border-slate-800 bg-slate-800 text-slate-800',  unchecked: 'border-slate-200 bg-white text-slate-500' },
        'rembush'   : { checked: 'border-indigo-600 bg-indigo-600 text-indigo-600', unchecked: 'border-slate-200 bg-white text-slate-500' },
        'pengajuan' : { checked: 'border-teal-600 bg-teal-600 text-teal-600',    unchecked: 'border-slate-200 bg-white text-slate-500' },
        'gudang'    : { checked: 'border-amber-600 bg-amber-600 text-amber-600',   unchecked: 'border-slate-200 bg-white text-slate-500' },
    };

    function updateTypeStyles() {
        document.querySelectorAll('input[name="export_type"]').forEach(radio => {
            const div = radio.nextElementSibling;
            const type = radio.value;
            const colors = typeColors[type] || typeColors[''];
            // Remove old
            div.className = div.className.replace(/(border-\w+-\d+|bg-\w+-\d+|text-\w+-\d+|text-white|text-slate-500)/g, '').trim();
            div.className += ' ' + (radio.checked ? colors.checked : colors.unchecked);
        });
    }

    document.querySelectorAll('input[name="export_type"]').forEach(radio => {
        radio.addEventListener('change', updateTypeStyles);
    });

    updateTypeStyles();

    // Close modal on backdrop click
    document.getElementById('export-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeExportModal();
    });
});
</script>
@endif

@push('modals')
    {{-- VIEW DETAIL MODAL --}}

    <div id="view-modal"
         class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/30 backdrop-blur-md p-0 sm:p-4 opacity-0 transition-all duration-300"
         role="dialog"
         aria-modal="true"
         aria-labelledby="view-modal-title">
        <div class="bg-white rounded-none sm:rounded-2xl shadow-2xl max-w-2xl w-full h-[100dvh] sm:h-auto sm:max-h-[90vh] flex flex-col overflow-hidden transform scale-95 transition-all duration-300 overscroll-contain"
             id="view-modal-content">

            <div id="view-loading" class="p-12 text-center w-full flex flex-col items-center justify-center min-h-[50vh]">
                <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>
            </div>

            <div id="view-body" class="flex-col flex-auto min-h-0 w-full" style="display: none;">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white z-10 shrink-0">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900" id="view-modal-title">Detail Pengajuan</h3>
                        <p class="text-xs text-slate-400 font-medium mt-0.5" id="v-invoice"></p>
                    </div>
                    <button onclick="closeViewModal()"
                        class="p-2 hover:bg-slate-100 rounded-xl transition-colors text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto grow min-h-0 overscroll-contain">
                    <div class="flex items-center gap-2 flex-wrap" id="v-badges"></div>
                    
                    {{-- ✅ Revisi Banner (Pengajuan yang direvisi Management) --}}
                    <div id="v-revision-banner" class="hidden"></div>

                    {{-- ✅ UPDATED: Foto/PDF dengan Click-to-Zoom --}}
                    <div id="v-image-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Foto Nota / Dokumen</label>
                        <div id="v-image-wrapper" 
                             class="group relative bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 flex items-center justify-center cursor-zoom-in hover:border-emerald-200 transition-all">
                            <img id="v-image" src="" class="max-h-48 object-contain rounded-xl" alt="Nota">
                            
                            {{-- PDF Placeholder in Detail View --}}
                            <div id="v-pdf-icon" class="hidden flex flex-col items-center justify-center py-4">
                                <i data-lucide="file-text" class="w-16 h-16 text-emerald-600 mb-2"></i>
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Dokumen PDF Terlampir</span>
                                <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold">Klik untuk melihat detail</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="v-fields"></div>
                    <div id="v-items-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Daftar Barang</label>
                        {{-- Table Container untuk Rembush --}}
                        <div id="v-items-table-container" class="border border-slate-100 rounded-xl overflow-hidden hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Nama</th>
                                        <th class="px-3 py-2 text-center">Qty</th>
                                        <th class="px-3 py-2 text-left">Satuan</th>
                                        <th class="px-3 py-2 text-right">Harga</th>
                                        <th class="px-3 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="v-items-tbody" class="divide-y divide-slate-50"></tbody>
                            </table>
                        </div>
                        {{-- Div Container untuk Pengajuan (Cards Grid) --}}
                        <div id="v-items-div-container" class="hidden flex-col"></div>
                    </div>
                    <div id="v-specs-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Spesifikasi</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="v-specs"></div>
                    </div>
                    
                    {{-- ✅ Summary Area (Keterangan Global & Total Estimasi) --}}
                    <div id="v-summary-wrap" class="hidden mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div id="v-summary-desc-wrap" class="md:col-span-2 bg-slate-50 border border-slate-200 rounded-xl p-4 hidden">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Keterangan Global</label>
                                <p class="text-xs font-medium text-slate-700 whitespace-pre-wrap leading-relaxed" id="v-summary-desc"></p>
                            </div>
                            <div id="v-summary-total-wrap" class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col justify-center shadow-sm">
                                <label class="block text-[10px] font-bold text-blue-800/60 uppercase tracking-wider mb-1">Total Estimasi</label>
                                <p class="text-lg md:text-xl font-black text-blue-700 tracking-tight flex items-baseline" id="v-summary-total"></p>
                            </div>
                        </div>
                    </div>

                    <div id="v-branches-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Pembagian Cabang</label>
                        <div class="space-y-2" id="v-branches"></div>
                    </div>
                    <div id="v-rejection-wrap" class="hidden">
                        <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                            <p class="text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">Alasan Penolakan</p>
                            <p class="text-sm text-red-700 font-medium" id="v-rejection"></p>
                        </div>
                    </div>
                    <div id="v-waiting-owner" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <div class="flex items-center gap-2 text-amber-700">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            <p class="text-xs font-bold">Menunggu persetujuan dari Owner (nominal ≥ Rp 1.000.000)</p>
                        </div>
                    </div>
                    <div id="v-reviewer-wrap" class="hidden items-center gap-2 text-xs text-slate-400 pt-2 border-t border-slate-100">
                        <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                        <span>Direview oleh <strong id="v-reviewer" class="text-slate-600"></strong> pada <span id="v-reviewed-at"></span></span>
                    </div>

                    <!-- Riwayat Pembayaran (Payment History) -->
                    <div id="v-payment-history-wrap" class="hidden mt-6 pt-6 border-t border-slate-100 bg-white">
                        <h4 class="text-lg font-black text-slate-800 mb-6">Riwayat Pembayaran</h4>

                        <!-- Main Card Container -->
                        <div class="bg-slate-100 border border-slate-100 rounded-3xl shadow-sm p-5 sm:p-6">
                            <div class="space-y-10 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
                                
                                <!-- Step 1: Penyerahan / Upload -->
                                <div class="relative pl-8">
                                    <div class="absolute left-0 top-1.5 w-3.5 h-3.5 bg-blue-600 rounded-full border-2 border-white shadow-[0_0_0_1px_rgba(37,99,235,0.2)]"></div>
                                    
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                        <div class="space-y-1.5">
                                            <h5 id="v-pay-step1-title" class="text-xs font-black text-slate-800 uppercase tracking-widest">BUKTI TRANSFER DIUNGGAH</h5>
                                            
                                            <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 text-slate-500 rounded-full w-fit border border-slate-100">
                                                <i data-lucide="calendar" class="w-3 h-3"></i>
                                                <span id="v-pay-step1-at" class="text-[10px] font-black uppercase tracking-tight"></span>
                                            </div>
                                            
                                            <p class="text-xs text-slate-400 font-medium">
                                                Oleh: <span id="v-pay-step1-by" class="font-bold text-slate-600"></span> 
                                                <span class="mx-1 opacity-50">•</span> 
                                                Role: <span id="v-pay-step1-role" class="font-bold text-slate-600"></span>
                                            </p>
                                        </div>

                                        <!-- Action Button Step 1 -->
                                        <div id="v-pay-step1-action-wrap"></div>
                                    </div>
                                </div>

                                <!-- Step 2: Penerimaan -->
                                <div class="relative pl-8" id="v-pay-step2-wrap">
                                    <div class="absolute left-0 top-1.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white shadow-[0_0_0_1px_rgba(16,185,129,0.2)]"></div>
                                    
                                    <div class="space-y-1.5">
                                        <h5 class="text-xs font-black text-slate-800 uppercase tracking-widest">PEMBAYARAN DITERIMA</h5>
                                        
                                        <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 text-slate-500 rounded-full w-fit border border-slate-100">
                                            <i data-lucide="calendar" class="w-3 h-3"></i>
                                            <span id="v-pay-step2-at" class="text-[10px] font-black uppercase tracking-tight"></span>
                                        </div>
                                        
                                        <p class="text-xs text-slate-400 font-medium">
                                            Oleh: <span id="v-pay-step2-by" class="font-bold text-slate-600"></span> 
                                            <span class="mx-1 opacity-50">•</span> 
                                            Role: <span id="v-pay-step2-role" class="font-bold text-slate-600"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Black Summary Box -->
                            <div class="mt-8 bg-[#0F172A] rounded-2xl p-6 text-white relative overflow-hidden group shadow-xl">
                                <!-- Subtle Grid Background -->
                                <div class="absolute inset-0  pointer-events-none" style="background-image: radial-gradient(#000000 1px, #000000  1px); background-size: 20px 20px;"></div>
                                
                                <div class="relative z-10 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-1">
                                            <span class="block text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">TOTAL DIBAYARKAN</span>
                                            <div id="v-pay-summary-amount" class="text-3xl sm:text-4xl font-black text-emerald-400 tracking-tighter"></div>
                                            <div id="v-pay-summary-discrepancy" class="text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg hidden uppercase tracking-wider"></div>
                                        </div>
                                        <div id="v-pay-method-wrap" class="space-y-1 md:text-right hidden">
                                            <span class="block text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">METODE PENCAIRAN</span>
                                            <div id="v-pay-summary-method" class="text-lg font-black text-white tracking-tight uppercase"></div>
                                            <div id="v-pay-summary-account" class="text-[10px] font-bold text-slate-400 mt-1 leading-relaxed"></div>
                                        </div>
                                    </div>

                                    <div class="pt-6 border-t border-slate-800 flex items-center gap-2">
                                        <div class="w-6 h-6 bg-emerald-500/10 rounded-lg flex items-center justify-center">
                                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-emerald-400"></i>
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-400 tracking-wide">
                                            Otomatis diverifikasi & divalidasi oleh <span class="text-white">AI</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="v-actions" class="hidden pt-2 border-t border-slate-100">
                        <button id="v-btn-reset"
                            onclick="submitApproval('pending')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold text-xs transition-all border border-slate-200">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset ke Pending
                        </button>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-100 bg-slate-50/50 shrink-0">
                    <button onclick="closeViewModal()"
                        class="w-full py-3 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- ✅ IMAGE/PDF VIEWER MODAL (Fullscreen Zoom) --}}
    <div id="image-viewer"
         class="fixed inset-0 bg-black/90 backdrop-blur-md hidden items-center justify-center z-[9999] p-4 sm:p-10 overscroll-contain"
         role="dialog" 
         aria-modal="true" 
         aria-labelledby="viewer-title">

        {{-- Container margin sisi 4 --}}
        <div class="w-full h-full max-w-4xl bg-white rounded-2xl flex flex-col p-4 sm:p-8 shadow-2xl relative overflow-hidden" id="viewer-card">

            {{-- Header & Close Button --}}
            <div class="flex justify-between items-center shrink-0 mb-6 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-800 uppercase tracking-widest" id="viewer-header-title">PREVIEW DOKUMEN</h3>
                    <p id="viewer-title" class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-wider">Klik di luar gambar atau X untuk menutup</p>
                </div>
                <button id="close-viewer"
                        type="button"
                        onclick="closeImageViewer()"
                        class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center rounded-2xl bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-500 transition-all active:scale-95"
                        aria-label="Tutup preview">
                    <i data-lucide="x" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </button>
            </div>

            {{-- Gambar/PDF Wrapper dengan Background Grid/Dots --}}
            <div class="w-full flex-1 flex justify-center items-center bg-slate-50 rounded-2xl overflow-hidden relative border-2 border-slate-100 p-2 sm:p-4">
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 20px 20px;"></div>
                <img id="viewer-image"
                     src=""
                     class="relative z-10 max-w-full max-h-full object-contain drop-shadow-2xl rounded-lg"
                     alt="Preview foto referensi" />

                {{-- PDF Viewer Iframe --}}
                <iframe id="viewer-pdf" class="hidden relative z-10 w-full h-full rounded-lg border-0" src=""></iframe>
            </div>

            {{-- Footer for PDF Actions --}}
            <div id="viewer-footer" class="mt-6 flex justify-center hidden">
                <a id="viewer-pdf-link" href="#" target="_blank" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl text-xs font-bold transition-all shadow-lg shadow-emerald-600/20 active:scale-95">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    BUKA DI TAB BARU / DOWNLOAD
                </a>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- REJECT MODAL --}}
    <div id="reject-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-red-100 rounded-xl">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Tolak Nota</h3>
                        <p class="text-xs text-slate-500">Nota: <strong id="reject-modal-invoice"></strong></p>
                    </div>
                </div>
                <form id="reject-form" method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="rejected">
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">
                            Alasan Penolakan <span class="text-red-500">*</span>
                        </label>
                        <textarea name="rejection_reason" rows="3" required placeholder="Tuliskan alasan penolakan..."
                            class="w-full border border-red-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeRejectModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all shadow-lg shadow-red-600/20">
                            Konfirmasi Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- OVERRIDE MODAL --}}
    <div id="override-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-orange-100 rounded-xl">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-orange-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Request Override</h3>
                        <p class="text-xs text-slate-500">Nota: <strong id="override-modal-invoice"></strong></p>
                    </div>
                </div>
                <form id="override-form" method="POST" action="">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">
                            Alasan Override <span class="text-red-500">*</span>
                        </label>
                        <textarea name="override_reason" rows="3" required placeholder="Jelaskan mengapa AI salah..."
                            class="w-full border border-orange-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-300 resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeOverrideModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitOverride"
                            class="flex-1 py-3 rounded-xl bg-orange-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20">
                            Kirim Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- FORCE APPROVE MODAL --}}
    <div id="force-approve-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-rose-100 rounded-xl">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-rose-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Force Approve</h3>
                        <p class="text-xs text-slate-500">Nota: <strong id="force-approve-modal-invoice"></strong></p>
                    </div>
                </div>
                <form id="force-approve-form" method="POST" action="">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">
                            Alasan Rekonsiliasi <span class="text-red-500">*</span>
                        </label>
                        <textarea name="force_approve_reason" rows="3" required placeholder="Alasan mengapa disetujui meski nilai beda..."
                            class="w-full border border-rose-200 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-rose-100 focus:border-rose-300 resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeForceApproveModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitForce"
                            class="flex-1 py-3 rounded-xl bg-rose-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-rose-700 transition-all shadow-lg shadow-rose-600/20">
                            Force Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- PAYMENT UPLOAD MODAL --}}
    <div id="payment-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300 overflow-y-auto pt-10 pb-10">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 transform scale-95 transition-all duration-300 mt-10 mb-auto">
            
            <div id="payment-loading" class="p-12 text-center w-full flex flex-col items-center justify-center min-h-[50vh]">
                <div class="w-10 h-10 border-4 border-slate-200 border-t-cyan-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>
            </div>

            <div id="payment-body" class="p-6 hidden">
                <div class="flex flex-col gap-1 mb-6 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-cyan-100 rounded-xl">
                            <i data-lucide="image" class="w-5 h-5 text-cyan-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900" id="payment-modal-title">Upload Bukti Transfer/Cash</h3>
                            <p class="text-xs text-slate-500">Nota: <strong id="payment-modal-invoice"></strong></p>
                        </div>
                    </div>
                </div>

                {{-- INSERT DETAILS HERE --}}
                <div id="p-detail-container" class="mb-6 space-y-4 border-b border-slate-100 pb-6 hidden">
                    <div class="flex items-center gap-2 flex-wrap" id="p-badges"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="p-fields"></div>
                    
                    <div id="p-items-wrap" class="hidden">
                         <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider">Daftar Barang</label>
                         {{-- Table Container untuk Rembush --}}
                         <div id="p-items-table-container" class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden hidden">
                             <table class="w-full text-xs text-left">
                                 <thead class="bg-slate-100/50 text-slate-500 font-bold uppercase tracking-wider">
                                     <tr>
                                         <th class="px-3 py-2">Nama Barang</th>
                                         <th class="px-3 py-2 text-center">Qty</th>
                                         <th class="px-3 py-2">Satuan</th>
                                         <th class="px-3 py-2 text-right">Harga Sat.</th>
                                         <th class="px-3 py-2 text-right">Total</th>
                                     </tr>
                                 </thead>
                                 <tbody id="p-items-tbody" class="divide-y divide-slate-100"></tbody>
                             </table>
                         </div>
                         {{-- Div Container untuk Pengajuan (Cards Grid) --}}
                         <div id="p-items-div-container" class="hidden flex-col"></div>
                    </div>

                    <div id="p-branches-wrap" class="hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-wider mt-4">Pembagian Cabang</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="p-branches"></div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 mb-4 flex flex-col justify-center shadow-sm">
                    <label class="block text-[10px] font-bold text-blue-800/60 uppercase tracking-wider mb-1">Tagihan Pembayaran</label>
                    <p class="text-lg md:text-xl font-black text-blue-700 tracking-tight flex items-baseline" id="payment-modal-amount"></p>
                </div>

                <form id="payment-form" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    
                    {{-- DYNAMIC FILE INPUT --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2" id="payment-modal-label">
                            Unggah Foto / Screenshot <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="file" id="payment_file_input" required accept=".jpg,.jpeg,.png,.pdf"
                            class="w-full border border-cyan-200 rounded-xl p-2 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 transition-all cursor-pointer bg-white">
                        <p class="mt-1 text-[11px] text-slate-400 font-medium" id="payment-modal-help">Format: JPG, PNG, PDF. Max 2MB.</p>
                    </div>

                    {{-- METODE PEMBAYARAN (Cash / Rekening) --}}
                    <div id="payment-method-container" class="mb-4 hidden">
                        <label class="block text-xs font-bold text-slate-600 mb-2">Metode Pembayaran <span class="text-red-500">*</span></label>
                        <select name="payment_method" id="payment_method_select"
                            class="w-full border border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-4 focus:ring-cyan-100 focus:border-cyan-300 transition-all bg-white">
                            <option value="cash">Cash (Tunai)</option>
                            <option value="transfer">Rekening (Transfer)</option>
                        </select>
                    </div>

                    {{-- TRANSFER FIELDS (Hidden by default) --}}
                    <div id="transfer-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1 uppercase tracking-wider">Metode Transfer</label>
                            <div id="transfer-method-badge" class="inline-block px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded">TRANSFER</div>
                        </div>

                        {{-- Bank Account Selection --}}
                        <div id="saved-accounts-container" class="hidden">
                            <label class="block text-xs font-bold text-indigo-600 mb-1.5">Pilih Rekening Tersimpan</label>
                            <select id="saved_bank_account" onchange="autoFillBankAccount(this)" 
                                class="w-full border-2 border-indigo-100 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-300 transition-all bg-indigo-50/30">
                                <option value="">-- Pilih Rekening --</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Bank Tujuan <span class="text-red-500">*</span></label>
                            <input type="text" name="rekening_bank" id="transfer_bank" placeholder="Contoh: BCA / Mandiri / GoPay" required
                                class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Nomor Rekening <span class="text-red-500">*</span></label>
                                <input type="text" name="rekening_nomor" id="transfer_nomor" placeholder="Contoh: 0987654321" required
                                    inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Atas Nama <span class="text-red-500">*</span></label>
                                <input type="text" name="rekening_nama" id="transfer_nama" placeholder="Contoh: Nama Pemilik" required
                                    class="w-full border border-slate-200 rounded-lg p-2.5 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300">
                            </div>
                        </div>
                        <div id="transfer-profile-alert" class="hidden text-[10px] text-emerald-600 bg-emerald-50 border border-emerald-100 p-2 rounded-lg flex items-start gap-1.5 mt-2">
                            <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5"></i>
                            <span>Rekening ini akan disimpan ke dalam Profil Teknisi untuk transaksi berikutnya.</span>
                        </div>
                    </div>

                    {{-- CASH FIELDS (Hidden by default) --}}
                    <div id="cash-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-lg flex items-start gap-2">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 mt-0.5"></i>
                            <div class="text-[11px] text-amber-800 font-medium">
                                Pastikan foto yang diunggah menampilkan wajah <strong>Teknisi</strong> dan <strong>Uang Tunai</strong> secara jelas sebagai bukti penyerahan.
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan Tambahan (Opsional)</label>
                            <textarea name="catatan" id="cash_catatan" rows="2" placeholder="Cth: Uang diserahkan ke teknisi A..."
                                class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-cyan-100 focus:border-cyan-300 resize-none"></textarea>
                        </div>
                    </div>

                    {{-- PENGAJUAN INVOICE FIELDS (Hidden by default) --}}
                    <div id="pengajuan-invoice-fields" class="hidden space-y-4 mb-5 border-t border-slate-100 pt-4">
                        <div class="p-3 bg-teal-50 border border-teal-100 rounded-lg flex items-start gap-2 mb-4">
                            <i data-lucide="info" class="w-4 h-4 text-teal-600 mt-0.5"></i>
                            <div class="text-[11px] text-teal-800 font-medium">
                                Pilih cabang <strong>Sumber Dana</strong> dan masukkan nominal yang dibayarkan. Cabang yang tidak dipilih otomatis <strong class="text-red-600">berhutang</strong>.
                            </div>
                        </div>

                        {{-- Multi Sumber Dana Section --}}
                        <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                            <div class="px-5 py-4 border-b border-slate-50">
                                <label class="text-xs font-black text-slate-800 uppercase tracking-widest">Rincian Sumber Dana <span class="text-red-500">*</span></label>
                            </div>
                            <div id="p_sumber_dana_container" class="p-5 space-y-4">
                                {{-- Dynamically populated by JS --}}
                            </div>

                            <div id="p_sumber_dana_total" class="px-6 py-5 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center hidden">
                                <div class="space-y-1">
                                    <span class="block text-xs font-black text-slate-800 uppercase tracking-widest leading-none">Total Sumber Dana</span>
                                    <div id="p_sumber_dana_diff" class="text-[10px] font-bold tracking-tight"></div>
                                </div>
                                <span id="p_sumber_dana_total_value" class="text-2xl font-black text-teal-600 tracking-tighter">Rp 0</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Ongkir</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="ongkir" id="p_ongkir" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Diskon Pengiriman</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="diskon_pengiriman" id="p_diskon_pengiriman" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Voucher Diskon</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="voucher_diskon" id="p_voucher_diskon" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">DPP Lainnya</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="dpp_lainnya" id="p_dpp_lainnya" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">PPN</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="tax_amount" id="p_tax_amount" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Biaya Layanan 1</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="biaya_layanan_1" id="p_biaya_layanan_1" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1.5">Biaya Layanan 2</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" name="biaya_layanan_2" id="p_biaya_layanan_2" placeholder="0"
                                        class="nominal-input w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan (Opsional)</label>
                            <textarea name="catatan" id="p_catatan" rows="2" placeholder="Cth: Pembayaran via Invoice..."
                                class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-teal-100 focus:border-teal-300 resize-none"></textarea>
                        </div>

                        {{-- Debt Preview --}}
                        <div id="p_debt_preview" class="hidden border border-red-100 rounded-2xl overflow-hidden mt-6 shadow-sm">
                            <div class="bg-red-50/50 px-4 py-3.5 border-b border-red-100 flex items-center gap-2.5">
                                <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center shadow-sm">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-red-600"></i>
                                </div>
                                <span class="text-[11px] font-black text-red-700 uppercase tracking-widest">Preview Hutang Otomatis</span>
                            </div>
                            <div id="p_debt_preview_list" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white/50"></div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="closePaymentModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitPayment"
                            class="flex-1 py-3 rounded-xl bg-cyan-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-cyan-700 transition-all shadow-lg shadow-cyan-600/20 relative">
                            <span id="btnSubmitPaymentText">Upload & Simpan</span>
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 hidden" id="btnSubmitPaymentLoader"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- BRANCH DEBT SETTLEMENT MODAL --}}
    <div id="branch-debt-modal"
         class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center opacity-0 transition-all duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4 border-b border-slate-100 pb-4">
                    <div class="p-2 bg-red-100 rounded-xl">
                        <i data-lucide="receipt" class="w-5 h-5 text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Upload Bukti Pembayaran Hutang</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Antar Cabang</p>
                    </div>
                </div>
                <form id="branch-debt-form" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-2">
                            Unggah Foto Bukti Transfer/Cash <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="payment_proof" id="branch_debt_file_input" required accept=".jpg,.jpeg,.png,.pdf"
                            class="w-full border border-red-200 rounded-xl p-2 text-sm outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 transition-all cursor-pointer bg-white">
                        <p class="mt-1 text-[11px] text-slate-400 font-medium">Format: JPG, PNG, PDF. Max 2MB.</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan (Opsional)</label>
                        <textarea name="notes" id="branch_debt_notes" rows="2" placeholder="Catatan pelunasan..."
                            class="w-full border border-slate-200 rounded-lg p-2 text-sm outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 resize-none"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeBranchDebtModal()"
                            class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs uppercase tracking-wider hover:bg-slate-200 transition-all border border-slate-200">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitBranchDebt"
                            class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold text-xs uppercase tracking-wider hover:bg-red-700 transition-all shadow-lg shadow-red-600/20 relative flex items-center justify-center">
                            <span id="btnSubmitBranchDebtText">Upload & Simpan</span>
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin absolute hidden" id="btnSubmitBranchDebtLoader"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@endsection

@section('styles')
    <style>
        .ai-status-badge {
            transition: all 0.2s ease-in-out;
        }
        .ai-status-badge:hover {
            transform: scale(1.05);
            z-index: 10;
        }
        @keyframes subtle-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .ai-status-badge.animate-pulse {
            animation: subtle-pulse 2s ease-in-out infinite;
        }
        #toast-container .flex.items-center.gap-3 {
            border-left: 4px solid transparent;
        }
        #toast-container .bg-emerald-600 { border-left-color: #34d399; }
        #toast-container .bg-red-600 { border-left-color: #f87171; }
        #toast-container .bg-blue-600 { border-left-color: #60a5fa; }

        /* ── Branch Tag Truncation Tooltip ── */
        .branch-more-wrap {
            position: relative;
            display: inline-flex;
        }
        .branch-more-wrap .branch-tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: #f8fafc;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.4;
            padding: 8px 12px;
            border-radius: 10px;
            white-space: nowrap;
            z-index: 50;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .branch-more-wrap .branch-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1e293b;
        }
        .branch-more-wrap:hover .branch-tooltip {
            visibility: visible;
            opacity: 1;
        }
    </style>
@endsection

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';
    let currentTransactionId = null;
    const userRole = '{{ Auth::user()->role }}';
    const userIsAdmin = {{ Auth::user()->isAdmin() ? 'true' : 'false' }};
    let currentPage = 1;
    const canManage = {{ Auth::user()->canManageStatus() ? 'true' : 'false' }};
    const isOwner = {{ Auth::user()->isOwner() ? 'true' : 'false' }};
    const isAdmin = {{ Auth::user()->isAdmin() ? 'true' : 'false' }};

    // ═══════════════════════════════════════════════════════════════
    // UTILS & AJAX FUNCTIONS
    // ═══════════════════════════════════════════════════════════════
    function escapeHtml(unsafe) {
        if (!unsafe || typeof unsafe !== 'string') return unsafe;
        return unsafe.replace(/[&<"'>]/g, function (match) {
            const map = {
                '&': '&amp;', '<': '&lt;', '>': '&gt;',
                '"': '&quot;', "'": '&#039;'
            };
            return map[match];
        });
    }

    function setAsReference(transactionId, itemName) {
        if (!confirm(`Jadikan harga untuk '${itemName}' sebagai referensi baru?`)) return;

        fetch(`/price-index/set-reference/${transactionId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ item_name: itemName })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, 'success');
            } else {
                showToast(res.error || 'Gagal menyimpan referensi.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan jaringan.', 'error');
        });
    }

    // ✅ UNIFIED SEARCH ENGINE - Server-Side dengan Debouncing
    //   KODE LOKAL SAYA
    // ═══════════════════════════════════════════════════════════════
    // SEARCH INPUT SYNC & UTILS (Global)
    // ═══════════════════════════════════════════════════════════════
    window.getActiveSearchValue = function() {
        const desktop = document.getElementById('instant-search');
        const tablet = document.getElementById('instant-search-tablet');
        const mobile = document.getElementById('instant-search-mobile');
        return (desktop?.value || tablet?.value || mobile?.value || '').trim();
    };

    window.syncSearchInputs = function(value) {
        document.querySelectorAll('.search-input-sync').forEach(input => {
            if (input.value !== value) input.value = value;
            
            // Toggle clear buttons in all parents
            const parent = input.parentElement;
            const clearBtn = parent.querySelector('.search-clear') || document.getElementById('search-clear');
            if (clearBtn) {
                if (value.length > 0) clearBtn.classList.remove('hidden');
                else clearBtn.classList.add('hidden');
            }
        });
    };

    // ═══════════════════════════════════════════════════════════════
    // HYBRID SEARCH ENGINE - Auto-Adaptive
    // Client-side untuk < 5k records | Server-side untuk ≥ 5k records
    // ═══════════════════════════════════════════════════════════════

    const SearchEngine = (function() 
    {
        // Configuration
        const CLIENT_SIDE_THRESHOLD = 5000; // Switch to server-side if dataset > 5k
        const SERVER_ITEMS_PER_PAGE = 20;
        
        // State
        let mode = null; // 'client' or 'server'
        let allTransactions = [];
        let filteredTransactions = [];
        let currentPage = 1;
        let totalRecords = 0;
        let totalPages = 0;
        let isLoading = false;
        let isFirstLoad = true;
        let searchTimer = null;
        let abortController = null;

        // ═══════════════════════════════════════════════════════════════
        // INITIAL LOAD - Auto-detect mode
        // ═══════════════════════════════════════════════════════════════
        async function loadData() {
            if (isLoading) {
                console.warn('[SearchEngine] Already loading, skipping...');
                return Promise.resolve();
            }
            
            isLoading = true;
            if(typeof NProgress !== 'undefined') NProgress.start();
            
            if (isFirstLoad) {
                renderSkeletons();
            }
            
            try {
                // First, check dataset size
                const countUrl = buildUrl('/transactions/count');
                const countResponse = await fetch(countUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!countResponse.ok) throw new Error(`HTTP ${countResponse.status}`);
                const { count } = await countResponse.json();
                
                totalRecords = count;
                
                // Auto-select mode based on dataset size
                if (count < CLIENT_SIDE_THRESHOLD) {
                    mode = 'client';
                    console.log(`[SearchEngine] Using CLIENT-SIDE mode (${count} records)`);
                    await loadClientSideData();
                } else {
                    mode = 'server';
                    console.log(`[SearchEngine] Using SERVER-SIDE mode (${count} records)`);
                    await loadServerSideData();
                }
                
                return Promise.resolve();
            } catch (error) {
                console.error('[SearchEngine] Failed to load:', error);
                showToast('Gagal memuat data', 'error');
                renderPage([]);
                return Promise.reject(error);
            } finally {
                isLoading = false;
                isFirstLoad = false;
                if(typeof NProgress !== 'undefined') NProgress.done();
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // CLIENT-SIDE MODE - Load all data once
        // ═══════════════════════════════════════════════════════════════
        async function loadClientSideData() {
            const url = buildUrl('/transactions/search-data');
            console.log('[SearchEngine] Fetching all data:', url);
            
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            allTransactions = await response.json();
            
            // Re-apply search if active
            const currentQuery = getActiveSearchValue();
            if (currentQuery) {
                filterClientSide(currentQuery);
            } else {
                filteredTransactions = [...allTransactions];
            }
            
            // Adjust page if out of bounds
            totalPages = Math.ceil(filteredTransactions.length / SERVER_ITEMS_PER_PAGE);
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            }
            
            renderPage();
            updateStats();
        }

        // ═══════════════════════════════════════════════════════════════
        // SERVER-SIDE MODE - Fetch page by page
        // ═══════════════════════════════════════════════════════════════
async function loadServerSideData() {
            if (abortController) abortController.abort();
            abortController = new AbortController();
            
            // --- TAMBAHAN: Efek Loading Ringan ---
            const desktopTbody = document.getElementById('desktop-tbody');
            const mobileContainer = document.getElementById('mobile-container');
            
            // Tambahkan class opacity agar tidak terlihat seperti refresh total
            desktopTbody.classList.add('opacity-40', 'transition-opacity');
            mobileContainer.classList.add('opacity-40', 'transition-opacity');
            
            if(typeof NProgress !== 'undefined') NProgress.start();

            // Synchronize search inputs value
            const searchVal = getActiveSearchValue();

            const url = buildUrl('/transactions/search', {
                page: currentPage,
                per_page: SERVER_ITEMS_PER_PAGE,
                search: searchVal
            });
            
            try {
                const response = await fetch(url, {
                    signal: abortController.signal,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const result = await response.json();
                
                filteredTransactions = result.data;
                totalRecords = result.total;
                totalPages = result.last_page;
                currentPage = result.current_page;
                
                renderPage();
                updateStats();
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('[SearchEngine] Failed:', error);
                }
            } finally {
                // --- TAMBAHAN: Kembalikan Opacity ---
                desktopTbody.classList.remove('opacity-40');
                mobileContainer.classList.remove('opacity-40');
                if(typeof NProgress !== 'undefined') NProgress.done();
            }
        }


        // ═══════════════════════════════════════════════════════════════
        // BUILD URL with filters
        // ═══════════════════════════════════════════════════════════════
        function buildUrl(endpoint, extraParams = {}) {
            const params = new URLSearchParams();
            
            // Search Query - Get from any available input
            const searchVal = getActiveSearchValue();
            if (searchVal) params.set('search', searchVal);

            // Inherit top-level filters from URL (Type & Status)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('type')) params.set('type', urlParams.get('type'));
            if (urlParams.has('status')) params.set('status', urlParams.get('status'));

            // Date Range
            const startDateEl = document.getElementById('filter-start-date');
            const endDateEl = document.getElementById('filter-end-date');
            if (startDateEl?.value) params.set('start_date', startDateEl.value);
            if (endDateEl?.value) params.set('end_date', endDateEl.value);

            // Branch (Array)
            const branchCheckboxes = document.querySelectorAll('input[name="branch_id[]"]:checked');
            branchCheckboxes.forEach(cb => params.append('branch_id[]', cb.value));

            // Category (Array)
            const categoryCheckboxes = document.querySelectorAll('input[name="category[]"]:checked');
            categoryCheckboxes.forEach(cb => params.append('category[]', cb.value));
            
            // Add extra params (like page)
            Object.entries(extraParams).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '' && key !== 'search') {
                    params.set(key, value);
                }
            });
            
            return endpoint + '?' + params.toString();
        }


        // ═══════════════════════════════════════════════════════════════
        // SEARCH - Adaptive based on mode
        // ═══════════════════════════════════════════════════════════════
        function search(query, resetPage = true) {
            clearTimeout(searchTimer);
            
            if (resetPage) currentPage = 1;
            
            if (mode === 'client') {
                filterClientSide(query);
                renderPage();
                updateStats();
            } else {
                // Jangan panggil renderSkeletons() di sini! 
                // Biarkan loadServerSideData yang menangani visual loadingnya.
                searchTimer = setTimeout(() => {
                    loadServerSideData();
                }, 300); // Debounce 300ms
            }
        }

        // Client-side filter algorithm
        function filterClientSide(query) {
            if (!query || query.trim() === '') {
                filteredTransactions = [...allTransactions];
            } else {
                const searchTerm = query.toLowerCase().trim();
                const terms = searchTerm.split(/\s+/);
                
                filteredTransactions = allTransactions.filter(transaction => {
                    return terms.every(term => transaction.search_text.includes(term));
                });
            }
            
            totalPages = Math.ceil(filteredTransactions.length / SERVER_ITEMS_PER_PAGE);
        }

        // ═══════════════════════════════════════════════════════════════
        // RENDER PAGE - Works for both modes
        // ═══════════════════════════════════════════════════════════════
        function renderPage() {
            let pageData;
            
            if (mode === 'client') {
                // Slice from filtered array
                const startIndex = (currentPage - 1) * SERVER_ITEMS_PER_PAGE;
                const endIndex = startIndex + SERVER_ITEMS_PER_PAGE;
                pageData = filteredTransactions.slice(startIndex, endIndex);
            } else {
                // Use data from server response
                pageData = filteredTransactions;
            }
            
            const startIndex = (currentPage - 1) * SERVER_ITEMS_PER_PAGE;
            
            renderDesktopTable(pageData, startIndex);
            renderMobileCards(pageData, startIndex);
            renderPagination();
            updateShowingText();
            
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function updateShowingText() {
            const startIndex = (currentPage - 1) * SERVER_ITEMS_PER_PAGE;
            const from = filteredTransactions.length > 0 ? startIndex + 1 : 0;
            const to = Math.min(startIndex + SERVER_ITEMS_PER_PAGE, 
                            mode === 'client' ? filteredTransactions.length : totalRecords);
            const total = mode === 'client' ? filteredTransactions.length : totalRecords;
            
            document.getElementById('showing-from').textContent = from;
            document.getElementById('showing-to').textContent = to;
            document.getElementById('total-records').textContent = total;
        }

        // ═══════════════════════════════════════════════════════════════
        // STATS UPDATE - Fetch from server
        // ═══════════════════════════════════════════════════════════════
        async function updateStats() {
            const url = buildUrl('/transactions/stats', { status: 'all' });
            
            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) return;
                
                const stats = await response.json();
                const statuses = ['all', 'pending', 'approved', 'completed', 'rejected', 'waiting_payment', 'flagged', 'auto_reject'];
                
                statuses.forEach(status => {
                    const el = document.querySelector(`.status-count[data-status="${status}"]`);
                    if (el && stats[status] !== undefined) {
                        el.textContent = `(${stats[status]})`;
                    }
                });
            } catch (error) {
                console.error('[Stats] Update failed:', error);
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // PAGINATION
        // ═══════════════════════════════════════════════════════════════
        function renderPagination() {
            const container = document.getElementById('pagination-container');
            const pages = mode === 'client' ? totalPages : totalPages;
            
            if (pages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            const isMobile = window.innerWidth < 640;
            const maxVisible = isMobile ? 3 : 5;
            
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(pages, startPage + maxVisible - 1);
            
            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }
            
            let html = '';
            
            // Previous
            html += `<button onclick="SearchEngine.goToPage(${currentPage - 1})" 
                        ${currentPage === 1 ? 'disabled' : ''} 
                        class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}">
                        <span class="hidden sm:inline">Prev</span>
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5 sm:hidden"></i>
                    </button>`;
            
            // First page + ellipsis
            if (startPage > 1) {
                html += `<button onclick="SearchEngine.goToPage(1)" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium hover:bg-gray-50">1</button>`;
                if (startPage > 2) html += `<span class="text-xs text-gray-400 px-0.5">…</span>`;
            }
            
            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                html += `<button onclick="SearchEngine.goToPage(${i})" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border text-xs sm:text-sm font-medium ${i === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 hover:bg-gray-50'}">
                            ${i}
                        </button>`;
            }
            
            // Last page + ellipsis
            if (endPage < pages) {
                if (endPage < pages - 1) html += `<span class="text-xs text-gray-400 px-0.5">…</span>`;
                html += `<button onclick="SearchEngine.goToPage(${pages})" 
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium hover:bg-gray-50">${pages}</button>`;
            }
            
            // Next
            html += `<button onclick="SearchEngine.goToPage(${currentPage + 1})" 
                        ${currentPage === pages ? 'disabled' : ''} 
                        class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-gray-200 text-xs sm:text-sm font-medium ${currentPage === pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}">
                        <span class="hidden sm:inline">Next</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 sm:hidden"></i>
                    </button>`;
            
            container.innerHTML = html;
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: container });
        }

        function goToPage(page) {
            const pages = mode === 'client' ? totalPages : totalPages;
            if (page < 1 || page > pages) return;
            
            currentPage = page;
            
            if (mode === 'client') {
                renderPage();
            } else {
                loadServerSideData();
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // SKELETON LOADERS
        // ═══════════════════════════════════════════════════════════════
        function renderSkeletons() {
            const tbody = document.getElementById('desktop-tbody');
            const container = document.getElementById('mobile-container');
            document.getElementById('table-no-results')?.classList.add('hidden');
            document.getElementById('mobile-no-results')?.classList.add('hidden');
            
            tbody.innerHTML = Array(6).fill(`
                <tr class="animate-pulse bg-white border-b border-gray-50">
                    <td class="px-4 py-4 hidden xl:table-cell"><div class="h-4 bg-slate-200 rounded w-6 mx-auto"></div></td>
                    <td class="px-5 py-4"><div class="flex gap-3 items-center"><div class="w-8 h-8 rounded-full bg-slate-200 shrink-0"></div><div><div class="h-4 bg-slate-200 rounded w-28 mb-1.5"></div><div class="h-3 bg-slate-100 rounded w-16"></div></div></div></td>
                    <td class="px-5 py-4"><div class="h-6 bg-slate-100 rounded-lg w-20 border border-slate-200"></div></td>
                    <td class="px-5 py-4 hidden lg:table-cell"><div class="h-4 bg-slate-200 rounded w-24"></div></td>
                    <td class="px-5 py-4"><div class="h-6 bg-slate-100 rounded-full w-24 border border-slate-200"></div></td>
                    <td class="px-5 py-4 hidden xl:table-cell"><div class="h-4 bg-slate-200 rounded w-20"></div></td>
                    <td class="px-5 py-4"><div class="h-5 bg-slate-200 rounded w-24"></div></td>
                    <td class="px-5 py-4"><div class="flex justify-center gap-1"><div class="w-8 h-8 rounded-lg bg-slate-200 shrink-0"></div><div class="w-8 h-8 rounded-lg bg-slate-200 shrink-0"></div></div></td>
                </tr>
            `).join('');

            container.innerHTML = Array(4).fill(`
                <div class="p-3 sm:p-4 animate-pulse bg-white border-b border-gray-100">
                    <div class="flex justify-between items-start gap-2 mb-2"><div class="flex items-center gap-2.5"><div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-200 shrink-0"></div><div><div class="h-3.5 bg-slate-200 rounded w-20 sm:w-24 mb-1.5"></div><div class="h-2.5 bg-slate-100 rounded w-14 sm:w-16"></div></div></div><div class="h-5 bg-slate-100 rounded-md w-14 sm:w-16 border border-slate-200"></div></div>
                    <div class="ml-[42px] sm:ml-[48px]"><div class="h-2.5 bg-slate-100 rounded w-40 sm:w-48 mb-2"></div>
                    <div class="h-5 bg-slate-200 rounded w-28 sm:w-32 mb-2"></div>
                    <div class="flex gap-1.5"><div class="h-7 sm:h-8 bg-slate-100 rounded-lg w-16 sm:w-20 border border-slate-200"></div><div class="h-7 sm:h-8 bg-slate-100 rounded-lg w-14 sm:w-16 border border-slate-200"></div></div></div>
                </div>
            `).join('');
        }

        // ═══════════════════════════════════════════════════════════════
        // RENDER FUNCTIONS (Same as before, preserved from Document #1)
        // ═══════════════════════════════════════════════════════════════
        
        function renderDesktopTable(data, startIndex = 0) {
            const tbody = document.getElementById('desktop-tbody');
            const noResults = document.getElementById('table-no-results');
            
            if (data.length === 0) {
                tbody.innerHTML = '';
                noResults?.classList.remove('hidden');
                const query = document.getElementById('instant-search').value;
                if (document.getElementById('no-result-query')) {
                    document.getElementById('no-result-query').textContent = query;
                }
            } else {
                noResults?.classList.add('hidden');
                tbody.innerHTML = data.map((t, i) => generateDesktopRow(t, startIndex + i + 1)).join('');
                
                // ✅ Update Lucide Icons for dynamic content
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: tbody });
            }
        }

        function renderMobileCards(data, startIndex = 0) {
            const container = document.getElementById('mobile-container');
            const noResults = document.getElementById('mobile-no-results');
            
            if (data.length === 0) {
                container.innerHTML = '';
                noResults?.classList.remove('hidden');
                const query = document.getElementById('instant-search').value;
                if (document.getElementById('mobile-no-result-query')) {
                    document.getElementById('mobile-no-result-query').textContent = query;
                }
            } else {
                noResults?.classList.add('hidden');
                container.innerHTML = data.map((t, i) => generateMobileCard(t, startIndex + i + 1)).join('');

                // ✅ Update Lucide Icons for dynamic content
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: container });
            }
        }

        // Helper: Branch Tags Rendering
        function renderBranchTags(branches, maxVisible = 2) {
            if (!branches || branches.length === 0) return '';
            const icon = '<i data-lucide="git-branch" class="w-2.5 h-2.5 mr-0.5"></i>';
            const visibleTags = branches.slice(0, maxVisible).map(b =>
                `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200">${icon} ${escapeHtml(b)}</span>`
            ).join('');
            
            if (branches.length <= maxVisible) return visibleTags;
            
            const remaining = branches.length - maxVisible;
            const hiddenItems = branches.slice(maxVisible).map(b => 
                `<div class="flex items-center gap-1.5 py-0.5">
                    <span class="w-1 h-1 rounded-full bg-blue-400"></span>
                    <span>${escapeHtml(b)}</span>
                </div>`
            ).join('');

            return visibleTags + `
                <div class="relative inline-block group/branch ml-0.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-200 cursor-help hover:bg-blue-100 transition-colors">
                        +${remaining}
                    </span>
                    <!-- Tooltip Premium -->
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 opacity-0 invisible group-hover/branch:opacity-100 group-hover/branch:visible transition-all duration-200 z-[100] pointer-events-none">
                        <div class="bg-slate-900 text-white p-2.5 rounded-xl shadow-2xl border border-white/10 backdrop-blur-md">
                            <div class="flex items-center gap-1.5 mb-1.5 pb-1.5 border-b border-white/10">
                                <i data-lucide="git-branch" class="w-3 h-3 text-blue-400"></i>
                                <span class="text-[10px] font-bold text-gray-400 tracking-wider">CABANG LAINNYA</span>
                            </div>
                            <div class="text-[11px] font-semibold text-gray-200 max-h-48 overflow-y-auto custom-scrollbar">
                                ${hiddenItems}
                            </div>
                        </div>
                        <!-- Arrow -->
                        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                    </div>
                </div>`;
        }

        // Helper: Escape HTML
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // ═══════════════════════════════════════════════════════════════
        // ROW GENERATORS (Preserved from Document #1 - with Price Index)
        // ═══════════════════════════════════════════════════════════════

        function generateDesktopRow(t, rowNum = '') {
            const isDebtPending = t.status === 'waiting_payment' && t.status_label === 'Menunggu Pelunasan';
            const isGudang = t.type === 'gudang';
            const isLargePengajuan = t.type === 'pengajuan' && t.effective_amount >= 1000000;

            const statusBadge = {
                pending:   isGudang ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200',
                approved:  'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                rejected:  'bg-red-50 text-red-700 border-red-200',
                waiting_payment: isDebtPending ? 'bg-amber-50 text-amber-700 border-amber-200' : (isGudang ? 'bg-slate-50 text-slate-700 border-slate-200' : 'bg-orange-50 text-orange-700 border-orange-200'),
                pending_technician: 'bg-teal-50 text-teal-700 border-teal-200',
                flagged:   'bg-rose-50 text-rose-700 border-rose-200',
                'auto-reject': 'bg-gray-800 text-gray-50 border-gray-900',
            };

            const statusIcon = {
                pending:   isGudang ? 'search' : 'clock',
                approved:  'shield-alert',
                completed: 'check-circle-2',
                rejected:  'x-circle',
                waiting_payment: isDebtPending ? 'wallet' : (isGudang ? 'store' : 'credit-card'),
                pending_technician: 'package-check',
                flagged:   'flag',
                'auto-reject': 'bot',
            };

            const statusLabel = {
                pending:   isGudang ? 'Review Management' : 'Pending',
                approved:  'Menunggu Owner',
                completed: 'Selesai',
                rejected:  'Ditolak',
                waiting_payment: t.status_label || (isGudang ? 'Belum Dibayar' : 'Menunggu Pembayaran'),
                flagged:   'Flagged',
                'auto-reject': 'Auto Reject',
            };

            const aiBadgeHtml = generateAIBadge(t);
            const inlineActionsHtml = generateInlineActions(t);

            return `
                <tr class="hover:bg-blue-50/30 transition-all duration-200 group">
                    <td class="px-5 py-4 text-center hidden xl:table-cell"><span class="text-xs font-bold text-slate-400">${rowNum}</span></td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 shrink-0">
                                ${(t.submitter_name ? t.submitter_name.charAt(0) : '?').toUpperCase()}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">${t.submitter_name || '-'}</div>
                                <div class="text-[11px] text-gray-400 font-medium">${t.invoice_number}</div>
                                ${t.branches && t.branches.length > 0 ? `<div class="flex items-center gap-1 mt-1 flex-wrap">${renderBranchTags(t.branches, 2)}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex flex-col items-start gap-1">
                            ${t.type === 'pengajuan' 
                                ? '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold bg-teal-50 text-teal-600 border border-teal-100"><i data-lucide="shopping-bag" class="w-3 h-3"></i> Pengajuan</span>'
                                : (t.type === 'gudang'
                                    ? '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-600 border border-amber-100"><i data-lucide="package" class="w-3 h-3"></i> Gudang</span>'
                                    : '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-600 border border-indigo-100"><i data-lucide="receipt" class="w-3 h-3"></i> Rembush</span>'
                                )}

                            ${t.has_price_anomaly 
                                ? `<span class="flex items-center gap-1 text-[10px] font-medium text-red-500" title="Anomaly">
                                    <i data-lucide="alert-triangle" class="w-3 h-3"></i> Anomali Harga
                                </span>` 
                                : ''
                            }
                        </div>
                    </td>
                    <td class="px-5 py-4 text-gray-700 font-medium text-xs whitespace-nowrap hidden lg:table-cell">${t.category_label}</td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border ${statusBadge[t.status] || 'bg-gray-50 text-gray-700 border-gray-200'}">
                                <i data-lucide="${statusIcon[t.status] || 'info'}" class="w-3 h-3"></i>
                                ${statusLabel[t.status] || t.status}
                            </span>
                            ${aiBadgeHtml}
                            ${inlineActionsHtml}
                        </div>
                    </td>
                    <td class="px-5 py-4 text-gray-500 font-medium text-xs whitespace-nowrap hidden xl:table-cell">${t.created_at}</td>
                    <td class="px-5 py-4 font-bold text-gray-900 whitespace-nowrap">Rp ${t.formatted_amount}</td>
                    <td class="px-5 py-4 whitespace-nowrap">
                        <div class="flex items-center justify-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
                            <button type="button" onclick="openViewModal(${t.id})" title="Lihat Detail"
                                class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 active:scale-95 transition-all outline-none">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            ${canManage ? `
                                ${buildEditButton(t, 'desktop')}
                                ${(!isAdmin) ? `
                                    <button type="button" onclick="confirmDeleteTransaction(${t.id}, '${t.invoice_number}')" title="Hapus"
                                            class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 active:scale-95 transition-all outline-none">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                ` : ''}
                            ` : ''}
                        </div>
                    </td>
                </tr>`;
        }

        function buildEditButton(t, style = 'desktop') {
            const label = 'Edit';
            const icon  = 'pencil';
            if (style === 'desktop') {
                return `<a href="/transactions/${t.id}/edit" title="${label}"
                    class="p-2 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 active:scale-95 transition-all outline-none">
                    <i data-lucide="${icon}" class="w-4 h-4"></i>
                </a>`;
            } else {
                return `<a href="/transactions/${t.id}/edit"
                    class="flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-200 text-slate-500 rounded-lg hover:text-amber-600 hover:border-amber-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                    <i data-lucide="${icon}" class="w-3 h-3"></i> ${label}
                </a>`;
            }
        }

        function generateMobileCard(t, rowNum = '') {
            const isDebtPending = t.status === 'waiting_payment' && t.status_label === 'Menunggu Pelunasan';
            const isGudang = t.type === 'gudang';
            const isLargePengajuan = t.type === 'pengajuan' && t.effective_amount >= 1000000;

            const mStatusBadge = {
                pending:   isGudang ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200',
                approved:  'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                rejected:  'bg-red-50 text-red-700 border-red-200',
                waiting_payment: isDebtPending ? 'bg-amber-50 text-amber-700 border-amber-200' : (isGudang ? 'bg-slate-50 text-slate-700 border-slate-200' : 'bg-orange-50 text-orange-700 border-orange-200'),
                pending_technician: 'bg-teal-50 text-teal-700 border-teal-200',
                flagged:   'bg-rose-50 text-rose-700 border-rose-200',
                'auto-reject': 'bg-gray-800 text-gray-50 border-gray-900',
            };

            const mStatusIcon = {
                pending:   isGudang ? 'search' : 'clock',
                approved:  'shield-alert',
                completed: 'check-circle-2',
                rejected:  'x-circle',
                waiting_payment: isDebtPending ? 'wallet' : (isGudang ? 'store' : 'credit-card'),
                pending_technician: 'package-check',
                flagged:   'flag',
                'auto-reject': 'bot',
            };

            const mStatusLabel = {
                pending:   isGudang ? 'Review' : 'Pending',
                approved:  'Menunggu Owner',
                completed: 'Selesai',
                rejected:  'Ditolak',
                waiting_payment: t.status_label || (isGudang ? 'Belum Bayar' : 'Belum Bayar'),
                flagged:   'Flagged',
                'auto-reject': 'Auto Reject',
            };

            const aiBadgeHtml = generateAIBadge(t);
            const mobileActionsHtml = generateMobileActions(t);

            return `
                <div class="tx-card px-3 sm:px-4 py-3 sm:py-3.5 border-b border-gray-100">
                    <div class="flex items-start gap-2.5 mb-2">
                        <div class="relative shrink-0 mt-0.5">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center text-xs font-bold text-slate-600">
                                ${(t.submitter_name ? t.submitter_name.charAt(0) : '?').toUpperCase()}
                            </div>
                            ${rowNum ? `<span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 flex items-center justify-center text-[7px] font-bold bg-slate-500 text-white rounded-full">${rowNum}</span>` : ''}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h5 class="font-bold text-slate-800 text-[13px] leading-snug truncate">${t.submitter_name || '-'}</h5>
                                    <p class="text-[10px] font-medium text-slate-400 truncate">${t.invoice_number}</p>
                                </div>
                                <div class="flex flex-col items-end gap-0.5 shrink-0">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold tracking-wide border ${mStatusBadge[t.status] || 'bg-gray-50 text-gray-700 border-gray-200'}">
                                        <i data-lucide="${mStatusIcon[t.status] || 'info'}" class="w-2 h-2"></i>
                                        ${mStatusLabel[t.status] || t.status}
                                    </span>
                                    ${aiBadgeHtml}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 flex-wrap text-[10px] text-slate-400 mb-3 pl-10 sm:pl-11">
                        <div class="flex items-center gap-1.5 flex-wrap flex-1">
                            ${t.type === 'pengajuan'
                                ? '<span class="inline-flex items-center gap-0.5 text-[9px] font-bold text-teal-600 px-1.5 py-0.5 rounded-md bg-teal-50 border border-teal-100"><i data-lucide="shopping-bag" class="w-2.5 h-2.5"></i> Pengajuan</span>'
                                : (t.type === 'gudang'
                                    ? '<span class="inline-flex items-center gap-0.5 text-[9px] font-bold text-amber-600 px-1.5 py-0.5 rounded-md bg-amber-50 border border-amber-100"><i data-lucide="package" class="w-2.5 h-2.5"></i> Gudang</span>'
                                    : '<span class="inline-flex items-center gap-0.5 text-[9px] font-bold text-indigo-600 px-1.5 py-0.5 rounded-md bg-indigo-50 border border-indigo-100"><i data-lucide="receipt" class="w-2.5 h-2.5"></i> Rembush</span>'
                                )}
                            <span class="text-slate-300">/</span>
                            <span class="font-bold text-slate-500">${t.category_label}</span>
                        </div>
                        <span class="font-medium text-slate-400 whitespace-nowrap">${t.created_at}</span>
                    </div>

                    <div class="flex items-center justify-between gap-2 pl-10 sm:pl-11 mb-2.5">
                        <p class="font-black text-slate-800 text-[15px] sm:text-base tracking-tight truncate">Rp ${t.formatted_amount}</p>
                        ${mobileActionsHtml ? `<div class="flex items-center gap-1.5 shrink-0">${mobileActionsHtml}</div>` : ''}
                    </div>

                    <div class="flex items-center gap-1.5 flex-wrap pl-10 sm:pl-11">
                        <button type="button" onclick="openViewModal(${t.id})"
                            class="flex items-center gap-1 px-2.5 py-1.5 bg-white border border-slate-200 text-slate-500 rounded-lg hover:text-blue-600 hover:border-blue-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                            <i data-lucide="eye" class="w-3 h-3"></i> Lihat
                        </button>
                        ${canManage ? `
                            ${buildEditButton(t, 'mobile')}
                            ${(!isAdmin) ? `
                                <button type="button" onclick="confirmDeleteTransaction(${t.id}, '${t.invoice_number}')"
                                        class="flex items-center gap-1 px-2 py-1.5 bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-red-500 hover:border-red-300 active:scale-95 transition-all text-[11px] font-semibold outline-none">
                                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                                </button>
                            ` : ''}
                        ` : ''}
                    </div>
                </div>`;
        }

        function generateAIBadge(t) {
            if (t.type !== 'rembush' || !['queued', 'pending', 'processing', 'completed', 'error'].includes(t.ai_status)) return '';
            const badges = {
                queued:     { color: 'bg-gray-50 text-gray-600 border-gray-200', icon: 'clock', label: 'Antrian' },
                pending:    { color: 'bg-gray-50 text-gray-600 border-gray-200', icon: 'clock', label: 'Pending' },
                processing: { color: 'bg-purple-50 text-purple-600 border-purple-200', icon: 'loader-2', label: 'OCR...', pulse: true },
                completed:  { color: 'bg-green-50 text-green-600 border-green-200', icon: 'check-circle', label: 'AI ✓' },
                error:      { color: 'bg-red-50 text-red-600 border-red-200', icon: 'alert-circle', label: 'AI ✗' },
            };
            const aiBadge = badges[t.ai_status];

            return `
                <span class="ai-status-badge inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[9px] font-bold border ml-1 ${aiBadge.color} ${aiBadge.pulse ? 'animate-pulse' : ''}">
                    <i data-lucide="${aiBadge.icon}" class="w-2.5 h-2.5 ${aiBadge.pulse ? 'animate-spin' : ''}"></i>
                    ${aiBadge.label}
                </span>`;
        }

function generateInlineActions(t) {
            if (!canManage) return '';

            let html = '';
            const isPengajuan = t.type === 'pengajuan';
            const canApprovePengajuan = isOwner || userRole === 'atasan';

            if (t.status === 'pending') {
                // For Pengajuan, only Atasan/Owner can approve
                if (isPengajuan && !canApprovePengajuan) {
                    return '';
                }

                const approveTitle = (!isPengajuan && t.effective_amount >= 1000000) ? 'Setujui (Menunggu Owner)' : 'Setujui';
                html = `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                            class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white border border-green-200 hover:border-green-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="check" class="w-3 h-3"></i>
                        </button>
                        <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                            class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            } else if (isOwner && t.status === 'approved') {
                const approveTitle = 'Approve Final';
                html = `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                            class="p-1.5 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white border border-green-200 hover:border-green-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="check" class="w-3 h-3"></i>
                        </button>
                        <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                            class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 hover:border-red-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            }

            // New Action Buttons
            if (t.status === 'auto-reject' && (canManage || isOwner)) {
                html += `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="openOverrideModal(${t.id}, '${t.invoice_number}')" title="Request Override"
                            class="p-1.5 rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white border border-orange-200 hover:border-orange-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            } else if (t.status === 'waiting_payment' && canManage) {
                // ✅ Only show Upload button if NO payment proof exists yet
                // AND there are no pending inter-branch debts
                const hasPaymentProof = !!(t.invoice_file_path || t.bukti_transfer || t.foto_penyerahan);
                const isDebtPending = t.status_label === 'Menunggu Pelunasan';
                
                if (!hasPaymentProof && !isDebtPending) {
                    html += `
                        <div class="flex items-center gap-1 ml-1">
                            <button type="button" onclick="openPaymentModal(${t.id})" title="Proses Pembayaran"
                                class="p-1.5 rounded-lg bg-cyan-50 text-cyan-600 hover:bg-cyan-600 hover:text-white border border-cyan-200 hover:border-cyan-600 active:scale-90 transition-all outline-none">
                                <i data-lucide="upload-cloud" class="w-3 h-3"></i>
                            </button>
                        </div>
                    `;
                }
            } else if (t.status === 'flagged' && (canManage || isOwner)) {
                html += `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="openForceApproveModal(${t.id}, '${t.invoice_number}')" title="Force Approve"
                            class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="shield-alert" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            } else if (t.status === 'Menunggu Konfirmasi Teknisi' && userRole === 'teknisi') {
                html += `
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" onclick="confirmCashPayment(${t.id}, 'terima')" title="Terima Uang"
                            class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 hover:border-emerald-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                        </button>
                        <button type="button" onclick="confirmCashPayment(${t.id}, 'tolak')" title="Tolak / Terdapat Kendala"
                            class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 active:scale-90 transition-all outline-none">
                            <i data-lucide="x-circle" class="w-3 h-3"></i>
                        </button>
                    </div>
                `;
            }

            return html;
        }

        function generateMobileActions(t) {
            if (!canManage) return '';

            let showActions = false;
            let approveTitle = 'Setujui';
            const isPengajuan = t.type === 'pengajuan';
            const canApprovePengajuan = isOwner || userRole === 'atasan';

            if (t.status === 'pending') {
                if (isPengajuan) {
                    if (canApprovePengajuan) {
                        showActions = true;
                        approveTitle = 'Setujui';
                    }
                } else {
                    showActions = true;
                    approveTitle = t.effective_amount >= 1000000 ? 'Setujui (Menunggu Owner)' : 'Setujui';
                }
            } else if (isOwner && t.status === 'approved') {
                showActions = true;
                approveTitle = 'Approve Final';
            }

            let extraActionHtml = '';
            if (t.status === 'auto-reject' && (canManage || isOwner)) {
                extraActionHtml = `
                    <button type="button" onclick="openOverrideModal(${t.id}, '${t.invoice_number}')" title="Override"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-orange-200 hover:border-orange-600 outline-none">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Override</span>
                    </button>
                `;
            } else if (t.status === 'waiting_payment' && canManage) {
                // ✅ Only show Upload button if NO payment proof exists yet
                // AND there are no pending inter-branch debts
                const hasPaymentProof = !!(t.invoice_file_path || t.bukti_transfer || t.foto_penyerahan);
                const isDebtPending = t.status_label === 'Menunggu Pelunasan';
                
                if (!hasPaymentProof && !isDebtPending) {
                    extraActionHtml = `
                        <button type="button" onclick="openPaymentModal(${t.id})" title="Upload Bukti"
                            class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-cyan-50 text-cyan-700 hover:bg-cyan-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-cyan-200 hover:border-cyan-600 outline-none">
                            <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Upload Bukti</span>
                        </button>
                    `;
                }
            } else if (t.status === 'flagged' && (canManage || isOwner)) {
                extraActionHtml = `
                    <button type="button" onclick="openForceApproveModal(${t.id}, '${t.invoice_number}')" title="Force Approve"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Force Approve</span>
                    </button>
                `;
            } else if (t.status === 'Menunggu Konfirmasi Teknisi' && userRole === 'teknisi') {
                extraActionHtml = `
                    <button type="button" onclick="confirmCashPayment(${t.id}, 'terima')" title="Terima"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-emerald-200 hover:border-emerald-600 outline-none">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    </button>
                    <button type="button" onclick="confirmCashPayment(${t.id}, 'tolak')" title="Tolak"
                        class="flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-bold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                    </button>
                `;
            }

            if (!showActions && !extraActionHtml) return '';

            if (extraActionHtml) {
                return extraActionHtml;
            }

            return `
                <button type="button" onclick="performStatusAction(${t.id}, 'approved', this)" title="${approveTitle}"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-semibold text-[11px] active:scale-95 transition-all border border-emerald-200 hover:border-emerald-600 outline-none">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">${approveTitle}</span>
                </button>
                <button type="button" onclick="openRejectModal(${t.id}, '${t.invoice_number}')" title="Tolak"
                    class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white font-semibold text-[11px] active:scale-95 transition-all border border-rose-200 hover:border-rose-600 outline-none">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Tolak</span>
                </button>
            `;
        }   
        

        function addTransaction(transaction) {
            if (mode === 'client') {
                if (!allTransactions.some(t => t.id === transaction.id)) {
                    allTransactions.unshift(transaction);
                    const query = document.getElementById('instant-search').value.trim();
                    search(query);
                }
            } else {
                // Server-side: just reload current page
                loadServerSideData();
            }
        }

        function updateTransaction(transaction) {
            if (mode === 'client') {
                const index = allTransactions.findIndex(t => t.id === transaction.id);
                if (index !== -1) {
                    allTransactions[index] = transaction;
                    const query = document.getElementById('instant-search').value.trim();
                    search(query, false);
                } else {
                    addTransaction(transaction);
                }
            } else {
                loadServerSideData();
            }
        }

        function deleteTransaction(id) {
            if (mode === 'client') {
                allTransactions = allTransactions.filter(t => t.id != id);
                const query = document.getElementById('instant-search').value.trim();
                search(query, false);
            } else {
                loadServerSideData();
            }
        }

        // Public API
        return {
            init: loadData,
            search: search,
            goToPage: goToPage,
            refresh: loadData,
            getAll: () => mode === 'client' ? allTransactions : filteredTransactions,
            getFiltered: () => filteredTransactions,
            addTransaction: addTransaction,
            updateTransaction: updateTransaction,
            deleteTransaction: deleteTransaction,
            getMode: () => mode
        };
    })();

    // ═══════════════════════════════════════════════════════════════
    // IMAGE VIEWER MODAL
    // ═══════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        const imageViewer = document.getElementById('image-viewer');
        const viewerImage = document.getElementById('viewer-image');
        const closeViewer = document.getElementById('close-viewer');
        let lastFocusedElement = null;

        window.openImageViewer = function(src, title = null, forcePdf = false) {
            lastFocusedElement = document.activeElement;
            const isPdf = forcePdf || src.toLowerCase().endsWith('.pdf') || src.startsWith('data:application/pdf');
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            const viewerPdf    = document.getElementById('viewer-pdf');
            const viewerFooter = document.getElementById('viewer-footer');
            const viewerPdfLink= document.getElementById('viewer-pdf-link');

            if (isPdf) {
                if (isMobile) {
                    window.open(src, '_blank');
                    return;
                }
                viewerImage.classList.add('hidden');
                viewerPdf.classList.remove('hidden');
                viewerPdf.src = src;
                viewerFooter.classList.remove('hidden');
                viewerPdfLink.href = src;
                document.getElementById('viewer-header-title').textContent = 'PREVIEW DOKUMEN PDF';
            } else {
                viewerImage.classList.remove('hidden');
                viewerPdf.classList.add('hidden');
                viewerFooter.classList.add('hidden');
                viewerImage.src = src;
                document.getElementById('viewer-header-title').textContent = 'PREVIEW FOTO';
            }

            imageViewer.classList.remove('hidden');
            imageViewer.classList.add('flex');
            requestAnimationFrame(() => {
                imageViewer.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: imageViewer });
                setTimeout(() => closeViewer?.focus(), 50);
            });
        };

        window.closeImageViewer = function() {
            if (document.activeElement && imageViewer.contains(document.activeElement)) document.activeElement.blur();
            imageViewer.classList.add('hidden');
            imageViewer.classList.remove('flex');
            document.body.style.overflow = '';
            imageViewer.setAttribute('aria-hidden', 'true');
            setTimeout(() => { 
                viewerImage.src = '';
                const vPdf = document.getElementById('viewer-pdf');
                if (vPdf) {
                    vPdf.src = '';
                    vPdf.classList.add('hidden');
                }
                const vFooter = document.getElementById('viewer-footer');
                if (vFooter) vFooter.classList.add('hidden');
                
                if (lastFocusedElement?.focus) lastFocusedElement.focus();
            }, 200);
        };

        if(closeViewer) closeViewer.addEventListener('click', e => { e.stopPropagation(); closeImageViewer(); });
        if(imageViewer) imageViewer.addEventListener('click', e => { if (e.target === imageViewer) closeImageViewer(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !imageViewer.classList.contains('hidden')) closeImageViewer(); });

        // ═══════════════════════════════════════════════════════════════
        // UI & FILTER LOGIC - NEW PREMIUM SYSTEM
        // ═══════════════════════════════════════════════════════════════
        
        // --- Popover DOM Containment Fix ---
        // Move popovers to body to avoid CSS transform/relative-containingblocks causing double offsets
        const popoverContainer = document.getElementById('popover-container');
        if (popoverContainer && popoverContainer.parentNode !== document.body) {
            document.body.appendChild(popoverContainer);
        }

        // --- Popover Management ---
        const popovers = {
            branch: { 
                btn: document.getElementById('btn-filter-branch'), 
                menu: document.getElementById('menu-filter-branch') 
            },
            category: { 
                btn: document.getElementById('btn-filter-category'), 
                menu: document.getElementById('menu-filter-category') 
            },
            date: { 
                btn: document.getElementById('btn-filter-date'), 
                menu: document.getElementById('menu-filter-date') 
            }
        };

        function closeAllPopovers() {
            Object.values(popovers).forEach(p => {
                if (!p.menu) return;
                p.menu.classList.add('hidden', 'opacity-0', 'scale-95');
                p.menu.classList.remove('opacity-100', 'scale-100');
                if (p.btn) p.btn.setAttribute('aria-expanded', 'false');
            });
        }

        // --- Popover Trigger Logic ---
        document.addEventListener('click', function(e) {
            // 1. Ignore if clicking a reset button (Reset logic handles this)
            if (e.target.closest('.js-filter-branch-reset, .js-filter-category-reset, .js-filter-date-reset')) {
                return;
            }
            
            const trigger = e.target.closest('.filter-trigger');
            
            // 2. Toggling logic if clicking a trigger
            if (trigger) {
                e.stopPropagation();
                const targetId = trigger.getAttribute('data-target');
                const targetMenu = document.getElementById(targetId);
                
                if (targetMenu) {
                    const isOpen = !targetMenu.classList.contains('hidden');
                    
                    // Close others first
                    document.querySelectorAll('.filter-popover').forEach(m => {
                        if (m !== targetMenu) {
                            m.classList.add('hidden', 'opacity-0', 'scale-95');
                            m.classList.remove('opacity-100', 'scale-100');
                        }
                    });
                    document.querySelectorAll('.filter-trigger').forEach(t => {
                        if (t !== trigger) t.classList.remove('active');
                    });
                    
                    if (isOpen) {
                        targetMenu.classList.add('hidden', 'opacity-0', 'scale-95');
                        targetMenu.classList.remove('opacity-100', 'scale-100');
                        trigger.classList.remove('active');
                    } else {
                        targetMenu.classList.remove('hidden');
                        trigger.classList.add('active');
                        
                        // Positioning
                        const rect = trigger.getBoundingClientRect();
                        const isMobile = window.innerWidth < 768;
                        
                        if (!isMobile) {
                            targetMenu.style.top = (rect.bottom + 8) + 'px';
                            
                            // Get natural width to calculate boundary
                            targetMenu.style.display = 'block'; // temp show for width
                            const menuWidth = targetMenu.offsetWidth || 300;
                            targetMenu.style.display = '';

                            let leftPos = rect.left;
                            if (leftPos + menuWidth > window.innerWidth) {
                                leftPos = window.innerWidth - menuWidth - 20;
                            }
                            
                            targetMenu.style.left = leftPos + 'px';
                            targetMenu.style.right = 'auto';
                            targetMenu.style.width = ''; // Let it use its own width classes
                        } else {
                            targetMenu.style.top = (rect.bottom + 8) + 'px';
                            targetMenu.style.left = '10px';
                            targetMenu.style.right = 'auto';
                            targetMenu.style.width = 'calc(100vw - 20px)';
                        }

                        targetMenu.offsetHeight; // force reflow
                        requestAnimationFrame(() => {
                            targetMenu.classList.remove('opacity-0', 'scale-95');
                            targetMenu.classList.add('opacity-100', 'scale-100');
                        });
                        
                        const searchInput = targetMenu.querySelector('input[type="text"]');
                        if (searchInput) setTimeout(() => searchInput.focus(), 100);
                    }
                }
                return;
            }

            // 3. Close all if clicking elsewhere (not on popover or trigger)
            if (!e.target.closest('.filter-popover')) {
                document.querySelectorAll('.filter-popover').forEach(m => {
                    m.classList.add('hidden', 'opacity-0', 'scale-95');
                    m.classList.remove('opacity-100', 'scale-100');
                });
                document.querySelectorAll('.filter-trigger').forEach(t => t.classList.remove('active'));
            }
        });

        // Global listeners for search sync
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('search-input-sync')) {
                const val = e.target.value;
                syncSearchInputs(val);
                SearchEngine.search(val);
            }
        });

        document.addEventListener('click', function(e) {
            const clearBtn = e.target.closest('.search-clear') || (e.target.id === 'search-clear' ? e.target : null);
            if (clearBtn) {
                syncSearchInputs('');
                SearchEngine.search('');
            }
        });

        // REDUNDANT LISTENER REMOVED TO PREVENT POP-UP REGRESSION

        // --- Multi-select Logic (Branch & Category) ---
        function updateFilterIndicators() {
            // Branch Indicator
            const branchCheckboxes = document.querySelectorAll('input[name="branch_id[]"]:checked');
            const branchBtns = document.querySelectorAll('.js-filter-branch-btn');
            const branchLabels = document.querySelectorAll('.js-filter-branch-label');
            const branchResets = document.querySelectorAll('.js-filter-branch-reset');
            
            if (branchCheckboxes.length > 0) {
                const firstLabel = branchCheckboxes[0].closest('label').querySelector('span').textContent.trim();
                const text = branchCheckboxes.length === 1 ? firstLabel : `${firstLabel} +${branchCheckboxes.length - 1}`;
                
                branchLabels.forEach(el => {
                    el.textContent = text;
                    el.classList.add('text-blue-600');
                });
                branchBtns.forEach(btn => {
                    btn.classList.add('border-blue-200', 'bg-blue-50/50', 'active');
                });
                branchResets.forEach(el => el.classList.remove('hidden'));
            } else {
                branchLabels.forEach(el => {
                    el.textContent = (window.innerWidth < 1024) ? 'Pilih Cabang' : 'Semua Cabang';
                    el.classList.remove('text-blue-600');
                });
                branchBtns.forEach(btn => {
                    btn.classList.remove('border-blue-200', 'bg-blue-50/50', 'active');
                });
                branchResets.forEach(el => el.classList.add('hidden'));
            }

            // Category Indicator
            const categoryCheckboxes = document.querySelectorAll('input[name="category[]"]:checked');
            const categoryBtns = document.querySelectorAll('.js-filter-category-btn');
            const categoryLabels = document.querySelectorAll('.js-filter-category-label');
            const categoryResets = document.querySelectorAll('.js-filter-category-reset');
            
            if (categoryCheckboxes.length > 0) {
                const firstLabel = categoryCheckboxes[0].closest('label').querySelector('span').textContent.trim();
                const text = categoryCheckboxes.length === 1 ? firstLabel : `${firstLabel} +${categoryCheckboxes.length - 1}`;
                
                categoryLabels.forEach(el => {
                    el.textContent = text;
                    el.classList.add('text-blue-600');
                });
                categoryBtns.forEach(btn => {
                    btn.classList.add('border-blue-200', 'bg-blue-50/50', 'active');
                });
                categoryResets.forEach(el => el.classList.remove('hidden'));
            } else {
                categoryLabels.forEach(el => {
                    el.textContent = (window.innerWidth < 1024) ? 'Pilih Kategori' : 'Semua Kategori';
                    el.classList.remove('text-blue-600');
                });
                categoryBtns.forEach(btn => {
                    btn.classList.remove('border-blue-200', 'bg-blue-50/50', 'active');
                });
                categoryResets.forEach(el => el.classList.add('hidden'));
            }

            // Date Indicator
            const startDate = document.getElementById('filter-start-date').value;
            const endDate = document.getElementById('filter-end-date').value;
            const dateBtns = document.querySelectorAll('.js-filter-date-btn');
            const dateLabels = document.querySelectorAll('.js-filter-date-label');
            const dateResets = document.querySelectorAll('.js-filter-date-reset');

            if (startDate || endDate) {
                const formatDateStr = (dateString) => {
                    if (!dateString) return '';
                    const options = { day: 'numeric', month: 'short', year: 'numeric' };
                    return new Date(dateString + 'T00:00:00Z').toLocaleDateString('id-ID', options);
                };

                const formattedStart = formatDateStr(startDate);
                const formattedEnd = formatDateStr(endDate);
                const text = (startDate && endDate) ? `${formattedStart} — ${formattedEnd}` : (formattedStart || formattedEnd);

                dateLabels.forEach(el => {
                    el.textContent = text;
                    el.classList.add('text-blue-600');
                });
                dateBtns.forEach(btn => {
                    btn.classList.add('border-blue-200', 'bg-blue-50/50', 'active');
                });
                dateResets.forEach(el => el.classList.remove('hidden'));
            } else {
                dateLabels.forEach(el => {
                    el.textContent = 'Pilih Tanggal';
                    el.classList.remove('text-blue-600');
                });
                dateBtns.forEach(btn => {
                    btn.classList.remove('border-blue-200', 'bg-blue-50/50', 'active');
                });
                dateResets.forEach(el => el.classList.add('hidden'));
            }
            
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Export to global for other scripts
        window.updateFilterIndicators = updateFilterIndicators;

        // Event listeners for checkboxes
        document.querySelectorAll('input[name="branch_id[]"], input[name="category[]"]').forEach(cb => {
            cb.addEventListener('change', () => {
                updateFilterIndicators();
                SearchEngine.init();
            });
        });

        // Search within popovers
        document.querySelectorAll('.popover-search').forEach(search => {
            search.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const container = this.closest('.p-3').querySelector('.custom-scrollbar');
                if (container) {
                    container.querySelectorAll('label').forEach(label => {
                        const text = label.querySelector('span').textContent.toLowerCase();
                        label.classList.toggle('hidden', !text.includes(query));
                    });
                }
            });
        });

        // Quick Reset Buttons with Immediate Propagation Control
        document.addEventListener('click', function(e) {
            // Branch Reset
            const branchReset = e.target.closest('.js-filter-branch-reset');
            if (branchReset) {
                e.preventDefault();
                e.stopImmediatePropagation();
                document.querySelectorAll('input[name="branch_id[]"]').forEach(cb => cb.checked = false);
                updateFilterIndicators();
                SearchEngine.init();
                return;
            }

            // Category Reset
            const categoryReset = e.target.closest('.js-filter-category-reset');
            if (categoryReset) {
                e.preventDefault();
                e.stopImmediatePropagation();
                document.querySelectorAll('input[name="category[]"]').forEach(cb => cb.checked = false);
                updateFilterIndicators();
                SearchEngine.init();
                return;
            }

            // Date Reset
            const dateReset = e.target.closest('.js-filter-date-reset');
            if (dateReset) {
                e.preventDefault();
                e.stopImmediatePropagation();
                document.getElementById('filter-start-date').value = '';
                document.getElementById('filter-end-date').value = '';
                updateFilterIndicators();
                SearchEngine.init();
                return;
            }
        }, true); // Use capture phase for reset to beat the trigger logic

        // --- Date Range Picker Logic ---
        window.setDateRange = function(range) {
            const start = document.getElementById('filter-start-date');
            const end = document.getElementById('filter-end-date');
            const today = new Date();
            let startDate, endDate = today;

            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            switch(range) {
                case 'today':
                    startDate = today;
                    break;
                case 'yesterday':
                    startDate = new Date();
                    startDate.setDate(today.getDate() - 1);
                    endDate = new Date(startDate);
                    break;
                case 'last7':
                    startDate = new Date();
                    startDate.setDate(today.getDate() - 6);
                    break;
                case 'last30':
                    startDate = new Date();
                    startDate.setDate(today.getDate() - 29);
                    break;
                case 'thisMonth':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
            }

            start.value = formatDate(startDate);
            end.value = formatDate(endDate);
            
            // Highlight active preset
            document.querySelectorAll('.date-preset-btn').forEach(p => {
                const isActive = p.dataset.range === range;
                p.classList.toggle('bg-white', isActive);
                p.classList.toggle('text-blue-600', isActive);
                p.classList.toggle('ring-1', isActive);
                p.classList.toggle('ring-blue-100', isActive);
            });
            updateApplyButtonState();
        }

        const filterStartDate = document.getElementById('filter-start-date');
        const filterEndDate = document.getElementById('filter-end-date');
        const btnApplyDate = document.getElementById('btn-apply-date');
        
        function updateApplyButtonState() {
            if (!filterStartDate || !filterEndDate || !btnApplyDate) return;
            const isFilled = filterStartDate.value && filterEndDate.value;
            btnApplyDate.disabled = !isFilled;
            if (isFilled) {
                btnApplyDate.classList.remove('opacity-50', 'cursor-not-allowed');
                btnApplyDate.classList.add('hover:bg-blue-700', 'shadow-lg');
            } else {
                btnApplyDate.classList.add('opacity-50', 'cursor-not-allowed');
                btnApplyDate.classList.remove('hover:bg-blue-700', 'shadow-lg');
            }
        }

        if (filterStartDate) filterStartDate.addEventListener('input', updateApplyButtonState);
        if (filterEndDate) filterEndDate.addEventListener('input', updateApplyButtonState);
        updateApplyButtonState();

        document.querySelectorAll('.date-preset-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                setDateRange(this.dataset.range);
            });
        });

        document.getElementById('btn-cancel-date')?.addEventListener('click', () => {
            // Close logic handled by global click listener
        });

        document.getElementById('btn-apply-date')?.addEventListener('click', () => {
            updateFilterIndicators();
            // Close logic handled by global click listener
            SearchEngine.init();
        });

        // --- Search input with debounce ---
        const searchInput = document.getElementById('instant-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                SearchEngine.search(this.value.trim());
            });
        }

        // Initialize clear button visibility on load
        if (searchInput && searchInput.value.trim() !== '') {
            const searchClearBtn = document.getElementById('search-clear');
            searchClearBtn?.classList.remove('hidden');
        }

        // Show session toasts
        @if(session('success'))
            showToast(`
                <div class="flex items-start gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Berhasil!</strong><br><span class="text-[11px] opacity-90">{{ session('success') }}</span></div>
                </div>
            `, 'success');
        @endif

        @if(session('error'))
            showToast(`
                <div class="flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Gagal!</strong><br><span class="text-[11px] opacity-90">{{ session('error') }}</span></div>
                </div>
            `, 'error');
        @endif

        @if($errors->any())
            @foreach ($errors->all() as $error)
                showToast(`
                    <div class="flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <div><strong>Error!</strong><br><span class="text-[11px] opacity-90">{{ $error }}</span></div>
                    </div>
                `, 'error');
            @endforeach
        @endif

        // ═══════════════════════════════════════════════════════════════
        // REAL-TIME UPDATES (Laravel Echo)
        // ═══════════════════════════════════════════════════════════════
        if (typeof window.Echo !== 'undefined') {
            @if(auth()->user()->role === 'teknisi')
                const echoChannel = window.Echo.private('transactions.{{ auth()->id() }}');
            @else
                const echoChannel = window.Echo.private('transactions');
            @endif

            echoChannel.listen('.transaction.updated', (e) => {
                console.log('🔔 [REALTIME] Transaction Updated:', e);
                SearchEngine.refresh();
            });

            console.log('📡 [REALTIME] Echo listener initialized on channel: ' + 
                ( @if(auth()->user()->role === 'teknisi') 'transactions.{{ auth()->id() }}' @else 'transactions' @endif )
            );
        }

        // --- Initial Load ---
        SearchEngine.init();
    });

    // ── DELETE TRANSACTION ─────────────────────────────────
    window.deleteTransaction = async function(id) {
        if (!id) {
            console.error('Delete error: No transaction ID provided');
            showToast('ID Transaksi tidak valid.', 'error');
            return;
        }

        if (!confirm('Apakah Anda yakin ingin menghapus transaksi ini secara permanen? Tindakan ini tidak dapat dibatalkan.')) {
            return;
        }

        try {
            if (typeof NProgress !== 'undefined') NProgress.start();
            
            const response = await fetch(`/transactions/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                // Refresh data using SearchEngine
                if (typeof SearchEngine !== 'undefined' && SearchEngine.refresh) {
                    await SearchEngine.refresh();
                } else {
                    window.location.reload();
                }
                
                showToast(result.message, 'success');
            } else {
                showToast(result.message || 'Gagal menghapus transaksi', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showToast('Terjadi kesalahan saat menghapus transaksi.', 'error');
        } finally {
            if (typeof NProgress !== 'undefined') NProgress.done();
        }
    };

    // ═══════════════════════════════════════════════════════════════
    // VIEW MODAL & OTHER FUNCTIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Helper to render application items with highlighting
     */
    function renderTransactionItemsCards(items, version, originalItems = []) {
        const purchaseReasons = {
            'rusak': 'Barang Rusak/Usang',
            'hilang': 'Barang Hilang',
            'upgrade': 'Upgrade/Peningkatan',
            'kebutuhan_baru': 'Kebutuhan Baru',
            'proyek': 'Keperluan Proyek',
            'lainnya': 'Lainnya'
        };

        return items.map((item, idx) => {
            const price = Number(item.estimated_price || 0);
            const qty = Number(item.quantity || 0);
            const total = price * qty;

            const originalItem = (version === 'management') ? (originalItems[idx] || null) : null;
            const isNew = version === 'management' && originalItems.length > 0 && idx >= originalItems.length;

            // Helper to check if a field has changed
            const isFieldChanged = (fieldName) => {
                if (version !== 'management' || isNew || !originalItem) return false;
                if (fieldName === 'specs') {
                    return JSON.stringify(item.specs) !== JSON.stringify(originalItem.specs);
                }
                return item[fieldName] != originalItem[fieldName];
            };

            const hasAnyChange = version === 'management' && originalItem && JSON.stringify(item) !== JSON.stringify(originalItem);

            const cardClass = isNew 
                ? 'border-emerald-200 bg-emerald-50/30' 
                : (hasAnyChange ? 'border-orange-200 bg-orange-50/30' : 'border-slate-200 bg-white');

            const fieldClass = (f) => isFieldChanged(f) 
                ? 'bg-orange-100/50 border-orange-300 ring-1 ring-orange-200' 
                : 'bg-white border-slate-200';

            const specsHTML = item.specs ? Object.entries({merk: 'Merk', tipe: 'Tipe/Seri', ukuran: 'Ukuran', warna: 'Warna'}).map(([key, label]) => {
                return item.specs[key] ? `
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">${label}</label>
                        <div class="${fieldClass('specs')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">${item.specs[key]}</div>
                    </div>
                ` : '';
            }).join('') : '';

            return `
                <div class="border ${cardClass} rounded-2xl overflow-hidden shadow-sm mb-4 last:mb-0 transition-all duration-300">
                    <!-- Card Header -->
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="const body = this.nextElementSibling; body.classList.toggle('hidden'); this.querySelector('.icon-collapse').classList.toggle('rotate-180')">
                        <div class="flex items-center gap-3 w-full max-w-[70%]">
                            <div class="w-7 h-7 shrink-0 rounded-full ${isNew ? 'bg-emerald-100 text-emerald-600' : (hasAnyChange ? 'bg-orange-100 text-orange-600' : 'bg-slate-200 text-slate-500')} flex items-center justify-center font-bold text-xs transition-colors">${idx + 1}</div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-700 text-sm flex items-center flex-wrap gap-2 truncate">
                                    <span class="truncate">${escapeHtml(item.customer || '-')}</span>
                                    ${isNew ? '<span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[9px] uppercase tracking-tighter font-black animate-pulse whitespace-nowrap">Baru</span>' : ''}
                                    ${hasAnyChange ? '<span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 text-[9px] uppercase tracking-tighter font-black whitespace-nowrap">Diedit</span>' : ''}
                                    ${(window._modalVersionData?.d?.can_manage || window._modalVersionData?.d?.is_owner) && escapeHtml(item.customer || '-') !== '-' ? `
                                        <button type="button" onclick="event.stopPropagation(); setAsReference('${window._modalVersionData.d.id}', '${escapeHtml(item.customer)}')" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-[9px] font-bold border border-blue-200 transition-colors whitespace-nowrap">
                                            <i data-lucide="bookmark-plus" class="w-3 h-3"></i> Referensi
                                        </button>
                                    ` : ''}
                                </h4>
                                <p class="text-[10px] text-slate-400">Rp ${price.toLocaleString('id-ID')} x ${qty}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="font-bold text-emerald-600 text-sm hidden sm:inline mr-2">Rp ${total.toLocaleString('id-ID')}</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 transition-transform duration-200 icon-collapse ${idx !== 0 ? 'rotate-180' : ''}"></i>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 sm:p-5 space-y-5 ${idx !== 0 ? 'hidden' : ''}">
                        <div>
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Informasi Barang / Jasa</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="md:col-span-2">
                                    <span class="block text-[10px] text-slate-400">Vendor</span>
                                    <div class="${fieldClass('vendor')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">${item.vendor || '-'}</div>
                                </div>
                                <div class="md:col-span-2">
                                    <span class="block text-[10px] text-slate-400">Link Rekomendasi</span>
                                    <div class="${fieldClass('link')} border rounded-lg px-3 py-2 text-xs">
                                        ${item.link ? `<a href="${item.link}" target="_blank" class="font-medium text-blue-600 hover:underline break-all">${item.link}</a>` : `<span class="font-medium text-slate-800">-</span>`}
                                    </div>
                                </div>
                            </div>
                        </div>

                        ${specsHTML ? `
                        <div>
                            <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Spesifikasi Barang</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">${specsHTML}</div>
                        </div>` : ''}

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Alasan Pembelian</label>
                                <div class="${fieldClass('category')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800">
                                    ${item.category || purchaseReasons[item.purchase_reason] || item.purchase_reason || '-'}
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] md:text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider">Keterangan</label>
                                <div class="${fieldClass('description')} border rounded-lg px-3 py-2 text-xs font-medium text-slate-800 text-wrap whitespace-pre-wrap">${item.description || '-'}</div>
                            </div>
                        </div>

                        <!-- Price & Qty highlights -->
                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                             <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Harga Satuan</label>
                                <div class="${fieldClass('estimated_price')} border rounded-lg px-3 py-2 text-xs font-bold text-slate-800">Rp ${price.toLocaleString('id-ID')}</div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jumlah</label>
                                <div class="${fieldClass('quantity')} border rounded-lg px-3 py-2 text-xs font-bold text-slate-800">${qty}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function openViewModal(id) {        currentTransactionId = id;

        const modal      = document.getElementById('view-modal');
        const modalBox   = document.getElementById('view-modal-content');
        const loading    = document.getElementById('view-loading');
        const body       = document.getElementById('view-body');

        loading.innerHTML = `
            <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-sm text-slate-400 font-medium">Memuat detail...</p>`;
        loading.style.display = 'flex';
        loading.classList.remove('hidden');
        body.style.display = 'none';

        // Show modal first using style.display to avoid Tailwind hidden/flex conflict
        modal.style.display = 'flex';
        modal.classList.remove('hidden', 'opacity-0');
        if (window.toggleBodyScroll) window.toggleBodyScroll(true);
        else { document.documentElement.style.overflow = 'hidden'; document.body.style.overflow = 'hidden'; }
        
        // Then animate
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            modalBox.classList.remove('scale-95');
            modalBox.classList.add('scale-100');
        });

        fetch(`/transactions/${id}/detail-json`)
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(d => {
                renderViewModal(d);
                loading.style.display = 'none';
                loading.classList.add('hidden');
                body.style.display = 'flex';
                body.style.flexDirection = 'column';
                
                // ✅ ATTACH CLICK EVENT TO IMAGE
                const imgWrapper = document.getElementById('v-image-wrapper');
                if (imgWrapper) {
                    // Remove previous listeners by cloning
                    const newWrapper = imgWrapper.cloneNode(true);
                    imgWrapper.parentNode.replaceChild(newWrapper, imgWrapper);
                    newWrapper.addEventListener('click', function() {
                        const img = document.getElementById('v-image');
                        const pdfIcon = document.getElementById('v-pdf-icon');
                        let src = '';
                        
                        if (img && !img.classList.contains('hidden') && img.src) {
                            openImageViewer(img.src);
                        } else if (pdfIcon && !pdfIcon.classList.contains('hidden') && pdfIcon.dataset.src) {
                            openImageViewer(pdfIcon.dataset.src, null, true);
                        }
                    });
                }
                
                if (typeof lucide !== 'undefined') lucide.createIcons();
                
                // Focus close button after content loads
                setTimeout(() => {
                    const closeBtn = modal.querySelector('button[onclick="closeViewModal()"]');
                    if (closeBtn) closeBtn.focus();
                }, 150);
            })
            .catch(err => {
                console.error(err);
                loading.innerHTML = '<p class="text-red-500 text-sm font-bold">Gagal memuat data. Coba lagi.</p>';
                loading.style.display = 'flex';
            });
    }

    function closeViewModal() {
        const modal    = document.getElementById('view-modal');
        const modalBox = document.getElementById('view-modal-content');
        
        // Remove focus from any element inside modal FIRST
        if (document.activeElement && modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        
        // Unlock scroll immediately (before animation finishes)
        if (window.toggleBodyScroll) window.toggleBodyScroll(false);
        else { document.documentElement.style.overflow = ''; document.body.style.overflow = ''; }
        
        // Animate close
        modal.classList.add('opacity-0');
        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');
        
        // After animation, hide
        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }, 300);
    }

    document.getElementById('view-modal').addEventListener('click', e => {
        if (e.target.id === 'view-modal') closeViewModal();
    });

    function settleBranchDebt(debtId) {
        currentDebtId = debtId;
        const modal = document.getElementById('branch-debt-modal');
        if (modal) {
            document.getElementById('branch_debt_file_input').value = '';
            document.getElementById('branch_debt_notes').value = '';
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
            document.body.style.overflow = 'hidden';
            if (lucide) lucide.createIcons({ root: modal });
        }
    }

    function closeBranchDebtModal() {
        const modal = document.getElementById('branch-debt-modal');
        if (modal) {
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }
    }

    // Branch debt form submit
    const branchDebtForm = document.getElementById('branch-debt-form');
    if (branchDebtForm) {
        branchDebtForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentDebtId) return;

            const btn = document.getElementById('btnSubmitBranchDebt');
            const loader = document.getElementById('btnSubmitBranchDebtLoader');
            const text = document.getElementById('btnSubmitBranchDebtText');

            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
            loader.classList.remove('hidden');
            text.textContent = 'Memproses...';

            const formData = new FormData(this);
            formData.append('_method', 'PATCH');
            
            fetch('/branch-debts/' + currentDebtId + '/settle', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showToast(res.message, 'success');
                    closeBranchDebtModal();
                    if (currentTransactionId) {
                        openViewModal(currentTransactionId);
                    }
                } else {
                    showToast(res.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Terjadi kesalahan jaringan.', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
                loader.classList.add('hidden');
                text.textContent = 'Upload & Simpan';
            });
        });
    }

    function renderViewModal(d) {
        currentTransactionId = d.id;
        const isDebtPending = d.status === 'waiting_payment' && d.status_label && d.status_label.includes('Hutang');
        const isGudang = d.type === 'gudang';
        const isLargePengajuan = d.type === 'pengajuan' && d.effective_amount >= 1000000;

        const statusColors = {
            pending:         isGudang ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200',
            approved:        isLargePengajuan ? 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200' : 'bg-purple-50 text-purple-700 border-purple-200',
            completed:       'bg-emerald-50 text-emerald-700 border-emerald-200',
            rejected:        'bg-red-50 text-red-700 border-red-200',
            waiting_payment: isDebtPending ? 'bg-amber-50 text-amber-700 border-amber-200' : (isGudang ? 'bg-slate-50 text-slate-700 border-slate-200' : 'bg-orange-50 text-orange-700 border-orange-200'),
            pending_technician: 'bg-teal-50 text-teal-700 border-teal-200',
            flagged:         'bg-rose-50 text-rose-700 border-rose-200',
            'auto-reject':   'bg-gray-800 text-gray-50 border-gray-900',
        };

        const statusIcons = {
            pending:         isGudang ? 'search' : 'clock',
            approved:        isLargePengajuan ? 'shield-alert' : 'user-check',
            completed:       'check-circle-2',
            rejected:        'x-circle',
            waiting_payment: isDebtPending ? 'wallet' : (isGudang ? 'store' : 'credit-card'),
            pending_technician: 'package-check',
            flagged:         'flag',
            'auto-reject':   'bot',
        };
        
        let modalTitle = 'Detail Reimbursement';
        if (d.type === 'pengajuan') modalTitle = 'Detail Pengajuan';
        if (d.type === 'gudang') modalTitle = 'Detail Belanja Gudang';

        document.getElementById('view-modal-title').textContent = modalTitle;
        document.getElementById('v-invoice').textContent = d.invoice_number + ' • ' + d.created_at;

        let typeBg = 'bg-indigo-50 text-indigo-600 border-indigo-100';
        let typeIcon = 'receipt';

        if (d.type === 'pengajuan') {
            typeBg = 'bg-teal-50 text-teal-600 border-teal-100';
            typeIcon = 'shopping-bag';
        } else if (d.type === 'gudang') {
            typeBg = 'bg-emerald-50 text-emerald-600 border-emerald-100';
            typeIcon = 'package';
        }

        document.getElementById('v-badges').innerHTML = `
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border ${statusColors[d.status] || ''}">
                <i data-lucide="${statusIcons[d.status] || 'info'}" class="w-3.5 h-3.5"></i>
                ${d.status_label}
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold border ${typeBg}">
                <i data-lucide="${typeIcon}" class="w-3 h-3"></i> ${d.type_label}
            </span>`;

        const imgWrap = document.getElementById('v-image-wrap');
        const vImage = document.getElementById('v-image');
        const vPdfIcon = document.getElementById('v-pdf-icon');

        if (d.image_url) {
            imgWrap.classList.remove('hidden');
            const isPdf = (d.file_path && d.file_path.toLowerCase().endsWith('.pdf')) || (d.image_url && d.image_url.toLowerCase().endsWith('.pdf'));
            
            if (isPdf) {
                vImage.classList.add('hidden');
                vPdfIcon.classList.remove('hidden');
                vPdfIcon.dataset.src = d.image_url;
            } else {
                vImage.classList.remove('hidden');
                vPdfIcon.classList.add('hidden');
                vImage.src = d.image_url;
            }
        } else {
            imgWrap.classList.add('hidden');
        }

        // ✅ Revisi Banner untuk Pengajuan yang sudah diedit Management
        const revisBannerContainer = document.getElementById('v-revision-banner');
        if (revisBannerContainer) {
            if (d.type === 'pengajuan' && d.is_edited_by_management) {
                revisBannerContainer.innerHTML = `
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col gap-3">
                        <div class="flex items-start gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="git-branch" class="w-4 h-4 text-blue-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800">Direvisi oleh Management</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Diedit oleh <strong class="text-blue-600">${d.editor_name || '-'}</strong>
                                    pada ${d.edited_at || '-'}
                                    &nbsp;<span class="inline-block bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Revisi ke-${d.revision_count}</span>
                                </p>
                            </div>
                        </div>
                        ${(d.items_snapshot && d.items_snapshot.length > 0) ? `
                        <div class="flex gap-2">
                            <button type="button" id="v-toggle-original"
                                onclick="toggleVersionInModal('original')"
                                class="flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-white text-slate-600 border border-slate-200 shadow-sm transition-all hover:bg-slate-50">
                                <i data-lucide="user" class="w-3.5 h-3.5 inline mr-1"></i>Versi Pengaju (V1)
                            </button>
                            <button type="button" id="v-toggle-management"
                                onclick="toggleVersionInModal('management')"
                                class="flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 inline mr-1"></i>Versi Management (V${(d.revision_count || 0) + 1})
                            </button>
                        </div>` : ''}
                    </div>`;
                revisBannerContainer.classList.remove('hidden');

                // Store version data on modal for toggle function
                window._modalVersionData = {
                    original: d.items_snapshot || [],
                    management: d.items || [],
                    d: d
                };
                window._modalCurrentVersion = 'management'; // Default to management if edited
            } else {
                revisBannerContainer.innerHTML = '';
                revisBannerContainer.classList.add('hidden');
                window._modalVersionData = {
                    original: d.items_snapshot || [],
                    management: d.items || [],
                    d: d
                };
                window._modalCurrentVersion = 'original';
            }
            // Re-init lucide
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: revisBannerContainer });
        }

        const fieldsEl = document.getElementById('v-fields');
        let fieldsHtml = '';

        const addField = (label, value, span2 = false) => {
            if (value === null || value === undefined || value === '') return;
            fieldsHtml += `
                <div class="${span2 ? 'sm:col-span-2' : ''}">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">${label}</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800">${value}</div>
                </div>`;
        };

        if (d.type === 'rembush') {
            addField('Pengaju', d.submitter?.name || '-');
            addField('Nama Vendor',       d.customer);
            addField('Tanggal Transaksi', d.date);
            addField('Kategori',          d.category_label);
            addField('Metode Pencairan',  d.payment_method_label);
            addField('Keterangan',        d.description, true);
            addField('Total Nominal',     d.amount ? 'Rp ' + Number(d.amount).toLocaleString('id-ID') : null);
        } else if (d.type === 'gudang') {
            addField('Pembeli',           d.submitter?.name || '-');
            addField('Toko / Vendor',     d.vendor || '-');
            addField('Tanggal Belanja',   d.date);
            addField('Kategori',          d.category_label);
            addField('Metode Bayar',      d.payment_method_label);
            addField('Keterangan',        d.description, true);

            // Gudang Payment Details (for completed ones or those waiting for debt settlement)
            if ((d.status === 'completed' || d.status === 'waiting_payment') && d.invoice_file_url) {
                let sumberDanaHtml = '';
                if (d.sumber_dana_data && d.sumber_dana_data.length > 0) {
                    const branchesLookup = {};
                    d.branches_raw.forEach(b => branchesLookup[b.id] = b.name);
                    
                    sumberDanaHtml = `
                    <div class="sm:col-span-2 mb-3">
                        <label class="block text-[9px] font-bold text-teal-600/60 uppercase mb-2 font-black tracking-widest">Sumber Dana Pembayaran</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            ${d.sumber_dana_data.map(sd => `
                                <div class="bg-teal-50 border border-teal-100 rounded-lg p-2.5 flex justify-between items-center shadow-sm">
                                    <span class="text-[10px] font-black text-slate-700 uppercase tracking-tight">${branchesLookup[sd.branch_id] || 'Cabang ' + sd.branch_id}</span>
                                    <span class="text-[11px] font-black text-teal-600">Rp ${Number(sd.amount).toLocaleString('id-ID')}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
                }

                fieldsHtml += `
                    <div class="sm:col-span-2 mt-4 pt-6 border-t border-slate-100">
                        <label class="block text-[11px] font-black text-emerald-500 uppercase mb-4 tracking-[0.2em]">Detail Pembayaran Gudang</label>
                        ${sumberDanaHtml}
                        <div class="mt-4 flex flex-col gap-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider">Bukti Transfer / Cash</label>
                            <a href="${d.invoice_file_url}" target="_blank" class="inline-flex items-center gap-2.5 px-5 py-3 bg-white border border-slate-200 rounded-xl text-xs font-black text-emerald-600 hover:bg-emerald-50 hover:border-emerald-100 transition-all shadow-sm active:scale-95 w-fit">
                                <i data-lucide="image" class="w-4 h-4"></i> Lihat Bukti Bayar
                            </a>
                        </div>
                    </div>`;
            }
        } else {
            addField('Pengaju', d.submitter?.name || '-');
            // Untuk pengajuan, selalu tampilkan alasan utama di header jika items tidak ada atau single.
            // Jika multi-item, alasan tiap item ada di card-nya masing-masing.
            // Namun agar konsisten dengan permintaan user, kita tampilkan alasan utama/kategori di header.
            if (!d.items || d.items.length === 0) {
                addField('Nama Barang/Jasa',      d.customer, true);
                addField('Vendor',                d.vendor);
                addField('Alasan Pembelian',      d.purchase_reason_label);
                addField('Jumlah',                d.quantity);
                addField('Estimasi Harga Satuan', d.estimated_price ? 'Rp ' + Number(d.estimated_price).toLocaleString('id-ID') : null);
            } else {
                // Untuk multi-item, kita tetap tampilkan Alasan Pembelian utama di header agar tidak "kosong"
                addField('Alasan Pembelian Utama', d.purchase_reason_label);
            }
            
            // Note: Keterangan Global & Total Estimasi mapped later to v-summary-wrap

            // Invoice details for Pengajuan (completed OR waiting for debt settlement)
            if ((d.status === 'completed' || d.status === 'waiting_payment') && d.invoice_file_url) {
                // Build Multi Sumber Dana HTML
                let sumberDanaHtml = '';
                if (d.sumber_dana_data && d.sumber_dana_data.length > 0) {
                    const branchesLookup = {};
                    d.branches_raw.forEach(b => branchesLookup[b.id] = b.name);
                    
                    sumberDanaHtml = `
                    <div class="sm:col-span-2 mb-3">
                        <label class="block text-[9px] font-bold text-teal-600/60 uppercase mb-2">Sumber Dana Pembayaran</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            ${d.sumber_dana_data.map(sd => `
                                <div class="bg-teal-50 border border-teal-100 rounded-lg p-2 flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-700">${branchesLookup[sd.branch_id] || 'Cabang ' + sd.branch_id}</span>
                                    <span class="text-xs font-bold text-teal-600">Rp ${Number(sd.amount).toLocaleString('id-ID')}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
                } else {
                    // Fallback for old transactions
                    sumberDanaHtml = `
                        <div class="sm:col-span-2 bg-teal-50/50 border border-teal-100 rounded-xl px-4 py-3 mb-3">
                            <label class="block text-[9px] font-bold text-teal-600/60 uppercase mb-1">Sumber Dana</label>
                            <div class="text-sm font-bold text-slate-700">${d.sumber_dana_branch_name || '-'}</div>
                        </div>
                    `;
                }

                // Build Branch Debts HTML (Hanya Perspektif Hutang)
                let debtsHtml = '';
                if (d.branch_debts && d.branch_debts.length > 0) {
                    debtsHtml = `
                    <div class="sm:col-span-2 mb-3">
                        <div class="flex items-center gap-2 mb-3">
                            <label class="block text-[10px] font-bold text-red-500 uppercase tracking-widest">Hutang Tersisa Antar Cabang</label>
                            <div class="flex-1 h-px bg-red-100"></div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            ${d.branch_debts.map(debt => `
                                <div class="relative ${debt.status === 'paid' ? 'bg-emerald-50 border-emerald-100' : 'bg-red-50 border-red-100'} rounded-xl p-3 ${debt.status === 'paid' ? 'opacity-90' : ''}">
                                    <div class="flex justify-between items-start pt-1">
                                        <div class="text-[11px] leading-relaxed">
                                            <span class="font-black ${debt.status === 'paid' ? 'text-emerald-600' : 'text-red-600'}">${debt.debtor_branch_name}</span>
                                            <span class="text-slate-500">berhutang kepada</span>
                                            <span class="font-bold text-slate-700">${debt.creditor_branch_name}</span>
                                        </div>
                                        <div class="text-xs font-black ${debt.status === 'paid' ? 'text-emerald-600' : 'text-red-600'} whitespace-nowrap ml-2">Rp ${Number(debt.amount).toLocaleString('id-ID')}</div>
                                    </div>
                                    <div class="flex items-center justify-between mt-3 pt-2 border-t ${debt.status === 'paid' ? 'border-emerald-100/50' : 'border-red-100/50'}">
                                        <span class="text-[9px] font-bold ${debt.status === 'paid' ? 'text-emerald-600' : 'text-red-400'} uppercase">
                                            Status: ${debt.status === 'paid' ? 'Lunas' : 'Belum Lunas'}
                                        </span>
                                    </div>
                                    ${debt.status === 'paid' && debt.payment_proof ? `
                                        <div class="mt-3 bg-white/50 rounded-lg p-2 border border-red-100/30">
                                            <label class="block text-[8px] font-bold text-slate-400 uppercase mb-1">Bukti Transfer</label>
                                            <a href="/storage/${debt.payment_proof}" target="_blank" class="inline-flex items-center gap-1.5 text-emerald-600 font-bold text-[10px] hover:underline">
                                                <i data-lucide="image" class="w-3 h-3"></i> Lihat Bukti
                                            </a>
                                            ${debt.notes ? `<p class="text-[9px] text-slate-500 mt-0.5 italic">"${debt.notes}"</p>` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
                }

                fieldsHtml += `
                    <div class="sm:col-span-2 mt-4 pt-4 border-t border-slate-100">
                        <label class="block text-[10px] font-bold text-teal-500 uppercase mb-3 tracking-widest">Detail Pembayaran Invoice</label>
                        
                        ${sumberDanaHtml}
                        ${debtsHtml}

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Ongkir</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.ongkir || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Diskon Pengiriman</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.diskon_pengiriman || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Voucher Diskon</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.voucher_diskon || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">DPP Lainnya</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.biaya_layanan_1 || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">PPN</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.tax_amount || 0).toLocaleString('id-ID')}</div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Biaya Layanan 2</label>
                                <div class="text-sm font-bold text-slate-700">Rp ${Number(d.biaya_layanan_2 || 0).toLocaleString('id-ID')}</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">File Invoice</label>
                            <a href="${d.invoice_file_url}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-teal-600 hover:bg-teal-50 transition-colors">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Lihat Invoice
                            </a>
                        </div>
                    </div>`;
            }
        }

        fieldsEl.innerHTML = fieldsHtml;

        // Populate Summary Wrap for Pengajuan
        const summaryWrap = document.getElementById('v-summary-wrap');
        const summaryDescWrap = document.getElementById('v-summary-desc-wrap');
        const summaryDesc = document.getElementById('v-summary-desc');
        const summaryTotalWrap = document.getElementById('v-summary-total-wrap');
        const summaryTotal = document.getElementById('v-summary-total');

        if (d.type === 'pengajuan' || d.type === 'gudang') {
            summaryWrap.classList.remove('hidden');

            if (d.items && d.items.length > 0) {
                // Show Keterangan Global
                if (d.description) {
                    summaryDescWrap.classList.remove('hidden');
                    summaryDescWrap.classList.add('md:col-span-2');
                    summaryTotalWrap.className = 'bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col justify-center shadow-sm';
                    if (d.type === 'gudang') {
                        summaryTotalWrap.className = 'bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-4 flex flex-col justify-center shadow-sm';
                    }
                    summaryDesc.textContent = d.description;
                } else {
                    summaryDescWrap.classList.add('hidden');
                    summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col justify-center shadow-sm';
                    if (d.type === 'gudang') {
                        summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-4 flex flex-col justify-center shadow-sm';
                    }
                }
            } else {
                summaryDescWrap.classList.add('hidden');
                summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex flex-col justify-center shadow-sm text-center items-center';
                if (d.type === 'gudang') {
                    summaryTotalWrap.className = 'md:col-span-3 bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-4 flex flex-col justify-center shadow-sm text-center items-center';
                }
            }
            
            let summaryTotalHtml = '';
            if (d.type === 'pengajuan' && (d.dpp_lainnya > 0 || d.tax_amount > 0 || d.biaya_layanan_1 > 0)) {
                summaryTotalHtml = `
                    <div class="flex flex-col gap-1 w-full">
                        <div class="flex justify-between items-center text-[10px] text-blue-600/70 font-bold uppercase tracking-wider border-b border-blue-100 pb-1 mb-1">
                            <span>Subtotal Items</span>
                            <span>Rp ${Number((d.amount || 0) - (d.dpp_lainnya || 0) - (d.tax_amount || 0) - (d.biaya_layanan_1 || 0)).toLocaleString('id-ID')}</span>
                        </div>
                        ${d.dpp_lainnya > 0 ? `
                        <div class="flex justify-between items-center text-[10px] text-blue-500 font-bold">
                            <span>DPP Lainnya</span>
                            <span>+ Rp ${Number(d.dpp_lainnya).toLocaleString('id-ID')}</span>
                        </div>` : ''}
                        ${d.tax_amount > 0 ? `
                        <div class="flex justify-between items-center text-[10px] text-blue-500 font-bold">
                            <span>PPN</span>
                            <span>+ Rp ${Number(d.tax_amount).toLocaleString('id-ID')}</span>
                        </div>` : ''}
                        ${d.biaya_layanan_1 > 0 ? `
                        <div class="flex justify-between items-center text-[10px] text-blue-500 font-bold">
                            <span>Biaya Layanan 1</span>
                            <span>+ Rp ${Number(d.biaya_layanan_1).toLocaleString('id-ID')}</span>
                        </div>` : ''}
                        <div class="flex justify-between items-center mt-1 pt-1 border-t-2 border-blue-200">
                            <span class="text-[11px] font-black text-blue-800 uppercase">Grand Total</span>
                            <span class="text-lg md:text-xl font-black text-blue-700 tracking-tight">Rp ${Number(d.amount).toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                `;
                summaryTotal.innerHTML = summaryTotalHtml;
            } else {
                summaryTotal.textContent = d.amount ? 'Rp ' + Number(d.amount).toLocaleString('id-ID') : '-';
            }
        } else {
            summaryWrap.classList.add('hidden');
        }

        const itemsWrap  = document.getElementById('v-items-wrap');
        const itemsTbody = document.getElementById('v-items-tbody');
        const itemsTableCont = document.getElementById('v-items-table-container');
        const itemsDivCont   = document.getElementById('v-items-div-container');
        
        if ((d.items && d.items.length > 0) || (d.items_snapshot && d.items_snapshot.length > 0)) {
            itemsWrap.classList.remove('hidden');
            
            if (d.type === 'pengajuan') {
                itemsTableCont.classList.add('hidden');
                itemsDivCont.classList.remove('hidden');
                
                // If it's edited, show the current version (management) by default
                const itemsToRender = window._modalCurrentVersion === 'management' ? d.items : (d.items_snapshot || d.items);
                itemsDivCont.innerHTML = renderTransactionItemsCards(itemsToRender, window._modalCurrentVersion, d.items_snapshot || []);
                
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
            } else {
                itemsDivCont.classList.add('hidden');
                itemsTableCont.classList.remove('hidden');
                
                let itemsHtml = d.items.map(item => `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">${item.name || item.nama_barang || '-'}</td>
                        <td class="px-3 py-2 text-center">${item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || item.satuan || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.price || item.harga_satuan || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${( (Number(item.qty) || 0) * (Number(item.price || item.harga_satuan) || 0) ).toLocaleString('id-ID')}</td>
                    </tr>`).join('');
                itemsTbody.innerHTML = itemsHtml;
            }
        } else {
            itemsWrap.classList.add('hidden');
        }

        const specsWrap = document.getElementById('v-specs-wrap');
        const specsEl   = document.getElementById('v-specs');
        if (d.type === 'pengajuan' && d.specs && Object.values(d.specs).some(v => v) && (!d.items || d.items.length === 0)) {
            specsWrap.classList.remove('hidden');
            const specLabels = { merk: 'Merk', tipe: 'Tipe/Seri', ukuran: 'Ukuran', warna: 'Warna' };
            specsEl.innerHTML = Object.entries(specLabels).map(([key, label]) => `
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">${label}</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800">${d.specs[key] || '-'}</div>
                </div>`).join('');
        } else {
            specsWrap.classList.add('hidden');
        }

        const branchesWrap = document.getElementById('v-branches-wrap');
        const branchesEl   = document.getElementById('v-branches');
        if (d.branches && d.branches.length > 0) {
            branchesWrap.classList.remove('hidden');
            branchesEl.innerHTML = d.branches.map(b => `
                <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                    <span class="text-sm font-bold text-slate-700">${b.name}</span>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-500">${b.percent}%</span>
                        <span class="text-xs text-slate-400 ml-2">(${b.amount})</span>
                    </div>
                </div>`).join('');
        } else {
            branchesWrap.classList.add('hidden');
        }

        const rejWrap = document.getElementById('v-rejection-wrap');
        if (d.status === 'rejected' && d.rejection_reason) {
            rejWrap.classList.remove('hidden');
            document.getElementById('v-rejection').textContent = d.rejection_reason;
        } else {
            rejWrap.classList.add('hidden');
        }

        const waitOwner = document.getElementById('v-waiting-owner');
        waitOwner.classList.toggle('hidden', d.status !== 'approved');

        const revWrap = document.getElementById('v-reviewer-wrap');
        if (d.reviewer) {
            revWrap.classList.remove('hidden');
            revWrap.classList.add('flex');
            document.getElementById('v-reviewer').textContent    = d.reviewer.name;
            document.getElementById('v-reviewed-at').textContent = d.reviewed_at;
        } else {
            revWrap.classList.add('hidden');
            revWrap.classList.remove('flex');
        }

        const actionsWrap = document.getElementById('v-actions');
        const btnReset    = document.getElementById('v-btn-reset');
        if (d.is_owner && d.status !== 'pending') {
            btnReset.classList.remove('hidden');
            actionsWrap.classList.remove('hidden');
        } else {
            btnReset.classList.add('hidden');
            actionsWrap.classList.add('hidden');
        }

        // Trigger initial version & history render
        toggleVersionInModal(window._modalCurrentVersion);
    }

    // ─── Version Toggle in Detail Modal ────────────────────────────
    function toggleVersionInModal(version) {
        if (!window._modalVersionData) return;

        const items = version === 'original'
            ? window._modalVersionData.original
            : window._modalVersionData.management;

        window._modalCurrentVersion = version;

        // Update button states
        const btnOriginal   = document.getElementById('v-toggle-original');
        const btnManagement = document.getElementById('v-toggle-management');

        if (btnOriginal && btnManagement) {
            if (version === 'original') {
                btnOriginal.className   = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all';
                btnManagement.className = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold text-slate-600 bg-white border border-slate-200 transition-all hover:bg-slate-50';
            } else {
                btnManagement.className = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold bg-blue-500 text-white shadow-sm transition-all';
                btnOriginal.className   = 'flex-1 px-3 py-2 rounded-lg text-[11px] font-bold text-slate-600 bg-white border border-slate-200 transition-all hover:bg-slate-50';
            }
        }

        const itemsWrap      = document.getElementById('v-items-wrap');
        const itemsTbody     = document.getElementById('v-items-tbody');
        const itemsDivCont   = document.getElementById('v-items-div-container');
        const itemsTableCont = document.getElementById('v-items-table-container');

        if (items && items.length > 0) {
            itemsWrap.classList.remove('hidden');
            const label = version === 'original'
                ? '<span class="text-xs text-blue-600 font-bold ml-2">(Versi Pengaju)</span>'
                : '<span class="text-xs text-emerald-600 font-bold ml-2">(Versi Management)</span>';

            // Update section title
            const sectionLabel = itemsWrap.querySelector('label');
            if (sectionLabel) {
                sectionLabel.innerHTML = 'Daftar Barang' + label;
            }

            if (itemsDivCont && !itemsDivCont.classList.contains('hidden')) {
                // Render Cards (Pengajuan)
                itemsDivCont.innerHTML = renderTransactionItemsCards(items, version, window._modalVersionData.original);
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
            } else if (itemsTbody) {
                // Render Table (Rembush)
                itemsTbody.innerHTML = items.map((item, idx) => {
                    const itemName = escapeHtml(item.customer || item.name || item.nama_barang || '-');
                    const canSetRef = window._modalVersionData?.d?.can_manage || window._modalVersionData?.d?.is_owner;
                    const refBtn = canSetRef && itemName !== '-' ? `
                        <button type="button" onclick="setAsReference('${window._modalVersionData.d.id}', '${itemName}')" class="ml-2 inline-flex items-center gap-1 px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-[9px] font-bold border border-blue-200 transition-colors">
                            <i data-lucide="bookmark-plus" class="w-3 h-3"></i> Jadikan Referensi
                        </button>
                    ` : '';

                    return `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">
                            <div class="flex items-center">${itemName}${refBtn}</div>
                        </td>
                        <td class="px-3 py-2 text-center">${item.quantity || item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || item.satuan || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.estimated_price || item.price || item.harga_satuan || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${((Number(item.quantity || item.qty) || 0) * (Number(item.estimated_price || item.price || item.harga_satuan) || 0)).toLocaleString('id-ID')}</td>
                    </tr>`;
                }).join('');
                if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsTbody });
            }
        } else {
            if (itemsWrap) itemsWrap.classList.add('hidden');
        }

        // ✅ Riwayat Pembayaran (Payment History)
        const payHistWrap = document.getElementById('v-payment-history-wrap');
        
        if (window._modalVersionData?.d?.is_paid && (window._modalVersionData?.d?.type === 'rembush' || window._modalVersionData?.d?.type === 'pengajuan')) {
            const hist = window._modalVersionData.d;
            payHistWrap.classList.remove('hidden');

            // Set Step 1 Info
            const step1Title = document.getElementById('v-pay-step1-title');
            if (hist.payment_type === 'Transfer') {
                step1Title.textContent = 'BUKTI TRANSFER DIUNGGAH';
            } else if (hist.payment_type === 'Tunai') {
                step1Title.textContent = 'PEMBAYARAN TUNAI DISERAHKAN';
            } else {
                step1Title.textContent = 'PEMBAYARAN DIPROSES';
            }

            document.getElementById('v-pay-step1-at').textContent = hist.payment_at || '-';
            document.getElementById('v-pay-step1-by').textContent = hist.paid_by_name || 'System';
            document.getElementById('v-pay-step1-role').textContent = hist.paid_by_role || 'Admin';

            // Action Button Step 1 (Lihat Bukti or Selesai Badge)
            const actionWrap1 = document.getElementById('v-pay-step1-action-wrap');
            if (hist.payment_type === 'Transfer' && hist.payment_proof_url) {
                actionWrap1.innerHTML = `
                    <button type="button" onclick="openImageViewer('${hist.payment_proof_url}', 'Bukti Pembayaran')" 
                        class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100 hover:bg-emerald-100 transition-all uppercase tracking-widest">
                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                        LIHAT BUKTI
                    </button>
                `;
            } else if (hist.payment_type === 'Tunai' && hist.payment_proof_url) {
                actionWrap1.innerHTML = `
                    <button type="button" onclick="openImageViewer('${hist.payment_proof_url}', 'Bukti Penyerahan')" 
                        class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100 hover:bg-emerald-100 transition-all uppercase tracking-widest">
                        <i data-lucide="image" class="w-3.5 h-3.5"></i>
                        LIHAT BUKTI
                    </button>
                `;
            } else if (hist.payment_type === 'Tunai') {
                actionWrap1.innerHTML = `
                    <div class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 bg-white px-3 py-1.5 rounded-xl border border-slate-100 uppercase tracking-widest">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-slate-400"></i>
                        Selesai
                    </div>
                `;
            } else {
                actionWrap1.innerHTML = '';
            }

            // Set Step 2 Info (Penerimaan)
            const step2Wrap = document.getElementById('v-pay-step2-wrap');
            if (hist.status === 'completed' || hist.konfirmasi_at) {
                step2Wrap.classList.remove('hidden');
                document.getElementById('v-pay-step2-at').textContent = hist.konfirmasi_at || hist.payment_at || '-';
                document.getElementById('v-pay-step2-by').textContent = hist.konfirmasi_by_name || hist.recipient_name || '-';
                document.getElementById('v-pay-step2-role').textContent = hist.konfirmasi_by_role || hist.recipient_role || 'Teknisi';
            } else {
                step2Wrap.classList.add('hidden');
            }

            // Summary Box
            const summaryMethod = document.getElementById('v-pay-summary-method');
            const summaryAccount = document.getElementById('v-pay-summary-account');
            const methodWrap = document.getElementById('v-pay-method-wrap');

            if (hist.payment_method && methodWrap) {
                methodWrap.classList.remove('hidden');
                
                let methodLabel = hist.payment_method_label || hist.payment_method;
                let accountInfo = '';

                if (hist.payment_method === 'transfer_teknisi') {
                    methodLabel = 'Transfer ke Teknisi';
                    if (hist.submitter) {
                        const bank = hist.submitter.rekening_bank || '-';
                        const name = hist.submitter.rekening_nama || '-';
                        const number = hist.submitter.rekening_nomor || '-';
                        accountInfo = `${bank} • ${name}<br>${number}`;
                    }
                } else if (hist.payment_method === 'transfer_penjual') {
                    methodLabel = 'Transfer ke Penjual';
                    if (hist.specs) {
                        const bank = hist.specs.bank_name || '-';
                        const name = hist.specs.account_name || '-';
                        const number = hist.specs.account_number || '-';
                        accountInfo = `${bank} • ${name}<br>${number}`;
                    }
                } else if (hist.payment_method === 'cash') {
                    methodLabel = 'Tunai (Cash)';
                    if (hist.type === 'pengajuan') accountInfo = 'Dibayarkan Tunai';
                } else if (hist.payment_method === 'transfer' && hist.type === 'pengajuan') {
                    methodLabel = 'Rekening (Transfer)';
                    accountInfo = 'Dibayarkan via Transfer';
                }

                if (summaryMethod) summaryMethod.innerHTML = methodLabel;
                if (summaryAccount) summaryAccount.innerHTML = accountInfo || (hist.payment_type || '-');
            } else if (methodWrap) {
                methodWrap.classList.add('hidden');
            }

            const summaryAmount = document.getElementById('v-pay-summary-amount');
            const summaryDiscrepancy = document.getElementById('v-pay-summary-discrepancy');

            // Set main amount (use actual_total if exists, else fallback to amount)
            const finalAmount = hist.actual_total || hist.amount || 0;
            summaryAmount.textContent = 'Rp ' + Number(finalAmount).toLocaleString('id-ID');

            // Set Discrepancy (Selisih)
            if (summaryDiscrepancy) {
                const selisih = Number(hist.selisih || 0);
                if (selisih !== 0) {
                    summaryDiscrepancy.classList.remove('hidden');
                    const absSelisih = Math.abs(selisih).toLocaleString('id-ID');
                    if (selisih > 0) {
                        // Surplus (Hemat/Saved) - Green
                        summaryDiscrepancy.innerHTML = `<i data-lucide="trending-down" class="w-2.5 h-2.5 inline mr-1"></i>Hemat Rp ${absSelisih}`;
                        summaryDiscrepancy.className = "text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30";
                    } else {
                        // Deficit (Overspent) - Red
                        summaryDiscrepancy.innerHTML = `<i data-lucide="trending-up" class="w-2.5 h-2.5 inline mr-1"></i>Lebih Rp ${absSelisih}`;
                        summaryDiscrepancy.className = "text-[10px] font-bold mt-1 px-2 py-0.5 rounded-lg uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30";
                    }
                } else {
                    summaryDiscrepancy.classList.add('hidden');
                }
            }

            // Re-init lucide icons
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: payHistWrap });
        } else {
            if (payHistWrap) payHistWrap.classList.add('hidden');
        }
    }

    // ─── Central AJAX status update (no page reload) ───────────────
    function performStatusAction(id, status, triggerEl) {
        if (triggerEl) {
            triggerEl.disabled = true;
            triggerEl.innerHTML = '<i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons({ root: triggerEl });
        }
        fetch(`/transactions/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status, _method: 'PATCH' }),
        })
        .then(r => r.json().catch(() => ({})))
        .then(data => {
            if (data.success) {
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${data.message || 'Status berhasil diperbarui.'}</span></div></div>`, 'success');
                if (data.transaction) {
                    SearchEngine.updateTransaction(data.transaction);
                } else {
                    SearchEngine.init();
                }
            } else {
                throw new Error(data.message || 'Gagal memperbarui status');
            }
        })
        .catch(err => {
            console.error(err);
            showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i><div><strong>Gagal!</strong><br><span class="text-[11px] opacity-90">Coba lagi.</span></div></div>`, 'error');
            if (triggerEl) { triggerEl.disabled = false; }
        });
    }

    function submitApproval(status) {
        if (!currentTransactionId) return;
        if (status === 'pending' && !confirm('Reset status ke Pending?')) return;
        closeViewModal();
        performStatusAction(currentTransactionId, status, null);
    }

    function openRejectModal(transactionId, invoiceNumber) {
        const modal = document.getElementById('reject-modal');
        const inner = modal.querySelector('div');

        document.getElementById('reject-form').action      = '/transactions/' + transactionId + '/status';
        document.getElementById('reject-modal-invoice').textContent = invoiceNumber;

        // Show modal first
        modal.classList.remove('hidden');
        
        // Then set aria-hidden and animate
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });
        
        // Focus textarea after animation
        setTimeout(() => {
            const textarea = modal.querySelector('textarea[name="rejection_reason"]');
            if (textarea) textarea.focus();
        }, 350);
    }

    function closeRejectModal() {
        const modal = document.getElementById('reject-modal');
        const inner = modal.querySelector('div');
        
        // Remove focus from any element inside modal FIRST
        if (document.activeElement && modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        
        // Animate close
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        
        // After animation, hide and set aria-hidden
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            modal.querySelector('textarea').value = '';
        }, 300);
    }

    document.getElementById('reject-modal').addEventListener('click', e => {
        if (e.target.id === 'reject-modal') closeRejectModal();
    });

    // ─── OVERRIDE MODAL ─────────────────────────
    function openOverrideModal(transactionId, invoiceNumber) {
        const modal = document.getElementById('override-modal');
        const inner = modal.querySelector('div');

        document.getElementById('override-form').action = '/transactions/' + transactionId + '/override';
        document.getElementById('override-modal-invoice').textContent = invoiceNumber;

        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });
        setTimeout(() => {
            const textarea = modal.querySelector('textarea[name="override_reason"]');
            if (textarea) textarea.focus();
        }, 350);
    }
    function closeOverrideModal() {
        const modal = document.getElementById('override-modal');
        const inner = modal.querySelector('div');
        if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('override-form').reset();
        }, 300);
    }
    document.getElementById('override-modal').addEventListener('click', e => {
        if (e.target.id === 'override-modal') closeOverrideModal();
    });

    // ─── FORCE APPROVE MODAL ────────────────────
    function openForceApproveModal(transactionId, invoiceNumber) {
        const modal = document.getElementById('force-approve-modal');
        const inner = modal.querySelector('div');

        document.getElementById('force-approve-form').action = '/transactions/' + transactionId + '/force-approve';
        document.getElementById('force-approve-modal-invoice').textContent = invoiceNumber;

        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });
        setTimeout(() => {
            const textarea = modal.querySelector('textarea[name="force_approve_reason"]');
            if (textarea) textarea.focus();
        }, 350);
    }
    function closeForceApproveModal() {
        const modal = document.getElementById('force-approve-modal');
        const inner = modal.querySelector('div');
        if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('force-approve-form').reset();
        }, 300);
    }
    document.getElementById('force-approve-modal').addEventListener('click', e => {
        if (e.target.id === 'force-approve-modal') closeForceApproveModal();
    });

    // ─── PAYMENT MODAL ──────────────────────────
    function renderPaymentModalDetails(d) {
        const detailContainer = document.getElementById('p-detail-container');
        detailContainer.classList.remove('hidden');

        const isDebtPending = d.status === 'waiting_payment' && d.status_label && d.status_label.includes('Hutang');
        const isGudang = d.type === 'gudang';
        const isLargePengajuan = d.type === 'pengajuan' && d.effective_amount >= 1000000;

        const statusColors = {
            pending:         isGudang ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200',
            approved:        isLargePengajuan ? 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200' : 'bg-purple-50 text-purple-700 border-purple-200',
            completed:       'bg-emerald-50 text-emerald-700 border-emerald-200',
            rejected:        'bg-red-50 text-red-700 border-red-200',
            waiting_payment: isDebtPending ? 'bg-amber-50 text-amber-700 border-amber-200' : (isGudang ? 'bg-slate-50 text-slate-700 border-slate-200' : 'bg-orange-50 text-orange-700 border-orange-200'),
            pending_technician: 'bg-teal-50 text-teal-700 border-teal-200',
            flagged:         'bg-rose-50 text-rose-700 border-rose-200',
            'auto-reject':   'bg-gray-800 text-gray-50 border-gray-900',
        };

        const statusIcons = {
            pending:         isGudang ? 'search' : 'clock',
            approved:        isLargePengajuan ? 'shield-alert' : 'user-check',
            completed:       'check-circle-2',
            rejected:        'x-circle',
            waiting_payment: isDebtPending ? 'wallet' : (isGudang ? 'store' : 'credit-card'),
            pending_technician: 'package-check',
            flagged:         'flag',
            'auto-reject':   'bot',
        };

        const typeBg      = d.type === 'pengajuan' ? 'bg-teal-50 text-teal-600 border-teal-100' : (d.type === 'gudang' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-indigo-50 text-indigo-600 border-indigo-100');
        const typeIcon    = d.type === 'pengajuan' ? 'shopping-bag' : (d.type === 'gudang' ? 'package' : 'receipt');

        document.getElementById('p-badges').innerHTML = `
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border ${statusColors[d.status] || ''}">
                <i data-lucide="${statusIcons[d.status] || 'info'}" class="w-3.5 h-3.5"></i>
                ${d.status_label}
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold border ${typeBg}">
                <i data-lucide="${typeIcon}" class="w-3 h-3"></i> ${d.type_label}
            </span>`;

        const fieldsEl = document.getElementById('p-fields');
        let fieldsHtml = '';

        const addField = (label, value, span2 = false) => {
            if (value === null || value === undefined || value === '') return;
            fieldsHtml += `
                <div class="${span2 ? 'sm:col-span-2' : ''}">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">${label}</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800">${value}</div>
                </div>`;
        };

        addField('Pengaju', d.submitter?.name || '-');

        if (d.type === 'rembush') {
            addField('Nama Vendor',       d.customer);
            addField('Tanggal Transaksi', d.date);
            addField('Kategori',          d.category_label);
            addField('Metode Pencairan',  d.payment_method_label);
            addField('Keterangan',        d.description, true);
        } else if (d.type === 'gudang') {
            addField('Pembeli',           d.submitter?.name || '-');
            addField('Toko / Vendor',     d.vendor || '-');
            addField('Tanggal Belanja',   d.date);
            addField('Kategori',          d.category_label);
            addField('Metode Bayar',      d.payment_method_label);
            addField('Keterangan',        d.description, true);
        } else {
            if (!d.items || d.items.length === 0) {
                addField('Nama Barang/Jasa',      d.customer, true);
                addField('Vendor',                d.vendor);
                addField('Alasan Pembelian',      d.purchase_reason_label);
                addField('Jumlah',                d.quantity);
                addField('Estimasi Harga Satuan', d.estimated_price ? 'Rp ' + Number(d.estimated_price).toLocaleString('id-ID') : null);
            } else {
                // Selalu tampilkan alasan utama di header untuk Pengajuan
                addField('Alasan Pembelian Utama', d.purchase_reason_label);
            }
        }

        fieldsEl.innerHTML = fieldsHtml;

        const itemsWrap      = document.getElementById('p-items-wrap');
        const itemsTbody     = document.getElementById('p-items-tbody');
        const itemsTableCont = document.getElementById('p-items-table-container');
        const itemsDivCont   = document.getElementById('p-items-div-container');

        if ((d.items && d.items.length > 0) || (d.items_snapshot && d.items_snapshot.length > 0)) {
            itemsWrap.classList.remove('hidden');
            
            if (d.type === 'pengajuan') {
                if (itemsTableCont) itemsTableCont.classList.add('hidden');
                if (itemsDivCont) {
                    itemsDivCont.classList.remove('hidden');
                    const versionToUse = d.is_edited_by_management ? 'management' : 'original';
                    const itemsToRender = d.is_edited_by_management ? d.items : (d.items_snapshot || d.items);
                    
                    itemsDivCont.innerHTML = renderTransactionItemsCards(itemsToRender, versionToUse, d.items_snapshot || []);
                    if (typeof lucide !== 'undefined') lucide.createIcons({ root: itemsDivCont });
                }
            } else {
                if (itemsDivCont) itemsDivCont.classList.add('hidden');
                if (itemsTableCont) itemsTableCont.classList.remove('hidden');
                
                itemsTbody.innerHTML = d.items.map(item => `
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-3 py-2 text-slate-700 font-medium">${item.name || '-'}</td>
                        <td class="px-3 py-2 text-center">${item.qty || '-'}</td>
                        <td class="px-3 py-2">${item.unit || '-'}</td>
                        <td class="px-3 py-2 text-right">Rp ${Number(item.price || 0).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2 text-right font-bold">Rp ${( (Number(item.qty) || 0) * (Number(item.price) || 0) ).toLocaleString('id-ID')}</td>
                    </tr>`).join('');
            }
        } else {
            itemsWrap.classList.add('hidden');
        }



        const branchesWrap = document.getElementById('p-branches-wrap');
        const branchesEl   = document.getElementById('p-branches');
        const pSumberDana  = document.getElementById('p_sumber_dana');

        if (d.branches && d.branches.length > 0) {
            branchesWrap.classList.remove('hidden');
            branchesEl.innerHTML = d.branches.map(b => `
                <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                    <span class="text-sm font-bold text-slate-700">${b.name}</span>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-500">${b.percent}%</span>
                        <span class="text-xs text-slate-400 ml-2">(${b.amount})</span>
                    </div>
                </div>`).join('');
            
            // Populate Sumber Dana for Pengajuan
            if (d.type === 'pengajuan') {
                const container = document.getElementById('p_sumber_dana_container');
                if (container) {
                    container.innerHTML = '';
                    d.branches_raw.forEach((b, idx) => {
                        const html = `
                            <div id="sd_card_${b.id}" class="sd-card p-4 bg-white border-2 border-slate-100 rounded-2xl hover:border-teal-400 transition-all duration-200">
                                <div class="flex items-center gap-4">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" id="sd_check_${b.id}" class="sd-checkbox peer sr-only" value="${b.id}" data-alloc="${b.allocation_amount}" data-percent="${b.allocation_percent}" data-name="${b.name}">
                                        <label for="sd_check_${b.id}" class="w-8 h-8 border-2 border-slate-200 rounded-xl flex items-center justify-center cursor-pointer transition-all peer-checked:bg-teal-600 peer-checked:border-teal-600 peer-checked:[&_svg]:opacity-100 peer-checked:[&_i]:opacity-100 hover:border-teal-200">
                                            <i data-lucide="check" class="w-4 h-4 text-white opacity-0 transition-opacity"></i>
                                        </label>
                                    </div>

                                    <div class="flex-1">
                                        <label for="sd_check_${b.id}" class="block cursor-pointer">
                                            <div class="text-sm font-black text-slate-800 uppercase tracking-tight leading-none mb-1.5">${b.name}</div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-none">Alokasi: Rp ${Number(b.allocation_amount).toLocaleString('id-ID')} (${b.allocation_percent}%)</div>
                                        </label>
                                    </div>

                                    <div class="w-44 relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">Rp</span>
                                        <input type="text" id="sd_amount_${b.id}" name="sumber_dana[${idx}][amount]" disabled placeholder="0"
                                            class="sd-amount nominal-input w-full pl-10 pr-4 py-2.5 text-sm font-black text-slate-800 border-2 border-slate-100 rounded-xl outline-none focus:border-teal-400 focus:ring-4 focus:ring-teal-50 transition-all disabled:bg-slate-50/50 disabled:text-slate-300">
                                        <input type="hidden" id="sd_branch_${b.id}" name="sumber_dana[${idx}][branch_id]" value="${b.id}" disabled>
                                    </div>
                                    </div>
                                    <div id="sd_status_${b.id}" class="mt-3 text-right text-[10px] font-bold tracking-tight hidden"></div>
                                    </div>
                                    `;
                                    container.insertAdjacentHTML('beforeend', html);
                                    });

                                    // Re-attach nominal formatters for static and dynamic
                                    attachNominalFormatters();

                                    // Add event listeners
                                    document.querySelectorAll('.sd-checkbox').forEach(cb => {
                                    cb.addEventListener('change', function() {
                                    const id = this.value;
                                    const card = document.getElementById('sd_card_' + id);
                                    const amountInput = document.getElementById('sd_amount_' + id);
                                    const branchInput = document.getElementById('sd_branch_' + id);
                                    const alloc = parseInt(this.dataset.alloc);

                                    if (this.checked) {
                                    amountInput.disabled = false;
                                    branchInput.disabled = false;
                                    amountInput.value = formatNumber(alloc); 
                                    amountInput.required = true;
                                    card.classList.remove('border-slate-100');
                                    card.classList.add('border-teal-500', 'bg-teal-50/10');
                                    } else {
                                    amountInput.disabled = true;
                                    branchInput.disabled = true;
                                    amountInput.value = '';
                                    amountInput.required = false;
                                    card.classList.remove('border-teal-500', 'bg-teal-50/10');
                                    card.classList.add('border-slate-100');
                                    }
                                    calculateSumberDanaTotal(d.effective_amount);
                                    });
                                    });

                                    document.querySelectorAll('.sd-amount').forEach(inp => {
                                    inp.addEventListener('input', () => calculateSumberDanaTotal(d.effective_amount));
                                    });

                                    // Adjustment fields listeners
                                    ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_dpp_lainnya', 'p_tax_amount', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
                                        document.getElementById(id).addEventListener('input', () => calculateSumberDanaTotal(d.effective_amount));
                                    });
                    
                    // Initial calculation to set initial state (e.g. disable button if total mismatch)
                    calculateSumberDanaTotal(d.effective_amount);
                }
            }
        } else {
            branchesWrap.classList.add('hidden');
            // Ensure button is not disabled by previous pengajuan mismatch if current is not pengajuan
            document.getElementById('btnSubmitPayment').disabled = false;
            document.getElementById('btnSubmitPayment').classList.remove('opacity-50', 'cursor-not-allowed');
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // NOMINAL FORMATTERS
    // ═══════════════════════════════════════════════════════════════
    function formatNumber(n) {
        if (!n) return '';
        return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function unformatNumber(s) {
        if (!s) return 0;
        return parseInt(s.toString().replace(/\D/g, "")) || 0;
    }

    function attachNominalFormatters() {
        document.querySelectorAll('.nominal-input').forEach(inp => {
            // Remove existing to avoid double binding
            inp.removeEventListener('input', handleNominalInput);
            inp.addEventListener('input', handleNominalInput);
        });
    }

    function handleNominalInput(e) {
        let cursor = e.target.selectionStart;
        let originalLen = e.target.value.length;
        let formatted = formatNumber(e.target.value);
        e.target.value = formatted;

        // Adjust cursor position
        let newLen = formatted.length;
        e.target.setSelectionRange(cursor + (newLen - originalLen), cursor + (newLen - originalLen));
    }

    // Initial attach
    document.addEventListener('DOMContentLoaded', () => {
        attachNominalFormatters();
    });

    function calculateSumberDanaTotal(baseTotal) {
        const totalEl = document.getElementById('p_sumber_dana_total');
        const totalVal = document.getElementById('p_sumber_dana_total_value');
        const diffEl = document.getElementById('p_sumber_dana_diff');
        const debtPreview = document.getElementById('p_debt_preview');
        const debtList = document.getElementById('p_debt_preview_list');
        const btnSubmit = document.getElementById('btnSubmitPayment');
        
        // Final Total Calculation: base + ongkir + fees - discounts
        const ongkir      = unformatNumber(document.getElementById('p_ongkir').value);
        const diskon      = unformatNumber(document.getElementById('p_diskon_pengiriman').value);
        const voucher     = unformatNumber(document.getElementById('p_voucher_diskon').value);
        const dppLainnya  = unformatNumber(document.getElementById('p_dpp_lainnya').value);
        const taxAmt      = unformatNumber(document.getElementById('p_tax_amount').value);
        const layanan1    = unformatNumber(document.getElementById('p_biaya_layanan_1').value);
        const layanan2    = unformatNumber(document.getElementById('p_biaya_layanan_2').value);
        
        const finalTotalTarget = baseTotal + ongkir + dppLainnya + taxAmt + layanan1 + layanan2 - diskon - voucher;

        totalEl.classList.remove('hidden');

        let total = 0;
        let creditors = {};
        let debtors = {};
        let branches = {};

        document.querySelectorAll('.sd-checkbox').forEach(cb => {
            const id = cb.value;
            const name = cb.dataset.name;
            const percent = parseFloat(cb.dataset.percent);
            
            // Recalculate allocation based on final target total
            const alloc = Math.round((finalTotalTarget * percent) / 100);
            
            const statusEl = document.getElementById('sd_status_' + id);
            const labelEl = document.querySelector(`label[for="sd_check_${id}"] div.text-slate-400`);
            
            if (labelEl) {
                labelEl.textContent = `Alokasi: Rp ${alloc.toLocaleString('id-ID')} (${percent}%)`;
            }
            
            branches[id] = { id, name, alloc };

            const paidValue = cb.checked ? document.getElementById('sd_amount_' + id).value : 0;
            const paid = unformatNumber(paidValue);
            total += paid;

            // Per-item status
            if (cb.checked) {
                if (paid > alloc) {
                    creditors[id] = paid - alloc;
                    statusEl.innerHTML = `<span class="text-teal-600">+ Lebih bayar Rp ${(paid - alloc).toLocaleString('id-ID')} (Menalangi)</span>`;
                    statusEl.classList.remove('hidden');
                } else if (paid < alloc) {
                    debtors[id] = alloc - paid;
                    statusEl.innerHTML = `<span class="text-red-500">- Kurang bayar Rp ${(alloc - paid).toLocaleString('id-ID')} (Berhutang)</span>`;
                    statusEl.classList.remove('hidden');
                } else {
                    statusEl.classList.add('hidden');
                }
            } else {
                debtors[id] = alloc;
                statusEl.innerHTML = `<span class="text-red-500">- Kurang bayar Rp ${alloc.toLocaleString('id-ID')} (Berhutang)</span>`;
                statusEl.classList.remove('hidden');
            }
        });
        totalVal.textContent = 'Rp ' + total.toLocaleString('id-ID');

        // Validation check vs total transaksi
        if (total !== finalTotalTarget) {
            totalVal.classList.remove('text-teal-600');
            totalVal.classList.add('text-red-500');
            diffEl.classList.remove('text-emerald-500');
            diffEl.classList.add('text-red-500');
            const diff = finalTotalTarget - total;
            diffEl.textContent = diff > 0 ? `Kurang Rp ${diff.toLocaleString('id-ID')} dari Total Tagihan` : `Kelebihan Rp ${Math.abs(diff).toLocaleString('id-ID')} dari Total Tagihan`;
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            totalVal.classList.remove('text-red-500');
            totalVal.classList.add('text-teal-600');
            diffEl.classList.remove('text-red-500');
            diffEl.classList.add('text-emerald-500');
            diffEl.textContent = 'Nominal sesuai dengan nilai bayar transaksi';
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        }        
        // Preview Debt
        const creditorIds = Object.keys(creditors);
        const debtorIds = Object.keys(debtors);

        if (creditorIds.length > 0 && debtorIds.length > 0) {
            debtPreview.classList.remove('hidden');
            let debtHtml = '';

            const totalExcess = Object.values(creditors).reduce((a,b)=>a+b,0);

            for (let debtorId of debtorIds) {
                const debtAmt = debtors[debtorId];
                let cardHtml = `
                    <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all">
                        <div class="flex justify-between items-start mb-1.5">
                            <div class="space-y-0.5">
                                <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight">${branches[debtorId].name}</h4>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total beban hutang</p>
                            </div>
                            <div class="px-2.5 py-1.5 bg-red-50/50 text-red-600 text-[11px] font-black rounded-lg border border-red-50">
                                Rp ${debtAmt.toLocaleString('id-ID')}
                            </div>
                        </div>

                        <div class="my-4 border-t border-slate-50 border-dashed"></div>

                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-[0.15em] mb-3">Rincian Pembayaran Ke:</div>
                        <div class="space-y-3">
                `;

                for (let creditorId of creditorIds) {
                    const excess = creditors[creditorId];
                    const proportion = excess / totalExcess;
                    const finalAmt = Math.round(debtAmt * proportion);

                    if (finalAmt > 0) {
                        cardHtml += `
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4 rounded-full bg-slate-50 flex items-center justify-center">
                                        <i data-lucide="arrow-right" class="w-2.5 h-2.5 text-slate-300 group-hover:text-teal-400 transition-colors"></i>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-600 group-hover:text-slate-800 transition-colors uppercase tracking-tight">${branches[creditorId].name}</span>
                                </div>
                                <span class="text-[11px] font-black text-slate-800 bg-slate-50/50 px-2 py-1 rounded-md">Rp ${finalAmt.toLocaleString('id-ID')}</span>
                            </div>
                        `;
                    }
                }

                cardHtml += `
                        </div>
                    </div>
                `;
                debtHtml += cardHtml;
            }
            debtList.innerHTML = debtHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            debtPreview.classList.add('hidden');
            debtList.innerHTML = '';
        }    }

    function openPaymentModal(id) {
        // Find transaction from the existing memory array
        const transaction = SearchEngine.getAll().find(x => x.id === id);
        if (!transaction) return;
        const transactionId = transaction.id;
        const invoiceNumber = transaction.invoice_number;
        const paymentMethod = transaction.payment_method;
        const amount = transaction.amount;
        const submitter = transaction.submitter || {};
        const specs = transaction.specs || {};
        const hasTelegram = transaction.submitter_has_telegram;

        const modal = document.getElementById('payment-modal');
        const inner = modal.querySelector('div');
        const loading = document.getElementById('payment-loading');
        const body = document.getElementById('payment-body');
        const submitBtn = document.getElementById('btnSubmitPayment');
        const submitBtnText = document.getElementById('btnSubmitPaymentText');

        // Form reset & display cleanups
        document.getElementById('payment-form').reset();
        document.getElementById('transfer-profile-alert').classList.add('hidden');
        document.getElementById('cash-fields').classList.add('hidden');
        document.getElementById('transfer-fields').classList.add('hidden');
        document.getElementById('pengajuan-invoice-fields').classList.add('hidden');
        document.getElementById('p-detail-container').classList.add('hidden');
        document.getElementById('payment-method-container').classList.add('hidden');
        document.getElementById('payment_method_select').required = false;

        // Show loading, hide body
        loading.classList.remove('hidden');
        body.classList.add('hidden');

        // Reset Submit Button
        submitBtn.disabled = false;
        submitBtn.classList.remove('bg-slate-400', 'cursor-not-allowed', 'hover:bg-slate-400');
        submitBtn.classList.add('bg-cyan-600', 'hover:bg-cyan-700');
        submitBtnText.textContent = 'Upload & Simpan';

        const isPengajuan = transaction.type === 'pengajuan';
        const isGudang    = transaction.type === 'gudang';

        // Check Telegram Registration (Only block for Cash/Transfer that requires tech confirmation)
        // For Pengajuan Invoice and Gudang, we don't block because it's processed by internal/vendor
        if (!hasTelegram && !isPengajuan && !isGudang) {
            submitBtn.disabled = true;
            submitBtn.classList.remove('bg-cyan-600', 'hover:bg-cyan-700');
            submitBtn.classList.add('bg-slate-400', 'cursor-not-allowed', 'hover:bg-slate-400');
            submitBtnText.textContent = 'Teknisi Belum Daftar Telegram';
            
            showToast(`<div class="flex items-start gap-2"><i data-lucide="bell-off" class="w-4 h-4 mt-0.5 flex-shrink-0 text-rose-600"></i><div><strong class="text-rose-800">Peringatan!</strong><br><span class="text-[11px] opacity-90 text-rose-700">Teknisi belum mendaftarkan Telegram. Pembayaran Cash/Transfer tidak dapat diproses hingga teknisi mendaftar via bot.</span></div></div>`, 'error');
        }

        let endpoint = '/api/v1/payment/cash/upload';
        
        // Populate display data
        document.getElementById('payment-modal-invoice').textContent = invoiceNumber;
        document.getElementById('payment-modal-amount').textContent = 'Rp ' + Number(amount).toLocaleString('id-ID');

        let form = document.getElementById('payment-form');
        form.querySelectorAll('.dyn-hidden').forEach(el => el.remove());
        
        // Add required hidden inputs
        const addHidden = (name, value) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = value; inp.className = 'dyn-hidden';
            form.appendChild(inp);
        };

        addHidden('transaksi_id', transactionId);
        addHidden('upload_id', transaction.upload_id || ('txn_' + transactionId));
        addHidden('expected_nominal', amount); // used by transfer
        addHidden('teknisi_id', submitter.id || ''); // used by cash

        const bankInput = document.getElementById('transfer_bank');
        const nomorInput = document.getElementById('transfer_nomor');
        const namaInput = document.getElementById('transfer_nama');
        const paymentFileInput = document.getElementById('payment_file_input');
        const paymentLabel = document.getElementById('payment-modal-label');

        // Reset inputs and validation
        if (bankInput) { bankInput.required = false; bankInput.disabled = true; }
        if (nomorInput) { nomorInput.required = false; nomorInput.disabled = true; }
        if (namaInput) { namaInput.required = false; namaInput.disabled = true; }
        document.getElementById('cash_catatan').disabled = true;
        document.getElementById('p_catatan').disabled = true;
        ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_dpp_lainnya', 'p_tax_amount', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = true;
        });

        paymentFileInput.name = 'file';

        if (isPengajuan) {
            endpoint = '/api/v1/payment/pengajuan/upload';
            document.getElementById('pengajuan-invoice-fields').classList.remove('hidden');
            document.getElementById('payment-method-container').classList.remove('hidden');
            document.getElementById('payment_method_select').required = true;
            document.getElementById('payment-modal-title').textContent = 'Upload Pembayaran Invoice';
            paymentFileInput.name = 'invoice_file';
            paymentFileInput.required = true;
            paymentLabel.innerHTML = 'Unggah Foto Invoice <span class="text-red-500">*</span>';
            
            // Enable Pengajuan fields
            document.getElementById('p_catatan').disabled = false;
            ['p_ongkir', 'p_diskon_pengiriman', 'p_voucher_diskon', 'p_dpp_lainnya', 'p_tax_amount', 'p_biaya_layanan_1', 'p_biaya_layanan_2'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = false;
            });
        } else {
            document.getElementById('payment-modal-title').textContent = 'Upload Bukti Transfer/Cash';
            // Dynamic File Requirement (Mandatory for Transfer, Optional for Cash)
            const isTransfer = paymentMethod && paymentMethod.includes('transfer');
            paymentFileInput.required = isTransfer;
            if (paymentLabel) {
                paymentLabel.innerHTML = isTransfer 
                    ? 'Unggah Foto / Screenshot <span class="text-red-500">*</span>'
                    : 'Unggah Foto / Screenshot <span class="text-slate-400 font-normal">(Opsional)</span>';
            }

            if (isTransfer) {
                endpoint = '/api/v1/payment/transfer/upload';
                document.getElementById('transfer-fields').classList.remove('hidden');

                // Reset readonly state and styles first
                [bankInput, nomorInput, namaInput].forEach(el => {
                    el.disabled = false;
                    el.readOnly = false;
                    el.required = true; // Wajib diisi agar btnSubmit memvalidasi form
                    el.classList.remove('bg-slate-100', 'cursor-not-allowed');
                });

                // Fetch Saved Accounts for Technician
                if (paymentMethod === 'transfer_teknisi') {
                    const select = document.getElementById('saved_bank_account');
                    const container = document.getElementById('saved-accounts-container');
                    select.innerHTML = '<option value="">-- Pilih Rekening --</option>';
                    container.classList.add('hidden');

                    fetch(`/user-bank-accounts/${submitter.id}`)
                        .then(r => r.json())
                        .then(accounts => {
                            if (accounts.length > 0) {
                                container.classList.remove('hidden');
                                accounts.forEach(acc => {
                                    const opt = document.createElement('option');
                                    opt.value = JSON.stringify(acc);
                                    opt.textContent = `${acc.bank_name} - ${acc.account_number} (${acc.account_name})`;
                                    
                                    // Auto-select if matches specs (pre-selected by management)
                                    if (specs && specs.bank_account_id == acc.id) {
                                        opt.selected = true;
                                    }
                                    
                                    select.appendChild(opt);
                                });

                                // If an account was auto-selected, trigger the autofill display
                                if (select.value) {
                                    autoFillBankAccount(select);
                                }
                            }
                        });
                } else {
                    document.getElementById('saved-accounts-container').classList.add('hidden');
                }

                if (paymentMethod === 'transfer_teknisi') {
                    document.getElementById('transfer-method-badge').textContent = 'TRANSFER TEKNISI';

                    // WAJIB menggunakan Dropdown (Input manual dikunci)
                    [bankInput, nomorInput, namaInput].forEach(el => {
                        el.readOnly = true;
                        el.classList.add('bg-slate-100', 'cursor-not-allowed');
                    });

                    // Kosongkan secara default agar Admin WAJIB memilih rekening dari Dropdown
                    bankInput.value = '';
                    nomorInput.value = '';
                    namaInput.value = '';

                    document.getElementById('transfer-profile-alert').classList.remove('hidden');
                } else if (paymentMethod === 'transfer_penjual') {
                    document.getElementById('transfer-method-badge').textContent = 'TRANSFER PENJUAL (VENDOR)';
                    
                    // Set to Read-Only as per requirement
                    [bankInput, nomorInput, namaInput].forEach(el => {
                        el.readOnly = true;
                        el.classList.add('bg-slate-100', 'cursor-not-allowed');
                    });

                    bankInput.value = specs.bank_name || '';
                    nomorInput.value = specs.account_number || '';
                    namaInput.value = specs.account_name || '';
                }
            } else {
                document.getElementById('cash-fields').classList.remove('hidden');
                document.getElementById('cash_catatan').disabled = false;
            }
        }

        form.action = endpoint;

        // Show modal and start loading animation
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.remove('opacity-0');
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
        });

        // Fetch transaction details and show form
        fetch(`/transactions/${id}/detail-json`)
            .then(r => r.json())
            .then(d => {
                renderPaymentModalDetails(d);
                loading.classList.add('hidden');
                body.classList.remove('hidden');
            })
            .catch(err => {
                console.error(err);
                loading.innerHTML = '<p class="text-red-500 text-sm font-bold">Gagal memuat data. Coba lagi.</p>';
            });
    }

    function autoFillBankAccount(select) {
        if (!select.value) {
            document.getElementById('transfer_bank').value = '';
            document.getElementById('transfer_nomor').value = '';
            document.getElementById('transfer_nama').value = '';
            return;
        }
        const acc = JSON.parse(select.value);
        document.getElementById('transfer_bank').value = acc.bank_name;
        document.getElementById('transfer_nomor').value = acc.account_number;
        document.getElementById('transfer_nama').value = acc.account_name;
    }

    function closePaymentModal() {
        const modal = document.getElementById('payment-modal');
        const inner = modal.querySelector('div');
        if (document.activeElement && modal.contains(document.activeElement)) document.activeElement.blur();
        modal.classList.add('opacity-0');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('payment-form').reset();
            document.querySelectorAll('.dyn-hidden').forEach(e => e.remove());
        }, 300);
    }
    document.getElementById('payment-modal').addEventListener('click', e => {
        if (e.target.id === 'payment-modal') closePaymentModal();
    });

    // ─── INIT AJAX FORMS ────────────────────────────
    function bindAjaxForm(formId, closeModalFunc, successMsg) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const submitText = submitBtn.querySelector('span') || submitBtn;
            const loader = submitBtn.querySelector('.animate-spin');
            
            const originalText = submitText.textContent;
            submitBtn.disabled = true;
            if (loader) {
                submitText.classList.add('opacity-0');
                loader.classList.remove('hidden');
            } else {
                submitText.textContent = 'Memproses...';
            }
            if (typeof NProgress !== 'undefined') NProgress.start();

            // Prepare Data
            const formData = new FormData(this);

            // Clean up nominal inputs before sending
            this.querySelectorAll('.nominal-input').forEach(inp => {
                if (inp.name && formData.has(inp.name)) {
                    // Use unformatNumber to remove non-digit characters
                    const rawValue = inp.value ? String(inp.value).replace(/\D/g, "") : "0";
                    formData.set(inp.name, rawValue || "0");
                }
            });

            // Append _method=PATCH if it's override/force-approve targeting standard updates? No, the PDF API might be POST. 
            // We'll let the HTML form method rule.

            fetch(this.action, {
                method: this.method || 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData // auto handles multipart if file exists
            })
            .then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const error = new Error(data.message || 'Gagal memproses form');
                    error.errors = data.errors;
                    throw error;
                }
                return data;
            })
            .then(data => {
                closeModalFunc();
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${successMsg || data.message || 'Aksi berhasil'}</span></div></div>`, 'success');
                if (data.transaction) {
                    SearchEngine.updateTransaction(data.transaction);
                } else {
                    SearchEngine.init();
                }
            })
            .catch(err => {
                console.error(err);
                let errorHtml = err.message || 'Terjadi kesalahan sistem.';
                if (err.errors) {
                    const errorList = Object.values(err.errors).flat();
                    if (errorList.length > 0) {
                        errorHtml = errorList.join('<br>');
                    }
                }
                showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">${errorHtml}</span></div></div>`, 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                if (loader) {
                    submitText.classList.remove('opacity-0');
                    loader.classList.add('hidden');
                } else {
                    submitText.textContent = originalText;
                }
                if(typeof NProgress !== 'undefined') NProgress.done();
            });
        });
    }

    bindAjaxForm('override-form', closeOverrideModal, 'Override berhasil diajukan.');
    bindAjaxForm('force-approve-form', closeForceApproveModal, 'Transaksi berhasil di Force Approve.');
    bindAjaxForm('payment-form', closePaymentModal, 'Bukti Pembayaran berhasil diunggah.');

    // ─── TECHNICIAN CASH CONFIRMATION ───────────────
    let isConfirmingCash = false;
    window.confirmCashPayment = function(id, action) {
        if (isConfirmingCash) return;
        
        const allTx = SearchEngine.getAll();
        if (!allTx || allTx.length === 0) return;
        const t = allTx.find(x => x.id === id);
        if (!t) return;
        
        let msg = action === 'terima' ? `Konfirmasi terima uang CASH untuk invoice ${t.invoice_number}?` : `Tolak penerimaan CASH untuk invoice ${t.invoice_number} karena tidak sesuai?`;
        if (!confirm(msg)) return;

        let catatan = '';
        if (action === 'tolak') {
            catatan = prompt("Harap masukkan alasan penolakan:") || '';
        } else {
            catatan = prompt("Catatan (Opsional):") || '';
        }

        isConfirmingCash = true;
        if(typeof NProgress !== 'undefined') NProgress.start();

        const formData = new FormData();
        formData.append('transaksi_id', t.id);
        formData.append('upload_id', t.upload_id || `txn_${t.id}`);
        formData.append('teknisi_id', t.submitter?.id || '');
        formData.append('action', action);
        formData.append('catatan', catatan);

        fetch('/api/v1/payment/cash/konfirmasi', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.message || 'Gagal mengirim konfirmasi.');
            return data;
        })
        .then(data => {
            showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">${data.message || 'Konfirmasi berhasil dikirim.'}</span></div></div>`, 'success');
            SearchEngine.init(); // Refresh grid
        })
        .catch(err => {
            console.error(err);
            showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">${err.message || 'Terjadi kesalahan sistem.'}</span></div></div>`, 'error');
        })
        .finally(() => {
            isConfirmingCash = false;
            if(typeof NProgress !== 'undefined') NProgress.done();
        });
    }


    // Convert reject form to AJAX (no page reload)
    const rejectForm = document.getElementById('reject-form');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id    = this.action.split('/').at(-2); // extract transaction id from URL
            const reason = this.querySelector('textarea[name="rejection_reason"]')?.value || '';
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; }
            if (typeof NProgress !== 'undefined') NProgress.start();

            fetch(`/transactions/${id}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status: 'rejected', rejection_reason: reason, _method: 'PATCH' }),
            })
            .then(r => r.json().catch(() => ({})))
            .then(data => {
                closeRejectModal();
                showToast(`<div class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-emerald-600"></i><div><strong class="text-emerald-800">Berhasil!</strong><br><span class="text-[11px] opacity-90 text-emerald-700">Transaksi berhasil ditolak.</span></div></div>`, 'success');
                if (data.transaction) {
                    SearchEngine.updateTransaction(data.transaction);
                } else {
                    SearchEngine.init();
                }
            })
            .catch(err => {
                console.error(err);
                showToast(`<div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-red-600"></i><div><strong class="text-red-800">Gagal!</strong><br><span class="text-[11px] opacity-90 text-red-700">Gagal menolak transaksi. Coba lagi.</span></div></div>`, 'error');
                if (submitBtn) { submitBtn.disabled = false; }
            })
            .finally(() => {
                if(typeof NProgress !== 'undefined') NProgress.done();
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // REALTIME EVENT HANDLERS - IMPROVED
    // ═══════════════════════════════════════════════════════════════

    let refreshTimer = null;
    const REFRESH_DEBOUNCE_MS = 500;

    function debouncedRefresh(eventName, data) {
        console.log(`🔔 [REVERB] Event received: ${eventName}`, data);
        
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => {
            console.log(`🔄 [REVERB] Refreshing grid after ${eventName}...`);
            SearchEngine.init().then(() => {
                console.log(`✅ [REVERB] Grid refreshed successfully`);
                
                // Re-init Lucide icons after DOM update
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }).catch(err => {
                console.error(`❌ [REVERB] Grid refresh failed:`, err);
            });
        }, REFRESH_DEBOUNCE_MS);
    }

    // ✅ Handler untuk TransactionUpdated event
    window.handleRealtimeTransactionUpdate = function(transaction) {
        console.log('📝 [REVERB] Transaction Updated:', transaction);
        if (transaction && transaction.id) {
            SearchEngine.updateTransaction(transaction);
        } else {
            debouncedRefresh('TransactionUpdated', transaction);
        }
    };

    // ✅ Handler untuk TransactionCreated event
    window.handleRealtimeTransactionCreation = function(transaction) {
        console.log('🆕 [REVERB] Transaction Created:', transaction);
        if (transaction && transaction.id) {
            SearchEngine.addTransaction(transaction);
        } else {
            debouncedRefresh('TransactionCreated', transaction);
        }
    };

    // ✅ Handler khusus untuk OCR status updates
    window.handleRealtimeOcrStatusUpdate = function(data) {
        console.log('🤖 [REVERB] OCR Status Updated:', data);
        
        // Immediate visual feedback
        const badge = document.querySelector(`.ai-status-badge[data-upload-id="${data.upload_id}"]`);
        if (badge) {
            badge.classList.add('opacity-50', 'animate-pulse');
        }
        
        // Then refresh grid
        if (data.transaction && data.transaction.id) {
            SearchEngine.updateTransaction(data.transaction);
        } else {
            debouncedRefresh('OcrStatusUpdated', data);
        }
    };

    window.confirmDeleteTransaction = function(id, invoiceNumber) {
        openConfirmModal('globalConfirmModal', {
            title: 'Hapus Transaksi?',
            message: `Anda yakin ingin menghapus transaksi <strong class="text-slate-800">${invoiceNumber}</strong>? Tindakan ini tidak dapat dibatalkan.`,
            action: `/transactions/${id}`,
            method: 'DELETE',
            submitText: 'Ya, Hapus',
            onConfirm: async () => {
                try {
                    const response = await fetch(`/transactions/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        showToast(result.message, 'success');
                        // Remove from SearchEngine instantly
                        if (typeof SearchEngine !== 'undefined') {
                            SearchEngine.deleteTransaction(id);
                        }
                    } else {
                        throw new Error(result.message || 'Gagal menghapus transaksi');
                    }
                } catch (err) {
                    showToast(err.message, 'error');
                }
            }
        });
    }
</script>
@endpush