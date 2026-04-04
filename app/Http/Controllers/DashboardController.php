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
                return $t->category_label ?: 'Lainnya';
            })
            ->map(fn($grp) => $grp->sum(fn($t) => $t->effective_amount))
            ->sortByDesc(fn($v) => $v)
            ->take(8);

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

        // ─────────────────────────────────────────────
        // 6. BRANCH COST BREAKDOWN (admin only)
        // ─────────────────────────────────────────────
        $startDate = request('start_date');
        $endDate   = request('end_date');

        $branchCostBreakdown = collect();
        if ($isAdmin) {
            $branchCostBreakdown = Branch::whereHas('transactions', function ($q) use ($startDate, $endDate) {
                $q->whereIn('status', ['approved', 'completed']);
                if ($startDate && $endDate) {
                    $q->whereBetween('transactions.created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                } elseif ($startDate) {
                    $q->whereDate('transactions.created_at', '>=', $startDate);
                } elseif ($endDate) {
                    $q->whereDate('transactions.created_at', '<=', $endDate);
                } else {
                    $q->whereMonth('transactions.created_at', now()->month)
                      ->whereYear('transactions.created_at', now()->year);
                }
            })
            ->with(['transactions' => function ($q) use ($startDate, $endDate) {
                $q->whereIn('status', ['approved', 'completed']);
                if ($startDate && $endDate) {
                    $q->whereBetween('transactions.created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                } elseif ($startDate) {
                    $q->whereDate('transactions.created_at', '>=', $startDate);
                } elseif ($endDate) {
                    $q->whereDate('transactions.created_at', '<=', $endDate);
                } else {
                    $q->whereMonth('transactions.created_at', now()->month)
                      ->whereYear('transactions.created_at', now()->year);
                }
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($branch) {
                $categories = $branch->transactions->groupBy(function ($t) {
                    return $t->category_label ?: 'Lainnya';
                })->map(function ($group) {
                    return $group->sum(function($t) {
                        return ($t->pivot->allocation_amount > 0) 
                            ? $t->pivot->allocation_amount 
                            : round(($t->effective_amount * ($t->pivot->allocation_percent ?? 100)) / 100);
                    });
                })->sortByDesc(fn($v) => $v);

                return (object) [
                    'name'       => $branch->name,
                    'categories' => $categories,
                    'total'      => $categories->sum(),
                ];
            });
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

        $branchCostBreakdown = Branch::whereHas('transactions', function ($q) use ($startDate, $endDate, $month, $year) {
            $q->whereIn('status', ['approved', 'completed']);
            
            if ($startDate && $endDate) {
                $q->whereBetween('transactions.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } elseif ($startDate) {
                $q->whereDate('transactions.created_at', '>=', $startDate);
            } elseif ($endDate) {
                $q->whereDate('transactions.created_at', '<=', $endDate);
            } else {
                $q->whereMonth('transactions.created_at', $month)
                  ->whereYear('transactions.created_at', $year);
            }
        })
        ->with(['transactions' => function ($q) use ($startDate, $endDate, $month, $year) {
            $q->whereIn('status', ['approved', 'completed']);

            if ($startDate && $endDate) {
                $q->whereBetween('transactions.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } elseif ($startDate) {
                $q->whereDate('transactions.created_at', '>=', $startDate);
            } elseif ($endDate) {
                $q->whereDate('transactions.created_at', '<=', $endDate);
            } else {
                $q->whereMonth('transactions.created_at', $month)
                  ->whereYear('transactions.created_at', $year);
            }
        }])
        ->orderBy('name')
        ->get()
        ->map(function ($branch) {
            $categories = $branch->transactions->groupBy(function ($t) {
                return $t->category_label ?: 'Lainnya';
            })->map(function ($group) {
                return $group->sum(function($t) {
                    return ($t->pivot->allocation_amount > 0) 
                        ? $t->pivot->allocation_amount 
                        : round(($t->effective_amount * ($t->pivot->allocation_percent ?? 100)) / 100);
                });
            })->sortByDesc(fn($v) => $v);

            return (object) [
                'name'       => $branch->name,
                'categories' => $categories,
                'total'      => $categories->sum(),
            ];
        });

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
            // Find the specific branch to get its allocated amount from pivot
            $branch = $t->branches->firstWhere('name', $branchName);
            
            // Logika robust: jika allocation_amount <= 0, hitung manual dari persen sebagai fallback
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

        // ✅ Inject Hutang Antar Cabang (Pengajuan Debts)
        $branchDebts = \App\Models\BranchDebt::with(['transaction', 'creditorBranch'])
            ->whereHas('debtorBranch', function($q) use ($branchName) {
                $q->where('name', $branchName);
            })
            ->where('status', 'pending')
            ->get();

        $debtData = $branchDebts->map(function ($debt) {
            return [
                'id'               => $debt->id,
                'type_label'       => 'Hutang ke Cab. ' . $debt->creditorBranch->name,
                'invoice_number'   => $debt->transaction->invoice_number ?? '-',
                'submitter_name'   => 'Antar Cabang', // Or transaction submitter
                'status'           => 'waiting_payment', // mapping UX style
                'amount'           => $debt->amount,
                'formatted_amount' => 'Rp ' . number_format($debt->amount, 0, ',', '.'),
                'created_at'       => $debt->created_at->format('d M Y'),
                'category'         => 'Pengajuan Multi-Source',
                'is_inter_branch'  => true,
            ];
        });

        $finalData = $data->concat($debtData)->sortByDesc('created_at')->values();

        $totalHutang = $finalData->sum('amount');

        return response()->json([
            'transactions'    => $finalData,
            'total_hutang'    => $totalHutang,
            'formatted_total' => 'Rp ' . number_format($totalHutang, 0, ',', '.'),
        ]);
    }
}
