# VU SA Test Suite

**Framework**: PestPHP with Laravel Sail  
**Database**: SQLite in-memory testing  
**Coverage**: 263 tests with comprehensive security testing

## Quick Start

```bash
# Run all tests
./vendor/bin/sail artisan test

# Run specific test file
./vendor/bin/sail artisan test tests/Feature/Auth/SecurityTest.php

# Run with coverage
./vendor/bin/sail artisan test --coverage

# Parallel execution (faster)
./vendor/bin/sail artisan test --parallel
```

## Test Organization

```
tests/Feature/
├── Auth/           # Authentication & Authorization
├── Content/        # News, Pages, Tags, Translation  
├── Forms/          # Dynamic Forms & Registration
├── Management/     # Users, Duties, Meetings, Calendar
├── Public/         # Public-facing features
├── Resources/      # Documents & Reservations  
├── System/         # API, Permissions, Integration
└── Other/          # Legacy tests
```