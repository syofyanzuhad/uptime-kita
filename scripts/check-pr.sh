#!/bin/bash

# PR Check Script - Run all checks locally before submitting a PR
# Usage: ./scripts/check-pr.sh

set -e  # Exit on any error

echo "🚀 Running PR checks locally..."
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
        exit 1
    fi
}

# Check if dependencies are installed
echo -e "${YELLOW}📦 Checking dependencies...${NC}"

if ! command -v composer &> /dev/null; then
    echo -e "${RED}❌ Composer is not installed${NC}"
    exit 1
fi

if ! command -v npm &> /dev/null; then
    echo -e "${RED}❌ npm is not installed${NC}"
    exit 1
fi

print_status $? "Dependencies check passed"

# Install dependencies if needed
echo -e "${YELLOW}📦 Installing dependencies...${NC}"

if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -d "node_modules" ]; then
    npm ci
fi

print_status $? "Dependencies installed"

# PHP Code Style Check
echo -e "${YELLOW}🎨 Checking PHP code style...${NC}"
vendor/bin/pint --test
print_status $? "PHP code style check passed"

# Frontend Formatting Check
echo -e "${YELLOW}🎨 Checking frontend formatting...${NC}"
npm run format:check
print_status $? "Frontend formatting check passed"

# Frontend Linting
echo -e "${YELLOW}🔍 Running frontend linting...${NC}"
npm run lint
print_status $? "Frontend linting passed"

# TypeScript Type Check
echo -e "${YELLOW}🔍 Running TypeScript type check...${NC}"
npx vue-tsc --noEmit
print_status $? "TypeScript type check passed"

# Build Check
echo -e "${YELLOW}🔨 Building assets...${NC}"
npm run build
print_status $? "Build check passed"

# Tests
echo -e "${YELLOW}🧪 Running tests...${NC}"
./vendor/bin/pest
print_status $? "Tests passed"

echo ""
echo -e "${GREEN}🎉 All checks passed! Your code is ready for PR submission.${NC}"
echo ""
echo "If you want to fix any formatting issues automatically, run:"
echo "  vendor/bin/pint"
echo "  npm run format"
echo "  npm run lint"
