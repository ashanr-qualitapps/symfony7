#!/bin/bash

# Product Repository Test Runner
# This script runs only the Product repository tests (database tests)

echo "=========================================="
echo "Running Product Repository Tests"
echo "=========================================="

# Set the working directory
cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Setting up test database...${NC}"
echo "Creating test database schema..."
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:create --env=test

echo ""
echo -e "${YELLOW}Running Product Repository Tests...${NC}"
php bin/phpunit tests/Repository/ProductRepositoryTest.php --testdox

EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}Product Repository Tests PASSED!${NC}"
else
    echo -e "${RED}Product Repository Tests FAILED!${NC}"
fi

exit $EXIT_CODE
