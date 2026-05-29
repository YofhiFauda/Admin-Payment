#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Run Tests Locally (Outside Docker)
#  Script untuk menjalankan tests di host machine
# ═══════════════════════════════════════════════════════════════════

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}🧪 WHUSNET Admin Payment - Local Test Runner${NC}"
echo "═══════════════════════════════════════════════════════════════════"

# Check if .env.testing exists
if [ ! -f .env.testing ]; then
    echo -e "${RED}❌ File .env.testing tidak ditemukan!${NC}"
    echo -e "${YELLOW}📝 Silakan buat .env.testing terlebih dahulu${NC}"
    exit 1
fi

# Check if vendor exists
if [ ! -d vendor ]; then
    echo -e "${YELLOW}📦 Installing dependencies...${NC}"
    composer install
fi

# Parse command line arguments
TEST_TYPE=${1:-all}
COVERAGE=${2:-no}

echo -e "${CYAN}🔧 Running tests locally (outside Docker)...${NC}"
echo ""

case $TEST_TYPE in
    all)
        echo -e "${CYAN}📦 Running all tests...${NC}"
        if [ "$COVERAGE" == "coverage" ]; then
            php artisan test --env=testing --coverage
        else
            php artisan test --env=testing
        fi
        ;;
    
    critical)
        echo -e "${CYAN}🔥 Running Critical Priority tests...${NC}"
        php artisan test --env=testing \
            tests/Feature/PengajuanManagementTest.php \
            tests/Feature/PembelianManagementTest.php \
            tests/Feature/TransactionSearchTest.php \
            tests/Feature/NotificationSystemTest.php \
            tests/Feature/FileUploadTest.php
        ;;
    
    high)
        echo -e "${CYAN}⚡ Running High Priority tests...${NC}"
        php artisan test --env=testing \
            tests/Feature/AiAutoFillTest.php \
            tests/Feature/ItemAutocompleteTest.php \
            tests/Feature/TransactionStatusTest.php \
            tests/Feature/UserBankAccountTest.php \
            tests/Feature/BranchBankAccountTest.php
        ;;
    
    parallel)
        echo -e "${CYAN}⚡ Running tests in parallel...${NC}"
        php artisan test --env=testing --parallel
        ;;
    
    fast)
        echo -e "${CYAN}🚀 Running fast tests (stop on failure)...${NC}"
        php artisan test --env=testing --stop-on-failure
        ;;
    
    *)
        echo -e "${YELLOW}Usage: $0 [test_type] [coverage]${NC}"
        echo ""
        echo "Test Types:"
        echo "  all       - Run all tests (default)"
        echo "  critical  - Run Critical Priority tests"
        echo "  high      - Run High Priority tests"
        echo "  parallel  - Run tests in parallel"
        echo "  fast      - Run with stop-on-failure"
        echo ""
        echo "Coverage:"
        echo "  coverage  - Generate coverage report"
        echo ""
        echo "Examples:"
        echo "  $0 all"
        echo "  $0 all coverage"
        echo "  $0 critical"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}✅ Tests completed!${NC}"
