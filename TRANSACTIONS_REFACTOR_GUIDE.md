# Guide: Refactoring `transactions`

Dokumen ini menjelaskan rancangan pemecahan file `index.blade.php` (5,000+ baris) menjadi struktur yang lebih bersih dan modular.

## 📂 Transaction Index File Tree

```text
resources/views/transactions/
├── index.blade.php                 # File utama (Hanya layout & include)
└── 📂 partials/
    ├── 📂 index/
    │   ├── filter-toolbar.blade.php # Search, Branch, Category, Date Filters
    │   ├── status-tabs.blade.php    # Tab navigasi status (Pending, Selesai, dll)
    │   ├── table-desktop.blade.php  # Template table untuk layar lebar
    │   ├── card-mobile.blade.php    # Template card untuk layar hp
    │   └── pagination.blade.php     # Footer & Kontrol halaman
    │
    ├── 📂 modals/
    │   ├── export-modal.blade.php   # Modal Export Excel
    │   ├── view-detail-modal.blade.php # Modal Detail (AJAX)
    │   └── payment-modal.blade.php  # Modal Upload Pembayaran
    │
    └── 📂 scripts/
        └── index-js.blade.php       # Semua logika JS (SearchEngine, AJAX)
```

---

## 🛠 Contoh Implementasi

### 1. `index.blade.php` (Setelah Refactor)
File utama menjadi sangat pendek dan mudah dibaca karena hanya memanggil partials.

```blade
@extends('layouts.app')

@section('page-title', 'Data Riwayat Transaksi')

@section('content')
    <div class="bg-white shadow-sm border border-gray-100">
        {{-- 1. Toolbar Filter --}}
        @include('transactions.partials.index.filter-toolbar')

        <div id="search-results-container">
            {{-- 2. Status Tabs --}}
            @include('transactions.partials.index.status-tabs')

            {{-- 3. Data View (Table & Mobile) --}}
            @include('transactions.partials.index.table-desktop')
            @include('transactions.partials.index.card-mobile')

            {{-- 4. Footer & Pagination --}}
            @include('transactions.partials.index.pagination')
        </div>
    </div>
@endsection

@push('modals')
    @include('transactions.partials.modals.export-modal')
    @include('transactions.partials.modals.view-detail-modal')
    @include('transactions.partials.modals.payment-modal')
@endpush

@push('scripts')
    @include('transactions.scripts.index-js')
@endpush
```

### 2. `partials/index/filter-toolbar.blade.php` (Contoh Ekstraksi)
Memindahkan logika UI yang kompleks ke file terpisah.

```blade
<div class="p-3 sm:p-4 md:p-5 border-b border-gray-100 bg-white/80 backdrop-blur-sm">
    <div class="hidden lg:flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="relative flex-1 group">
            <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <input type="text" id="instant-search" placeholder="Cari invoice..." 
                   class="search-input-sync w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-2xl text-xs font-semibold">
        </div>
        
        <!-- Filter Cabang -->
        @if(auth()->user()->role !== 'teknisi')
            @include('transactions.partials.index.filters.branch-filter')
            @include('transactions.partials.index.filters.category-filter')
            @include('transactions.partials.index.filters.date-filter')
        @endif
    </div>
    
    <!-- Mobile Filter Layout (Optional can be further broken down) -->
</div>
```

### 3. `scripts/index-js.blade.php` (Logika Terpisah)
Logika JavaScript tidak lagi bercampur dengan HTML, memudahkan debugging.

```blade
<script>
    const csrfToken = '{{ csrf_token() }}';
    
    // Inisialisasi SearchEngine
    const SearchEngine = (function() {
        // ... (Ribuan baris logika SearchEngine Anda di sini)
    })();

    // Modal Handlers
    function openExportModal() {
        // Logic modal...
    }
</script>
```

---

## 📈 Keuntungan Struktur Ini
1. **Navigasi Cepat**: Jika ingin mengubah tampilan tabel, Anda langsung menuju `table-desktop.blade.php` tanpa perlu scroll ribuan baris.
2. **Gampang Debugging**: Jika ada error JavaScript, Anda tahu file `index-js.blade.php` adalah tempatnya.
3. **Collaboration Ready**: Beberapa developer bisa mengerjakan bagian berbeda (misal: satu di Modal, satu di Filter) tanpa konflik di file yang sama.
