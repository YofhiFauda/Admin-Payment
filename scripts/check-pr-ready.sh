#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  PR Readiness Checker
#  Check if your code is ready for Pull Request
# ═══════════════════════════════════════════════════════════════════

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0
WARNINGS=0

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                    PR READINESS CHECKER                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# ─────────────────────────────────────────
#  1. Check Code Style (Laravel Pint)
# ─────────────────────────────────────────
echo -e "${BLUE}[1/8]${NC} Checking code style with Laravel Pint..."
if ./vendor/bin/pint --test > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Code style is clean"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Code style issues found. Run: ./vendor/bin/pint"
    ((FAILED++))
fi

# ─────────────────────────────────────────
#  2. Check for Debug Statements
# ─────────────────────────────────────────
echo -e "${BLUE}[2/8]${NC} Checking for debug statements..."
if git diff origin/main...HEAD | grep -qE "(dd\(|dump\(|var_dump\(|print_r\(|console\.log\(|debugger)"; then
    echo -e "${RED}✗${NC} Debug statements found in code"
    git diff origin/main...HEAD | grep -E "(dd\(|dump\(|var_dump\(|print_r\(|console\.log\(|debugger)" | head -5
    ((FAILED++))
else
    echo -e "${GREEN}✓${NC} No debug statements found"
    ((PASSED++))
fi

# ─────────────────────────────────────────
#  3. Check for TODO/FIXME Comments
# ─────────────────────────────────────────
echo -e "${BLUE}[3/8]${NC} Checking for TODO/FIXME comments..."
TODOS=$(git diff origin/main...HEAD | grep -iE "TODO|FIXME|XXX" || true)
if [ -n "$TODOS" ]; then
    echo -e "${YELLOW}⚠${NC} TODO/FIXME comments found (not blocking)"
    echo "$TODOS" | head -3
    ((WARNINGS++))
else
    echo -e "${GREEN}✓${NC} No TODO/FIXME comments"
    ((PASSED++))
fi

# ─────────────────────────────────────────
#  4. Check for Sensitive Data
# ─────────────────────────────────────────
echo -e "${BLUE}[4/8]${NC} Checking for sensitive data..."
if git diff origin/main...HEAD | grep -qiE "(password|secret|api_key|token|private_key|aws_access|credential).*=.*['\"]"; then
    echo -e "${RED}✗${NC} Potential sensitive data found in code"
    ((FAILED++))
else
    echo -e "${GREEN}✓${NC} No sensitive data detected"
    ((PASSED++))
fi

# ─────────────────────────────────────────
#  5. Run Tests
# ─────────────────────────────────────────
echo -e "${BLUE}[5/8]${NC} Running tests..."
if php artisan test --compact > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} All tests passed"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Some tests failed. Run: php artisan test"
    ((FAILED++))
fi

# ─────────────────────────────────────────
#  6. Check Test Coverage
# ─────────────────────────────────────────
echo -e "${BLUE}[6/8]${NC} Checking test coverage..."
if php artisan test --coverage --min=80 > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Test coverage >= 80%"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠${NC} Test coverage < 80% (recommended to add more tests)"
    ((WARNINGS++))
fi

# ─────────────────────────────────────────
#  7. Security Audit
# ─────────────────────────────────────────
echo -e "${BLUE}[7/8]${NC} Running security audit..."
if composer audit --no-dev > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} No security vulnerabilities found"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠${NC} Security vulnerabilities detected. Run: composer audit"
    ((WARNINGS++))
fi

# ─────────────────────────────────────────
#  8. Check Migration Files
# ─────────────────────────────────────────
echo -e "${BLUE}[8/8]${NC} Checking migration files..."
MIGRATIONS=$(git diff --name-only origin/main...HEAD | grep "database/migrations" || true)
if [ -n "$MIGRATIONS" ]; then
    echo -e "${YELLOW}⚠${NC} Migration changes detected:"
    echo "$MIGRATIONS"
    echo ""
    echo "Please ensure:"
    echo "  - Migration has down() method"
    echo "  - Tested with production-like data"
    echo "  - No data loss will occur"
    echo "  - Indexes added for foreign keys"
    ((WARNINGS++))
else
    echo -e "${GREEN}✓${NC} No migration changes"
    ((PASSED++))
fi

# ─────────────────────────────────────────
#  Summary
# ─────────────────────────────────────────
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                          SUMMARY                               ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}Passed:${NC}   $PASSED"
echo -e "${YELLOW}Warnings:${NC} $WARNINGS"
echo -e "${RED}Failed:${NC}   $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ Your code is ready for Pull Request!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. git push origin <your-branch>"
    echo "  2. Create PR with semantic title (e.g., 'feat: add new feature')"
    echo "  3. Fill PR template"
    echo ""
    exit 0
else
    echo -e "${RED}✗ Please fix the issues above before creating PR${NC}"
    echo ""
    echo "Quick fixes:"
    echo "  - Code style: ./vendor/bin/pint"
    echo "  - Tests: php artisan test"
    echo "  - Remove debug statements manually"
    echo ""
    exit 1
fi
