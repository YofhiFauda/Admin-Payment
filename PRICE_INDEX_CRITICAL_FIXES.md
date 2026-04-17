# Price Index - Common Implementation Issues & Solutions

> **Critical Fixes: Avg Calculation & Form Validation Strategy**

📅 **Created:** April 9, 2026  
🎯 **Priority:** CRITICAL - System Design Flaw  
⚠️ **Status:** Implementation Bug Fix

---

## 🚨 Issue #1: Avg Price Tidak Update Setelah Edit Min/Max

### Problem Statement

```
Scenario:
1. User edit price index manual:
   - Min: Rp 139.000
   - Max: Rp 170.000
   
2. Submit form
   
3. Result:
   - Min: Rp 139.000 ✅
   - Max: Rp 170.000 ✅
   - Avg: Rp 139.000 ❌ (masih sama!)
   
Expected: Avg seharusnya update ke ~Rp 154.500
```

### Root Cause Analysis

Ada **2 kemungkinan penyebab**:

#### Cause #1: Avg Tidak Ter-Update di Form Handler

**Wrong Implementation:**

```php
// Controller - Manual price index adjustment
public function update(Request $request, PriceIndex $priceIndex)
{
    $validated = $request->validate([
        'min_price' => 'required|numeric',
        'max_price' => 'required|numeric|gt:min_price',
    ]);
    
    // ❌ WRONG: Only update min & max, avg tidak touched
    $priceIndex->update([
        'min_price' => $validated['min_price'],
        'max_price' => $validated['max_price'],
        // avg_price TIDAK DI-UPDATE!
    ]);
    
    return back()->with('success', 'Price index updated');
}
```

**Correct Implementation:**

```php
// Controller - Manual price index adjustment
public function update(Request $request, PriceIndex $priceIndex)
{
    $validated = $request->validate([
        'min_price' => 'required|numeric',
        'max_price' => 'required|numeric|gt:min_price',
        'avg_price' => 'required|numeric|between:min_price,max_price', // ✅ VALIDATE avg
    ]);
    
    // ✅ CORRECT: Update semua values
    $priceIndex->update([
        'min_price' => $validated['min_price'],
        'max_price' => $validated['max_price'],
        'avg_price' => $validated['avg_price'], // ✅ Update avg
        'is_manual' => true,
        'manual_set_by' => auth()->id(),
        'manual_set_at' => now(),
    ]);
    
    // Log manual adjustment untuk audit
    PriceIndexAdjustment::create([
        'price_index_id' => $priceIndex->id,
        'adjusted_by_user_id' => auth()->id(),
        'old_min_price' => $priceIndex->getOriginal('min_price'),
        'old_max_price' => $priceIndex->getOriginal('max_price'),
        'old_avg_price' => $priceIndex->getOriginal('avg_price'),
        'new_min_price' => $validated['min_price'],
        'new_max_price' => $validated['max_price'],
        'new_avg_price' => $validated['avg_price'],
        'adjustment_type' => 'manual_correction',
        'reason' => $request->input('reason'),
    ]);
    
    return back()->with('success', 'Price index updated');
}
```

#### Cause #2: Form UI Tidak Ada Input untuk Avg

**Wrong Form UI:**

```html
<!-- ❌ WRONG: No avg_price input -->
<form method="POST" action="{{ route('price-index.update', $priceIndex) }}">
    <div class="form-group">
        <label>Harga Minimum</label>
        <input type="number" name="min_price" value="{{ $priceIndex->min_price }}">
    </div>
    
    <div class="form-group">
        <label>Harga Maksimum</label>
        <input type="number" name="max_price" value="{{ $priceIndex->max_price }}">
    </div>
    
    <!-- MISSING: avg_price input! -->
    
    <button type="submit">Update</button>
</form>
```

**Correct Form UI:**

```html
<!-- ✅ CORRECT: Include avg_price with auto-calculation -->
<form method="POST" action="{{ route('price-index.update', $priceIndex) }}" id="priceIndexForm">
    @csrf
    @method('PUT')
    
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Harga Minimum <span class="text-danger">*</span></label>
                <input 
                    type="number" 
                    name="min_price" 
                    id="min_price"
                    value="{{ old('min_price', $priceIndex->min_price) }}"
                    class="form-control"
                    required
                >
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-group">
                <label>Harga Maksimum <span class="text-danger">*</span></label>
                <input 
                    type="number" 
                    name="max_price" 
                    id="max_price"
                    value="{{ old('max_price', $priceIndex->max_price) }}"
                    class="form-control"
                    required
                >
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-group">
                <label>Harga Rata-rata <span class="text-danger">*</span></label>
                <input 
                    type="number" 
                    name="avg_price" 
                    id="avg_price"
                    value="{{ old('avg_price', $priceIndex->avg_price) }}"
                    class="form-control"
                    required
                    readonly
                >
                <small class="form-text text-muted">
                    Auto-calculated. Click "Calculate Average" to update.
                </small>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <button type="button" class="btn btn-secondary" onclick="calculateAverage()">
            📊 Calculate Average
        </button>
        <button type="button" class="btn btn-info" onclick="suggestPrices()">
            💡 Suggest from Recent Data
        </button>
    </div>
    
    <div class="form-group">
        <label>Reason for Adjustment <span class="text-danger">*</span></label>
        <textarea 
            name="reason" 
            class="form-control" 
            rows="3"
            required
            placeholder="e.g., Market inflation, supplier price change, etc."
        >{{ old('reason') }}</textarea>
    </div>
    
    <button type="submit" class="btn btn-primary">
        💾 Update Price Index
    </button>
</form>

<script>
// Auto-calculate average ketika min/max berubah
function calculateAverage() {
    const minPrice = parseFloat(document.getElementById('min_price').value) || 0;
    const maxPrice = parseFloat(document.getElementById('max_price').value) || 0;
    
    if (minPrice > 0 && maxPrice > 0 && maxPrice >= minPrice) {
        const avgPrice = (minPrice + maxPrice) / 2;
        document.getElementById('avg_price').value = Math.round(avgPrice);
    } else {
        alert('Please enter valid min and max prices');
    }
}

// Suggest prices based on recent transactions
async function suggestPrices() {
    const priceIndexId = {{ $priceIndex->id }};
    
    try {
        const response = await fetch(`/api/price-index/${priceIndexId}/suggest-prices`);
        const data = await response.json();
        
        if (data.success) {
            if (confirm(
                `Suggested prices based on recent data:\n\n` +
                `Min: Rp ${data.min_price.toLocaleString()}\n` +
                `Max: Rp ${data.max_price.toLocaleString()}\n` +
                `Avg: Rp ${data.avg_price.toLocaleString()}\n\n` +
                `Apply these values?`
            )) {
                document.getElementById('min_price').value = data.min_price;
                document.getElementById('max_price').value = data.max_price;
                document.getElementById('avg_price').value = data.avg_price;
            }
        }
    } catch (error) {
        console.error('Error fetching suggested prices:', error);
        alert('Failed to fetch suggested prices');
    }
}

// Auto-calculate on min/max change
document.getElementById('min_price').addEventListener('change', calculateAverage);
document.getElementById('max_price').addEventListener('change', calculateAverage);
</script>
```

### Understanding: Manual Edit vs Auto-Calculate

**CRITICAL CONCEPT:**

Price index ada **2 modes**:

#### Mode 1: Auto-Calculated (Default)

```php
price_indexes:
├─ min_price: 125000  (calculated from transactions)
├─ max_price: 175000  (calculated from transactions)
├─ avg_price: 148500  (calculated from transactions)
├─ is_manual: false   ✅
├─ last_calculated_at: 2026-04-09 10:00:00
└─ total_transactions: 45

// System recalculates daily based on approved transactions
```

**Calculation Source:** Actual transaction data

```php
// app/Jobs/CalculatePriceIndexJob.php
public function handle()
{
    $transactions = $this->getApprovedTransactions();
    
    $stats = $this->calculateIQRStatistics($transactions);
    
    $priceIndex->update([
        'min_price' => $stats['min'],
        'max_price' => $stats['max'],
        'avg_price' => $stats['avg'], // ✅ All 3 calculated
        'is_manual' => false,
        'last_calculated_at' => now(),
    ]);
}
```

#### Mode 2: Manual Override (Owner Adjustment)

```php
price_indexes:
├─ min_price: 139000  (manually set by owner)
├─ max_price: 170000  (manually set by owner)
├─ avg_price: 154500  (manually set by owner) ✅
├─ is_manual: true    ✅
├─ manual_set_by: 1 (owner user_id)
├─ manual_set_at: 2026-04-09 14:30:00
└─ manual_reason: "Market inflation - tembaga naik 20%"

// System TIDAK auto-recalculate jika is_manual = true
```

**Source:** Owner decision (dengan audit trail)

```php
// When owner manually adjusts
PriceIndexAdjustment::create([
    'price_index_id' => $priceIndex->id,
    'old_min_price' => 125000,
    'old_max_price' => 175000,
    'old_avg_price' => 148500,
    'new_min_price' => 139000,
    'new_max_price' => 170000,
    'new_avg_price' => 154500, // ✅ Avg MUST be set
    'adjustment_type' => 'market_inflation',
    'reason' => 'Market inflation - tembaga naik 20%',
]);
```

### Complete Fix Implementation

```php
// Migration: Add is_manual tracking
Schema::table('price_indexes', function (Blueprint $table) {
    $table->boolean('is_manual')->default(false)->after('avg_price');
    $table->foreignId('manual_set_by')->nullable()->after('is_manual')->constrained('users');
    $table->timestamp('manual_set_at')->nullable()->after('manual_set_by');
    $table->text('manual_reason')->nullable()->after('manual_set_at');
});

// Modified CalculatePriceIndexJob
public function handle()
{
    $priceIndex = PriceIndex::find($this->priceIndexId);
    
    // ✅ Skip if manually overridden
    if ($priceIndex->is_manual) {
        Log::info("Skipping auto-calculation for manually set price index", [
            'price_index_id' => $priceIndex->id,
            'manual_set_by' => $priceIndex->manual_set_by,
            'manual_set_at' => $priceIndex->manual_set_at,
        ]);
        return;
    }
    
    // Normal auto-calculation
    $transactions = $this->getApprovedTransactions();
    $stats = $this->calculateIQRStatistics($transactions);
    
    $priceIndex->update([
        'min_price' => $stats['min'],
        'max_price' => $stats['max'],
        'avg_price' => $stats['avg'], // ✅ All calculated
        'last_calculated_at' => now(),
    ]);
}
```

---

## 🚨 Issue #2: Form Validation Blocking Submissions = No Anomaly Detection

### The CRITICAL Misunderstanding

**User's Current Implementation (WRONG):**

```php
// ❌ WRONG: Hard validation di form
public function store(Request $request)
{
    $validated = $request->validate([
        'item_name' => 'required',
        'quantity' => 'required|numeric',
        'unit_price' => 'required|numeric',
    ]);
    
    // Get price index
    $priceIndex = PriceIndex::where('item_name', $validated['item_name'])->first();
    
    // ❌ FATAL ERROR: Block submission jika harga > max
    if ($validated['unit_price'] > $priceIndex->max_price) {
        return back()->withErrors([
            'unit_price' => 'Harga melebihi harga maksimum (Rp ' . number_format($priceIndex->max_price) . ')'
        ]);
    }
    
    // Create pengajuan...
}
```

**Result:**
```
Teknisi input: Kabel NYM @ Rp 50.000
Price index max: Rp 30.000

❌ Form error: "Harga melebihi harga maksimum"
❌ Pengajuan TIDAK dibuat
❌ Anomaly TIDAK terdeteksi
❌ Owner TIDAK diberi notif
❌ Price index system TIDAK BERGUNA!
```

### Why This is COMPLETELY WRONG

**Price index system bukan untuk BLOCK, tapi untuk DETECT & ALERT!**

```
┌────────────────────────────────────────────────┐
│  WRONG UNDERSTANDING (Current)                 │
└────────────────────────────────────────────────┘

Teknisi → Input harga tinggi
       ↓
       Form validation: "Error!" ❌
       ↓
       STOP. No submission.
       
Owner never knows!


┌────────────────────────────────────────────────┐
│  CORRECT UNDERSTANDING (How it should work)    │
└────────────────────────────────────────────────┘

Teknisi → Input harga tinggi
       ↓
       Form submit SUCCESS ✅
       ↓
       Pengajuan created (status: pending)
       ↓
       Anomaly detected 🚨
       ↓
       Owner notified via Telegram 📱
       ↓
       Owner reviews & decides
       ↓
       Approve ✅ or Reject ❌
```

### The Correct Implementation

**Step 1: Remove Hard Validation**

```php
// ✅ CORRECT: Allow submission, detect anomaly after
public function store(Request $request)
{
    $validated = $request->validate([
        'item_name' => 'required',
        'quantity' => 'required|numeric|min:1',
        'unit_price' => 'required|numeric|min:0', // ✅ No max validation!
    ]);
    
    // Create pengajuan (ALWAYS allow)
    $pengajuan = Pengajuan::create([
        'reported_by_user_id' => auth()->id(),
        'status' => 'pending', // ✅ Start as pending
        'submitted_at' => now(),
    ]);
    
    $item = PengajuanItem::create([
        'pengajuan_id' => $pengajuan->id,
        'name' => $validated['item_name'],
        'quantity' => $validated['quantity'],
        'unit_price' => $validated['unit_price'],
        'total_price' => $validated['quantity'] * $validated['unit_price'],
    ]);
    
    // ✅ AFTER creation: Detect anomaly
    dispatch(new DetectPriceAnomalyJob($item->id));
    
    return redirect()
        ->route('pengajuan.show', $pengajuan)
        ->with('success', 'Pengajuan submitted successfully. Waiting for approval.');
}
```

**Step 2: Anomaly Detection (After Submission)**

```php
// app/Jobs/DetectPriceAnomalyJob.php

public function handle(AnomalyDetectionService $detector)
{
    $item = PengajuanItem::find($this->itemId);
    
    if (!$item) {
        return;
    }
    
    // ✅ Detect anomaly (does not block submission)
    $anomaly = $detector->detectAnomaly($item);
    
    if ($anomaly) {
        // Anomaly detected!
        
        // 1. Update pengajuan status
        $item->pengajuan->update([
            'has_price_anomaly' => true,
            'requires_owner_approval' => true,
        ]);
        
        // 2. Notify owner via Telegram
        dispatch(new SendPriceAnomalyNotificationJob($anomaly->id));
        
        // 3. Notify teknisi
        $this->notifyTechnician($item, $anomaly);
    } else {
        // No anomaly - auto-approve jika dalam range
        if (auth()->user()->hasRole('teknisi')) {
            // Teknisi submission needs manager approval
            $item->pengajuan->update([
                'status' => 'pending_manager_approval',
            ]);
        }
    }
}

private function notifyTechnician(PengajuanItem $item, PriceAnomaly $anomaly)
{
    // Notify teknisi that their submission flagged
    Notification::create([
        'user_id' => $item->pengajuan->reported_by_user_id,
        'type' => 'price_anomaly_flagged',
        'title' => '⚠️ Harga Anda Di-Review',
        'message' => "Item {$item->name} dengan harga Rp " . number_format($item->unit_price) 
            . " melebihi referensi {$anomaly->excess_percentage}%. "
            . "Menunggu approval dari Owner.",
        'data' => json_encode([
            'pengajuan_id' => $item->pengajuan_id,
            'anomaly_id' => $anomaly->id,
        ]),
    ]);
}
```

**Step 3: Form UI with Soft Warning (Not Blocking)**

```html
<!-- ✅ CORRECT: Show warning but allow submit -->
<form method="POST" action="{{ route('pengajuan.store') }}" id="pengajuanForm">
    @csrf
    
    <div class="form-group">
        <label>Item Name</label>
        <input 
            type="text" 
            name="item_name" 
            id="item_name"
            class="form-control"
            required
        >
    </div>
    
    <div class="form-group">
        <label>Quantity</label>
        <input 
            type="number" 
            name="quantity" 
            id="quantity"
            class="form-control"
            required
        >
    </div>
    
    <div class="form-group">
        <label>Unit Price</label>
        <input 
            type="number" 
            name="unit_price" 
            id="unit_price"
            class="form-control"
            required
        >
        
        <!-- ✅ SOFT warning (tidak blocking) -->
        <div id="priceWarning" class="alert alert-warning mt-2" style="display: none;">
            <strong>⚠️ Price Alert</strong>
            <p id="warningMessage"></p>
            <small>
                Submission akan di-review oleh Owner sebelum disetujui.
                Anda masih bisa submit jika yakin harga ini benar.
            </small>
        </div>
        
        <!-- ✅ SOFT info (reference price) -->
        <div id="priceReference" class="alert alert-info mt-2" style="display: none;">
            <strong>📊 Price Reference</strong>
            <p id="referenceMessage"></p>
        </div>
    </div>
    
    <!-- ✅ Submit button ALWAYS enabled -->
    <button type="submit" class="btn btn-primary">
        Submit Pengajuan
    </button>
</form>

<script>
// Real-time price check (soft warning only)
document.getElementById('unit_price').addEventListener('change', async function() {
    const itemName = document.getElementById('item_name').value;
    const unitPrice = parseFloat(this.value);
    
    if (!itemName || !unitPrice) {
        return;
    }
    
    // Fetch price index
    try {
        const response = await fetch(`/api/price-index/check`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                item_name: itemName,
                unit_price: unitPrice
            })
        });
        
        const data = await response.json();
        
        if (data.has_reference) {
            // Show reference
            document.getElementById('referenceMessage').innerHTML = 
                `Reference range: Rp ${data.min_price.toLocaleString()} - Rp ${data.max_price.toLocaleString()}<br>` +
                `Average: Rp ${data.avg_price.toLocaleString()}`;
            document.getElementById('priceReference').style.display = 'block';
            
            // ✅ Show WARNING if anomaly (but don't block!)
            if (data.is_anomaly) {
                document.getElementById('warningMessage').innerHTML = 
                    `Harga Anda (Rp ${unitPrice.toLocaleString()}) melebihi referensi maksimum ` +
                    `(Rp ${data.max_price.toLocaleString()}) sebesar ${data.excess_percentage}%.<br>` +
                    `<strong>Submission Anda akan di-review oleh Owner.</strong>`;
                document.getElementById('priceWarning').style.display = 'block';
            } else {
                document.getElementById('priceWarning').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error checking price:', error);
    }
});
</script>
```

**Step 4: API Endpoint for Price Check**

```php
// app/Http/Controllers/Api/PriceIndexCheckController.php

public function check(Request $request, AnomalyDetectionService $detector)
{
    $validated = $request->validate([
        'item_name' => 'required|string',
        'unit_price' => 'required|numeric',
    ]);
    
    $priceIndex = PriceIndex::where('item_name', $validated['item_name'])->first();
    
    if (!$priceIndex) {
        return response()->json([
            'has_reference' => false,
            'message' => 'No price reference found for this item',
        ]);
    }
    
    // Check if anomaly
    $excessAmount = $validated['unit_price'] - $priceIndex->max_price;
    $excessPercentage = ($excessAmount / $priceIndex->max_price) * 100;
    
    $isAnomaly = $validated['unit_price'] > $priceIndex->max_price;
    
    return response()->json([
        'has_reference' => true,
        'min_price' => $priceIndex->min_price,
        'max_price' => $priceIndex->max_price,
        'avg_price' => $priceIndex->avg_price,
        'is_anomaly' => $isAnomaly,
        'excess_amount' => $isAnomaly ? $excessAmount : 0,
        'excess_percentage' => $isAnomaly ? round($excessPercentage, 1) : 0,
    ]);
}
```

### Approval Workflow

```
┌─────────────────────────────────────────────────┐
│  Complete Flow: Teknisi → Owner → Approved      │
└─────────────────────────────────────────────────┘

1. Teknisi Submit
   ├─ Item: Kabel NYM @ Rp 50.000
   ├─ Form shows: ⚠️ "Price exceeds reference by 67%"
   ├─ Teknisi clicks: "Submit Anyway"
   └─ Status: pending ✅

2. System Detects
   ├─ Anomaly detected: +67% excess
   ├─ Severity: critical (>50%)
   ├─ Create anomaly record
   └─ Trigger notification job

3. Owner Notified (Telegram)
   ├─ 🚨 ANOMALI HARGA TERDETEKSI
   ├─ Item: Kabel NYM
   ├─ Input: Rp 50.000
   ├─ Ref Max: Rp 30.000
   ├─ Excess: +67%
   └─ [Approve] [Reject] [Review Detail]

4. Owner Decision
   ├─ Option A: Click "Approve" → Status: approved ✅
   ├─ Option B: Click "Reject" → Status: rejected ❌
   └─ Option C: "Review Detail" → See full context

5. Teknisi Notified
   ├─ If approved: "Your submission has been approved"
   ├─ If rejected: "Your submission was rejected. Reason: [owner notes]"
   └─ Update pengajuan status
```

---

## 📊 Comparison: Wrong vs Correct

### WRONG Implementation (Current)

```php
❌ Hard Validation at Form Level

Flow:
1. User inputs high price
2. Form validation fails
3. Error message shown
4. Submission blocked
5. Owner never knows
6. No data collected
7. No learning/improvement

Impact:
- No anomaly detection
- No owner alerts
- No audit trail
- System useless
- False sense of control
```

### CORRECT Implementation (Should Be)

```php
✅ Soft Warning + Post-Submission Detection

Flow:
1. User inputs high price
2. Warning shown (but not blocking)
3. User confirms & submits
4. Pengajuan created (pending)
5. Anomaly detected
6. Owner notified & reviews
7. Owner approves/rejects
8. System learns from decision

Impact:
+ Full anomaly detection
+ Owner oversight & control
+ Complete audit trail
+ Data-driven improvements
+ Fraud detection works
+ Trend analysis possible
```

---

## 🎯 Action Items

### Immediate Fixes (This Week)

1. **Fix Avg Price Calculation**
   - [ ] Add avg_price input to form
   - [ ] Add auto-calculate button
   - [ ] Update controller to save avg
   - [ ] Add is_manual flag
   - [ ] Test manual adjustment flow

2. **Remove Hard Validation**
   - [ ] Remove max_price validation from form
   - [ ] Change errors to soft warnings
   - [ ] Always allow submission
   - [ ] Test submission flow

3. **Implement Soft Warning UI**
   - [ ] Add warning alert (non-blocking)
   - [ ] Real-time price check API
   - [ ] Show reference prices
   - [ ] Test user experience

4. **Fix Anomaly Detection Flow**
   - [ ] DetectPriceAnomalyJob runs AFTER submit
   - [ ] Create anomaly record
   - [ ] Trigger notifications
   - [ ] Update pengajuan status
   - [ ] Test end-to-end

### Testing Checklist

```
Test Case #1: Normal Price (Within Range)
├─ Input: Rp 27.000 (ref: 25K-30K)
├─ Expected: ℹ️ Info shown, submit allowed
├─ Expected: No anomaly created
└─ Expected: Auto-approved (if teknisi)

Test Case #2: High Price (Above Max)
├─ Input: Rp 50.000 (ref: 25K-30K)
├─ Expected: ⚠️ Warning shown, submit allowed ✅
├─ Expected: Anomaly created ✅
├─ Expected: Owner notified ✅
└─ Expected: Status = pending_owner_approval

Test Case #3: Very High Price (Critical)
├─ Input: Rp 100.000 (ref: 25K-30K)
├─ Expected: 🚨 Critical warning, submit allowed
├─ Expected: Severity = critical
├─ Expected: Multi-level approval required
└─ Expected: Manager + Owner notification

Test Case #4: Manual Price Index Edit
├─ Action: Owner edits min=139K, max=170K, avg=154.5K
├─ Expected: All 3 values saved ✅
├─ Expected: is_manual = true
├─ Expected: Audit log created
└─ Expected: Auto-calculation skipped
```

---

## 💡 Key Takeaways

### For Price Index System Design:

1. **Price Index = Reference, Not Hard Limit**
   - Purpose: Detect unusual prices
   - Not: Block submissions

2. **Soft Validation > Hard Validation**
   - Show warnings: Yes ✅
   - Block submissions: No ❌
   - Owner decides: Always ✅

3. **Trust but Verify**
   - Allow teknisi to submit
   - Detect anomalies automatically
   - Owner reviews & approves
   - Learn from patterns

4. **Complete Audit Trail**
   - Every submission recorded
   - Every decision logged
   - Pattern analysis possible
   - Continuous improvement

### Common Misconceptions:

```
❌ WRONG: "Price index mencegah input harga salah"
✅ CORRECT: "Price index mendeteksi & alert harga unusual"

❌ WRONG: "Form validation = security"
✅ CORRECT: "Approval workflow = security"

❌ WRONG: "Block bad data at entry"
✅ CORRECT: "Capture all data, review afterward"

❌ WRONG: "Automated rules prevent fraud"
✅ CORRECT: "Human oversight + data = prevent fraud"
```

---

**Document Status:** Critical Fix Required  
**Implementation Priority:** Immediate (Week 1)  
**Impact if Not Fixed:** System completely non-functional

