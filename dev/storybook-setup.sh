#!/bin/bash
# Storybook Browser Testing Setup Script
# Run from project root: ./dev/storybook-setup.sh
# This script must be run from outside the container (uses sail commands)

set -e

echo "🎭 Setting up Playwright browsers for Storybook testing..."

# Install Playwright system dependencies (requires root)
echo "📦 Installing system dependencies..."
./vendor/bin/sail root-shell -c "apt-get update && apt-get install -y libnspr4 libnss3 libatk1.0-0 libatk-bridge2.0-0 libcups2 libxkbcommon0 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libgbm1 libpango-1.0-0 libcairo2 libasound2t64"

# Install Playwright browsers (chromium only for faster setup)
echo "🌐 Installing Playwright Chromium browser..."
./vendor/bin/sail npx playwright install chromium

echo "✅ Storybook browser testing environment is ready!"
echo ""
echo "You can now run: sail npm run test:storybook"
