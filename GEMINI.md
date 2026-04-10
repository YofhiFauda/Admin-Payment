# 🏢 WHUSNET Admin Payment - GEMINI Context

This file provides essential technical context for Gemini CLI when working on the **WHUSNET Admin Payment** project.

## 🎯 Project Overview
An internal financial management system for **WHUSNET** to manage reimbursements (Rembush), purchase requests (Pengajuan), Warehouse purchases (Gudang), miscellaneous expenditures, and salaries. It features automated **OCR data extraction** (Gemini AI), multi-level approval workflows, and real-time monitoring.

### Key Capabilities:
- **OCR Workflow:** Uses n8n + Google Gemini Pro for 3-layer security (Duplicate detection, Date logic, AI extraction). Supports complex invoices with shipping, service fees, and discounts.
- **Dual-Version System:** Management (Owner/Atasan) can override technician submissions (Pengajuan). Both original and edited versions are stored for audit trails.
- **Multi-Branch Strategy:** 
    - **Allocation:** Single transactions can be split across multiple branches with specific percentage/amount allocation.
    - **Branch Debt:** Automatic tracking of inter-branch borrowing when one branch pays for another's needs (`BranchDebt` model).
    - **Filtering:** Real-time client-side multi-branch filtering via `SearchEngine`.
- **Payment History:** Detailed tracking of payment events (transfer/cash), payment proofs, and multi-step verification (Step 1: Payer → Recipient, Step 2: Confirmation/AI Verification).
- **Gudang Module:** Internal warehouse purchase recording. Optimized for speed: Bypasses mandatory Telegram registration for submitters and skips AI verification (OCR) for payment proofs.
- **Financial Modules:**
    - **Other Expenditures:** Prefix `PL-` (Payable, Receivable, Prive).
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
