@extends('layouts.app')

@section('title', 'Analytics Price Index')

@section('content')
<div class="space-y-6 p-6">

    {{-- ─── Header ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📈 Analytics Price Index</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Ringkasan tren harga, volatilitas, dan distribusi kategori</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('price-index.index') }}"
               class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                ← Kembali
            </a>
            <a href="{{ route('price-index.export-csv') }}"
               class="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                ⬇️ Export CSV
            </a>
        </div>
    </div>

    {{-- ─── Summary Cards ────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Item Terindeks</span>
            <span class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ number_format($totalIndexed) }}</span>
            <span class="text-xs text-gray-400">dari master catalog</span>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col gap-1 {{ $pendingAnomalies > 0 ? 'border-l-4 border-l-red-500' : '' }}">
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Anomali Pending</span>
            <span class="text-3xl font-black {{ $pendingAnomalies > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                {{ number_format($pendingAnomalies) }}
            </span>
            <a href="{{ route('price-index.anomalies') }}" class="text-xs text-blue-500 hover:underline">Review →</a>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col gap-1 {{ $needsReview > 0 ? 'border-l-4 border-l-amber-500' : '' }}">
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Perlu Review</span>
            <span class="text-3xl font-black {{ $needsReview > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600' }}">
                {{ number_format($needsReview) }}
            </span>
            <span class="text-xs text-gray-400">item baru belum terverifikasi</span>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Override Manual</span>
            <span class="text-3xl font-black text-purple-600 dark:text-purple-400">{{ number_format($manualOverrides) }}</span>
            <span class="text-xs text-gray-400">ditetapkan Owner/Atasan</span>
        </div>
    </div>

    {{-- ─── Row 1: Trend Chart + Severity ────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Anomali Trend (3 months) --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Tren Anomali Harga</h2>
                    <p class="text-xs text-gray-400">90 hari terakhir</p>
                </div>
            </div>
            <div class="h-56">
                <canvas id="trendChart"></canvas>
            </div>
            @if($anomalyTrend->isEmpty())
                <div class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">
                    Belum ada data anomali
                </div>
            @endif
        </div>

        {{-- Severity Donut --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-1">Distribusi Severity</h2>
            <p class="text-xs text-gray-400 mb-4">Anomali pending aktif</p>
            <div class="h-44 relative">
                <canvas id="severityChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Critical</span>
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $severityBreakdown['critical'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span> Medium</span>
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $severityBreakdown['medium'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-400 inline-block"></span> Low</span>
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $severityBreakdown['low'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Row 2: Top Volatile + Category Breakdown ─────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top 10 Volatile Items --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-1">🔥 Top 10 Item Paling Volatile</h2>
            <p class="text-xs text-gray-400 mb-5">Berdasarkan rata-rata deviation % tertinggi</p>

            @forelse($topVolatile as $idx => $item)
                @php
                    $barWidth = min(100, $item['avg_excess']);
                    $barColor = $item['avg_excess'] >= 50 ? 'bg-red-500' : ($item['avg_excess'] >= 20 ? 'bg-amber-400' : 'bg-emerald-400');
                @endphp
                <div class="mb-3">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-gray-700 dark:text-gray-300 truncate max-w-[60%]">
                            {{ $idx + 1 }}. {{ $item['item_name'] }}
                        </span>
                        <span class="font-bold {{ $item['avg_excess'] >= 50 ? 'text-red-600' : ($item['avg_excess'] >= 20 ? 'text-amber-600' : 'text-emerald-600') }}">
                            +{{ $item['avg_excess'] }}% <span class="text-gray-400 font-normal">({{ $item['anomaly_count'] }}x)</span>
                        </span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $barWidth }}%"></div>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-gray-400 text-sm">
                    ✅ Tidak ada anomali terdeteksi. Semua harga normal.
                </div>
            @endforelse
        </div>

        {{-- Category Breakdown --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-1">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">📦 Distribusi Kategori</h2>
            </div>
            <p class="text-xs text-gray-400 mb-5">Jumlah item per kategori</p>

            <div class="h-56">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ─── Items Needing Review ──────────────────────────────── --}}
    @if($itemsNeedingReview->isNotEmpty())
    <div class="bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-200 dark:border-amber-800 p-6">
        <h2 class="text-base font-bold text-amber-900 dark:text-amber-400 mb-1">⚠️ Item Baru — Perlu Review Harga</h2>
        <p class="text-xs text-amber-700 dark:text-amber-500 mb-4">Item ini terdeteksi dari pengajuan tetapi belum diverifikasi harga referensinya</p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-amber-700 dark:text-amber-500 uppercase">
                    <tr>
                        <th class="pb-3 pr-4">Nama Barang</th>
                        <th class="pb-3 pr-4">Kategori</th>
                        <th class="pb-3 pr-4 text-right">Harga Saat Ini</th>
                        <th class="pb-3 text-right">Pertama Masuk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-amber-100 dark:divide-amber-900/30">
                    @foreach($itemsNeedingReview as $item)
                        <tr class="hover:bg-amber-100/50 dark:hover:bg-amber-900/20 transition">
                            <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ $item->item_name }}</td>
                            <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $item->category ?? '-' }}</td>
                            <td class="py-2 pr-4 text-right text-gray-700 dark:text-gray-300">Rp {{ number_format($item->min_price, 0, ',', '.') }}</td>
                            <td class="py-2 text-right text-gray-400 text-xs">{{ $item->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <a href="{{ route('price-index.index', ['review' => 1]) }}"
               class="inline-flex items-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
               Review Semua Item →
            </a>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor  = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const labelColor = isDark ? '#9ca3af' : '#6b7280';

    // ── Anomaly Trend Chart ─────────────────────────────────
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        const trendData = @json($anomalyTrend);
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendData.map(d => d.month),
                datasets: [{
                    label: 'Anomali',
                    data: trendData.map(d => d.count),
                    backgroundColor: 'rgba(239,68,68,0.7)',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: labelColor, font: { size: 11 } } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: labelColor, font: { size: 11 }, stepSize: 1 } }
                }
            }
        });
    }

    // ── Severity Donut Chart ────────────────────────────────
    const sevCtx = document.getElementById('severityChart');
    if (sevCtx) {
        const sev = @json($severityBreakdown);
        new Chart(sevCtx, {
            type: 'doughnut',
            data: {
                labels: ['Critical', 'Medium', 'Low'],
                datasets: [{
                    data: [sev.critical ?? 0, sev.medium ?? 0, sev.low ?? 0],
                    backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${ctx.raw} anomali`
                        }
                    }
                }
            }
        });
    }

    // ── Category Bar Chart ──────────────────────────────────
    const catCtx = document.getElementById('categoryChart');
    if (catCtx) {
        const catData = @json($categoryBreakdown);
        const palette = [
            '#6366f1','#8b5cf6','#ec4899','#f43f5e',
            '#f97316','#eab308','#22c55e','#06b6d4',
        ];
        new Chart(catCtx, {
            type: 'bar',
            data: {
                labels: catData.map(d => d.category),
                datasets: [{
                    label: 'Jumlah Item',
                    data: catData.map(d => d.count),
                    backgroundColor: catData.map((_, i) => palette[i % palette.length]),
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: labelColor, font: { size: 11 }, stepSize: 1 } },
                    y: { grid: { display: false }, ticks: { color: labelColor, font: { size: 11 } } }
                }
            }
        });
    }
});
</script>
@endpush
