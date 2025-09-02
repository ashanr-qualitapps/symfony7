#!/bin/bash

# Quick Product Entity Test Runner
# This script runs only the Product entity tests for quick validation

echo "=========================================="
echo "Running Product Entity Tests (Quick)"
echo "=========================================="

# Set the working directory
cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Running Product Entity Tests...${NC}"
php bin/phpunit tests/Entity/ProductTest.php --testdox

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}Product Entity Tests PASSED!${NC}"
else
    echo -e "${RED}Product Entity Tests FAILED!${NC}"
fi

exit $EXIT_CODE
