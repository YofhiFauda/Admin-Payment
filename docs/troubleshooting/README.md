# 🔧 Troubleshooting Documentation

This folder contains troubleshooting guides for common issues encountered in the WHUSNET Admin Payment system.

---

## 📋 Available Guides

| Guide | Topic |
|-------|-------|
| [503 Error Analysis](503_ERROR_ANALYSIS.md) | Diagnose & fix 503 Service Unavailable errors |
| [Export Troubleshooting](EXPORT_TROUBLESHOOTING.md) | Excel export issues (524, 500, 404, permissions) |

---

## 🔍 General Troubleshooting Resources

- [Operations Troubleshooting](../operations/TROUBLESHOOTING.md) — Common operational issues
- [PR Validation Issues](../../TROUBLESHOOTING_PR_VALIDATION.md) — Pull request validation errors
- [Logging Solution](../operations/LOGGING_COMPLETE_SOLUTION.md) — How to use logs for debugging
- [Pulse & Log Viewer](../operations/PULSE_LOG_VIEWER_SETUP.md) — Web-based log viewer

---

## 🆘 When You Need Help

1. Check the relevant troubleshooting guide above
2. Look at logs: `docker compose exec app tail -f storage/logs/laravel-$(date +%Y-%m-%d).log`
3. Run diagnostic commands (e.g., `php artisan export:test`)
4. Check [Operations Troubleshooting](../operations/TROUBLESHOOTING.md) for common patterns
5. Escalate to the development team with collected diagnostic info

---

**Last Updated:** 28 Mei 2026  
**Maintainer:** WHUSNET Development Team
