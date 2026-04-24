# 🏢 WHUSNET Admin Payment - GEMINI Context

This file provides essential technical context for Gemini CLI when working on the **WHUSNET Admin Payment** project.

## 🎯 Project Overview
An internal financial management system for **WHUSNET** to manage reimbursements (Rembush), purchase requests (Pengajuan), Warehouse purchases (Gudang), miscellaneous expenditures, and salaries. It features automated **OCR data extraction** (Gemini AI), multi-level approval workflows, and real-time monitoring.

### Key Capabilities:
- **Dual-Gate Approval (New):** For **Pengajuan (Purchase Request)**, transactions ≥ Rp 1.000.000 require two-level approval: first by **Atasan** (status: `approved` / "Menunggu Approve Owner"), then by **Owner** (status: `waiting_payment`). Transaksi < 1jt remain single-gate.
- **Pengajuan Status Flow (Debt-Aware):** 
    - Transactions move to `waiting_payment` after Owner/Atasan approval.
    - Upon uploading an invoice, if there are inter-branch debts (`BranchDebt`), the status **remains** `waiting_payment`.
    - The transaction automatically transitions to `completed` **only after** all associated branch debts are marked as `paid`.
- **OCR Workflow:** Uses n8n + Google Gemini Pro for 3-layer security (Duplicate detection, Date logic, AI extraction). Supports complex invoices with shipping, service fees, and discounts.
- **Dual-Version System:** Management (Owner/Atasan) can override technician submissions (Pengajuan). Both original and edited versions are stored for audit trails. A version switcher allows side-by-side comparison with change highlighting.
- **Transaction Access Control & Phase Locking (New):**
    - **Settlement Phase ("Menunggu Pelunasan"):** Fully read-only for ALL roles. No further edits or uploads allowed. Version switching remains visible for audit purposes.
    - **Payment Wait Phase ("Menunggu Pembayaran"):**
        - **Management:** Full access to edit items, prices, and branches.
        - **Admin:** **Restricted Edit Mode.** Only allowed to modify "Branch Distribution" and "Distribution Methods". All financial fields (Items, Prices, DPP, PPN, etc.) are strictly locked.
    - **Visual Enforcement:** UI components are dynamically disabled based on role/status, supplemented by backend controller guards.
- **Multi-Branch Strategy:** 
    - **Allocation:** Single transactions can be split across multiple branches with specific percentage/amount allocation.
    - **Branch Debt:** Automatic tracking of inter-branch borrowing when one branch pays for another's needs (`BranchDebt` model).
    - **BranchDebt Settlement:** Support for settling inter-branch debts with payment proof (transfer/cash) and notes.
    - **Dashboard Tracking:** New AJAX-powered dashboard widgets for real-time monitoring of "Hutang Antar Cabang" (Inter-branch Debt) and "Piutang Antar Cabang" (Inter-branch Receivable).
- **Payment History:** Detailed tracking of payment events (transfer/cash), payment proofs, and multi-step verification (Step 1: Payer → Recipient, Step 2: Confirmation/AI Verification).
- **Hybrid Search Logic (Auto-Adaptive):**
    - **Threshold:** Switches between modes at **5,000 records** benchmark.
    - **Client-Side (< 5k):** Fetches entire dataset (lean version) for instant, browser-side filtering. Safety limit is capped at 10,000 records.
    - **Server-Side (≥ 5k):** Switches to standard paginated database queries to prevent browser memory overflow and maintain performance.
    - **Auto-Detection:** Every initial load triggers a `/count` check to determine the most efficient search mode.
- **Price Index System (Dual-Tracking):**
    - **Market Pricing:** Automatically calculates Min, Max, and Avg prices from historical approved transactions (using IQR outlier detection).
    - **Manual Override:** Allows management to set fixed reference prices with an audit trail, while the system continues to track "calculated" values in the background.
    - **Reset to Auto:** One-click feature to revert manual overrides back to system-calculated market prices.
    - **Anomaly Detection:** Real-time flagging of transactions that exceed market price thresholds (Low, Medium, Critical severity).
- **Gudang Module:** Internal warehouse purchase recording. Optimized for speed: Bypasses mandatory Telegram registration for submitters and skips AI verification (OCR) for payment proofs.
- **Financial Modules:**
    - **Other Expenditures:** Prefix `PL-` (Payable, Receivable, Prive). Now supports real-time multi-branch filtering, invoice search, and direct management of inter-branch debts.
    - **Salary Records:** Prefix `GP-` (Automated calculation of base pay, bonuses, and deductions).
- **Real-time Engine:** Laravel Reverb (WebSockets) for instant UI updates.
- **Infrastructure:** Docker (9 services: App, Nginx, DB, Redis, Horizon, Reverb, Scheduler, Node, phpMyAdmin).

## 🛠 Tech Stack
- **Backend:** PHP 8.4, Laravel 12.
- **Frontend:** Blade, Tailwind CSS v4, Vite, Vanilla JS (SearchEngine, Axios).
- **Database/Cache:** MySQL 8.0, Redis 7.2.
- **Queues/Monitoring:** Laravel Horizon, Laravel Telescope.
- **Real-time:** Laravel Reverb.
- **Automation:** n8n (Self-hosted workflow orchestrator).
- **AI:** Google Gemini Pro (Text/Image Extraction).

## 🚀 Building and Running

| Task | Command |
|---|---|
| **Quick Setup** | `composer run setup` |
| **Start Docker** | `docker-compose up -d` |
| **Development** | `composer run dev` (runs server, queue, logs, and vite concurrently) |
| **Run Tests** | `php artisan test` |
| **Horizon** | `php artisan horizon` |
| **Reverb** | `php artisan reverb:start` |

## 📂 Architecture & Conventions

### Directory Highlights
- `app/Services/`: `IdGeneratorService` (Redis-based atomic IDs), OCR processing.
- `app/Models/`: 
    - `Transaction`: Core logic for Rembush/Pengajuan/Gudang & Versioning.
    - `BranchDebt`: Tracks inter-unit financial obligations.
    - `GudangController`: Handles internal warehouse expenditure flow.
    - `OtherExpenditure` & `SalaryRecord`: Extended financial modules.
- `resources/js/`: Contains `SearchEngine` logic for real-time list filtering.
- `routes/api.php`: Webhook endpoints (n8n, Telegram) protected by `n8n.secret`.

### Coding Standards
- **Invoice Prefixes:** `UP-` (Transactions), `PL-` (Other Expenditures), `GP-` (Salaries).
- **Versioning:** `is_edited_by_management` flag and `items_snapshot` blob in `transactions` table.
- **ID Strategy:** Redis-based Sequential ID Generator for atomicity across Docker containers.
- **Authorization:** Role-based (`owner`, `atasan`, `admin`, `teknisi`) via `role` middleware.

### OCR Security Layers
1. **Layer 1:** Duplicate detection (Redis hash check).
2. **Layer 2:** Date validation (Auto-reject old notas).
3. **Layer 3:** AI Extraction (Gemini via n8n).
4. **Layer 4:** Payment verification (Nominal comparison for transfers).
*Note: Gudang transactions bypass Layer 3 and mandatory Telegram checks for operational speed.*

---

## 🧪 Testing Strategy
- **Feature Tests:** Authentication, Transaction flows, and `OcrNotaFlowTest`.
- **Unit Tests:** `IdGeneratorService`, `BranchDebt` logic.

## ⚠️ Important Notes
- **Horizon Monitoring:** Critical for OCR status updates.
- **Status Flow:** `OtherExpenditure` and `SalaryRecord` have specific approval states (`draft`, `pending`, `approved`, `paid`, `rejected`).
- **Webhook Security:** n8n calls must include the `X-SECRET` header.
