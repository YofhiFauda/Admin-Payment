<!-- DESKTOP: Responsive Layout (1024px to 1439px: 2 Rows, >= 1440px: 1 Row) -->
<div class="hidden lg:flex flex-wrap min-[1510px]:flex-nowrap items-center gap-3 md:gap-4 lg:gap-3">
    {{-- <div class="hidden lg:flex flex-wrap min-[1440px]:flex-nowrap items-center gap-3 md:gap-4 lg:gap-3"> --}}

        <!-- Group 1: Search, Multi Cabang, Multi Kategori, Date Range Picker -->
        <div
            class="flex items-center gap-3 w-full min-[1510px]:w-auto overflow-x-auto scrollbar-hide pb-1 min-[1510px]:pb-0">
            <!-- Search -->
            <div class="relative flex-1 min-[1510px]:flex-none min-[1510px]:w-72 group">
                <i data-lucide="search"
                    class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                <input type="text" id="instant-search" value="{{ request('search') }}"
                    placeholder="Cari invoice, nama..." autocomplete="off"
                    class="search-input-sync w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-semibold focus:outline-none focus:ring-4 focus:ring-blue-500/5 focus:border-blue-400 transition-all placeholder:text-gray-400">
                <button type="button" id="search-clear"
                    class="absolute right-3 top-1/2 -translate-y-1/2 hidden p-1 rounded-lg hover:bg-gray-200 transition-colors">
                    <i data-lucide="x" class="w-3 h-3 text-gray-400"></i>
                </button>
            </div>

            @if(auth()->user()->role !== 'teknisi')
                <!-- Branch Filter -->
                <div class="relative filter-group flex-shrink-0" id="group-branch">
                    <button type="button"
                        class="js-filter-branch-btn filter-trigger flex items-center gap-2 px-3 py-2.5 bg-white border border-gray-200 rounded-2xl text-[11px] font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all active:scale-95 group"
                        data-target="menu-filter-branch">
                        <i data-lucide="git-branch"
                            class="w-3.5 h-3.5 text-gray-400 group-[.active]:text-blue-600 transition-colors"></i>
                        <span class="js-filter-branch-label filter-label">Semua Cabang</span>
                        <div class="flex items-center">
                            <i data-lucide="chevron-down"
                                class="w-3 h-3 ml-1 text-gray-400 group-[.active]:rotate-180 transition-transform"></i>
                            <span
                                class="js-filter-branch-reset filter-clear hidden ml-1.5 p-0.5 rounded-md hover:bg-blue-100 text-blue-600 transition-colors"
                                title="Bersihkan">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </span>
                        </div>
                    </button>
                </div>

                <!-- Category Filter -->
                <div class="relative filter-group flex-shrink-0" id="group-category">
                    <button type="button"
                        class="js-filter-category-btn filter-trigger flex items-center gap-2 px-3 py-2.5 bg-white border border-gray-200 rounded-2xl text-[11px] font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all active:scale-95 group"
                        data-target="menu-filter-category">
                        <i data-lucide="layout-grid"
                            class="w-3.5 h-3.5 text-gray-400 group-[.active]:text-blue-600 transition-colors"></i>
                        <span class="js-filter-category-label filter-label">Semua Kategori</span>
                        <div class="flex items-center">
                            <i data-lucide="chevron-down"
                                class="w-3 h-3 ml-1 text-gray-400 group-[.active]:rotate-180 transition-transform"></i>
                            <span
                                class="js-filter-category-reset filter-clear hidden ml-1.5 p-0.5 rounded-md hover:bg-blue-100 text-blue-600 transition-colors"
                                title="Bersihkan">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </span>
                        </div>
                    </button>
                </div>

                <!-- Date Filter -->
                <div class="relative filter-group flex-shrink-0" id="group-date">
                    <button type="button"
                        class="js-filter-date-btn filter-trigger flex items-center gap-2 px-3 py-2.5 bg-white border border-gray-200 rounded-2xl text-[11px] font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all active:scale-95 group"
                        data-target="menu-filter-date">
                        <i data-lucide="calendar"
                            class="w-3.5 h-3.5 text-gray-400 group-[.active]:text-blue-600 transition-colors"></i>
                        <span class="js-filter-date-label filter-label">Pilih Tanggal</span>
                        <div class="flex items-center">
                            <i data-lucide="chevron-down"
                                class="w-3 h-3 ml-1 text-gray-400 group-[.active]:rotate-180 transition-transform"></i>
                            <span
                                class="js-filter-date-reset filter-clear hidden ml-1.5 p-0.5 rounded-md hover:bg-blue-100 text-blue-600 transition-colors"
                                title="Bersihkan">
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
        <div
            class="w-full min-[1510px]:w-auto grid grid-cols-4 gap-2 min-[1510px]:flex min-[1510px]:items-center min-[1510px]:gap-2 overflow-x-auto scrollbar-hide pb-1 min-[1510px]:pb-0">
            @php $currentType = request('type', 'all'); @endphp
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => null])) }}"
                class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap text-center border {{ $currentType === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50' }}">
                Semua
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'rembush'])) }}"
                class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap text-center border {{ $currentType === 'rembush' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-indigo-50' }}">
                <i data-lucide="receipt" class="w-3 h-3 inline mr-1"></i>Rembush
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'pengajuan'])) }}"
                class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap text-center border {{ $currentType === 'pengajuan' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-teal-50' }}">
                <i data-lucide="shopping-bag" class="w-3 h-3 inline mr-1"></i>Pengajuan
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'gudang'])) }}"
                class="px-3 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap text-center border {{ $currentType === 'gudang' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-500 border-slate-200 hover:bg-amber-50' }}">
                <i data-lucide="package" class="w-3 h-3 inline mr-1"></i>Gudang
            </a>
        </div>
    </div>

    <!-- TABLET: 3 Rows Layout (md to lg screens) -->
    <div class="hidden md:flex lg:hidden flex-col gap-3">
        <!-- Row 1: Search + Date -->
        <div class="flex gap-3">
            <div class="relative flex-1 group">
                <i data-lucide="search"
                    class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                <input type="text" id="instant-search-tablet" value="{{ request('search') }}"
                    placeholder="Cari invoice, nama..." autocomplete="off"
                    class="search-input-sync w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-semibold focus:outline-none focus:ring-4 focus:ring-blue-500/5 focus:border-blue-400 transition-all placeholder:text-gray-400">
                <button type="button"
                    class="search-clear absolute right-3 top-1/2 -translate-y-1/2 hidden p-1 rounded-lg hover:bg-gray-200 transition-colors">
                    <i data-lucide="x" class="w-3 h-3 text-gray-400"></i>
                </button>
            </div>
            @if(auth()->user()->role !== 'teknisi')
                <button type="button"
                    class="js-filter-date-btn filter-trigger flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-2xl text-[11px] font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all whitespace-nowrap group"
                    data-target="menu-filter-date">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400 group-[.active]:text-blue-600"></i>
                    <span class="js-filter-date-label filter-label">Pilih Tanggal</span>
                    <i data-lucide="x" class="js-filter-date-reset hidden w-3 h-3 ml-1 text-blue-600"></i>
                </button>
            @endif
        </div>

        @if(auth()->user()->role !== 'teknisi')
            <!-- Row 2: Branch and Category -->
            <div class="flex gap-3">
                <button type="button"
                    class="js-filter-branch-btn filter-trigger flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all bg-white text-slate-500 border-2 border-slate-200 hover:border-blue-300 group"
                    data-target="menu-filter-branch">
                    <i data-lucide="git-branch" class="w-4 h-4 group-[.active]:text-blue-600"></i>
                    <span class="js-filter-branch-label filter-label">Pilih Cabang</span>
                    <i data-lucide="x" class="js-filter-branch-reset hidden w-3 h-3 ml-1 text-blue-600"></i>
                </button>
                <button type="button"
                    class="js-filter-category-btn filter-trigger flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all bg-white text-slate-500 border-2 border-slate-200 hover:border-blue-300 group"
                    data-target="menu-filter-category">
                    <i data-lucide="layout-grid" class="w-4 h-4 group-[.active]:text-blue-600"></i>
                    <span class="js-filter-category-label filter-label">Pilih Kategori</span>
                    <i data-lucide="x" class="js-filter-category-reset hidden w-3 h-3 ml-1 text-blue-600"></i>
                </button>
            </div>
        @endif

        <!-- Row 3: Type Filters -->
        <div class="bg-slate-50 rounded-2xl p-3 grid grid-cols-4 gap-2">
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => null])) }}"
                class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'all' ? 'bg-slate-800 text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-100' }}">
                Semua
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'rembush'])) }}"
                class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'rembush' ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-100' }}">
                <i data-lucide="receipt" class="w-3.5 h-3.5 inline mr-1"></i>Rembush
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'pengajuan'])) }}"
                class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'pengajuan' ? 'bg-teal-600 text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-100' }}">
                <i data-lucide="shopping-bag" class="w-3.5 h-3.5 inline mr-1"></i>Pengajuan
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'gudang'])) }}"
                class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'gudang' ? 'bg-amber-600 text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-100' }}">
                <i data-lucide="package" class="w-3.5 h-3.5 inline mr-1"></i>Gudang
            </a>
        </div>
    </div>

    <!-- MOBILE: 5 Rows Layout (screens below md) -->
    <div class="flex md:hidden flex-col gap-3">
        <!-- Row 1: Search -->
        <div class="relative group">
            <i data-lucide="search"
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
            <input type="text" id="instant-search-mobile" value="{{ request('search') }}"
                placeholder="Cari invoice, nama..." autocomplete="off"
                class="search-input-sync w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-semibold focus:outline-none focus:ring-4 focus:ring-blue-500/5 focus:border-blue-400 transition-all placeholder:text-gray-400">
            <button type="button"
                class="search-clear absolute right-3 top-1/2 -translate-y-1/2 hidden p-1 rounded-lg hover:bg-gray-200 transition-colors">
                <i data-lucide="x" class="w-3 h-3 text-gray-400"></i>
            </button>
        </div>

        @if(auth()->user()->role !== 'teknisi')
            <!-- Row 2: Date -->
            <button type="button"
                class="js-filter-date-btn filter-trigger w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border-2 border-gray-200 rounded-2xl text-xs font-bold text-gray-600 hover:border-blue-300 hover:bg-blue-50/30 transition-all group"
                data-target="menu-filter-date">
                <i data-lucide="calendar" class="w-4 h-4 text-gray-400 group-[.active]:text-blue-600"></i>
                <span class="js-filter-date-label filter-label">Pilih Tanggal</span>
                <i data-lucide="x" class="js-filter-date-reset hidden w-4 h-4 ml-1 text-blue-600"></i>
            </button>

            <!-- Row 3: Branch -->
            <button type="button"
                class="js-filter-branch-btn filter-trigger w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all bg-white text-slate-500 border-2 border-slate-200 hover:border-blue-300 group"
                data-target="menu-filter-branch">
                <i data-lucide="git-branch" class="w-4 h-4 group-[.active]:text-blue-600"></i>
                <span class="js-filter-branch-label filter-label">Pilih Cabang</span>
                <i data-lucide="x" class="js-filter-branch-reset hidden w-4 h-4 ml-1 text-blue-600"></i>
            </button>

            <!-- Row 4: Category -->
            <button type="button"
                class="js-filter-category-btn filter-trigger w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all bg-white text-slate-500 border-2 border-slate-200 hover:border-blue-300 group"
                data-target="menu-filter-category">
                <i data-lucide="layout-grid" class="w-4 h-4 group-[.active]:text-blue-600"></i>
                <span class="js-filter-category-label filter-label">Pilih Kategori</span>
                <i data-lucide="x" class="js-filter-category-reset hidden w-4 h-4 ml-1 text-blue-600"></i>
            </button>
        @endif

        <!-- Row 5: Type Filters -->
        <div class="bg-slate-50 rounded-2xl p-2.5 grid grid-cols-2 gap-2">
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => null])) }}"
                class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'all' ? 'bg-slate-800 text-white shadow-lg' : 'bg-white text-slate-500' }}">
                Semua
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'rembush'])) }}"
                class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'rembush' ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-slate-500' }}">
                <i data-lucide="receipt" class="w-3.5 h-3.5 inline mr-1"></i>Rembush
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'pengajuan'])) }}"
                class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'pengajuan' ? 'bg-teal-600 text-white shadow-lg' : 'bg-white text-slate-500' }}">
                <i data-lucide="shopping-bag" class="w-3.5 h-3.5 inline mr-1"></i>Pengajuan
            </a>
            <a href="{{ route('transactions.index', array_merge(request()->except('type'), ['type' => 'gudang'])) }}"
                class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all text-center {{ $currentType === 'gudang' ? 'bg-amber-600 text-white shadow-lg' : 'bg-white text-slate-500' }}">
                <i data-lucide="package" class="w-3.5 h-3.5 inline mr-1"></i>Gudang
            </a>
        </div>
    </div>

    @if(auth()->user()->role !== 'teknisi')
        <!-- SINGLETON POPOVERS: Shared by all layouts (Fixed Positioning) -->
        <div id="popover-container">
            <!-- Branch Popover -->
            <div id="menu-filter-branch"
                class="filter-popover hidden fixed w-full md:w-72 bg-white border border-slate-200 rounded-2xl shadow-xl z-[100] p-3 animate-in fade-in slide-in-from-top-2 duration-200">
                <div class="relative mb-3">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"></i>
                    <input type="text" placeholder="Cari cabang..."
                        class="popover-search w-full pl-8 pr-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all placeholder:text-slate-400">
                </div>
                <div class="max-h-64 overflow-y-auto custom-scrollbar flex flex-col gap-1 pr-1" id="branch-list">
                    @foreach($branches as $b)
                        <label
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all text-sm select-none hover:bg-slate-50 [&:has(input:checked)]:bg-blue-50 [&:has(input:checked)]:ring-1 [&:has(input:checked)]:ring-blue-200">
                            <div class="flex items-center justify-center relative w-4 h-4">
                                <input type="checkbox" name="branch_id[]" value="{{ $b->id }}"
                                    class="peer absolute w-full h-full opacity-0 cursor-pointer filter-checkbox z-10">
                                <div
                                    class="w-4 h-4 rounded border-2 border-slate-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 transition-all flex items-center justify-center">
                                    <i data-lucide="check"
                                        class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                            </div>
                            <span
                                class="text-xs font-bold text-slate-600 group-hover:text-slate-900 peer-checked:text-blue-700 transition-colors leading-none truncate">{{ $b->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Category Popover -->
            <div id="menu-filter-category"
                class="filter-popover hidden fixed w-full md:w-64 bg-white border border-slate-200 rounded-2xl shadow-xl z-[100] p-3 animate-in fade-in slide-in-from-top-2 duration-200">
                <div class="relative mb-3">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"></i>
                    <input type="text" placeholder="Cari kategori..."
                        class="popover-search w-full pl-8 pr-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-300 transition-all placeholder:text-slate-400">
                </div>
                <div class="max-h-64 overflow-y-auto custom-scrollbar flex flex-col gap-1 pr-1" id="category-list">
                    @foreach($categories as $key => $val)
                        <label
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer group transition-all text-sm select-none hover:bg-slate-50 [&:has(input:checked)]:bg-blue-50 [&:has(input:checked)]:ring-1 [&:has(input:checked)]:ring-blue-200">
                            <div class="flex items-center justify-center relative w-4 h-4">
                                <input type="checkbox" name="category[]" value="{{ $key }}"
                                    class="peer absolute w-full h-full opacity-0 cursor-pointer filter-checkbox z-10">
                                <div
                                    class="w-4 h-4 rounded border-2 border-slate-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 transition-all flex items-center justify-center">
                                    <i data-lucide="check"
                                        class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                </div>
                            </div>
                            <span
                                class="text-xs font-bold text-slate-600 group-hover:text-slate-900 peer-checked:text-blue-700 transition-colors leading-none truncate">{{ $val }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Date Popover -->
            <div id="menu-filter-date"
                class="filter-popover hidden fixed bg-white border border-slate-200 rounded-2xl shadow-xl z-[100] flex flex-col md:flex-row divide-y md:divide-y-0 md:divide-x divide-slate-100 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200 min-w-min w-full md:w-auto">
                <!-- Presets -->
                <div class="w-full md:w-40 bg-slate-50 p-2.5 flex flex-col gap-1 shrink-0">
                    <button type="button"
                        class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all"
                        data-range="today">Hari Ini</button>
                    <button type="button"
                        class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all"
                        data-range="yesterday">Kemarin</button>
                    <button type="button"
                        class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all"
                        data-range="last7">7 Hari Terakhir</button>
                    <button type="button"
                        class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all"
                        data-range="last30">30 Hari Terakhir</button>
                    <button type="button"
                        class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all"
                        data-range="thisMonth">Bulan Ini</button>
                    <button type="button"
                        class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-semibold text-left text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm transition-all"
                        data-range="lastMonth">Bulan Lalu</button>
                    <div class="h-px bg-slate-200/60 my-1 mx-2"></div>
                    <button type="button"
                        class="date-preset-btn px-3 py-2.5 rounded-xl text-xs font-bold text-left bg-blue-50 text-blue-700 ring-1 ring-blue-200"
                        data-range="custom">Custom Tanggal</button>
                </div>
                <!-- Custom Range -->
                <div class="p-4 flex-1 flex flex-col gap-4 bg-white md:w-[360px]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="group">
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5 ml-1 group-focus-within:text-blue-500 transition-colors">DARI
                                TANGGAL</label>
                            <input type="date" id="filter-start-date"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                        <div class="group">
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5 ml-1 group-focus-within:text-blue-500 transition-colors">SAMPAI
                                TANGGAL</label>
                            <input type="date" id="filter-end-date"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="btn-cancel-date"
                            class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition-all active:scale-95">Batal</button>
                        <button type="button" id="btn-apply-date"
                            class="flex-[2] py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]"
                            disabled>Terapkan Filter</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>