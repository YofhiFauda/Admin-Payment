# 🏢 WHUSNET Admin Payment - GEMINI Context

This file provides essential technical context for Gemini CLI when working on the **WHUSNET Admin Payment** project.

## 🎯 Project Overview
An internal financial management system for **WHUSNET** to manage reimbursements (Rembush) and purchase requests (Pengajuan). It features automated **OCR data extraction** (Gemini AI), multi-level approval workflows, and real-time monitoring.

### Key Capabilities:
- **OCR Workflow:** Uses n8n + Google Gemini Pro 1.5/2.0 for 3-layer security verification (Duplicate detection, Date logic, AI extraction).
- **Approval Levels:** Teknisi (Submitter) → Admin (Initial check) → Atasan (Department head) → Owner (Final approval for ≥ Rp 1,000,000).
- **Real-time Engine:** Laravel Reverb (WebSockets) for instant UI updates and notifications.
- **Telegram Sync:** Integration for payment confirmations and critical error alerts.
- **Infrastructure:** Fully containerized with Docker (9 services: App, Nginx, DB, Redis, Horizon, Reverb, Scheduler, Node, phpMyAdmin).

## 🛠 Tech Stack
- **Backend:** PHP 8.4, Laravel 12.
- **Frontend:** Blade Templates, Tailwind CSS v4, Vite, Vanilla JS, Axios.
- **Database/Cache:** MySQL 8.0, Redis 7.2.
- **Queues/Monitoring:** Laravel Horizon, Laravel Telescope (Dev).
- **Real-time:** Laravel Reverb.
- **Automation:** n8n (Self-hosted workflow orchestrator).
- **AI:** Google Gemini Pro (Text/Image Extraction).

## 🚀 Building and Running

### Commands
| Task | Command |
|---|---|
| **Quick Setup** | `composer run setup` (installs deps, migrates, builds assets) |
| **Start Docker** | `docker-compose up -d` |
| **Development** | `composer run dev` (runs server, queue, logs, and vite concurrently) |
| **Run Tests** | `php artisan test` or `composer run test` |
| **Horizon** | `php artisan horizon` |
| **Reverb** | `php artisan reverb:start` |
| **Database** | `php artisan migrate` / `php artisan db:seed` |

### Environment Setup
Requires a `.env` file based on `.env.example`. Key variables include Redis configuration, Reverb keys, and n8n webhook secrets (`N8N_WEBHOOK_URL`, `N8N_SECRET`).

## 📂 Architecture & Conventions

### Directory Highlights
- `app/Services/`: Contains core logic (OCR processing, Telegram Bot, ID Generation).
- `app/Http/Controllers/Api/`: Handles webhooks (n8n, Telegram) and polling status.
- `app/Jobs/`: Manages asynchronous OCR and notification processing.
- `app/Models/`: Core entities (`Transaction`, `Branch`, `ActivityLog`).
- `routes/api.php`: Public/Webhook endpoints (protected by `n8n.secret` middleware).
- `routes/web.php`: Role-protected application routes.

### Coding Standards
- **Naming:** Follows standard Laravel/PSR-12 conventions.
- **ID Strategy:** Uses a Redis-based Sequential ID Generator (`IdGeneratorService`) for atomicity across containers (e.g., `UP-YYYYMMDD-XXXXX`).
- **Authorization:** Enforced via `role` middleware (`CheckRole.php`). Roles: `owner`, `atasan`, `admin`, `teknisi`.
- **Validation:** Heavy use of FormRequests and manual validation for complex OCR logic.

### OCR Security Layers
1. **Layer 1:** Duplicate detection (Redis hash check).
2. **Layer 2:** Date validation (Nota older than 2 days are auto-rejected).
3. **Layer 3:** AI Extraction (Gemini via n8n).
4. **Layer 4:** Payment verification (Nominal comparison for transfers).

## 🤖 Model Context Protocol (MCP)
This project is configured with GitHub MCP integration for automated code reviews, issue management, and PR triage.

### Local Configuration
1. **GitHub PAT:** Generate a Personal Access Token with `read` (Metadata, Contents) and `read/write` (Issues, Pull Requests) permissions.
2. **Environment:** Add `GITHUB_PERSONAL_ACCESS_TOKEN` to your local `.env`.
3. **CLI Setup:** Run `/mcp list` in the Gemini CLI to verify the connection.

### GitHub Actions Integration
Workflows in `.github/workflows/` are pre-configured to use MCP. Ensure your repository has the following secrets:
- `GEMINI_API_KEY`: Your Google AI Studio API key.
- `GITHUB_TOKEN`: Automatically provided by Actions, or a custom PAT for higher limits.

---

## 🧪 Testing Strategy
- **Feature Tests:** Located in `tests/Feature/`. Covers authentication, transaction flows, and OCR simulation (`OcrNotaFlowTest`).
- **Unit Tests:** Located in `tests/Unit/`. Covers standalone services like `IdGeneratorService`.

## ⚠️ Important Notes
- Always check `Horizon` for failed jobs if OCR status isn't updating.
- Frontend polling for OCR status happens at `/api/ai/auto-fill/status/{uploadId}`.
- Webhooks from n8n MUST include the `X-SECRET` header matching the `.env` configuration.
