@extends('layouts.app')

@section('title', 'Price Index')

@section('content')
<div class="space-y-6 p-6">

    {{-- ─── Header ─────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📊 Price Index</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Referensi harga barang dari data historis pengajuan</p>
        </div>

        <div class="flex items-center gap-2">
            @if (in_array(auth()->user()->role, ['atasan','owner']))
                {{-- Analytics Link --}}
                <a href="{{ route('price-index.analytics') }}"
                   class="flex items-center gap-1.5 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800 px-3 py-2 rounded-lg text-sm font-medium hover:bg-purple-100 transition">
                    📈 Analytics
                </a>

                {{-- Anomali badge --}}
                @if ($pendingCount > 0)
                    <a href="{{ route('price-index.anomalies') }}"
                       class="flex items-center gap-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        {{ $pendingCount }} Anomali Pending
                    </a>
                @else
                    <a href="{{ route('price-index.anomalies') }}"
                       class="flex items-center gap-1.5 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800 px-3 py-2 rounded-lg text-sm font-medium hover:bg-green-100 transition">
                        ✅ Anomali Harga
                    </a>
                @endif

                {{-- Tombol Tambah --}}
                <button onclick="openAddModal()"
                        class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Manual
                </button>
            @endif
        </div>
    </div>

    {{-- ─── Flash messages ─────────────────────────────── --}}
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 text-green-800 dark:text-green-300 text-sm flex items-center gap-2">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- ─── Filter & Search ────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Tabs --}}
            <div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-xl self-start">
                <a href="{{ route('price-index.index') }}" 
                   class="px-4 py-2 text-xs font-bold rounded-lg transition {{ !request('review') ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    Semua Data
                </a>
                <a href="{{ route('price-index.index', ['review' => 1]) }}" 
                   class="px-4 py-2 text-xs font-bold rounded-lg transition flex items-center gap-1.5 {{ request('review') ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    Perlu Review
                    @if($needsReviewCount > 0)
                        <span class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $needsReviewCount }}</span>
                    @endif
                </a>
            </div>

            <form method="GET" action="{{ route('price-index.index') }}" class="flex flex-col sm:flex-row gap-3 flex-1 lg:justify-end">
                @if(request('review')) <input type="hidden" name="review" value="1"> @endif
                
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama barang..."
                       class="flex-1 lg:max-w-xs border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"/>

                <select name="category" class="border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-sm font-medium transition">
                    Cari
                </button>
                @if (request()->hasAny(['search','category','review']))
                    <a href="{{ route('price-index.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 px-3 py-2 text-sm underline">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- ─── Tabel ───────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left w-8">#</th>
                        <th class="px-4 py-3 text-left">Nama Barang</th>
                        <th class="px-4 py-3 text-left">Kategori</th>
                        <th class="px-4 py-3 text-left">Satuan</th>
                        <th class="px-4 py-3 text-right">Harga Min</th>
                        <th class="px-4 py-3 text-right">Harga Avg</th>
                        <th class="px-4 py-3 text-right">Harga Maks</th>
                        <th class="px-4 py-3 text-center">Sumber</th>
                        <th class="px-4 py-3 text-center">Transaksi</th>
                        <th class="px-4 py-3 text-center">Update</th>
                        @if (in_array(auth()->user()->role, ['atasan','owner']))
                            <th class="px-4 py-3 text-center w-24">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($priceIndexes as $idx => $pi)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition" id="pi-row-{{ $pi->id }}">
                            <td class="px-4 py-3 text-gray-400">{{ $priceIndexes->firstItem() + $idx }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                <div class="flex flex-col">
                                    <span>{{ $pi->item_name }}</span>
                                    @if($pi->needs_initial_review)
                                        <span class="inline-flex items-center gap-1 w-fit mt-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider animate-pulse">
                                            ⚠️ Perlu Review
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $pi->category ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $pi->unit }}</td>

                            {{-- Harga Min --}}
                            <td class="px-4 py-3 text-right">
                                <span class="text-green-600 dark:text-green-400 font-medium">
                                    {{ $pi->formatted_min }}
                                </span>
                            </td>

                            {{-- Harga Avg --}}
                            <td class="px-4 py-3 text-right">
                                <span class="text-blue-600 dark:text-blue-400 font-medium">
                                    {{ $pi->formatted_avg }}
                                </span>
                            </td>

                            {{-- Harga Maks --}}
                            <td class="px-4 py-3 text-right">
                                <span class="text-orange-600 dark:text-orange-400 font-medium">
                                    {{ $pi->formatted_max }}
                                </span>
                            </td>

                            {{-- Sumber: Manual vs Auto --}}
                            <td class="px-4 py-3 text-center">
                                @if($pi->needs_initial_review)
                                    <span class="inline-flex items-center gap-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs px-2 py-0.5 rounded-full font-medium">
                                        Review
                                    </span>
                                @elseif ($pi->is_manual)
                                    <span class="inline-flex items-center gap-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs px-2 py-0.5 rounded-full font-medium">
                                        Manual
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs px-2 py-0.5 rounded-full font-medium">
                                        Auto
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">{{ number_format($pi->total_transactions) }}</td>

                            <td class="px-4 py-3 text-center text-xs text-gray-400">
                                {{ $pi->last_calculated_at?->format('d/m/Y') ?? '-' }}
                            </td>

                            @if (in_array(auth()->user()->role, ['atasan','owner']))
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        {{-- Edit --}}
                                        <button onclick="openEditModal({{ $pi->id }}, {{ $pi->toJson() }})"
                                                class="p-1.5 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 transition"
                                                title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>

                                        {{-- Delete (Owner only) --}}
                                        @if (auth()->user()->role === 'owner')
                                            <button onclick="deleteIndex({{ $pi->id }}, '{{ addslashes($pi->item_name) }}')"
                                                    class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500 dark:text-red-400 transition"
                                                    title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-10 h-10 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm">Belum ada data Price Index.</p>
                                    @if (in_array(auth()->user()->role, ['atasan','owner']))
                                        <button onclick="openAddModal()" class="text-blue-600 text-sm underline">Tambah sekarang</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($priceIndexes->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                {{ $priceIndexes->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     MODAL: Tambah Price Index
══════════════════════════════════════════════════════════════════ --}}
@if (in_array(auth()->user()->role, ['atasan','owner']))
<div id="addModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">➕ Tambah Price Index Manual</h2>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form action="{{ route('price-index.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Barang *</label>
                    <input type="text" name="item_name" required
                           class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                           placeholder="cth: Kabel NYM 3x2.5"/>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                        <input type="text" name="category"
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                               placeholder="cth: Elektrikal"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Satuan *</label>
                        <input type="text" name="unit" value="pcs" required
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                               placeholder="pcs, meter, kg, set"/>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Min *</label>
                        <input type="number" name="min_price" id="add_min_price" required min="0" step="1"
                               oninput="autoCalcAvgAdd()"
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                               placeholder="0"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Harga Avg *
                            <button type="button" onclick="autoCalcAvgAdd()" class="ml-1 text-xs text-blue-500 hover:underline">(Hitung)</button>
                        </label>
                        <input type="number" name="avg_price" id="add_avg_price" required min="0" step="1"
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                               placeholder="0"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Maks *</label>
                        <input type="number" name="max_price" id="add_max_price" required min="0" step="1"
                               oninput="autoCalcAvgAdd()"
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                               placeholder="0"/>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ─── MODAL: Edit Price Index ─────────────────────────────────── --}}
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">✏️ Edit Price Index</h2>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="editForm">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Barang *</label>
                    <input type="text" id="edit_item_name" name="item_name" required
                           class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                        <input type="text" id="edit_category" name="category"
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Satuan *</label>
                        <input type="text" id="edit_unit" name="unit" required
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Min *</label>
                        <input type="number" id="edit_min_price" name="min_price" required min="0"
                               oninput="autoCalcAvgEdit()"
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Harga Avg *
                            <button type="button" onclick="autoCalcAvgEdit()" class="ml-1 text-xs text-blue-500 hover:underline">(Hitung)</button>
                        </label>
                        <input type="number" id="edit_avg_price" name="avg_price" required min="0"
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Harga Maks *</label>
                        <input type="number" id="edit_max_price" name="max_price" required min="0"
                               oninput="autoCalcAvgEdit()"
                               class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
                    </div>
                </div>
                {{-- ✅ Calculated Info (System Recommendation) --}}
                <div id="calculatedInfoBlock" class="hidden p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-xl space-y-2">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">💡 System Recommendation (Auto)</p>
                    <div class="grid grid-cols-3 gap-2 text-xs text-blue-600 dark:text-blue-300">
                        <div>Min: <span id="calc_min_val"></span></div>
                        <div>Avg: <span id="calc_avg_val"></span></div>
                        <div>Max: <span id="calc_max_val"></span></div>
                    </div>
                    <div class="flex items-center justify-between pt-1 mt-1 border-t border-blue-100 dark:border-blue-800">
                        <span class="text-[10px] text-blue-500">Berdasarkan total <span id="calc_tx_count"></span> transaksi</span>
                        <button type="button" onclick="useSystemValues()" class="text-[10px] font-bold text-blue-700 dark:text-blue-400 hover:underline">
                            Gunakan Nilai Ini
                        </button>
                    </div>
                </div>

                {{-- ✅ Audit Trail: Manual Reason --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Alasan Penyesuaian
                        <span class="text-gray-400 text-xs font-normal ml-1">(opsional, untuk audit trail)</span>
                    </label>
                    <textarea id="edit_manual_reason" name="manual_reason" rows="2"
                              maxlength="500"
                              placeholder="cth: Penyesuaian harga pasaran per April 2026..."
                              class="w-full border rounded-xl px-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                </div>
                <div id="editError" class="hidden text-red-600 text-sm"></div>
            </div>
            <div class="px-6 pb-6 flex items-center justify-between">
                <div>
                    <button type="button" id="resetToAutoBtn" onclick="resetToAuto()"
                            class="hidden text-xs font-medium text-red-600 dark:text-red-400 hover:underline">
                        🔄 Reset ke Auto
                    </button>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition">Batal</button>
                    <button type="button" id="editSubmitBtn" onclick="submitEdit()"
                            class="px-5 py-2 rounded-xl text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium transition">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
let currentEditId = null;
const csrfToken = '{{ csrf_token() }}';

// ─── Add Modal ─────────────────────────────────────────────────
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addModal').classList.add('flex');
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addModal').classList.remove('flex');
}

// ─── Edit Modal ────────────────────────────────────────────────
function openEditModal(id, data) {
    currentEditId = id;
    document.getElementById('edit_item_name').value = data.item_name || '';
    document.getElementById('edit_category').value  = data.category  || '';
    document.getElementById('edit_unit').value      = data.unit      || 'pcs';
    document.getElementById('edit_min_price').value = data.min_price || 0;
    document.getElementById('edit_max_price').value = data.max_price || 0;
    document.getElementById('edit_avg_price').value = data.avg_price || 0;
    document.getElementById('edit_manual_reason').value = '';

    // Handle Calculated Stats
    const calcBlock = document.getElementById('calculatedInfoBlock');
    if (data.calculated_avg_price > 0) {
        calcBlock.classList.remove('hidden');
        document.getElementById('calc_min_val').textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(data.calculated_min_price);
        document.getElementById('calc_avg_val').textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(data.calculated_avg_price);
        document.getElementById('calc_max_val').textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(data.calculated_max_price);
        document.getElementById('calc_tx_count').textContent = data.total_transactions || 0;
        
        // Simpan raw data untuk fungsi useSystemValues
        calcBlock.dataset.min = data.calculated_min_price;
        calcBlock.dataset.max = data.calculated_max_price;
        calcBlock.dataset.avg = data.calculated_avg_price;
    } else {
        calcBlock.classList.add('hidden');
    }

    // Handle Reset Button Visibility
    const resetBtn = document.getElementById('resetToAutoBtn');
    if (data.is_manual) {
        resetBtn.classList.remove('hidden');
    } else {
        resetBtn.classList.add('hidden');
    }

    document.getElementById('editError').classList.add('hidden');
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
    currentEditId = null;
}

// ─── Auto-calc avg helpers ──────────────────────────────────────
function autoCalcAvgEdit() {
    const min = parseFloat(document.getElementById('edit_min_price').value) || 0;
    const max = parseFloat(document.getElementById('edit_max_price').value) || 0;
    if (min > 0 && max >= min) {
        document.getElementById('edit_avg_price').value = Math.round((min + max) / 2);
    }
}
function autoCalcAvgAdd() {
    const min = parseFloat(document.getElementById('add_min_price')?.value) || 0;
    const max = parseFloat(document.getElementById('add_max_price')?.value) || 0;
    if (min > 0 && max >= min) {
        document.getElementById('add_avg_price').value = Math.round((min + max) / 2);
    }
}

async function submitEdit() {
    if (!currentEditId) return;
    const btn = document.getElementById('editSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const form = document.getElementById('editForm');
    const data = {
        item_name:     document.getElementById('edit_item_name').value,
        category:      document.getElementById('edit_category').value,
        unit:          document.getElementById('edit_unit').value,
        min_price:     parseFloat(document.getElementById('edit_min_price').value),
        avg_price:     parseFloat(document.getElementById('edit_avg_price').value),
        max_price:     parseFloat(document.getElementById('edit_max_price').value),
        manual_reason: document.getElementById('edit_manual_reason').value, // ✅ Audit trail
    };

    try {
        const res = await fetch(`/price-index/${currentEditId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(data),
        });
        const json = await res.json();
        if (res.ok && json.success) {
            closeEditModal();
            location.reload();
        } else {
            document.getElementById('editError').textContent = json.error || 'Gagal menyimpan.';
            document.getElementById('editError').classList.remove('hidden');
        }
    } catch (e) {
        document.getElementById('editError').textContent = 'Terjadi kesalahan koneksi.';
        document.getElementById('editError').classList.remove('hidden');
    }
    btn.disabled = false;
    btn.textContent = 'Simpan Perubahan';
}
// ─── Reset & Auto Helpers ──────────────────────────────────────────
function useSystemValues() {
    const block = document.getElementById('calculatedInfoBlock');
    if (block.dataset.min) {
        document.getElementById('edit_min_price').value = Math.round(block.dataset.min);
        document.getElementById('edit_max_price').value = Math.round(block.dataset.max);
        document.getElementById('edit_avg_price').value = Math.round(block.dataset.avg);
        document.getElementById('edit_manual_reason').value = 'Mengikuti rekomendasi sistem (Sync)';
    }
}

async function resetToAuto() {
    if (!currentEditId) return;
    
    openConfirmModal('globalConfirmModal', {
        title: 'Reset ke Auto?',
        message: 'Kembalikan status ke <strong>Auto</strong>? Harga akan dihitung ulang secara otomatis dari history transaksi.',
        action: `/price-index/${currentEditId}/reset-auto`,
        method: 'POST',
        submitText: 'Ya, Reset',
        submitColor: 'bg-amber-500 hover:bg-amber-600',
        icon: 'refresh-cw',
        iconColor: 'text-amber-500',
        iconBg: 'bg-amber-50',
        onConfirm: async () => {
            try {
                const response = await fetch(`/price-index/${currentEditId}/reset-auto`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error(result.error || 'Gagal reset auto');
                }
            } catch (err) {
                showToast(err.message, 'error');
            }
        }
    });
}

// ─── Delete ─────────────────────────────────────────────────────
function deleteIndex(id, name) {
    openConfirmModal('globalConfirmModal', {
        title: 'Hapus Price Index?',
        message: `Yakin ingin menghapus Price Index <strong class="text-slate-800">"${name}"</strong>?`,
        action: `/price-index/${id}`,
        method: 'DELETE',
        submitText: 'Ya, Hapus',
        onConfirm: async () => {
            try {
                const response = await fetch(`/price-index/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    showToast(result.message, 'success');
                    const row = document.getElementById(`pi-row-${id}`);
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                        setTimeout(() => row.remove(), 300);
                    }
                } else {
                    throw new Error(result.error || 'Gagal menghapus');
                }
            } catch (err) {
                showToast(err.message, 'error');
            }
        }
    });
}
</script>

@endpush
