#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Run Tests Script
#  Script untuk menjalankan tests dengan berbagai opsi
# ═══════════════════════════════════════════════════════════════════

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}🧪 WHUSNET Admin Payment - Test Runner${NC}"
echo "═══════════════════════════════════════════════════════════════════"

# Check if .env.testing exists
if [ ! -f .env.testing ]; then
    echo -e "${RED}❌ File .env.testing tidak ditemukan!${NC}"
    echo -e "${YELLOW}📝 Membuat .env.testing dari .env.example...${NC}"
    cp .env.example .env.testing
    echo -e "${GREEN}✅ File .env.testing berhasil dibuat${NC}"
fi

# Parse command line arguments
TEST_TYPE=${1:-all}
COVERAGE=${2:-no}

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
    
    medium)
        echo -e "${CYAN}📊 Running Medium Priority tests...${NC}"
        php artisan test --env=testing \
            tests/Feature/TransactionExportTest.php \
            tests/Feature/RembushEditTest.php \
            tests/Feature/TransactionConfirmationTest.php \
            tests/Feature/EventBroadcastingTest.php \
            tests/Feature/JobQueueTest.php
        ;;
    
    low)
        echo -e "${CYAN}📝 Running Low Priority tests...${NC}"
        php artisan test --env=testing \
            tests/Feature/RateLimitingTest.php \
            tests/Feature/MiddlewareTest.php \
            tests/Unit/IdGeneratorServiceTest.php \
            tests/Feature/FormValidationTest.php \
            tests/Feature/ApiVersioningTest.php \
            tests/Feature/ErrorHandlingTest.php \
            tests/Performance/LoadTest.php
        ;;
    
    feature)
        echo -e "${CYAN}🎯 Running Feature tests...${NC}"
        php artisan test --env=testing --testsuite=Feature
        ;;
    
    unit)
        echo -e "${CYAN}🔬 Running Unit tests...${NC}"
        php artisan test --env=testing --testsuite=Unit
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
        echo "  medium    - Run Medium Priority tests"
        echo "  low       - Run Low Priority tests"
        echo "  feature   - Run Feature tests only"
        echo "  unit      - Run Unit tests only"
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
        echo "  $0 parallel"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}✅ Tests completed!${NC}"
