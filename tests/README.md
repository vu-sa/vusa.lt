# VU SA Test Suite

**Framework**: PestPHP with Laravel Sail  
**Database**: SQLite in-memory testing  
**Search**: Database driver (tests don't require Typesense connection)  
**Coverage**: 450+ tests with comprehensive security testing

## Authorization Testing Changes

Due to changes in `app/Exceptions/Handler.php`, authorization tests now behave differently:

### Direct Requests (no Inertia headers)
- **GET requests** to protected pages return `403` status code
- **POST/PUT/DELETE requests** to protected endpoints return `403` status code

### Inertia Requests (with X-Inertia header)
- **Any request** returns `302` redirect with error flash message in session

### Test Patterns

#### Testing Unauthorized Access
```php
// Direct request - expect 403
test('cannot access protected page', function () {
    asUser($user)->get(route('protected.page'))->assertStatus(403);
});

// Inertia request - expect 302 redirect (typically back to form with flash message)
test('cannot perform protected action via inertia', function () {
    asUserWithInertia($user)->post(route('protected.action'), $data)
        ->assertRedirect();
        // Flash messages are typically handled by the application
});
```

#### Testing Authorized Actions
```php
// Successful operations (typically use Inertia in real app)
test('can perform authorized action', function () {
    asUserWithInertia($authorizedUser)->post(route('action'), $data)
        ->assertRedirect(); // or assertStatus(302/303)
});
```

#### Testing Failed Operations (Business Logic)
```php
// When testing operations that should fail due to business rules:
test('cannot update resource in invalid state', function () {
    asUserWithInertia($authorizedUser)->post(route('update.action'), $data)
        ->assertRedirect(); // Gets redirected with error message
    
    // Don't use followRedirects() if you expect the operation to fail
    // The redirect may return 409 or other conflict statuses
    
    // Instead, verify the operation actually failed:
    expect($model->fresh()->status)->toBe('unchanged');
});
```

#### Helper Functions
- `asUser($user)` - Direct request without Inertia headers
- `asUserWithInertia($user)` - Request with Inertia headers (simulates in-app navigation)

**Important**: 
- Never expect both `assertStatus(403)` and `assertRedirect()` in the same test - 403 responses don't redirect
- When testing failed operations, use `assertRedirect()` but avoid `followRedirects()` if business logic conflicts are expected
- Use correct roles for tests: check role permissions in `database/seeders/Role*Seeder.php` files

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