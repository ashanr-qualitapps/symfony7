#!/bin/bash

# Product Test Coverage Runner
# This script runs product tests with coverage reporting

echo "=========================================="
echo "Running Product Tests with Coverage"
echo "=========================================="

# Set the working directory
cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Create coverage directory if it doesn't exist
mkdir -p coverage

echo -e "${YELLOW}Running Product Tests with Coverage Analysis...${NC}"

# Run tests with coverage (requires xdebug or phpdbg)
php bin/phpunit \
    --coverage-html coverage/product \
    --coverage-text \
    --whitelist src/Entity/Product.php \
    --whitelist src/Controller/ProductController.php \
    --whitelist src/Repository/ProductRepository.php \
    tests/Entity/ProductTest.php \
    tests/Repository/ProductRepositoryTest.php \
    tests/Controller/ProductControllerTest.php

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}Product Tests with Coverage COMPLETED!${NC}"
    echo -e "Coverage report generated in: ${YELLOW}coverage/product/index.html${NC}"
else
    echo -e "${RED}Product Tests with Coverage FAILED!${NC}"
fi

exit $EXIT_CODE
