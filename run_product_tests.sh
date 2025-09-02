#!/bin/bash

# Product Test Runner Script
# This script runs all product-related unit tests

echo "=========================================="
echo "Running Product Management Unit Tests"
echo "=========================================="

# Set the working directory
cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Running Product Entity Tests...${NC}"
php bin/phpunit tests/Entity/ProductTest.php
ENTITY_EXIT_CODE=$?

echo ""
echo -e "${YELLOW}Running Product Repository Tests...${NC}"
php bin/phpunit tests/Repository/ProductRepositoryTest.php
REPO_EXIT_CODE=$?

echo ""
echo -e "${YELLOW}Running Product Controller Tests...${NC}"
php bin/phpunit tests/Controller/ProductControllerTest.php
CONTROLLER_EXIT_CODE=$?

echo ""
echo "=========================================="
echo "Test Results Summary:"
echo "=========================================="

if [ $ENTITY_EXIT_CODE -eq 0 ]; then
    echo -e "Product Entity Tests: ${GREEN}PASSED${NC}"
else
    echo -e "Product Entity Tests: ${RED}FAILED${NC}"
fi

if [ $REPO_EXIT_CODE -eq 0 ]; then
    echo -e "Product Repository Tests: ${GREEN}PASSED${NC}"
else
    echo -e "Product Repository Tests: ${RED}FAILED${NC}"
fi

if [ $CONTROLLER_EXIT_CODE -eq 0 ]; then
    echo -e "Product Controller Tests: ${GREEN}PASSED${NC}"
else
    echo -e "Product Controller Tests: ${RED}FAILED${NC}"
fi

# Calculate overall exit code
OVERALL_EXIT_CODE=$((ENTITY_EXIT_CODE + REPO_EXIT_CODE + CONTROLLER_EXIT_CODE))

if [ $OVERALL_EXIT_CODE -eq 0 ]; then
    echo -e "\n${GREEN}All Product Tests PASSED!${NC}"
else
    echo -e "\n${RED}Some Product Tests FAILED!${NC}"
fi

exit $OVERALL_EXIT_CODE
