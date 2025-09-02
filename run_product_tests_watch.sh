#!/bin/bash

# Product Test Watch Mode
# This script runs product tests in watch mode for development

echo "=========================================="
echo "Product Test Watch Mode"
echo "=========================================="

# Set the working directory
cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Watching for changes in Product-related files...${NC}"
echo -e "${YELLOW}Press Ctrl+C to stop watching${NC}"
echo ""

# Function to run tests
run_tests() {
    echo -e "${YELLOW}[$(date '+%H:%M:%S')] Running Product Tests...${NC}"
    php bin/phpunit tests/Entity/ProductTest.php tests/Repository/ProductRepositoryTest.php tests/Controller/ProductControllerTest.php --testdox
    echo -e "${BLUE}Waiting for file changes...${NC}"
    echo ""
}

# Run tests initially
run_tests

# Watch for changes in product-related files
if command -v inotifywait &> /dev/null; then
    # Linux/WSL with inotify-tools
    while inotifywait -e modify,create,delete \
        src/Entity/Product.php \
        src/Controller/ProductController.php \
        src/Repository/ProductRepository.php \
        tests/Entity/ProductTest.php \
        tests/Repository/ProductRepositoryTest.php \
        tests/Controller/ProductControllerTest.php \
        2>/dev/null; do
        run_tests
    done
elif command -v fswatch &> /dev/null; then
    # macOS with fswatch
    fswatch -o \
        src/Entity/Product.php \
        src/Controller/ProductController.php \
        src/Repository/ProductRepository.php \
        tests/Entity/ProductTest.php \
        tests/Repository/ProductRepositoryTest.php \
        tests/Controller/ProductControllerTest.php | while read; do
        run_tests
    done
else
    echo -e "${RED}File watching not available. Please install inotify-tools (Linux) or fswatch (macOS)${NC}"
    echo -e "${YELLOW}Falling back to manual test running...${NC}"
    echo "Run this script again after making changes, or use: ./run_product_tests.sh"
fi
