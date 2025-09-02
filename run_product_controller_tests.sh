#!/bin/bash

# Product Controller Test Runner
# This script runs only the Product controller tests (functional tests)

echo "=========================================="
echo "Running Product Controller Tests"
echo "=========================================="

# Set the working directory
cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Setting up test environment...${NC}"
echo "Clearing test cache..."
php bin/console cache:clear --env=test --no-debug

echo ""
echo -e "${YELLOW}Running Product Controller Tests...${NC}"
php bin/phpunit tests/Controller/ProductControllerTest.php --testdox

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}Product Controller Tests PASSED!${NC}"
else
    echo -e "${RED}Product Controller Tests FAILED!${NC}"
fi

exit $EXIT_CODE
