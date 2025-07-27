# VU SA Test Suite

**Framework**: PestPHP with Laravel Sail  
**Database**: SQLite in-memory testing  
**Search**: Database driver (tests don't require Typesense connection)  
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

## Test Environment

### Local Development
- Uses Docker services via Laravel Sail (MySQL, Redis, Typesense)
- Admin operations use database driver (via ModelIndexer)
- Public search would use Typesense in production

### CI/CD Environment
- SQLite in-memory database for PHP tests
- Database driver for all Scout operations
- No external services required for testing

### Search Testing Architecture
- **TypesenseSearchTest.php**: Tests configuration and model searchability
- **Database driver**: Used for all tests to avoid external dependencies  
- **Configuration testing**: Validates Typesense config without connection
- **Model testing**: Validates `shouldBeSearchable()` and `toSearchableArray()`

### Search Architecture in Production
- **Public frontend**: Typesense for fast, typo-tolerant search
- **Admin operations**: Database driver (prevents circular dependencies)
- **ModelIndexer**: Automatically switches to database driver for admin searches

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