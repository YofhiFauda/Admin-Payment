# Price Index System - Advanced Features & Data Governance

> **Extending Price Index dengan Multi-Supplier Tracking, Market Intelligence, dan Data Quality Control**

📅 **Document Version:** 2.0  
🔄 **Last Updated:** April 8, 2024  
🎯 **Status:** Advanced Features - Phase 2 Implementation

---

## 🎯 Executive Summary

Dokumen ini adalah **lanjutan** dari `PRICE_INDEX_IMPROVEMENTS.md` yang membahas:

### **Advanced Features (Saran 1-3):**
1. **Multi-Supplier Price Comparison** - Track harga per vendor untuk leverage negotiation
2. **Market Price Intelligence** - Auto-scraping harga referensi dari marketplace
3. **Confidence Score System** - Weighted reliability metric untuk price index

### **Data Governance (Pertanyaan Kritis):**
4. **Item Name Standardization** - Solusi komprehensif untuk data quality control

---

## 📊 Feature #1: Multi-Supplier Price Comparison

### Business Value

**Problem Statement:**
```
Current System:
- Hanya track harga rata-rata per item, tanpa diferensiasi supplier
- Tidak tahu supplier mana yang consistently lebih murah
- Kehilangan leverage untuk negotiation

Example:
Item: "Kabel NYM 3x2.5"
- Supplier A: Rp 28.000/m (5 transaksi)
- Supplier B: Rp 32.000/m (8 transaksi)
- Supplier C: Rp 29.500/m (3 transaksi)

Current avg_price = Rp 29.875
→ Tidak terlihat bahwa Supplier A selalu 10% lebih murah!
```

**Impact:**
- **Cost Savings**: Identifikasi supplier terbaik untuk setiap kategori
- **Negotiation Power**: Data historis untuk tender negotiation
- **Risk Management**: Deteksi price fixing atau collusion
- **Strategic Sourcing**: Build preferred supplier list

### Database Schema Enhancement

```sql
-- New table: Supplier-specific price indexes
CREATE TABLE supplier_price_indexes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    price_index_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    supplier_name VARCHAR(255) NOT NULL,
    
    -- Price statistics per supplier
    min_price DECIMAL(15,2) NOT NULL,
    max_price DECIMAL(15,2) NOT NULL,
    avg_price DECIMAL(15,2) NOT NULL,
    median_price DECIMAL(15,2) NOT NULL,
    
    -- Quality metrics
    total_transactions INT DEFAULT 0,
    on_time_delivery_rate DECIMAL(5,2) NULL, -- %
    quality_rejection_rate DECIMAL(5,2) NULL, -- %
    
    -- Price trends
    price_trend ENUM('stable', 'rising', 'falling') NULL,
    volatility_score DECIMAL(5,2) NULL, -- Coefficient of Variation
    last_transaction_date DATE NULL,
    
    -- Rankings
    rank_by_price INT NULL, -- 1 = cheapest
    rank_by_reliability INT NULL, -- Combined score
    
    is_preferred_supplier BOOLEAN DEFAULT FALSE,
    notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_price_supplier (price_index_id, supplier_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_rank_price (rank_by_price),
    FOREIGN KEY (price_index_id) REFERENCES price_indexes(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
);

-- Enhancement to pengajuan_items
ALTER TABLE pengajuan_items ADD COLUMN (
    supplier_id BIGINT UNSIGNED NULL,
    supplier_price_index_id BIGINT UNSIGNED NULL,
    
    INDEX idx_supplier (supplier_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_price_index_id) REFERENCES supplier_price_indexes(id) ON DELETE SET NULL
);
```

### Calculation Logic

```php
class SupplierPriceIndexService
{
    public function calculateSupplierPriceIndex(int $priceIndexId, int $supplierId)
    {
        // 1. Get all approved transactions for this item + supplier
        $transactions = DB::table('pengajuan_items')
            ->join('pengajuans', 'pengajuan_items.pengajuan_id', '=', 'pengajuans.id')
            ->where('pengajuan_items.price_index_id', $priceIndexId)
            ->where('pengajuan_items.supplier_id', $supplierId)
            ->where('pengajuans.status', 'approved')
            ->where('pengajuans.created_at', '>=', now()->subMonths(6))
            ->pluck('pengajuan_items.unit_price')
            ->toArray();
        
        if (count($transactions) < 3) {
            // Not enough data for supplier-specific index
            return null;
        }
        
        // 2. Calculate statistics
        $stats = $this->calculatePriceStatistics($transactions);
        
        // 3. Calculate quality metrics
        $qualityMetrics = $this->calculateSupplierQualityMetrics($supplierId, $priceIndexId);
        
        // 4. Update or create supplier price index
        $supplierIndex = SupplierPriceIndex::updateOrCreate(
            [
                'price_index_id' => $priceIndexId,
                'supplier_id' => $supplierId,
            ],
            [
                'supplier_name' => Supplier::find($supplierId)->name,
                'min_price' => $stats['min'],
                'max_price' => $stats['max'],
                'avg_price' => $stats['avg'],
                'median_price' => $stats['median'],
                'total_transactions' => count($transactions),
                'volatility_score' => $stats['coefficient_of_variation'],
                'price_trend' => $this->detectPriceTrend($transactions),
                'last_transaction_date' => now(),
                'on_time_delivery_rate' => $qualityMetrics['on_time_rate'],
                'quality_rejection_rate' => $qualityMetrics['rejection_rate'],
            ]
        );
        
        // 5. Update rankings
        $this->updateSupplierRankings($priceIndexId);
        
        return $supplierIndex;
    }
    
    private function calculatePriceStatistics(array $prices): array
    {
        sort($prices);
        
        return [
            'min' => min($prices),
            'max' => max($prices),
            'avg' => array_sum($prices) / count($prices),
            'median' => $this->getMedian($prices),
            'coefficient_of_variation' => $this->getCoefficientOfVariation($prices),
        ];
    }
    
    private function getCoefficientOfVariation(array $prices): float
    {
        $mean = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $prices)) / count($prices);
        $stdDev = sqrt($variance);
        
        return $mean > 0 ? ($stdDev / $mean) * 100 : 0; // as percentage
    }
    
    private function detectPriceTrend(array $prices): string
    {
        if (count($prices) < 5) return 'stable';
        
        // Simple linear regression slope
        $n = count($prices);
        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($prices);
        $sumXY = array_sum(array_map(fn($xi, $yi) => $xi * $yi, $x, $prices));
        $sumX2 = array_sum(array_map(fn($xi) => $xi ** 2, $x));
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX ** 2);
        
        if ($slope > 500) return 'rising'; // Rising > Rp 500/transaction
        if ($slope < -500) return 'falling';
        return 'stable';
    }
    
    private function updateSupplierRankings(int $priceIndexId)
    {
        // Rank by price (ascending)
        $suppliers = SupplierPriceIndex::where('price_index_id', $priceIndexId)
            ->orderBy('avg_price', 'asc')
            ->get();
        
        $rank = 1;
        foreach ($suppliers as $supplier) {
            $supplier->rank_by_price = $rank++;
            $supplier->save();
        }
        
        // Rank by reliability (composite score)
        $suppliers = SupplierPriceIndex::where('price_index_id', $priceIndexId)
            ->get()
            ->map(function ($supplier) {
                // Weighted score: 50% price, 30% on-time, 20% quality
                $priceScore = 100 - (($supplier->rank_by_price - 1) * 10); // Lower rank = higher score
                $onTimeScore = $supplier->on_time_delivery_rate ?? 50;
                $qualityScore = 100 - ($supplier->quality_rejection_rate ?? 50);
                
                $supplier->reliability_score = 
                    ($priceScore * 0.5) + 
                    ($onTimeScore * 0.3) + 
                    ($qualityScore * 0.2);
                
                return $supplier;
            })
            ->sortByDesc('reliability_score');
        
        $rank = 1;
        foreach ($suppliers as $supplier) {
            $supplier->rank_by_reliability = $rank++;
            $supplier->save();
        }
    }
}
```

### UI/UX Implementation

#### **Owner Dashboard Widget:**

```html
<!-- Supplier Comparison Table -->
<div class="card">
    <div class="card-header">
        <h5>📊 Perbandingan Supplier - {{ $priceIndex->item_name }}</h5>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Supplier</th>
                    <th>Harga Rata-rata</th>
                    <th>Transaksi</th>
                    <th>Trend</th>
                    <th>Volatilitas</th>
                    <th>On-Time %</th>
                    <th>Quality %</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supplierIndexes as $supplier)
                <tr class="{{ $supplier->is_preferred_supplier ? 'table-success' : '' }}">
                    <td>
                        @if($supplier->rank_by_price === 1)
                            <span class="badge bg-success">🏆 #{{ $supplier->rank_by_price }}</span>
                        @else
                            <span class="badge bg-secondary">#{{ $supplier->rank_by_price }}</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $supplier->supplier_name }}</strong>
                        @if($supplier->is_preferred_supplier)
                            <span class="badge bg-primary">Preferred</span>
                        @endif
                    </td>
                    <td>Rp {{ number_format($supplier->avg_price) }}</td>
                    <td>
                        <span class="badge bg-info">{{ $supplier->total_transactions }}x</span>
                    </td>
                    <td>
                        @if($supplier->price_trend === 'rising')
                            <span class="text-danger">📈 Rising</span>
                        @elseif($supplier->price_trend === 'falling')
                            <span class="text-success">📉 Falling</span>
                        @else
                            <span class="text-muted">➡️ Stable</span>
                        @endif
                    </td>
                    <td>
                        @if($supplier->volatility_score < 10)
                            <span class="badge bg-success">Low ({{ number_format($supplier->volatility_score, 1) }}%)</span>
                        @elseif($supplier->volatility_score < 20)
                            <span class="badge bg-warning">Medium ({{ number_format($supplier->volatility_score, 1) }}%)</span>
                        @else
                            <span class="badge bg-danger">High ({{ number_format($supplier->volatility_score, 1) }}%)</span>
                        @endif
                    </td>
                    <td>
                        <span class="text-{{ $supplier->on_time_delivery_rate >= 90 ? 'success' : 'warning' }}">
                            {{ number_format($supplier->on_time_delivery_rate, 1) }}%
                        </span>
                    </td>
                    <td>
                        <span class="text-{{ $supplier->quality_rejection_rate <= 5 ? 'success' : 'danger' }}">
                            {{ number_format(100 - $supplier->quality_rejection_rate, 1) }}%
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="setPreferredSupplier({{ $supplier->id }})">
                            Set Preferred
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="mt-3">
            <p class="text-muted">
                💡 <strong>Insight:</strong> 
                {{ $bestSupplier->supplier_name }} adalah pilihan terbaik 
                ({{ number_format($savingsPotential) }}% lebih murah dari rata-rata market)
            </p>
        </div>
    </div>
</div>
```

#### **Form Pengajuan Enhancement:**

```html
<!-- When teknisi selects item, show supplier recommendations -->
<div class="form-group">
    <label>Item</label>
    <select name="item_id" id="item_select" class="form-control">
        <option value="">Pilih Item...</option>
        @foreach($items as $item)
            <option value="{{ $item->id }}">{{ $item->name }}</option>
        @endforeach
    </select>
</div>

<!-- Dynamic supplier recommendation panel -->
<div id="supplier-recommendations" class="alert alert-info" style="display: none;">
    <h6>💡 Rekomendasi Supplier:</h6>
    <div id="supplier-list"></div>
</div>

<script>
$('#item_select').on('change', function() {
    const itemId = $(this).val();
    
    fetch(`/api/supplier-recommendations/${itemId}`)
        .then(res => res.json())
        .then(data => {
            if (data.suppliers.length > 0) {
                let html = '<ul>';
                data.suppliers.forEach((supplier, index) => {
                    const badge = index === 0 ? '🏆 Recommended' : `#${index + 1}`;
                    html += `
                        <li>
                            <strong>${badge}: ${supplier.name}</strong> - 
                            Rp ${supplier.avg_price.toLocaleString()} 
                            (${supplier.total_transactions} transaksi, 
                            ${supplier.on_time_rate}% on-time)
                        </li>
                    `;
                });
                html += '</ul>';
                
                $('#supplier-list').html(html);
                $('#supplier-recommendations').show();
            }
        });
});
</script>
```

### Analytics & Reporting

```php
// Command: php artisan supplier:analyze-performance

class AnalyzeSupplierPerformance extends Command
{
    public function handle()
    {
        $this->info('Analyzing supplier performance...');
        
        $analysis = DB::table('supplier_price_indexes')
            ->select([
                'supplier_id',
                'supplier_name',
                DB::raw('COUNT(DISTINCT price_index_id) as items_supplied'),
                DB::raw('SUM(total_transactions) as total_transactions'),
                DB::raw('AVG(rank_by_price) as avg_price_rank'),
                DB::raw('AVG(on_time_delivery_rate) as avg_on_time'),
                DB::raw('AVG(quality_rejection_rate) as avg_rejection'),
                DB::raw('AVG(volatility_score) as avg_volatility'),
            ])
            ->groupBy('supplier_id', 'supplier_name')
            ->orderBy('avg_price_rank', 'asc')
            ->get();
        
        // Generate report
        $this->table(
            ['Supplier', 'Items', 'Transactions', 'Avg Rank', 'On-Time %', 'Quality %', 'Volatility'],
            $analysis->map(fn($s) => [
                $s->supplier_name,
                $s->items_supplied,
                $s->total_transactions,
                number_format($s->avg_price_rank, 1),
                number_format($s->avg_on_time, 1) . '%',
                number_format(100 - $s->avg_rejection, 1) . '%',
                number_format($s->avg_volatility, 1) . '%',
            ])
        );
        
        // Recommend preferred suppliers
        $this->info("\n📊 Recommended Preferred Suppliers:");
        $topSuppliers = $analysis->take(5);
        foreach ($topSuppliers as $supplier) {
            $this->line("• {$supplier->supplier_name}");
        }
    }
}
```

---

## 🌐 Feature #2: Market Price Intelligence (Auto-Scraping)

### Business Value

**Problem Statement:**
```
Current: Price references hanya dari internal transaction history
→ Tidak tahu apakah harga pasar sudah berubah
→ Bisa saja semua supplier sudah overcharge

Example:
Internal avg price: Rp 30.000/m (dari 50 transaksi)
Market price (Tokopedia): Rp 22.000/m
→ Overpayment 36% selama berbulan-bulan!
```

**Impact:**
- **Market Awareness**: Tahu harga pasar real-time
- **Negotiation Leverage**: "Competitor sell at Rp X, why yours Rp Y?"
- **Budget Optimization**: Validate internal prices vs market
- **Trend Detection**: Early warning jika harga pasar naik drastis

### Architecture

```
n8n Workflow (Daily 3 AM)
    ↓
Scrape marketplace APIs/websites
    ↓
Parse & normalize data
    ↓
Store in market_prices table
    ↓
Compare vs internal price_indexes
    ↓
Alert Owner jika price gap > threshold
```

### Database Schema

```sql
CREATE TABLE market_prices (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    price_index_id BIGINT UNSIGNED NULL,
    
    -- Source information
    source_platform ENUM('tokopedia', 'shopee', 'bukalapak', 'manual') NOT NULL,
    source_url TEXT NULL,
    scrape_timestamp TIMESTAMP NOT NULL,
    
    -- Product info
    product_name VARCHAR(255) NOT NULL,
    product_spec TEXT NULL,
    brand VARCHAR(100) NULL,
    
    -- Pricing
    market_price DECIMAL(15,2) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    currency VARCHAR(3) DEFAULT 'IDR',
    
    -- Quality indicators
    seller_rating DECIMAL(3,2) NULL,
    total_sold INT NULL,
    is_verified_seller BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    is_active BOOLEAN DEFAULT TRUE,
    confidence_score DECIMAL(5,2) NULL, -- How confident matching is correct
    matched_by ENUM('auto', 'manual', 'ai') DEFAULT 'auto',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_price_index (price_index_id),
    INDEX idx_platform (source_platform),
    INDEX idx_scrape_time (scrape_timestamp),
    FOREIGN KEY (price_index_id) REFERENCES price_indexes(id) ON DELETE SET NULL
);

CREATE TABLE price_comparison_alerts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    price_index_id BIGINT UNSIGNED NOT NULL,
    
    internal_avg_price DECIMAL(15,2) NOT NULL,
    market_avg_price DECIMAL(15,2) NOT NULL,
    price_gap_amount DECIMAL(15,2) NOT NULL,
    price_gap_percentage DECIMAL(5,2) NOT NULL,
    
    status ENUM('new', 'investigating', 'resolved', 'false_positive') DEFAULT 'new',
    alert_type ENUM('overpaying', 'underpaying', 'market_spike') NOT NULL,
    
    owner_notified_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_price_index (price_index_id),
    FOREIGN KEY (price_index_id) REFERENCES price_indexes(id) ON DELETE CASCADE
);
```

### n8n Workflow Implementation

```yaml
Workflow Name: Daily Market Price Scraper
Schedule: 0 3 * * * (Every day 3 AM)

Nodes:
  1. Schedule Trigger
  
  2. HTTP Request - Get items to scrape
     URL: https://whusnet.com/api/price-indexes/high-priority
     Method: GET
     → Returns: Top 100 items dengan transaction volume tertinggi
  
  3. Loop Over Items (Split in Batches - 10 items)
  
  4. Function - Build search query
     Code:
       const item = $json;
       return {
         searchQuery: `${item.item_name} ${item.category_name}`,
         itemId: item.id,
         internalPrice: item.avg_price
       };
  
  5. HTTP Request - Tokopedia API
     URL: https://gql.tokopedia.com/graphql/SearchProductQuery
     Method: POST
     Body:
       {
         "query": "{{$json.searchQuery}}",
         "limit": 20
       }
     Rate Limit: 1 request per 2 seconds
  
  6. Function - Parse Tokopedia results
     Code:
       const results = $json.data.searchProduct.data;
       return results.map(product => ({
         source_platform: 'tokopedia',
         product_name: product.name,
         market_price: product.price.value,
         unit: 'pcs', // Need normalization
         seller_rating: product.shop.reputation,
         total_sold: product.stats.countSold,
         source_url: product.url,
         scrape_timestamp: new Date().toISOString()
       }));
  
  7. HTTP Request - Shopee API (Similar)
  
  8. Function - Merge & deduplicate results
  
  9. Function - Calculate confidence score
     Code:
       // Fuzzy string matching untuk product name vs item_name
       const similarity = stringSimilarity.compareTwoStrings(
         $json.product_name.toLowerCase(),
         $input.first().json.item_name.toLowerCase()
       );
       
       return {
         ...$json,
         confidence_score: similarity * 100,
         matched_by: similarity > 0.8 ? 'auto' : 'manual_review_needed'
       };
  
  10. HTTP Request - Save to database
      URL: https://whusnet.com/api/market-prices/bulk-create
      Method: POST
      Body: {{$json}}
  
  11. Function - Calculate price gap
      Code:
        const marketAvg = average($json.map(p => p.market_price));
        const internalPrice = $input.first().json.internalPrice;
        const gap = ((internalPrice - marketAvg) / marketAvg) * 100;
        
        return {
          price_index_id: $input.first().json.itemId,
          internal_avg_price: internalPrice,
          market_avg_price: marketAvg,
          price_gap_percentage: gap,
          alert_type: gap > 20 ? 'overpaying' : 
                      gap < -20 ? 'underpaying' : null
        };
  
  12. Switch - Route by alert threshold
      Condition: {{$json.price_gap_percentage}} > 20
  
  13a. HTTP Request - Create alert (if gap > 20%)
       URL: https://whusnet.com/api/price-comparison-alerts
  
  13b. Telegram - Notify Owner
       Message:
         ⚠️ *MARKET PRICE ALERT*
         
         Item: {{$json.item_name}}
         Internal Avg: Rp {{$json.internal_avg_price}}
         Market Avg: Rp {{$json.market_avg_price}}
         Gap: {{$json.price_gap_percentage}}% HIGHER
         
         Potential savings: Rp {{$json.potential_savings}}
         [Review Now]({{$json.review_url}})
  
  14. Google Sheets - Log results
      Spreadsheet: Market Price Monitoring
      Sheet: Daily Scrapes
```

### Laravel Integration

```php
// API Endpoint untuk n8n
class MarketPriceController extends Controller
{
    public function getHighPriorityItems(Request $request)
    {
        // Get items dengan transaction volume tertinggi
        $items = PriceIndex::where('is_manual', false)
            ->where('total_transactions', '>=', 5)
            ->orderBy('total_transactions', 'desc')
            ->limit(100)
            ->get(['id', 'item_name', 'category_id', 'avg_price', 'unit'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'category_name' => $item->category->name ?? null,
                    'avg_price' => $item->avg_price,
                    'unit' => $item->unit,
                ];
            });
        
        return response()->json($items);
    }
    
    public function bulkCreateMarketPrices(Request $request)
    {
        $validated = $request->validate([
            '*.price_index_id' => 'nullable|exists:price_indexes,id',
            '*.source_platform' => 'required|in:tokopedia,shopee,bukalapak,manual',
            '*.product_name' => 'required|string|max:255',
            '*.market_price' => 'required|numeric|min:0',
            '*.unit' => 'required|string|max:50',
            '*.confidence_score' => 'nullable|numeric|min:0|max:100',
            '*.matched_by' => 'nullable|in:auto,manual,ai',
        ]);
        
        DB::beginTransaction();
        try {
            foreach ($validated as $data) {
                MarketPrice::create($data);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Market prices saved successfully',
                'count' => count($validated),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function createPriceComparisonAlert(Request $request)
    {
        $validated = $request->validate([
            'price_index_id' => 'required|exists:price_indexes,id',
            'internal_avg_price' => 'required|numeric',
            'market_avg_price' => 'required|numeric',
            'price_gap_percentage' => 'required|numeric',
            'alert_type' => 'required|in:overpaying,underpaying,market_spike',
        ]);
        
        $alert = PriceComparisonAlert::create([
            ...$validated,
            'price_gap_amount' => $validated['internal_avg_price'] - $validated['market_avg_price'],
            'status' => 'new',
        ]);
        
        // Queue notification
        dispatch(new SendPriceComparisonAlertJob($alert->id));
        
        return response()->json($alert, 201);
    }
}
```

### Owner Dashboard

```html
<!-- Market Price Comparison Widget -->
<div class="card">
    <div class="card-header">
        <h5>🌐 Market vs Internal Price Comparison</h5>
        <span class="badge bg-warning">{{ $activeAlerts }} alerts</span>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Internal Avg</th>
                    <th>Market Avg</th>
                    <th>Gap</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alerts as $alert)
                <tr>
                    <td>{{ $alert->priceIndex->item_name }}</td>
                    <td>Rp {{ number_format($alert->internal_avg_price) }}</td>
                    <td>
                        Rp {{ number_format($alert->market_avg_price) }}
                        <small class="text-muted d-block">
                            ({{ $alert->marketPrices->count() }} sources)
                        </small>
                    </td>
                    <td>
                        @if($alert->price_gap_percentage > 0)
                            <span class="badge bg-danger">
                                +{{ number_format($alert->price_gap_percentage, 1) }}% 
                                (Overpaying)
                            </span>
                        @else
                            <span class="badge bg-success">
                                {{ number_format($alert->price_gap_percentage, 1) }}%
                            </span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $alert->status === 'new' ? 'warning' : 'secondary' }}">
                            {{ ucfirst($alert->status) }}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" 
                                onclick="showMarketDetails({{ $alert->id }})">
                            View Details
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Market Price Details -->
<div class="modal" id="marketDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Market Price Sources</h5>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Seller Rating</th>
                            <th>Sold</th>
                            <th>Link</th>
                        </tr>
                    </thead>
                    <tbody id="market-sources-table">
                        <!-- Populated via AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" onclick="markAlertResolved()">
                    Mark as Resolved
                </button>
                <button class="btn btn-warning" onclick="markFalsePositive()">
                    False Positive
                </button>
            </div>
        </div>
    </div>
</div>
```

### Matching Algorithm (AI-Powered)

Untuk meningkatkan akurasi matching antara internal item vs market products:

```php
use OpenAI\Laravel\Facades\OpenAI;

class MarketPriceMatchingService
{
    public function matchProductToItem(array $marketProduct, PriceIndex $priceIndex): array
    {
        // Use AI untuk semantic matching
        $prompt = "
            Apakah produk berikut sama dengan item yang kami cari?
            
            Item Kami:
            - Nama: {$priceIndex->item_name}
            - Kategori: {$priceIndex->category->name}
            - Unit: {$priceIndex->unit}
            
            Produk Market:
            - Nama: {$marketProduct['product_name']}
            - Spec: {$marketProduct['product_spec']}
            
            Respond dengan JSON:
            {
                \"is_match\": true/false,
                \"confidence\": 0-100,
                \"reasoning\": \"penjelasan singkat\"
            }
        ";
        
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);
        
        return json_decode($response->choices[0]->message->content, true);
    }
}
```

---

## 🎯 Feature #3: Confidence Score System

### Business Value

**Problem Statement:**
```
Current: Semua price index treated equally
- Item dengan 100 transaksi = sama dipercaya dengan item 5 transaksi
- Tidak ada visual indicator untuk data quality

Example:
Item A: N=5, range Rp 20K-30K → avg Rp 25K (unreliable!)
Item B: N=100, range Rp 24K-26K → avg Rp 25K (very reliable!)

Both show avg Rp 25K, tapi confidence sangat berbeda!
```

**Impact:**
- **Better Decision Making**: Owner tahu mana data yang reliable
- **Prioritize Reviews**: Focus on low-confidence items first
- **Visual Trust Indicators**: Clear UI feedback
- **Auto-Flag Manual Review**: Items needing owner attention

### Confidence Score Calculation

```php
class ConfidenceScoreCalculator
{
    public function calculate(PriceIndex $priceIndex): float
    {
        $score = 0;
        
        // Factor 1: Sample Size (0-40 points)
        $sampleScore = $this->calculateSampleScore($priceIndex->total_transactions);
        $score += $sampleScore * 0.4;
        
        // Factor 2: Price Consistency (0-30 points)
        $consistencyScore = $this->calculateConsistencyScore($priceIndex);
        $score += $consistencyScore * 0.3;
        
        // Factor 3: Data Recency (0-20 points)
        $recencyScore = $this->calculateRecencyScore($priceIndex->last_calculated_at);
        $score += $recencyScore * 0.2;
        
        // Factor 4: Supplier Diversity (0-10 points)
        $diversityScore = $this->calculateDiversityScore($priceIndex->id);
        $score += $diversityScore * 0.1;
        
        return min(100, max(0, $score)); // Clamp to 0-100
    }
    
    private function calculateSampleScore(int $transactions): float
    {
        // Logarithmic scale: diminishing returns after 50 samples
        if ($transactions >= 50) return 100;
        if ($transactions >= 20) return 80;
        if ($transactions >= 10) return 60;
        if ($transactions >= 5) return 40;
        return ($transactions / 5) * 40; // Linear below 5
    }
    
    private function calculateConsistencyScore(PriceIndex $priceIndex): float
    {
        // Lower Coefficient of Variation = higher consistency
        $cv = $this->getCoefficientOfVariation($priceIndex->id);
        
        if ($cv < 5) return 100;  // Very consistent
        if ($cv < 10) return 80;  // Consistent
        if ($cv < 20) return 60;  // Moderate
        if ($cv < 30) return 40;  // Variable
        return 20; // Highly variable
    }
    
    private function calculateRecencyScore(Carbon $lastCalculated): float
    {
        $daysOld = $lastCalculated->diffInDays(now());
        
        if ($daysOld <= 7) return 100;   // Fresh
        if ($daysOld <= 30) return 80;   // Recent
        if ($daysOld <= 90) return 60;   // Acceptable
        if ($daysOld <= 180) return 40;  // Stale
        return 20; // Very stale
    }
    
    private function calculateDiversityScore(int $priceIndexId): float
    {
        // More unique suppliers = more reliable data
        $uniqueSuppliers = DB::table('pengajuan_items')
            ->where('price_index_id', $priceIndexId)
            ->distinct('supplier_id')
            ->count('supplier_id');
        
        if ($uniqueSuppliers >= 5) return 100;
        if ($uniqueSuppliers >= 3) return 80;
        if ($uniqueSuppliers >= 2) return 60;
        return 40; // Single supplier
    }
}
```

### Database Schema

```sql
ALTER TABLE price_indexes ADD COLUMN (
    confidence_score DECIMAL(5,2) NULL,
    confidence_level ENUM('very_high', 'high', 'medium', 'low', 'very_low') NULL,
    requires_owner_review BOOLEAN DEFAULT FALSE,
    
    INDEX idx_confidence (confidence_score),
    INDEX idx_review_required (requires_owner_review)
);
```

### Auto-Update Confidence

```php
// Observer: Auto-update confidence after calculation
class PriceIndexObserver
{
    public function updated(PriceIndex $priceIndex)
    {
        if ($priceIndex->isDirty(['total_transactions', 'last_calculated_at'])) {
            $calculator = new ConfidenceScoreCalculator();
            $score = $calculator->calculate($priceIndex);
            
            $priceIndex->confidence_score = $score;
            $priceIndex->confidence_level = $this->getConfidenceLevel($score);
            $priceIndex->requires_owner_review = $score < 50; // Flag if low confidence
            
            $priceIndex->saveQuietly(); // Prevent recursion
        }
    }
    
    private function getConfidenceLevel(float $score): string
    {
        return match(true) {
            $score >= 80 => 'very_high',
            $score >= 60 => 'high',
            $score >= 40 => 'medium',
            $score >= 20 => 'low',
            default => 'very_low',
        };
    }
}
```

### UI Implementation

```html
<!-- Price Index Table dengan Confidence Indicators -->
<table class="table">
    <thead>
        <tr>
            <th>Item</th>
            <th>Avg Price</th>
            <th>Transactions</th>
            <th>Confidence</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($priceIndexes as $index)
        <tr class="{{ $index->requires_owner_review ? 'table-warning' : '' }}">
            <td>
                {{ $index->item_name }}
                @if($index->requires_owner_review)
                    <span class="badge bg-warning">⚠️ Review Needed</span>
                @endif
            </td>
            <td>Rp {{ number_format($index->avg_price) }}</td>
            <td>
                <span class="badge bg-info">{{ $index->total_transactions }}x</span>
            </td>
            <td>
                <!-- Confidence Score Indicator -->
                <div class="d-flex align-items-center">
                    <!-- Progress bar -->
                    <div class="progress flex-grow-1 me-2" style="height: 20px; min-width: 100px;">
                        <div class="progress-bar bg-{{ $index->confidence_level === 'very_high' ? 'success' : 
                                                          ($index->confidence_level === 'high' ? 'info' : 
                                                          ($index->confidence_level === 'medium' ? 'warning' : 'danger')) }}" 
                             role="progressbar" 
                             style="width: {{ $index->confidence_score }}%">
                            {{ number_format($index->confidence_score, 0) }}%
                        </div>
                    </div>
                    
                    <!-- Icon indicator -->
                    @if($index->confidence_score >= 80)
                        <span class="text-success" title="Very High Confidence">✓✓</span>
                    @elseif($index->confidence_score >= 60)
                        <span class="text-info" title="High Confidence">✓</span>
                    @elseif($index->confidence_score >= 40)
                        <span class="text-warning" title="Medium Confidence">~</span>
                    @else
                        <span class="text-danger" title="Low Confidence">⚠️</span>
                    @endif
                </div>
                
                <!-- Tooltip with breakdown -->
                <small class="text-muted d-block">
                    Sample: {{ $index->sample_score }}% | 
                    Consistency: {{ $index->consistency_score }}% | 
                    Recency: {{ $index->recency_score }}%
                </small>
            </td>
            <td>
                @if($index->requires_owner_review)
                    <button class="btn btn-sm btn-warning" 
                            onclick="reviewItem({{ $index->id }})">
                        Set Manual Reference
                    </button>
                @else
                    <button class="btn btn-sm btn-outline-secondary" 
                            onclick="viewDetails({{ $index->id }})">
                        Details
                    </button>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
```

### Dashboard Summary Widget

```html
<div class="row">
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5>Very High Confidence</h5>
                <h2>{{ $veryHighCount }}</h2>
                <small>{{ number_format(($veryHighCount/$totalItems)*100, 1) }}%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5>High Confidence</h5>
                <h2>{{ $highCount }}</h2>
                <small>{{ number_format(($highCount/$totalItems)*100, 1) }}%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5>Medium Confidence</h5>
                <h2>{{ $mediumCount }}</h2>
                <small>{{ number_format(($mediumCount/$totalItems)*100, 1) }}%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5>Low Confidence</h5>
                <h2>{{ $lowCount }}</h2>
                <small>Needs Review!</small>
            </div>
        </div>
    </div>
</div>
```

---

## 📋 Feature #4: Item Name Standardization (Critical!)

### Problem Statement

**Real-World Chaos:**
```sql
-- Same item, different names in database:
'Kabel NYM 3x2.5'
'kabel nym 3x2,5'
'Kabel NYM 3 x 2.5mm'
'KABEL NYM 3X2.5'
'Kabel NYM 3x2.5 Meter'
'Kabel NYM Supreme 3x2.5'

→ 6 different price indexes untuk item yang SAMA!
→ Anomaly detection tidak berfungsi
→ Data terpecah, statistik tidak akurat
```

**Root Causes:**
1. **Manual input** oleh teknisi (typo, case sensitivity)
2. **Tidak ada validation** saat create item
3. **Tidak ada master catalog** yang enforce
4. **Copy-paste** dari nota/invoice (inconsistent formatting)

### Multi-Layer Solution Strategy

#### **Layer 1: Master Item Catalog (Preventive)**

```sql
-- New table: Master item catalog dengan standardized names
CREATE TABLE master_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Standardized naming
    item_code VARCHAR(50) UNIQUE NOT NULL, -- e.g., KBL-NYM-3X2.5
    item_name_standard VARCHAR(255) NOT NULL, -- "Kabel NYM 3x2.5"
    category_id BIGINT UNSIGNED NOT NULL,
    unit_standard ENUM('pcs', 'kg', 'meter', 'liter', 'box', 'roll') NOT NULL,
    
    -- Variations/aliases for matching
    name_variations JSON NULL, -- ["kabel nym", "nym cable", etc.]
    
    -- Specifications
    brand VARCHAR(100) NULL,
    specification TEXT NULL,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_item_code (item_code),
    INDEX idx_category (category_id),
    FULLTEXT INDEX ft_item_name (item_name_standard),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Link pengajuan_items to master catalog
ALTER TABLE pengajuan_items ADD COLUMN (
    master_item_id BIGINT UNSIGNED NULL,
    
    INDEX idx_master_item (master_item_id),
    FOREIGN KEY (master_item_id) REFERENCES master_items(id) ON DELETE SET NULL
);
```

#### **Layer 2: AI-Powered Auto-Suggest (UX)**

```php
// API untuk autocomplete dengan fuzzy matching
class MasterItemController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        // 1. Exact match first
        $exactMatches = MasterItem::where('item_name_standard', 'LIKE', "%{$query}%")
            ->orWhere('item_code', $query)
            ->limit(5)
            ->get();
        
        if ($exactMatches->isNotEmpty()) {
            return response()->json([
                'matches' => $exactMatches,
                'match_type' => 'exact',
            ]);
        }
        
        // 2. Fuzzy matching dengan PostgreSQL similarity
        $fuzzyMatches = DB::select("
            SELECT *, 
                   similarity(item_name_standard, ?) as score
            FROM master_items
            WHERE similarity(item_name_standard, ?) > 0.3
            ORDER BY score DESC
            LIMIT 10
        ", [$query, $query]);
        
        if (!empty($fuzzyMatches)) {
            return response()->json([
                'matches' => $fuzzyMatches,
                'match_type' => 'fuzzy',
                'suggestion' => 'Apakah maksud Anda salah satu dari ini?',
            ]);
        }
        
        // 3. AI-powered semantic search
        $aiMatches = $this->aiSemanticSearch($query);
        
        return response()->json([
            'matches' => $aiMatches,
            'match_type' => 'ai',
            'suggestion' => 'Item tidak ditemukan. Buat item baru?',
        ]);
    }
    
    private function aiSemanticSearch(string $query): array
    {
        // Use embeddings untuk semantic similarity
        $queryEmbedding = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $query,
        ])->embeddings[0]->embedding;
        
        // Search in vector database (e.g., Pinecone, Qdrant)
        // Or calculate cosine similarity in PostgreSQL with pgvector
        
        $results = DB::select("
            SELECT *,
                   1 - (embedding <=> ?) as similarity
            FROM master_items
            ORDER BY embedding <=> ?
            LIMIT 5
        ", [json_encode($queryEmbedding), json_encode($queryEmbedding)]);
        
        return $results;
    }
}
```

**Form Implementation:**

```html
<!-- Enhanced Item Selection dengan AI Autocomplete -->
<div class="form-group">
    <label>Nama Item</label>
    <input type="text" 
           id="item-search" 
           class="form-control" 
           placeholder="Ketik nama item..."
           autocomplete="off">
    
    <!-- Autocomplete suggestions -->
    <div id="suggestions" class="list-group position-absolute" style="z-index: 1000; display: none;">
        <!-- Populated via AJAX -->
    </div>
    
    <!-- Selected item (hidden) -->
    <input type="hidden" name="master_item_id" id="master-item-id">
    
    <!-- Or create new if not found -->
    <button type="button" class="btn btn-sm btn-link" id="create-new-item">
        + Buat Item Baru
    </button>
</div>

<script>
let debounceTimer;

$('#item-search').on('input', function() {
    const query = $(this).val();
    
    if (query.length < 3) {
        $('#suggestions').hide();
        return;
    }
    
    clearTimeout(debounceTimer);
    
    debounceTimer = setTimeout(() => {
        fetch(`/api/master-items/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                let html = '';
                
                if (data.match_type === 'fuzzy') {
                    html += `<div class="list-group-item bg-warning text-dark">
                        <small>${data.suggestion}</small>
                    </div>`;
                }
                
                data.matches.forEach(item => {
                    const score = item.score ? `(${Math.round(item.score * 100)}% match)` : '';
                    html += `
                        <a href="#" class="list-group-item list-group-item-action" 
                           data-item-id="${item.id}" 
                           data-item-name="${item.item_name_standard}">
                            <strong>${item.item_code}</strong> - ${item.item_name_standard}
                            <small class="text-muted">${score}</small>
                        </a>
                    `;
                });
                
                if (data.match_type === 'ai') {
                    html += `<div class="list-group-item bg-info text-white">
                        <small>${data.suggestion}</small>
                    </div>`;
                }
                
                $('#suggestions').html(html).show();
            });
    }, 300); // Debounce 300ms
});

// Select item from suggestions
$(document).on('click', '#suggestions a', function(e) {
    e.preventDefault();
    
    const itemId = $(this).data('item-id');
    const itemName = $(this).data('item-name');
    
    $('#master-item-id').val(itemId);
    $('#item-search').val(itemName);
    $('#suggestions').hide();
});
</script>
```

#### **Layer 3: Bulk Data Cleanup (Corrective)**

```php
// Command: php artisan items:deduplicate

class DeduplicateItems extends Command
{
    protected $signature = 'items:deduplicate {--dry-run}';
    
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Finding duplicate items...');
        
        // Group similar item names using Levenshtein distance
        $allItems = DB::table('pengajuan_items')
            ->select('item_name', DB::raw('COUNT(*) as count'))
            ->groupBy('item_name')
            ->get();
        
        $duplicateGroups = [];
        
        foreach ($allItems as $item) {
            $found = false;
            
            foreach ($duplicateGroups as &$group) {
                $similarity = similar_text(
                    strtolower($group['canonical']), 
                    strtolower($item->item_name)
                );
                
                // If > 80% similar, add to group
                if ($similarity / strlen($group['canonical']) > 0.8) {
                    $group['variants'][] = $item->item_name;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // Create new group
                $duplicateGroups[] = [
                    'canonical' => $item->item_name, // Most common version
                    'variants' => [$item->item_name],
                ];
            }
        }
        
        // Show results
        $this->table(
            ['Canonical Name', 'Variants', 'Total Items'],
            collect($duplicateGroups)
                ->filter(fn($g) => count($g['variants']) > 1)
                ->map(fn($g) => [
                    $g['canonical'],
                    implode(', ', $g['variants']),
                    count($g['variants']),
                ])
        );
        
        if (!$dryRun) {
            // Ask for confirmation
            if ($this->confirm('Proceed with standardization?')) {
                $this->standardizeNames($duplicateGroups);
            }
        }
    }
    
    private function standardizeNames(array $groups)
    {
        foreach ($groups as $group) {
            if (count($group['variants']) <= 1) continue;
            
            $canonical = $group['canonical'];
            
            // Update all variants to canonical name
            foreach ($group['variants'] as $variant) {
                if ($variant === $canonical) continue;
                
                DB::table('pengajuan_items')
                    ->where('item_name', $variant)
                    ->update(['item_name' => $canonical]);
                
                $this->info("Standardized: {$variant} → {$canonical}");
            }
        }
        
        $this->info('✓ Standardization complete!');
    }
}
```

#### **Layer 4: Validation Rules (Enforcement)**

```php
// Form Request Validation
class StorePengajuanItemRequest extends FormRequest
{
    public function rules()
    {
        return [
            'item_name' => [
                'required',
                'string',
                'max:255',
                new ItemNameFormat(), // Custom rule
                new ItemExistsInCatalog(), // Check master catalog
            ],
            'master_item_id' => 'required|exists:master_items,id',
            // ...
        ];
    }
}

// Custom Validation Rule: Format Check
class ItemNameFormat implements Rule
{
    public function passes($attribute, $value)
    {
        // Enforce naming conventions:
        // 1. Title case
        // 2. No multiple spaces
        // 3. No trailing spaces
        // 4. Use standard separators (x not X, . not ,)
        
        $normalized = $this->normalize($value);
        
        return $value === $normalized;
    }
    
    public function message()
    {
        return 'Format nama item tidak sesuai standar. Gunakan autocomplete atau hubungi admin.';
    }
    
    private function normalize(string $value): string
    {
        return trim(
            preg_replace('/\s+/', ' ', // Replace multiple spaces
                ucwords(strtolower($value)) // Title case
            )
        );
    }
}

// Custom Validation Rule: Catalog Check
class ItemExistsInCatalog implements Rule
{
    private $suggestedItem;
    
    public function passes($attribute, $value)
    {
        // Check if item exists in master catalog
        $exists = MasterItem::where('item_name_standard', $value)->exists();
        
        if ($exists) {
            return true;
        }
        
        // Try fuzzy match untuk suggestion
        $this->suggestedItem = DB::selectOne("
            SELECT item_name_standard 
            FROM master_items
            WHERE similarity(item_name_standard, ?) > 0.7
            ORDER BY similarity(item_name_standard, ?) DESC
            LIMIT 1
        ", [$value, $value]);
        
        return false;
    }
    
    public function message()
    {
        $suggestion = $this->suggestedItem 
            ? "Apakah maksud Anda: '{$this->suggestedItem->item_name_standard}'?" 
            : "Item tidak ada di katalog. Mohon buat item baru terlebih dahulu.";
        
        return $suggestion;
    }
}
```

#### **Layer 5: Admin Approval Workflow**

```php
// Workflow untuk new item creation
class MasterItemApprovalWorkflow
{
    public function requestNewItem(Request $request)
    {
        $item = MasterItem::create([
            'item_code' => null, // Will be generated after approval
            'item_name_standard' => $request->input('item_name'),
            'category_id' => $request->input('category_id'),
            'unit_standard' => $request->input('unit'),
            'created_by' => auth()->id(),
            'is_active' => false, // Pending approval
        ]);
        
        // Notify admin
        $admins = User::role('admin')->get();
        Notification::send($admins, new NewItemApprovalNeeded($item));
        
        return response()->json([
            'message' => 'Item baru diajukan untuk approval',
            'item_id' => $item->id,
            'status' => 'pending',
        ]);
    }
    
    public function approveNewItem(int $itemId, Request $request)
    {
        $item = MasterItem::findOrFail($itemId);
        
        // Generate item code
        $categoryCode = $item->category->code; // e.g., "KBL" untuk Kabel
        $sequence = MasterItem::where('category_id', $item->category_id)
            ->where('is_active', true)
            ->count() + 1;
        
        $item->item_code = sprintf('%s-%04d', $categoryCode, $sequence);
        $item->is_active = true;
        $item->approved_by = auth()->id();
        $item->save();
        
        // Notify requester
        $requester = User::find($item->created_by);
        $requester->notify(new ItemApproved($item));
        
        return response()->json([
            'message' => 'Item approved',
            'item_code' => $item->item_code,
        ]);
    }
}
```

#### **Layer 6: Monitoring & Metrics**

```php
// Dashboard metrics untuk data quality
class DataQualityMetrics
{
    public function getMetrics()
    {
        return [
            // Standardization rate
            'items_using_master_catalog' => DB::table('pengajuan_items')
                ->whereNotNull('master_item_id')
                ->count(),
            
            'total_items' => DB::table('pengajuan_items')->count(),
            
            'standardization_rate' => $this->calculateStandardizationRate(),
            
            // Duplicate detection
            'potential_duplicates' => $this->findPotentialDuplicates(),
            
            // Naming convention compliance
            'naming_compliance_rate' => $this->checkNamingCompliance(),
            
            // Pending approvals
            'pending_new_items' => MasterItem::where('is_active', false)->count(),
        ];
    }
    
    private function calculateStandardizationRate(): float
    {
        $using = DB::table('pengajuan_items')->whereNotNull('master_item_id')->count();
        $total = DB::table('pengajuan_items')->count();
        
        return $total > 0 ? ($using / $total) * 100 : 0;
    }
    
    private function findPotentialDuplicates(): int
    {
        // Find items dengan similarity > 80%
        $items = DB::table('pengajuan_items')
            ->select('item_name')
            ->distinct()
            ->get();
        
        $duplicates = 0;
        
        foreach ($items as $i => $item1) {
            foreach (array_slice($items->toArray(), $i + 1) as $item2) {
                $similarity = similar_text(
                    strtolower($item1->item_name),
                    strtolower($item2->item_name)
                );
                
                if ($similarity / strlen($item1->item_name) > 0.8) {
                    $duplicates++;
                }
            }
        }
        
        return $duplicates;
    }
}
```

**Dashboard Widget:**

```html
<!-- Data Quality Dashboard -->
<div class="card">
    <div class="card-header">
        <h5>📊 Data Quality Metrics</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Standardization Rate</h6>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-{{ $metrics['standardization_rate'] > 80 ? 'success' : 'warning' }}" 
                         style="width: {{ $metrics['standardization_rate'] }}%">
                        {{ number_format($metrics['standardization_rate'], 1) }}%
                    </div>
                </div>
                <small class="text-muted">
                    {{ number_format($metrics['items_using_master_catalog']) }} / {{ number_format($metrics['total_items']) }} items
                </small>
            </div>
            
            <div class="col-md-6">
                <h6>Potential Duplicates</h6>
                <h3 class="text-{{ $metrics['potential_duplicates'] > 0 ? 'danger' : 'success' }}">
                    {{ $metrics['potential_duplicates'] }}
                </h3>
                @if($metrics['potential_duplicates'] > 0)
                    <button class="btn btn-sm btn-warning" onclick="runDeduplication()">
                        Run Deduplication
                    </button>
                @else
                    <small class="text-success">✓ No duplicates found</small>
                @endif
            </div>
        </div>
        
        <hr>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <h6>Naming Convention Compliance</h6>
                <h3>{{ number_format($metrics['naming_compliance_rate'], 1) }}%</h3>
            </div>
            
            <div class="col-md-6">
                <h6>Pending New Item Approvals</h6>
                <h3 class="text-warning">{{ $metrics['pending_new_items'] }}</h3>
                <a href="{{ route('master-items.pending') }}" class="btn btn-sm btn-primary">
                    Review Now
                </a>
            </div>
        </div>
    </div>
</div>
```

---

## 🎯 Implementation Roadmap

### Phase 1: Multi-Supplier & Confidence (Week 1-3)
- [ ] Database migrations (supplier_price_indexes, confidence_score)
- [ ] SupplierPriceIndexService implementation
- [ ] ConfidenceScoreCalculator
- [ ] Dashboard UI updates
- [ ] Testing & deployment

### Phase 2: Master Catalog & Standardization (Week 4-6) **CRITICAL**
- [ ] Create master_items table
- [ ] Build autocomplete API dengan AI
- [ ] Implement validation rules
- [ ] Bulk deduplication tool
- [ ] Admin approval workflow
- [ ] Data quality dashboard

### Phase 3: Market Price Intelligence (Week 7-10)
- [ ] Setup n8n scraping workflows
- [ ] Tokopedia/Shopee API integration
- [ ] AI matching algorithm
- [ ] Price comparison alerts
- [ ] Dashboard widgets

---

## 📊 Success Metrics

```
Multi-Supplier Tracking:
- % items dengan 2+ supplier tracked: Target > 60%
- Average price variance between suppliers: Target < 15%
- Preferred supplier usage rate: Target > 70%

Market Intelligence:
- Market price coverage: Target > 50% of high-volume items
- Price gap detection accuracy: Target > 90%
- Market data freshness: Target < 7 days

Confidence Scoring:
- % items dengan confidence > 60%: Target > 80%
- Low-confidence items reviewed: Target < 5% unreviewed

Data Standardization (MOST CRITICAL):
- Standardization rate: Target > 95% within 6 months
- Duplicate items: Target < 2%
- Naming compliance: Target > 90%
- New item approval time: Target < 24 hours
```

---

## 🚨 Critical Success Factor

**Data Standardization adalah FONDASI dari seluruh sistem Price Index!**

Tanpa standardisasi nama item yang baik:
- ❌ Anomaly detection tidak akurat
- ❌ Multi-supplier comparison tidak berfungsi
- ❌ Market price matching gagal
- ❌ Confidence score misleading
- ❌ Reporting tidak reliable

**Recommendation:**
1. **Prioritize Phase 2 (Standardization) BEFORE atau PARALLEL dengan Phase 1**
2. **Allocate dedicated Admin resource** untuk master catalog management
3. **Run bulk deduplication** segera setelah launch
4. **Enforce validation** dari hari pertama (no exceptions!)
5. **Monitor data quality metrics** weekly

---

## 📝 Deployment Checklist (Extended)

```
Before Launch:
[ ] Bulk deduplicate existing data
[ ] Create master catalog dari top 200 items
[ ] Train admins on item approval workflow
[ ] Setup validation rules
[ ] Configure AI autocomplete API

Week 1 Post-Launch:
[ ] Monitor standardization rate daily
[ ] Review and approve pending new items
[ ] Fix naming convention violations
[ ] Run data quality report

Month 1:
[ ] Target: 50%+ standardization rate
[ ] Zero duplicate approvals
[ ] 100% naming compliance on new items

Month 3:
[ ] Target: 80%+ standardization rate
[ ] Launch market price scraping
[ ] Enable supplier comparison

Month 6:
[ ] Target: 95%+ standardization rate
[ ] Full system maturity
```

---

**Document Owner:** Development & Data Quality Team  
**Next Review:** After Phase 2 completion (Standardization)  
**Priority:** 🔴 CRITICAL - Data Quality is Foundation

