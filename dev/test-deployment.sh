#!/bin/bash

# Test Deployment Script for Local Development
# Usage: ./scripts/test-deployment.sh [options]

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print colored output
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running in Laravel Sail environment
check_sail_environment() {
    if ! command -v "./vendor/bin/sail" &> /dev/null; then
        print_error "Laravel Sail not found. Make sure you're in the project root and Sail is installed."
        exit 1
    fi
    
    # Check if Sail is running
    if ! ./vendor/bin/sail ps | grep -q "Up"; then
        print_warning "Laravel Sail containers don't appear to be running."
        print_info "Starting Sail containers..."
        ./vendor/bin/sail up -d
        sleep 5
    fi
}

# Show usage information
show_usage() {
    echo "Test Deployment Script"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --dry-run         Show deployment plan without executing"
    echo "  --from=STEP       Start deployment from specific step"
    echo "  --show-state      Show current deployment state"
    echo "  --clean           Clean deployment state before running"
    echo "  --help            Show this help message"
    echo ""
    echo "Available steps:"
    echo "  backup, maintenance, assets, migrate, optimize, search, health, online"
    echo ""
    echo "Examples:"
    echo "  $0 --dry-run                    # Show what would happen"
    echo "  $0                              # Run full deployment test"
    echo "  $0 --from=migrate               # Start from migration step"
    echo "  $0 --clean                      # Clean state and run full test"
    echo "  $0 --show-state                 # Show current deployment state"
}

# Parse command line arguments
DRY_RUN=false
FROM_STEP=""
SHOW_STATE=false
CLEAN_STATE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --from=*)
            FROM_STEP="${1#*=}"
            shift
            ;;
        --show-state)
            SHOW_STATE=true
            shift
            ;;
        --clean)
            CLEAN_STATE=true
            shift
            ;;
        --help)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Main execution
main() {
    print_info "🧪 Local Deployment Testing"
    echo ""
    
    # Check environment
    check_sail_environment
    
    # Show current deployment state if requested
    if [ "$SHOW_STATE" = true ]; then
        print_info "Current deployment state:"
        ./vendor/bin/sail artisan deployment:resume --show-state
        exit 0
    fi
    
    # Clean state if requested
    if [ "$CLEAN_STATE" = true ]; then
        print_info "Cleaning deployment state..."
        ./vendor/bin/sail artisan tinker --execute="Storage::disk('local')->delete('deployment/state.json');"
        print_success "State cleaned"
    fi
    
    # Build command arguments
    ARGS=()
    
    if [ "$DRY_RUN" = true ]; then
        ARGS+=(--dry-run)
        print_info "Running in dry-run mode (no changes will be made)"
    fi
    
    if [ -n "$FROM_STEP" ]; then
        ARGS+=(--from="$FROM_STEP")
        print_info "Starting from step: $FROM_STEP"
    fi
    
    # Execute deployment test
    print_info "Executing deployment test..."
    echo ""
    
    if ./vendor/bin/sail artisan deployment:run "${ARGS[@]}"; then
        echo ""
        print_success "Deployment test completed successfully!"
        
        if [ "$DRY_RUN" = false ]; then
            print_info "💡 Tips:"
            echo "  - Check your local site at http://localhost"
            echo "  - View logs with: ./vendor/bin/sail logs"
            echo "  - Test individual steps with: ./vendor/bin/sail artisan deployment:COMMAND"
        fi
    else
        echo ""
        print_error "Deployment test failed!"
        print_info "💡 Recovery options:"
        echo "  - View state: $0 --show-state"
        echo "  - Resume: ./vendor/bin/sail artisan deployment:resume"
        echo "  - Clean and retry: $0 --clean"
        exit 1
    fi
}

# Run main function
main