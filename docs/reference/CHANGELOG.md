# 📝 Changelog - WHUSNET Admin Payment

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Fixed
- **Payment Verification:** Fixed stuck status "Sedang Diverifikasi AI" issue
  - Changed hard-coded invalid status to valid `waiting_payment` status
  - Added `ai_status` field for tracking AI processing separately
  - Created recovery script for stuck transactions
  - Updated troubleshooting documentation
  - See: `docs/fixes/PAYMENT_VERIFICATION_FIX.md`

### Added
- Documentation reorganization with hierarchical structure
- CONTRIBUTING.md for development guidelines
- QUICK_START.md for 5-minute setup
- CHANGELOG.md for version tracking
- Recovery script for stuck payment verification (`scripts/fix-stuck-transactions.php`)
- SQL queries for monitoring stuck transactions (`scripts/check-stuck-transactions.sql`)

---

## [4.5.0] - 2026-05-04

### Added
- **Real-time Notifications:** Migrated from polling to Laravel Reverb WebSocket
- **Price Index System:** Automatic price reference with IQR outlier detection
- **Dual-Version System:** Track changes between Teknisi and Management versions
- **Branch Debt Management:** Inter-branch debt tracking and settlement
- **Payment Verification:** AI-powered payment proof verification (Layer 4)
- **Telegram Bot Integration:** Real-time notifications and cash payment confirmation
- **API Documentation:** Interactive API docs with Scramble (OpenAPI 3.1)
- **Monitoring Tools:** Laravel Pulse and Log Viewer integration
- **Docker Production:** Production-ready Docker setup with multi-stage builds
- **CI/CD Pipeline:** GitHub Actions for automated testing and deployment

### Changed
- **Dashboard:** Replaced polling with WebSocket for real-time updates
- **Transaction Flow:** Enhanced approval workflow with multi-level gates
- **OCR Processing:** Improved 4-layer security verification
- **Database Schema:** Added support for branch debts and price anomalies

### Fixed
- **Cost Allocation:** Fixed calculation bugs in branch allocation
- **Payment Discrepancy:** Resolved race conditions in payment verification
- **Real-time Updates:** Fixed double initialization issues
- **Invoice Generation:** Corrected sequential ID generation

### Security
- **4-Layer Verification:** Enhanced security for OCR processing
- **RBAC:** Improved role-based access control
- **Payment Audit:** Added payment discrepancy audit trail
- **Force Approve:** Added reason tracking for flagged transactions

### Performance
- **Query Optimization:** Reduced N+1 queries in dashboard
- **Redis Caching:** Implemented caching for frequently accessed data
- **Image Compression:** Automatic image optimization on upload
- **Lazy Loading:** Implemented lazy loading for large datasets

### Documentation
- **README.md:** Comprehensive project documentation
- **API Documentation:** Detailed API reference (v4.5)
- **Backend Documentation:** Architecture and business logic (v1.0)
- **Database Schema:** Complete ER diagram and table descriptions
- **Price Index Docs:** Detailed price index system documentation
- **Deployment Guides:** Docker, CI/CD, and production checklists

---

## [4.0.0] - 2026-03-15

### Added
- **Pengajuan System:** Purchase request workflow with dual-version tracking
- **Gudang Module:** Internal warehouse purchase tracking
- **Category Management:** Dynamic transaction categories with Glass UI
- **Branch Bank Accounts:** Bank account management per branch
- **Other Expenditures:** PL- module for non-operational costs
- **Salary Records:** GP- module for payroll management

### Changed
- **Transaction Types:** Expanded from Rembush-only to multi-type system
- **Approval Logic:** Implemented threshold-based approval (< 1M vs ≥ 1M)
- **Status Lifecycle:** Enhanced status transitions with debt settlement

### Fixed
- **Branch Allocation:** Fixed percentage calculation bugs
- **File Upload:** Resolved storage path issues

---

## [3.5.0] - 2026-02-01

### Added
- **OCR Integration:** Gemini AI for automatic receipt data extraction
- **n8n Workflow:** Automated OCR processing pipeline
- **4-Layer Security:**
  - Layer 1: Duplicate detection (MD5 hash)
  - Layer 2: Date logic validation (max 2 days)
  - Layer 3: AI extraction with confidence scoring
  - Layer 4: Payment verification (coming in v4.5)

### Changed
- **Upload Flow:** Integrated OCR processing with loading page
- **Form Auto-fill:** Automatic form population from OCR results

---

## [3.0.0] - 2026-01-10

### Added
- **Multi-Branch Support:** Branch allocation for transactions
- **Activity Logs:** Comprehensive audit trail
- **Notifications:** In-app notification system
- **Dashboard Analytics:** Real-time statistics and charts

### Changed
- **Database Schema:** Added branches and transaction_branches tables
- **User Roles:** Expanded from 2 to 4 roles (Owner, Atasan, Admin, Teknisi)

---

## [2.0.0] - 2025-12-01

### Added
- **Rembush Module:** Reimbursement workflow
- **Approval System:** Multi-level approval process
- **File Upload:** Receipt image upload and storage
- **User Management:** CRUD for users with role-based access

### Changed
- **Authentication:** Implemented role-based login
- **UI/UX:** Redesigned with Tailwind CSS

---

## [1.0.0] - 2025-11-01

### Added
- **Initial Release:** Basic transaction management system
- **Authentication:** Login/logout functionality
- **Transaction CRUD:** Create, read, update, delete transactions
- **Basic Dashboard:** Transaction list and statistics

---

## Version History Summary

| Version | Release Date | Major Features |
|---------|--------------|----------------|
| 4.5.0 | 2026-05-04 | Real-time, Price Index, Dual-Version, Branch Debt |
| 4.0.0 | 2026-03-15 | Pengajuan, Gudang, Categories, Bank Accounts |
| 3.5.0 | 2026-02-01 | OCR Integration, n8n Workflow, 4-Layer Security |
| 3.0.0 | 2026-01-10 | Multi-Branch, Activity Logs, Dashboard Analytics |
| 2.0.0 | 2025-12-01 | Rembush Module, Approval System, File Upload |
| 1.0.0 | 2025-11-01 | Initial Release, Basic CRUD |

---

## Upgrade Guides

### Upgrading to 4.5.0

**Breaking Changes:**
- Real-time notifications require Reverb configuration
- Price Index system requires database migration
- Branch debt settlement affects transaction completion logic

**Migration Steps:**
```bash
# 1. Backup database
php artisan backup:run

# 2. Pull latest code
git pull origin main

# 3. Update dependencies
composer install
npm install

# 4. Run migrations
php artisan migrate

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 6. Rebuild assets
npm run build

# 7. Restart services
docker-compose restart
```

**Configuration Changes:**
```env
# Add to .env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

---

## Deprecations

### Version 4.5.0
- **Polling Endpoints:** `/dashboard/pendingListData` and `/dashboard/branchCostData` are deprecated in favor of WebSocket events. Will be removed in v5.0.0.

### Version 4.0.0
- **Old Transaction API:** `/api/transactions/old-format` deprecated. Use `/api/v1/transactions` instead.

---

## Security Advisories

### CVE-2026-XXXX (Fixed in 4.5.0)
- **Severity:** Medium
- **Description:** Payment verification race condition
- **Impact:** Potential for payment amount mismatch
- **Fix:** Implemented atomic transaction locking

### CVE-2026-YYYY (Fixed in 4.0.0)
- **Severity:** Low
- **Description:** File upload path traversal
- **Impact:** Potential unauthorized file access
- **Fix:** Implemented strict path validation

---

## Contributors

Thank you to all contributors who made these releases possible!

### Version 4.5.0
- [@developer1] - Real-time migration
- [@developer2] - Price Index system
- [@developer3] - Branch debt management
- [@developer4] - Documentation overhaul

---

## Links

- [Documentation](../../README.md)
- [API Documentation](../api/API_REFERENCE.md)
- [Migration Guides](MIGRATION_GUIDES.md)
- [Security Policy](../security/SECURITY.md)

---

**Maintained by:** WHUSNET Development Team  
**Last Updated:** 4 Mei 2026

---

## Legend

- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for now removed features
- `Fixed` for any bug fixes
- `Security` for vulnerability fixes
- `Performance` for performance improvements
- `Documentation` for documentation changes
