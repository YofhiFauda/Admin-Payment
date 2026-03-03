<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $isAdmin = $user->canManageStatus(); // admin, atasan, owner
        $userId  = $user->id;

        // ─────────────────────────────────────────────
        // Base query builder helpers
        // ─────────────────────────────────────────────
        $base = function () use ($isAdmin, $userId) {
            $q = Transaction::query();
            if (!$isAdmin) {
                $q->where('submitted_by', $userId);
            }
            return $q;
        };

        $thisMonth = function () use ($base) {
            return $base()->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
        };

        // ─────────────────────────────────────────────
        // 1. KEY METRICS (bulan ini)
        // ─────────────────────────────────────────────
        $totalPengeluaran = (clone $thisMonth())
            ->whereIn('status', ['approved', 'completed'])
            ->sum(DB::raw('COALESCE(amount, 0) + COALESCE(estimated_price, 0)'));

        // More precise: use effective_amount logic per row
        $totalPengeluaranRaw = (clone $thisMonth())
            ->whereIn('status', ['approved', 'completed'])
            ->get()
            ->sum(fn($t) => $t->effective_amount);

        $pendingCount = (clone $thisMonth())->where('status', 'pending')->count();
        $pendingTotal = (clone $thisMonth())->where('status', 'pending')->get()
                        ->sum(fn($t) => $t->effective_amount);

        $totalTransaksi = (clone $thisMonth())->count();

        $rejectedCount = (clone $thisMonth())->where('status', 'rejected')->count();

        // ─────────────────────────────────────────────
        // 2. CHART DATA
        // ─────────────────────────────────────────────

        // 2a. Per kategori (rembush category + pengajuan purchase_reason)
        $byCategory = (clone $base())
            ->whereIn('status', ['approved', 'completed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get()
            ->groupBy(function ($t) {
                if ($t->type === 'rembush') {
                    return Transaction::CATEGORIES[$t->category] ?? $t->category ?? 'Lainnya';
                }
                return Transaction::PURCHASE_REASONS[$t->purchase_reason] ?? $t->purchase_reason ?? 'Pengajuan';
            })
            ->map(fn($grp) => $grp->sum(fn($t) => $t->effective_amount))
            ->sortByDesc(fn($v) => $v)
            ->take(8);

        // 2b. Tren 6 bulan terakhir
        $trendMonths = collect(range(5, 0))->map(function ($offset) use ($base) {
            $month = now()->subMonths($offset);
            $total = (clone $base())
                ->whereIn('status', ['approved', 'completed'])
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->get()
                ->sum(fn($t) => $t->effective_amount);
            return [
                'label' => $month->format('M Y'),
                'value' => $total,
            ];
        });

        // 2c. Rembush vs Pengajuan (bulan ini, all statuses)
        $rembushTotal = (clone $thisMonth())->where('type', 'rembush')
            ->whereIn('status', ['approved', 'completed'])->get()
            ->sum(fn($t) => $t->effective_amount);
        $pengajuanTotal = (clone $thisMonth())->where('type', 'pengajuan')
            ->whereIn('status', ['approved', 'completed'])->get()
            ->sum(fn($t) => $t->effective_amount);

        // 2d. Per Branch (hanya admin/atasan/owner, karena butuh pivot)
        $byBranch = collect();
        if ($isAdmin) {
            $byBranch = DB::table('transaction_branches as tb')
                ->join('branches', 'branches.id', '=', 'tb.branch_id')
                ->join('transactions', 'transactions.id', '=', 'tb.transaction_id')
                ->whereIn('transactions.status', ['approved', 'completed'])
                ->whereMonth('transactions.created_at', now()->month)
                ->whereYear('transactions.created_at', now()->year)
                ->groupBy('branches.id', 'branches.name')
                ->orderByDesc('total')
                ->limit(8)
                ->select('branches.name', DB::raw('SUM(tb.allocation_amount) as total'))
                ->get();
        }

        // ─────────────────────────────────────────────
        // 3. QUICK ACTIONS — Pending transactions (admin/atasan/owner only)
        // ─────────────────────────────────────────────
        $pendingTransactions = collect();
        if ($isAdmin) {
            $pendingTransactions = Transaction::with(['submitter', 'branches'])
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get();
        }

        // ─────────────────────────────────────────────
        // 4. RECENT TRANSACTIONS
        // ─────────────────────────────────────────────
        $recentTransactions = (clone $base())
            ->with(['submitter'])
            ->latest()
            ->take(10)
            ->get();

        // ─────────────────────────────────────────────
        // 5. LEADERBOARD (admin/atasan/owner only)
        // ─────────────────────────────────────────────
        $topTeknisi = collect();
        $topVendor  = collect();

        if ($isAdmin) {
            $topTeknisi = User::withCount(['transactions as trx_count'])
                ->withSum(['transactions as trx_total' => function ($q) {
                    $q->whereIn('status', ['approved', 'completed']);
                }], 'amount')
                ->where('role', 'teknisi')
                ->orderByDesc('trx_count')
                ->take(5)
                ->get();

            $topVendor = Transaction::query()
                ->whereNotNull('customer')
                ->where('customer', '!=', '')
                ->whereIn('status', ['approved', 'completed'])
                ->groupBy('customer')
                ->orderByDesc('count')
                ->take(5)
                ->select('customer', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(amount,0)) as total'))
                ->get();
        }

        return view('dashboard.index', compact(
            'isAdmin',
            // Metrics
            'totalPengeluaranRaw',
            'pendingCount',
            'pendingTotal',
            'totalTransaksi',
            'rejectedCount',
            // Charts
            'byCategory',
            'trendMonths',
            'rembushTotal',
            'pengajuanTotal',
            'byBranch',
            // Tables
            'pendingTransactions',
            'recentTransactions',
            // Leaderboard
            'topTeknisi',
            'topVendor'
        ));
    }
}
