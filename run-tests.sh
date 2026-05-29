#!/bin/bash

# Admin Payment Application - Test Runner Script
# This script runs all test suites and generates coverage reports

set -e

echo "========================================="
echo "  Admin Payment Application Test Suite  "
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
    fi
}

# Check if .env.testing exists
if [ ! -f .env.testing ]; then
    echo -e "${YELLOW}⚠ .env.testing not found. Creating from .env.example...${NC}"
    cp .env.example .env.testing
    sed -i 's/DB_DATABASE=.*/DB_DATABASE=admin_payment_testing/' .env.testing
    sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env.testing
    sed -i 's/CACHE_DRIVER=.*/CACHE_DRIVER=array/' .env.testing
fi

# Create test database if it doesn't exist
echo "Checking test database..."
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS admin_payment_testing;" 2>/dev/null || true

# Run migrations
echo "Running migrations..."
php artisan migrate --env=testing --force
print_status $? "Database migrations"

echo ""
echo "========================================="
echo "  Running PHP Tests (PHPUnit)           "
echo "========================================="
echo ""

# Run PHP tests with coverage
php artisan test --coverage --min=80
PHP_TEST_RESULT=$?
print_status $PHP_TEST_RESULT "PHP Tests"

echo ""
echo "========================================="
echo "  Running JavaScript Tests (Vitest)     "
echo "========================================="
echo ""

# Run JavaScript tests
npm test -- --run
JS_TEST_RESULT=$?
print_status $JS_TEST_RESULT "JavaScript Tests"

echo ""
echo "========================================="
echo "  Test Summary                           "
echo "========================================="
echo ""

if [ $PHP_TEST_RESULT -eq 0 ] && [ $JS_TEST_RESULT -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    [ $PHP_TEST_RESULT -ne 0 ] && echo -e "${RED}  - PHP tests failed${NC}"
    [ $JS_TEST_RESULT -ne 0 ] && echo -e "${RED}  - JavaScript tests failed${NC}"
    exit 1
fi
