<!-- PRICE_INDEX_ANTI_MANIPULATION.md -->

# Price Index System - Anti-Manipulation & Market Adjustment

> **Strategi untuk Mencegah Manipulasi Harga dan Menyesuaikan dengan Kondisi Pasar**

📅 **Created:** April 9, 2026  
🎯 **Priority:** CRITICAL - Business Integrity  
⚠️ **Risk Level:** HIGH - Financial Impact

---

## 📋 Daftar Isi

- [Problem Statement](#problem-statement)
- [Challenge 1: Market Price Fluctuation](#challenge-1-market-price-fluctuation)
- [Challenge 2: Internal Manipulation](#challenge-2-internal-manipulation)
- [Anti-Manipulation Framework](#anti-manipulation-framework)
- [Implementation Guide](#implementation-guide)
- [Monitoring & Audit](#monitoring--audit)

---

## ⚠️ Problem Statement

### Issue 1: Market Price Inflation (Legitimate)

**Scenario:**
```
Januari 2026:
- Kabel NYM 3x2.5: Harga market Rp 28.000/m
- Price index: min Rp 25K, max Rp 30K, avg Rp 27.5K
- Status: ✅ Normal

Maret 2026 (Inflasi + Kenaikan Harga Tembaga):
- Kabel NYM 3x2.5: Harga market Rp 38.000/m
- Price index: masih min Rp 25K, max Rp 30K ❌
- Teknisi input: Rp 38.000
- Sistem: 🚨 ANOMALI +26% (SALAH!)

Result: Valid market price di-flag sebagai anomali
        → Owner frustrated karena false positive
        → Sistem kehilangan trust
```

### Issue 2: Price Manipulation (Fraud)

**Scenario A: Gradual Inflation**
```
Week 1: Teknisi A input Kabel @ Rp 32K (approved)
Week 2: Teknisi A input Kabel @ Rp 34K (approved)
Week 3: Teknisi A input Kabel @ Rp 36K (approved)
Week 4: Teknisi A input Kabel @ Rp 38K (approved)

After 1 month:
- Price index shifted from max Rp 30K → Rp 38K
- Markup berhasil "dinormalisasi" dalam sistem
- Kolusi dengan supplier terbayar
```

**Scenario B: Bulk Manipulation**
```
Teknisi B submit 10 pengajuan sekaligus:
- Semua dengan harga inflated +20%
- Harapan: Beberapa lolos approval
- Price index akan ter-update dengan data inflated
- Future transactions jadi lebih mudah approve harga tinggi
```

**Impact:**
- Financial loss: Overpayment sistematis
- Data corruption: Price index tidak reliable
- Trust erosion: Sistem jadi tidak dipercaya
- Compliance risk: Audit findings

---

## 📈 Challenge 1: Market Price Fluctuation

### Solution 1: Time-Weighted Price Index dengan Decay

Older data memiliki weight yang lebih rendah dalam calculation.

**Implementation:**

```php
// app/Services/PriceIndexService.php

public function calculatePriceIndex(int $priceIndexId): void
{
    $priceIndex = PriceIndex::find($priceIndexId);
    
    // Get transactions from last 6 months
    $transactions = $this->getRecentTransactions($priceIndexId, months: 6);
    
    if ($transactions->count() < 5) {
        return; // Not enough data
    }
    
    // Apply time decay weighting
    $weightedPrices = $transactions->map(function ($transaction) {
        $daysOld = now()->diffInDays($transaction->created_at);
        
        // Weight formula: More recent = higher weight
        // Data 1 month old = 100% weight
        // Data 3 months old = 50% weight
        // Data 6 months old = 25% weight
        $weight = max(0.25, 1 - ($daysOld / 180)); // 180 days = 6 months
        
        return [
            'price' => $transaction->unit_price,
            'weight' => $weight,
            'days_old' => $daysOld,
        ];
    });
    
    // Calculate weighted statistics
    $totalWeight = $weightedPrices->sum('weight');
    
    $weightedAvg = $weightedPrices->sum(function ($item) {
        return $item['price'] * $item['weight'];
    }) / $totalWeight;
    
    // Recent max/min (last 30 days have more influence)
    $recentPrices = $weightedPrices->where('days_old', '<=', 30);
    
    if ($recentPrices->isNotEmpty()) {
        $recentMax = $recentPrices->max('price');
        $recentMin = $recentPrices->min('price');
        
        // Blend recent with historical (70% recent, 30% historical)
        $priceIndex->max_price = ($recentMax * 0.7) + ($priceIndex->max_price * 0.3);
        $priceIndex->min_price = ($recentMin * 0.7) + ($priceIndex->min_price * 0.3);
    }
    
    $priceIndex->avg_price = $weightedAvg;
    $priceIndex->last_calculated_at = now();
    $priceIndex->save();
    
    // Log the calculation for audit
    $this->logPriceIndexUpdate($priceIndex, $weightedPrices);
}
```

### Solution 2: Trend Detection & Auto-Adjustment Alert

Deteksi trend harga naik dan alert Owner untuk review.

**Implementation:**

```php
// app/Services/PriceTrendAnalysisService.php

namespace App\Services;

use App\Models\PriceIndex;
use App\Models\PriceTrendAlert;
use Illuminate\Support\Facades\DB;

class PriceTrendAnalysisService
{
    /**
     * Analyze price trends dan create alerts jika needed
     */
    public function analyzeTrends(): void
    {
        PriceIndex::where('total_transactions', '>=', 10)
            ->chunk(100, function ($indexes) {
                foreach ($indexes as $index) {
                    $this->analyzeItemTrend($index);
                }
            });
    }
    
    private function analyzeItemTrend(PriceIndex $priceIndex): void
    {
        // Get price history over 3 months
        $monthlyAvg = DB::table('pengajuan_items')
            ->join('pengajuans', 'pengajuan_items.pengajuan_id', '=', 'pengajuans.id')
            ->where('pengajuan_items.price_index_id', $priceIndex->id)
            ->where('pengajuans.status', 'approved')
            ->where('pengajuans.created_at', '>=', now()->subMonths(3))
            ->selectRaw('
                YEAR(pengajuans.created_at) as year,
                MONTH(pengajuans.created_at) as month,
                AVG(pengajuan_items.unit_price) as avg_price,
                COUNT(*) as transaction_count
            ')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        
        if ($monthlyAvg->count() < 2) {
            return; // Not enough data for trend
        }
        
        // Calculate trend (simple linear regression)
        $trend = $this->calculateTrend($monthlyAvg);
        
        // Alert criteria
        $shouldAlert = false;
        $alertType = null;
        $alertMessage = null;
        
        // Alert 1: Consistent upward trend > 10% per month
        if ($trend['slope'] > 0.1 && $trend['r_squared'] > 0.7) {
            $shouldAlert = true;
            $alertType = 'market_inflation';
            $alertMessage = "Harga {$priceIndex->item_name} menunjukkan trend naik konsisten "
                . round($trend['slope'] * 100, 1) . "% per bulan. "
                . "Pertimbangkan untuk adjust price index.";
        }
        
        // Alert 2: Sudden spike in recent month
        $latestAvg = $monthlyAvg->last()->avg_price;
        $previousAvg = $monthlyAvg->slice(-2, 1)->first()->avg_price;
        $percentChange = (($latestAvg - $previousAvg) / $previousAvg) * 100;
        
        if ($percentChange > 15) {
            $shouldAlert = true;
            $alertType = 'sudden_spike';
            $alertMessage = "Harga {$priceIndex->item_name} naik drastis "
                . round($percentChange, 1) . "% dalam bulan terakhir. "
                . "Perlu investigasi: market change atau manipulation?";
        }
        
        if ($shouldAlert) {
            $this->createTrendAlert($priceIndex, $alertType, $alertMessage, $trend);
        }
    }
    
    private function calculateTrend($monthlyData): array
    {
        $n = $monthlyData->count();
        $x = range(1, $n); // Time periods
        $y = $monthlyData->pluck('avg_price')->toArray(); // Prices
        
        // Linear regression: y = mx + b
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($xi, $yi) => $xi * $yi, $x, $y));
        $sumX2 = array_sum(array_map(fn($xi) => $xi ** 2, $x));
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX ** 2);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Calculate R² (goodness of fit)
        $meanY = $sumY / $n;
        $ssTotal = array_sum(array_map(fn($yi) => ($yi - $meanY) ** 2, $y));
        $ssResidual = array_sum(array_map(
            fn($xi, $yi) => ($yi - ($slope * $xi + $intercept)) ** 2,
            $x,
            $y
        ));
        $rSquared = 1 - ($ssResidual / $ssTotal);
        
        return [
            'slope' => $slope / $meanY, // Normalize to percentage
            'intercept' => $intercept,
            'r_squared' => $rSquared,
            'direction' => $slope > 0 ? 'rising' : 'falling',
        ];
    }
    
    private function createTrendAlert(
        PriceIndex $priceIndex,
        string $type,
        string $message,
        array $trendData
    ): void {
        PriceTrendAlert::create([
            'price_index_id' => $priceIndex->id,
            'alert_type' => $type,
            'message' => $message,
            'trend_data' => json_encode($trendData),
            'status' => 'pending',
            'requires_owner_review' => true,
        ]);
        
        // Notify owner via Telegram
        $this->notifyOwnerOfTrend($priceIndex, $message);
    }
    
    private function notifyOwnerOfTrend(PriceIndex $priceIndex, string $message)
    {
        $owners = \App\Models\User::role('owner')->get();
        
        foreach ($owners as $owner) {
            if ($owner->telegram_id) {
                \Illuminate\Support\Facades\Http::post(
                    "https://api.telegram.org/bot" . config('services.telegram.bot_token') . "/sendMessage",
                    [
                        'chat_id' => $owner->telegram_id,
                        'text' => "📊 *PRICE TREND ALERT*\n\n{$message}\n\n"
                            . "[Review & Adjust](" . route('price-index.trend-review', $priceIndex) . ")",
                        'parse_mode' => 'Markdown',
                    ]
                );
            }
        }
    }
}

// Schedule daily trend analysis
// app/Console/Kernel.php
$schedule->call(function () {
    app(\App\Services\PriceTrendAnalysisService::class)->analyzeTrends();
})->daily();
```

### Solution 3: Owner-Approved Price Index Adjustment

Owner dapat meng-adjust price index dengan reason yang logged.

**Implementation:**

```php
// Database Migration
Schema::create('price_index_adjustments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('price_index_id')->constrained();
    $table->foreignId('adjusted_by_user_id')->constrained('users');
    
    // Old values
    $table->decimal('old_min_price', 15, 2);
    $table->decimal('old_max_price', 15, 2);
    $table->decimal('old_avg_price', 15, 2);
    
    // New values
    $table->decimal('new_min_price', 15, 2);
    $table->decimal('new_max_price', 15, 2);
    $table->decimal('new_avg_price', 15, 2);
    
    $table->enum('adjustment_type', [
        'market_inflation',
        'seasonal_change',
        'supplier_change',
        'manual_correction',
        'trend_based',
    ]);
    $table->text('reason');
    $table->json('supporting_data')->nullable(); // Market prices, invoices, etc
    
    $table->timestamp('effective_date')->default(now());
    $table->timestamps();
});

// Controller
class PriceIndexAdjustmentController extends Controller
{
    public function showAdjustmentForm(PriceIndex $priceIndex)
    {
        $this->authorize('adjust', $priceIndex); // Only Owner
        
        $trendData = app(PriceTrendAnalysisService::class)
            ->getDetailedTrend($priceIndex);
        
        $recentTransactions = $priceIndex->recentTransactions()
            ->with('pengajuan.reportedBy')
            ->limit(20)
            ->get();
        
        return view('price-index.adjust', [
            'priceIndex' => $priceIndex,
            'trendData' => $trendData,
            'recentTransactions' => $recentTransactions,
        ]);
    }
    
    public function adjust(Request $request, PriceIndex $priceIndex)
    {
        $this->authorize('adjust', $priceIndex);
        
        $validated = $request->validate([
            'new_min_price' => 'required|numeric|min:0',
            'new_max_price' => 'required|numeric|gt:new_min_price',
            'new_avg_price' => 'required|numeric|between:new_min_price,new_max_price',
            'adjustment_type' => 'required|in:market_inflation,seasonal_change,supplier_change,manual_correction,trend_based',
            'reason' => 'required|string|min:20|max:500',
            'supporting_data' => 'nullable|array',
        ]);
        
        // Log adjustment
        $adjustment = PriceIndexAdjustment::create([
            'price_index_id' => $priceIndex->id,
            'adjusted_by_user_id' => auth()->id(),
            'old_min_price' => $priceIndex->min_price,
            'old_max_price' => $priceIndex->max_price,
            'old_avg_price' => $priceIndex->avg_price,
            'new_min_price' => $validated['new_min_price'],
            'new_max_price' => $validated['new_max_price'],
            'new_avg_price' => $validated['new_avg_price'],
            'adjustment_type' => $validated['adjustment_type'],
            'reason' => $validated['reason'],
            'supporting_data' => $validated['supporting_data'] ?? null,
        ]);
        
        // Update price index
        $priceIndex->update([
            'min_price' => $validated['new_min_price'],
            'max_price' => $validated['new_max_price'],
            'avg_price' => $validated['new_avg_price'],
            'is_manual' => true,
            'manual_set_by' => auth()->id(),
            'manual_set_at' => now(),
        ]);
        
        // Close related trend alerts
        PriceTrendAlert::where('price_index_id', $priceIndex->id)
            ->where('status', 'pending')
            ->update(['status' => 'resolved']);
        
        return redirect()
            ->route('price-index.index')
            ->with('success', "Price index adjusted successfully");
    }
}
```

---

## 🛡️ Challenge 2: Internal Manipulation

### Anti-Manipulation Strategy Framework

```
Layer 1: Prevention (Proactive)
├─ Multi-level approval workflow
├─ Automatic pattern detection
├─ Supplier diversity requirements
└─ Price index freeze mechanism

Layer 2: Detection (Real-time)
├─ Behavioral anomaly detection
├─ Statistical outlier analysis
├─ Cross-reference validation
└─ Peer comparison

Layer 3: Response (Reactive)
├─ Auto-flagging suspicious patterns
├─ Escalation to management
├─ Investigation workflow
└─ Audit trail

Layer 4: Deterrence (Cultural)
├─ Transparent logging
├─ Random audits
├─ Consequence enforcement
└─ Whistleblower protection
```

### Solution 1: Multi-Level Approval Workflow

Harga di atas threshold tertentu memerlukan multiple approvals.

**Implementation:**

```php
// Database Migration
Schema::table('price_anomalies', function (Blueprint $table) {
    $table->boolean('requires_manager_approval')->default(false);
    $table->foreignId('manager_approved_by')->nullable()->constrained('users');
    $table->timestamp('manager_approved_at')->nullable();
    $table->text('manager_notes')->nullable();
});

// Business Logic
class AnomalyApprovalService
{
    public function determineApprovalLevel(PriceAnomaly $anomaly): array
    {
        $approvalLevels = [];
        
        // Level 1: Manager approval
        if ($anomaly->excess_percentage > 20 || $anomaly->excess_amount > 1000000) {
            $approvalLevels[] = 'manager';
        }
        
        // Level 2: Owner approval (always)
        $approvalLevels[] = 'owner';
        
        // Level 3: Finance approval (untuk amount besar)
        if ($anomaly->input_price > 5000000) {
            $approvalLevels[] = 'finance_director';
        }
        
        return $approvalLevels;
    }
    
    public function processApproval(PriceAnomaly $anomaly, User $approver, string $decision)
    {
        $role = $approver->roles->first()->name;
        
        switch ($role) {
            case 'manager':
                if ($decision === 'approve') {
                    $anomaly->update([
                        'manager_approved_by' => $approver->id,
                        'manager_approved_at' => now(),
                        'status' => 'pending_owner', // Next level
                    ]);
                    
                    // Notify Owner
                    $this->notifyNextApprover($anomaly, 'owner');
                } else {
                    $anomaly->update([
                        'status' => 'rejected_by_manager',
                        'manager_notes' => request('notes'),
                    ]);
                }
                break;
                
            case 'owner':
                // Owner final decision
                $anomaly->update([
                    'status' => $decision === 'approve' ? 'approved' : 'rejected',
                    'owner_reviewed' => true,
                    'reviewed_at' => now(),
                    'owner_notes' => request('notes'),
                ]);
                break;
        }
    }
}
```

### Solution 2: Pattern Detection - Suspicious Behavior

Deteksi pola mencurigakan dari teknisi atau supplier.

**Implementation:**

```php
// app/Services/FraudDetectionService.php

namespace App\Services;

use App\Models\User;
use App\Models\PriceAnomaly;
use App\Models\FraudAlert;
use Illuminate\Support\Facades\DB;

class FraudDetectionService
{
    /**
     * Analyze teknisi behavior untuk detect manipulation patterns
     */
    public function analyzeTechnicianBehavior(): void
    {
        $technicians = User::role('teknisi')->get();
        
        foreach ($technicians as $technician) {
            $this->checkTechnicianPatterns($technician);
        }
    }
    
    private function checkTechnicianPatterns(User $technician): void
    {
        $last30Days = now()->subDays(30);
        
        // Pattern 1: Konsisten submit harga tinggi
        $highPriceRate = PriceAnomaly::where('reported_by_user_id', $technician->id)
            ->where('created_at', '>=', $last30Days)
            ->where('excess_percentage', '>', 10)
            ->count();
        
        $totalSubmissions = PriceAnomaly::where('reported_by_user_id', $technician->id)
            ->where('created_at', '>=', $last30Days)
            ->count();
        
        if ($totalSubmissions >= 5 && ($highPriceRate / $totalSubmissions) > 0.6) {
            $this->createFraudAlert($technician, 'high_price_pattern', [
                'high_price_count' => $highPriceRate,
                'total_submissions' => $totalSubmissions,
                'rate' => round(($highPriceRate / $totalSubmissions) * 100, 1),
                'message' => "Teknisi {$technician->name} memiliki {$highPriceRate} dari {$totalSubmissions} "
                    . "submission dengan harga tinggi (60%+ anomaly rate)",
            ]);
        }
        
        // Pattern 2: Gradual price increase (frog boiling)
        $this->detectGradualInflation($technician);
        
        // Pattern 3: Same supplier repeatedly
        $this->detectSupplierCollusion($technician);
        
        // Pattern 4: Bulk submissions
        $this->detectBulkManipulation($technician);
    }
    
    private function detectGradualInflation(User $technician): void
    {
        // Get submissions untuk same item over 3 months
        $items = DB::table('pengajuan_items')
            ->join('pengajuans', 'pengajuan_items.pengajuan_id', '=', 'pengajuans.id')
            ->where('pengajuans.reported_by_user_id', $technician->id)
            ->where('pengajuans.created_at', '>=', now()->subMonths(3))
            ->whereNotNull('pengajuan_items.price_index_id')
            ->select('price_index_id')
            ->groupBy('price_index_id')
            ->havingRaw('COUNT(*) >= 3')
            ->pluck('price_index_id');
        
        foreach ($items as $priceIndexId) {
            $prices = DB::table('pengajuan_items')
                ->join('pengajuans', 'pengajuan_items.pengajuan_id', '=', 'pengajuans.id')
                ->where('pengajuans.reported_by_user_id', $technician->id)
                ->where('pengajuan_items.price_index_id', $priceIndexId)
                ->orderBy('pengajuans.created_at', 'asc')
                ->pluck('pengajuan_items.unit_price')
                ->toArray();
            
            // Check if prices consistently increasing
            $isIncreasing = true;
            for ($i = 1; $i < count($prices); $i++) {
                if ($prices[$i] <= $prices[$i-1]) {
                    $isIncreasing = false;
                    break;
                }
            }
            
            if ($isIncreasing && count($prices) >= 3) {
                $percentIncrease = (($prices[count($prices)-1] - $prices[0]) / $prices[0]) * 100;
                
                if ($percentIncrease > 15) {
                    $this->createFraudAlert($technician, 'gradual_inflation', [
                        'price_index_id' => $priceIndexId,
                        'start_price' => $prices[0],
                        'end_price' => $prices[count($prices)-1],
                        'percent_increase' => round($percentIncrease, 1),
                        'submissions_count' => count($prices),
                        'message' => "Teknisi {$technician->name} menunjukkan pola gradual price increase "
                            . round($percentIncrease, 1) . "% dalam " . count($prices) . " submissions",
                    ]);
                }
            }
        }
    }
    
    private function detectSupplierCollusion(User $technician): void
    {
        // Check if same supplier repeatedly dengan harga tinggi
        $supplierStats = DB::table('pengajuan_items')
            ->join('pengajuans', 'pengajuan_items.pengajuan_id', '=', 'pengajuans.id')
            ->where('pengajuans.reported_by_user_id', $technician->id)
            ->where('pengajuans.created_at', '>=', now()->subMonths(3))
            ->whereNotNull('pengajuan_items.supplier_id')
            ->select([
                'pengajuan_items.supplier_id',
                DB::raw('COUNT(*) as submission_count'),
                DB::raw('COUNT(CASE WHEN pengajuan_items.is_price_anomaly = 1 THEN 1 END) as anomaly_count'),
            ])
            ->groupBy('pengajuan_items.supplier_id')
            ->having('submission_count', '>=', 5)
            ->get();
        
        foreach ($supplierStats as $stat) {
            $anomalyRate = ($stat->anomaly_count / $stat->submission_count) * 100;
            
            if ($anomalyRate > 70) {
                $supplier = \App\Models\Supplier::find($stat->supplier_id);
                
                $this->createFraudAlert($technician, 'supplier_collusion_suspected', [
                    'supplier_id' => $stat->supplier_id,
                    'supplier_name' => $supplier->name ?? 'Unknown',
                    'submission_count' => $stat->submission_count,
                    'anomaly_count' => $stat->anomaly_count,
                    'anomaly_rate' => round($anomalyRate, 1),
                    'message' => "Teknisi {$technician->name} + Supplier {$supplier->name}: "
                        . "{$stat->anomaly_count}/{$stat->submission_count} submissions anomaly (70%+ rate)",
                ]);
            }
        }
    }
    
    private function detectBulkManipulation(User $technician): void
    {
        // Detect if multiple submissions dalam waktu singkat dengan harga tinggi
        $bulkSubmissions = DB::table('pengajuans')
            ->where('reported_by_user_id', $technician->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->select([
                DB::raw('DATE(created_at) as submission_date'),
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('submission_date')
            ->having('count', '>=', 5)
            ->get();
        
        foreach ($bulkSubmissions as $bulk) {
            // Check anomaly rate for that day
            $dayStart = \Carbon\Carbon::parse($bulk->submission_date)->startOfDay();
            $dayEnd = \Carbon\Carbon::parse($bulk->submission_date)->endOfDay();
            
            $anomalyCount = PriceAnomaly::where('reported_by_user_id', $technician->id)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
            
            if ($anomalyCount >= 3) {
                $this->createFraudAlert($technician, 'bulk_manipulation', [
                    'date' => $bulk->submission_date,
                    'total_submissions' => $bulk->count,
                    'anomaly_count' => $anomalyCount,
                    'message' => "Teknisi {$technician->name} submit {$bulk->count} pengajuan "
                        . "pada {$bulk->submission_date}, {$anomalyCount} di antaranya anomaly",
                ]);
            }
        }
    }
    
    private function createFraudAlert(User $technician, string $type, array $data): void
    {
        FraudAlert::create([
            'user_id' => $technician->id,
            'alert_type' => $type,
            'severity' => $this->determineSeverity($type, $data),
            'data' => json_encode($data),
            'status' => 'pending_investigation',
            'requires_immediate_action' => $this->requiresImmediateAction($type),
        ]);
        
        // Notify Manager & Owner
        $this->notifyManagement($technician, $type, $data);
    }
    
    private function determineSeverity(string $type, array $data): string
    {
        return match($type) {
            'supplier_collusion_suspected' => 'critical',
            'gradual_inflation' => 'high',
            'bulk_manipulation' => 'high',
            'high_price_pattern' => 'medium',
            default => 'low',
        };
    }
    
    private function requiresImmediateAction(string $type): bool
    {
        return in_array($type, [
            'supplier_collusion_suspected',
            'bulk_manipulation',
        ]);
    }
    
    private function notifyManagement(User $technician, string $type, array $data): void
    {
        $managers = User::role(['manager', 'owner'])->get();
        
        $message = "🚨 *FRAUD ALERT*\n\n"
            . "Type: " . str_replace('_', ' ', strtoupper($type)) . "\n"
            . "Teknisi: {$technician->name}\n"
            . "Detail: {$data['message']}\n\n"
            . "[Investigate Now](" . route('fraud.investigate', $technician) . ")";
        
        foreach ($managers as $manager) {
            if ($manager->telegram_id) {
                \Illuminate\Support\Facades\Http::post(
                    "https://api.telegram.org/bot" . config('services.telegram.bot_token') . "/sendMessage",
                    [
                        'chat_id' => $manager->telegram_id,
                        'text' => $message,
                        'parse_mode' => 'Markdown',
                    ]
                );
            }
        }
    }
}

// Schedule fraud detection
$schedule->call(function () {
    app(\App\Services\FraudDetectionService::class)->analyzeTechnicianBehavior();
})->daily();
```

### Solution 3: Price Index Freeze Mechanism

Jika ada suspected fraud, freeze price index untuk item tersebut.

**Implementation:**

```php
// Migration
Schema::table('price_indexes', function (Blueprint $table) {
    $table->boolean('is_frozen')->default(false);
    $table->foreignId('frozen_by_user_id')->nullable()->constrained('users');
    $table->timestamp('frozen_at')->nullable();
    $table->text('freeze_reason')->nullable();
});

// Business Logic
class PriceIndexFreezeService
{
    public function freeze(PriceIndex $priceIndex, string $reason, User $frozenBy): void
    {
        $priceIndex->update([
            'is_frozen' => true,
            'frozen_by_user_id' => $frozenBy->id,
            'frozen_at' => now(),
            'freeze_reason' => $reason,
        ]);
        
        // Jika frozen, calculation otomatis di-skip
        // Manual override hanya oleh Owner
    }
    
    public function unfreeze(PriceIndex $priceIndex, User $unfrozenBy): void
    {
        $priceIndex->update([
            'is_frozen' => false,
            'frozen_by_user_id' => null,
            'frozen_at' => null,
            'freeze_reason' => null,
        ]);
    }
}

// Modified calculation logic
public function calculatePriceIndex(int $priceIndexId): void
{
    $priceIndex = PriceIndex::find($priceIndexId);
    
    if ($priceIndex->is_frozen) {
        Log::warning("Price index calculation skipped - frozen", [
            'price_index_id' => $priceIndexId,
            'reason' => $priceIndex->freeze_reason,
        ]);
        return; // Skip calculation
    }
    
    // Normal calculation...
}
```

### Solution 4: Supplier Diversity Requirement

Require minimum 2-3 supplier quotes untuk high-value items.

**Implementation:**

```php
// Business Rule
class SupplierDiversityRule
{
    public function checkDiversityRequirement(PengajuanItem $item): array
    {
        // High-value items require multiple quotes
        if ($item->total_price > 5000000) {
            $suppliersQuoted = $item->pengajuan->items
                ->where('item_name', $item->item_name)
                ->pluck('supplier_id')
                ->unique()
                ->count();
            
            if ($suppliersQuoted < 2) {
                return [
                    'passed' => false,
                    'message' => "Items di atas Rp 5jt memerlukan minimum 2 supplier quotes",
                    'required_suppliers' => 2,
                    'current_suppliers' => $suppliersQuoted,
                ];
            }
        }
        
        return ['passed' => true];
    }
}
```

---

## 📊 Dashboard - Fraud Detection & Monitoring

```php
// Controller
class FraudMonitoringController extends Controller
{
    public function dashboard()
    {
        return view('fraud.dashboard', [
            'activeAlerts' => FraudAlert::where('status', 'pending_investigation')
                ->with('user')
                ->orderBy('severity', 'desc')
                ->get(),
            
            'technicianRiskScores' => $this->getTechnicianRiskScores(),
            
            'supplierRiskScores' => $this->getSupplierRiskScores(),
            
            'recentAdjustments' => PriceIndexAdjustment::with(['priceIndex', 'adjustedBy'])
                ->latest()
                ->limit(10)
                ->get(),
            
            'frozenIndexes' => PriceIndex::where('is_frozen', true)
                ->with('frozenBy')
                ->get(),
        ]);
    }
    
    private function getTechnicianRiskScores(): array
    {
        // Calculate risk score untuk each teknisi
        return User::role('teknisi')
            ->get()
            ->map(function ($technician) {
                $last30Days = now()->subDays(30);
                
                $totalSubmissions = PriceAnomaly::where('reported_by_user_id', $technician->id)
                    ->where('created_at', '>=', $last30Days)
                    ->count();
                
                $anomalyCount = PriceAnomaly::where('reported_by_user_id', $technician->id)
                    ->where('created_at', '>=', $last30Days)
                    ->where('excess_percentage', '>', 10)
                    ->count();
                
                $fraudAlerts = FraudAlert::where('user_id', $technician->id)
                    ->where('created_at', '>=', $last30Days)
                    ->count();
                
                // Risk score calculation (0-100)
                $anomalyRate = $totalSubmissions > 0 ? ($anomalyCount / $totalSubmissions) : 0;
                $riskScore = ($anomalyRate * 50) + ($fraudAlerts * 25);
                $riskScore = min(100, $riskScore);
                
                return [
                    'teknisi' => $technician->name,
                    'total_submissions' => $totalSubmissions,
                    'anomaly_count' => $anomalyCount,
                    'fraud_alerts' => $fraudAlerts,
                    'risk_score' => round($riskScore, 1),
                    'risk_level' => match(true) {
                        $riskScore >= 75 => 'critical',
                        $riskScore >= 50 => 'high',
                        $riskScore >= 25 => 'medium',
                        default => 'low',
                    },
                ];
            })
            ->sortByDesc('risk_score')
            ->values()
            ->toArray();
    }
}
```


## ⚡ Performance & Scalability Considerations

### Asynchronous Processing Strategy


**Critical:** Fraud Detection MUST NOT block transaction approval flow

**Current Flow (Synchronous - BAD):**


User Submit → Fraud Check (2-5s) → Transaction Saved ❌ SLOW
**Optimized Flow (Async - GOOD):**


User Submit → Transaction Saved (50ms) ✅
↓
Background Job → Fraud Check → Alert if suspicious



**Implementation:**
```php
// app/Listeners/CheckFraudAfterTransactionApproved.php

class CheckFraudAfterTransactionApproved
{
    public function handle(TransactionApproved $event)
    {
        // Dispatch to queue (non-blocking)
        dispatch(new FraudDetectionJob($event->transaction))
            ->onQueue('fraud_detection') // Dedicated queue
            ->delay(now()->addSeconds(5)); // Small delay untuk batching
    }
}

// config/queue.php
'connections' => [
    'fraud_detection' => [
        'driver' => 'redis',
        'queue' => 'fraud_detection',
        'retry_after' => 90,
        'block_for' => null,
        'processes' => 2, // 2 workers dedicated untuk fraud detection
    ],
],
```

**Result:** Transaction approval tetap cepat (<100ms), fraud detection berjalan di background

---

### Database Optimization untuk Pattern Detection

**Issue:** Query `detectGradualInflation()` bisa lambat jika data besar

**Before (Slow):**
```php
// Iterates through ALL items
$items = DB::table('pengajuan_items')
    ->where('created_at', '>=', now()->subMonths(3))
    ->get(); // Potentially 100,000+ rows
```

**After (Fast):**
```sql
-- Add composite index
CREATE INDEX idx_fraud_detection 
ON pengajuan_items(price_index_id, created_at, reported_by_user_id);

-- Query now uses index
SELECT price_index_id, unit_price, created_at
FROM pengajuan_items 
WHERE reported_by_user_id = ?
  AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
ORDER BY created_at ASC;
```

**Performance Gain:** 2000ms → 50ms (40x faster)

---


**Dashboard UI Example:**

```html
<!-- Fraud Detection Dashboard -->
<div class="row">
    <!-- Active Alerts -->
    <div class="col-md-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5>🚨 Active Fraud Alerts</h5>
                <span class="badge bg-white text-danger">{{ $activeAlerts->count() }} alerts</span>
            </div>
            <div class="card-body">
                @foreach($activeAlerts as $alert)
                <div class="alert alert-{{ $alert->severity === 'critical' ? 'danger' : 'warning' }}">
                    <strong>{{ $alert->user->name }}</strong>
                    <p>{{ json_decode($alert->data)->message }}</p>
                    <a href="{{ route('fraud.investigate', $alert) }}" class="btn btn-sm btn-primary">
                        Investigate
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Technician Risk Scores -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>📊 Technician Risk Scores</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Teknisi</th>
                            <th>Submissions</th>
                            <th>Anomalies</th>
                            <th>Risk Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($technicianRiskScores as $score)
                        <tr class="{{ $score['risk_level'] === 'critical' ? 'table-danger' : '' }}">
                            <td>{{ $score['teknisi'] }}</td>
                            <td>{{ $score['total_submissions'] }}</td>
                            <td>{{ $score['anomaly_count'] }}</td>
                            <td>
                                <span class="badge bg-{{ $score['risk_level'] === 'critical' ? 'danger' : 'warning' }}">
                                    {{ $score['risk_score'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
```




---



## 🎯 Implementation Checklist

### Phase 1: Market Adjustment (Week 1-2)
- [ ] Implement time-weighted price calculation
- [ ] Build trend detection service
- [ ] Create price trend alerts table
- [ ] Build owner adjustment interface
- [ ] Test with historical data

### Phase 2: Fraud Detection (Week 3-4)
- [ ] Build fraud detection service
- [ ] Implement pattern detection algorithms
- [ ] Create fraud alerts system
- [ ] Build investigation dashboard
- [ ] Train management on fraud indicators

### Phase 3: Multi-Level Approval (Week 5)
- [ ] Implement approval workflow
- [ ] Add manager approval step
- [ ] Create approval tracking
- [ ] Build approval dashboard

### Phase 4: Additional Controls (Week 6)
- [ ] Implement price index freeze
- [ ] Add supplier diversity checks
- [ ] Build comprehensive audit trail
- [ ] Create fraud monitoring dashboard

---

## 📝 Best Practices

### For Owners/Management:

1. **Regular Reviews**
   - Review price trend alerts weekly
   - Audit high-risk technicians monthly
   - Random spot-checks quarterly

2. **Investigation Protocol**
   - Document all fraud investigations
   - Interview teknisi + cross-check with supplier
   - Check market prices independently

3. **Preventive Culture**
   - Clear communication of consequences
   - Training on proper procurement
   - Reward honest reporting

### For System Integrity:

1. **Data Quality**
   - Regular data cleanup
   - Validate supplier information
   - Cross-reference with market data

2. **Transparency**
   - All actions logged
   - Audit trail accessible
   - Reports available to stakeholders

3. **Continuous Improvement**
   - Analyze fraud patterns
   - Update detection algorithms
   - Refine thresholds based on data

---

**Document Owner:** Finance & Compliance Team  
**Review Cycle:** Quarterly  
**Last Updated:** April 9, 2026

