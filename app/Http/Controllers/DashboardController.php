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
        ini_set('memory_limit', '256M');
        $user    = Auth::user();

        // Check if user is technician, they cannot access dashboard
        if ($user->isTeknisi()) {
            return redirect()->route('transactions.create');
        }

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
        // 44: More precise: use database-level SUM to avoid memory exhaustion
        $totalPengeluaranRaw = (clone $thisMonth())
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');

        $pendingCount = (clone $thisMonth())->where('status', 'pending')->count();
        $pendingTotal = (clone $thisMonth())
            ->where('status', 'pending')
            ->sum(DB::raw('COALESCE(amount, estimated_price, 0)'));

        $totalTransaksi = (clone $thisMonth())->count();

        $rejectedCount = (clone $thisMonth())->where('status', 'rejected')->count();

        // ─────────────────────────────────────────────
        // 2. CHART DATA
        // ─────────────────────────────────────────────

        // 2a. Per kategori (optimized SQL grouping)
        $byCategoryData = (clone $base())
            ->whereIn('status', ['approved', 'completed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->groupBy('category')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->orderByDesc('total')
            ->take(8)
            ->get();

        $byCategory = $byCategoryData->pluck('total', 'category')->mapWithKeys(function ($total, $cat) {
            return [\App\Models\TransactionCategory::resolveLabel($cat, 'rembush') => (float) $total];
        });

        // 2b. Tren 6 bulan terakhir per Cabang (Multi-line)
        $trendMonthsLabels = collect(range(5, 0))->map(fn($offset) => now()->subMonths($offset)->format('Y-m'));
        $trendLabels = $trendMonthsLabels->map(fn($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'))->values()->toArray();
        $startDate = now()->subMonths(5)->startOfMonth();

        $trendQuery = DB::table('transaction_branches as tb')
            ->join('branches', 'branches.id', '=', 'tb.branch_id')
            ->join('transactions', 'transactions.id', '=', 'tb.transaction_id')
            ->whereIn('transactions.status', ['approved', 'completed'])
            ->where('transactions.created_at', '>=', $startDate);

        if (!$isAdmin) {
            $trendQuery->where('transactions.submitted_by', $userId);
        }

        $trendDataRaw = $trendQuery->select(
                'branches.name as branch_name',
                DB::raw('DATE_FORMAT(transactions.created_at, "%Y-%m") as month_key'),
                DB::raw('SUM(tb.allocation_amount) as total')
            )
            ->groupBy('branches.name', 'month_key')
            ->get();

        $branchTrends = [];
        foreach ($trendDataRaw as $row) {
            $branchTrends[$row->branch_name][$row->month_key] = (float) $row->total;
        }

        $trendDatasets = [];
        foreach ($branchTrends as $branchName => $monthlyData) {
            $data = [];
            foreach ($trendMonthsLabels as $monthKey) {
                $data[] = $monthlyData[$monthKey] ?? 0;
            }
            $trendDatasets[] = [
                'label' => $branchName,
                'data' => $data,
            ];
        }

        // 2c. Perbandingan Tipe Transaksi (optimized database sum)
        $rembushTotal  = (clone $thisMonth())->where('type', 'rembush')->whereIn('status', ['approved', 'completed'])->sum('amount');
        $pengajuanTotal = (clone $thisMonth())->where('type', 'pengajuan')->whereIn('status', ['approved', 'completed'])->sum('amount');
        $gudangTotal    = (clone $thisMonth())->where('type', 'gudang')->whereIn('status', ['approved', 'completed'])->sum('amount');

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
            $user = Auth::user();
            $pendingTransactions = Transaction::with(['submitter', 'branches'])
                ->where(function ($q) use ($user) {
                    if ($user->isOwner()) {
                        // Owner sees: pending + approved (waiting owner approval for >= 1jt)
                        $q->whereIn('status', ['pending', 'approved']);
                    } else {
                        // Admin/Atasan sees: only pending
                        $q->where('status', 'pending');
                    }
                })
                ->latest()
                ->take(10)
                ->get();
        }

        // ─────────────────────────────────────────────
        // 4. RECENT TRANSACTIONS
        // ─────────────────────────────────────────────
        $recentTransactions = (clone $base())
            ->with(['submitter', 'branches'])
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

        // ─────────────────────────────────────────────
        // 6. BRANCH COST BREAKDOWN (admin only)
        // ─────────────────────────────────────────────
        $startDate = request('start_date');
        $endDate   = request('end_date');

        $branchCostBreakdown = collect();
        if ($isAdmin) {
            $startDateObj = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
            $endDateObj   = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

            $costData = DB::table('transaction_branches as tb')
                ->join('branches', 'branches.id', '=', 'tb.branch_id')
                ->join('transactions', 'transactions.id', '=', 'tb.transaction_id')
                ->whereIn('transactions.status', ['approved', 'completed'])
                ->when($startDateObj && $endDateObj, function($q) use ($startDateObj, $endDateObj) {
                    $q->whereBetween('transactions.created_at', [$startDateObj, $endDateObj]);
                })
                ->when($startDateObj && !$endDateObj, function($q) use ($startDateObj) {
                    $q->whereDate('transactions.created_at', '>=', $startDateObj);
                })
                ->when(!$startDateObj && $endDateObj, function($q) use ($endDateObj) {
                    $q->whereDate('transactions.created_at', '<=', $endDateObj);
                })
                ->when(!$startDateObj && !$endDateObj, function($q) {
                    $q->whereMonth('transactions.created_at', now()->month)
                      ->whereYear('transactions.created_at', now()->year);
                })
                ->select(
                    'branches.name as branch_name',
                    'transactions.category',
                    'transactions.type',
                    DB::raw('SUM(tb.allocation_amount) as total_amount')
                )
                ->groupBy('branch_name', 'category', 'type')
                ->get();

            $branchCostBreakdown = $costData->groupBy('branch_name')->map(function ($items, $branchName) {
                $categories = $items->groupBy(function($item) {
                     return \App\Models\TransactionCategory::resolveLabel($item->category, $item->type);
                })->map(fn($group) => $group->sum('total_amount'))->sortByDesc(fn($v) => $v);

                return (object) [
                    'name'       => $branchName,
                    'categories' => $categories,
                    'total'      => $categories->sum(),
                ];
            })->values();
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
            'trendLabels',
            'trendDatasets',
            'rembushTotal',
            'pengajuanTotal',
            'gudangTotal',
            'byBranch',
            // Tables
            'pendingTransactions',
            'recentTransactions',
            // Leaderboard
            'topTeknisi',
            'topVendor',
            // Branch Cost Breakdown
            'branchCostBreakdown'
        ));
    }

    /**
     * AJAX endpoint: returns branch cost breakdown HTML partial
     */
    public function branchCostData(Request $request)
    {
        $user = Auth::user();
        if (!$user->canManageStatus()) {
            return response()->json(['html' => '', 'count' => 0]);
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        // Fallback to current month if no range given
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $startDateObj = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $endDateObj   = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        $costData = DB::table('transaction_branches as tb')
            ->join('branches', 'branches.id', '=', 'tb.branch_id')
            ->join('transactions', 'transactions.id', '=', 'tb.transaction_id')
            ->whereIn('transactions.status', ['approved', 'completed'])
            ->when($startDateObj && $endDateObj, function($q) use ($startDateObj, $endDateObj) {
                $q->whereBetween('transactions.created_at', [$startDateObj, $endDateObj]);
            })
            ->when($startDateObj && !$endDateObj, function($q) use ($startDateObj) {
                $q->whereDate('transactions.created_at', '>=', $startDateObj);
            })
            ->when(!$startDateObj && $endDateObj, function($q) use ($endDateObj) {
                $q->whereDate('transactions.created_at', '<=', $endDateObj);
            })
            ->when(!$startDateObj && !$endDateObj, function($q) use ($month, $year) {
                $q->whereMonth('transactions.created_at', $month)
                  ->whereYear('transactions.created_at', $year);
            })
            ->select(
                'branches.name as branch_name',
                'transactions.category',
                'transactions.type',
                DB::raw('SUM(tb.allocation_amount) as total_amount')
            )
            ->groupBy('branch_name', 'category', 'type')
            ->get();

        $branchCostBreakdown = $costData->groupBy('branch_name')->map(function ($items, $branchName) {
            $categories = $items->groupBy(function($item) {
                 return \App\Models\TransactionCategory::resolveLabel($item->category, $item->type);
            })->map(fn($group) => $group->sum('total_amount'))->sortByDesc(fn($v) => $v);

            return (object) [
                'name'       => $branchName,
                'categories' => $categories,
                'total'      => $categories->sum(),
            ];
        })->values();

        $html = view('dashboard._branch-cost-cards', compact('branchCostBreakdown'))->render();

        return response()->json([
            'html'  => $html,
            'count' => $branchCostBreakdown->count(),
            'month' => $month,
            'year'  => $year,
        ]);
    }

    /**
     * AJAX endpoint: returns refreshed pending transactions list HTML
     */
    public function pendingListData()
    {
        $user = Auth::user();
        if (!$user->canManageStatus()) {
            return response()->json(['html' => '', 'count' => 0, 'totalPending' => 0]);
        }

        $statusFilter = function ($q) use ($user) {
            if ($user->isOwner()) {
                $q->whereIn('status', ['pending', 'approved']);
            } else {
                $q->where('status', 'pending');
            }
        };

        $totalPending = Transaction::where($statusFilter)->count();

        $pendingTransactions = Transaction::with(['submitter', 'branches'])
            ->where($statusFilter)
            ->latest()
            ->take(10)
            ->get();

        $html = view('dashboard._pending-rows', compact('pendingTransactions'))->render();

        return response()->json([
            'html'         => $html,
            'count'        => $pendingTransactions->count(),
            'totalPending' => $totalPending,
        ]);
    }

    /**
     * AJAX endpoint: returns pending/unresolved rembush transactions for a branch
     */
    public function branchHutangData(Request $request)
    {
        $user = Auth::user();
        if (!$user->canManageStatus()) {
            return response()->json(['transactions' => [], 'total_hutang' => 0]);
        }

        $branchName = $request->input('branch_name', '');

        // Statuses considered as "hutang" (unresolved rembush)
        $hutangStatuses = ['pending', 'waiting_payment', 'flagged', 'pending_technician', 'approved'];

        $transactions = Transaction::with(['submitter', 'branches'])
            ->where('type', 'rembush')
            ->whereIn('status', $hutangStatuses)
            ->whereHas('branches', function ($q) use ($branchName) {
                $q->where('branches.name', $branchName);
            })
            ->latest()
            ->get();

        $data = $transactions->map(function ($t) use ($branchName) {
            $branch = $t->branches->firstWhere('name', $branchName);
            if ($branch) {
                $allocatedAmount = ($branch->pivot->allocation_amount > 0)
                    ? $branch->pivot->allocation_amount
                    : (int) round(($t->effective_amount * ($branch->pivot->allocation_percent ?? 100)) / 100);
            } else {
                $allocatedAmount = $t->effective_amount;
            }

            return [
                'id'               => $t->id,
                'type_label'       => 'Hutang Rembush',
                'invoice_number'   => $t->invoice_number,
                'submitter_name'   => $t->submitter->name ?? '-',
                'status'           => $t->status,
                'amount'           => $allocatedAmount,
                'formatted_amount' => 'Rp ' . number_format($allocatedAmount, 0, ',', '.'),
                'created_at'       => $t->created_at->format('d M Y'),
                'category'         => $t->category_label ?: 'Lainnya',
                'is_inter_branch'  => false,
            ];
        });

        $totalHutang = $data->sum('amount');

        return response()->json([
            'transactions'    => $data,
            'total_hutang'    => $totalHutang,
            'formatted_total' => 'Rp ' . number_format($totalHutang, 0, ',', '.'),
        ]);
    }

    /**
     * AJAX endpoint: returns Hutang Usaha (Inter-Branch Debts where this branch is Debtor)
     */
    public function branchInterBranchDebtData(Request $request)
    {
        $user = Auth::user();
        if (!$user->canManageStatus()) {
            return response()->json(['transactions' => [], 'total_hutang' => 0]);
        }

        $branchName = $request->input('branch_name', '');

        $branchDebts = \App\Models\BranchDebt::with(['transaction', 'creditorBranch'])
            ->whereHas('debtorBranch', function($q) use ($branchName) {
                $q->where('name', $branchName);
            })
            ->where('status', 'pending')
            ->latest()
            ->get();

        $data = $branchDebts->map(function ($debt) {
            return [
                'id'               => $debt->id,
                'type_label'       => 'Hutang ke Cab. ' . $debt->creditorBranch->name,
                'invoice_number'   => $debt->transaction->invoice_number ?? '-',
                'submitter_name'   => 'Antar Cabang',
                'status'           => 'waiting_payment',
                'amount'           => $debt->amount,
                'formatted_amount' => 'Rp ' . number_format($debt->amount, 0, ',', '.'),
                'created_at'       => $debt->created_at->format('d M Y'),
                'category'         => 'Pengajuan Multi-Source',
                'is_inter_branch'  => true,
            ];
        });

        $totalHutang = $data->sum('amount');

        return response()->json([
            'transactions'    => $data,
            'total_hutang'    => $totalHutang,
            'formatted_total' => 'Rp ' . number_format($totalHutang, 0, ',', '.'),
        ]);
    }

    /**
     * AJAX endpoint: returns Piutang Usaha (Inter-Branch receivables where this branch is Creditor)
     */
    public function branchInterBranchReceivableData(Request $request)
    {
        $user = Auth::user();
        if (!$user->canManageStatus()) {
            return response()->json(['transactions' => [], 'total_piutang' => 0]);
        }

        $branchName = $request->input('branch_name', '');

        $receivables = \App\Models\BranchDebt::with(['transaction', 'debtorBranch'])
            ->whereHas('creditorBranch', function($q) use ($branchName) {
                $q->where('name', $branchName);
            })
            ->where('status', 'pending')
            ->latest()
            ->get();

        $data = $receivables->map(function ($debt) {
            return [
                'id'               => $debt->id,
                'type_label'       => 'Piutang di Cab. ' . $debt->debtorBranch->name,
                'invoice_number'   => $debt->transaction->invoice_number ?? '-',
                'submitter_name'   => 'Antar Cabang',
                'status'           => 'waiting_payment', // Still unpaid
                'amount'           => $debt->amount,
                'formatted_amount' => 'Rp ' . number_format($debt->amount, 0, ',', '.'),
                'created_at'       => $debt->created_at->format('d M Y'),
                'category'         => 'Pengajuan Multi-Source',
                'is_inter_branch'  => true,
            ];
        });

        $totalPiutang = $data->sum('amount');

        return response()->json([
            'transactions'    => $data,
            'total_piutang'   => $totalPiutang,
            'formatted_total' => 'Rp ' . number_format($totalPiutang, 0, ',', '.'),
        ]);
    }
}
