# CLAUDE.md for Tests

This file provides specific guidance for Claude Code when working with tests in this repository.

For a quick overview and basic usage, see @README.md in this directory.

## Testing Framework & Structure

**Framework**: PestPHP with Laravel Sail  
**Database**: SQLite in-memory testing  
**Coverage**: 263 tests with comprehensive security testing  
**Organization**: Feature tests organized by domain area

## Test Directory Structure

```
tests/Feature/
├── Auth/           # Authentication & Authorization
├── Content/        # News, Pages, Tags, Translation  
├── Forms/          # Dynamic Forms & Registration
├── Management/     # Users, Duties, Meetings, Calendar
├── Public/         # Public-facing features
├── Resources/      # Documents & Reservations  
├── System/         # API, Permissions, Integration
└── Other/          # Legacy (to be reorganized)
```

## Key Testing Patterns

### User Creation & Authentication
```php
// Create test users with specific roles
$user = makeTenantUser('Communication Coordinator');
$resourceManager = makeTenantUser('Resource Manager');
asUser($user)->get('/mano/news');

// For comprehensive testing when all permissions needed
$user->assignRole(config('permission.super_admin_role_name'));
```

### Security Testing
```php
// Security expectations
expect($response->status())->toBeSecureResponse();
expect($response->status())->toRequireAuth();
expect($content)->toNotExposePassword();
```

### Permission Testing Patterns
```php
// Test different permission scenarios
test('user can access own resources')
    ->expect(fn() => asUser($user)->get(route('resources.show', $ownResource)))
    ->toBeSuccessful();

test('user cannot access other tenant resources')
    ->expect(fn() => asUser($user)->get(route('resources.show', $otherTenantResource)))
    ->toBeNotFound();
```

### Factory Usage
```php
// Always use factories for consistent test data
$form = Form::factory()->create(['tenant_id' => $tenant->id]);
$user = User::factory()->create();

// For translatable models, include translations
$news = News::factory()->create([
    'title' => ['lt' => 'Lietuviškas pavadinimas', 'en' => 'English title'],
    'content' => ['lt' => 'Lietuviškas turinys', 'en' => 'English content']
]);
```

### Database Management
```php
// ALWAYS use RefreshDatabase per file
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Each test file should declare this explicitly
```

## Test Organization Guidelines

### When to Create New Test Files
- **Domain-specific features**: Create separate files for each major feature area
- **Complex workflows**: Break down complex features into focused test files
- **Security scenarios**: Keep security tests organized by feature area

### Test Naming Conventions
- Use descriptive test names that explain the expected behavior
- Group related tests using `describe()` blocks
- Use `test()` function for simple assertions
- Use `it()` function for BDD-style tests

## Common Test Patterns

### Route Testing
```php
// Test route accessibility
test('authenticated users can access admin dashboard')
    ->expect(fn() => asUser($user)->get('/mano'))
    ->toBeSuccessful();

// Test route parameters
test('user can view their own profile')
    ->expect(fn() => asUser($user)->get("/mano/users/{$user->id}"))
    ->toBeSuccessful();
```

### Form Testing
```php
// Test form validation
test('form requires valid data')
    ->expect(fn() => asUser($user)->post('/mano/forms', []))
    ->toHaveValidationErrors(['name', 'description']);

// Test form submission
test('user can create form with valid data')
    ->expect(fn() => asUser($user)->post('/mano/forms', $validData))
    ->toRedirect()
    ->and(fn() => Form::count())
    ->toBe(1);
```

### API Testing
```php
// Test API endpoints
test('API returns correct data structure')
    ->expect(fn() => asUser($user)->getJson('/api/forms'))
    ->toBeSuccessful()
    ->json()
    ->toHaveStructure(['data', 'meta']);
```

## Security Test Requirements

### Critical Security Areas
- **Authentication**: All admin routes require authentication
- **Authorization**: Users can only access permitted resources
- **Tenant Isolation**: Users cannot access other tenant data
- **Data Exposure**: No sensitive data in responses
- **Permission Escalation**: Cannot gain unauthorized permissions

### Security Test Examples
```php
// Test authentication requirement
test('admin routes require authentication')
    ->expect(fn() => $this->get('/mano/users'))
    ->toRedirect('/login');

// Test authorization
test('user cannot access unauthorized resources')
    ->expect(fn() => asUser($user)->get('/mano/super-admin'))
    ->toBeForbidden();

// Test tenant isolation
test('user cannot see other tenant data')
    ->expect(fn() => asUser($user)->get('/mano/news'))
    ->not()->toContain($otherTenantNews->title);
```

## Testing Multi-language Features

### Translation Testing
```php
// Test translated content
test('news displays correct language')
    ->expect(fn() => asUser($user)->get('/en/news'))
    ->toSee($news->getTranslation('title', 'en'));

// Test admin translation forms
test('admin can update translations')
    ->expect(fn() => asUser($admin)->put("/mano/news/{$news->id}", [
        'title' => ['lt' => 'Lietuviškas', 'en' => 'English'],
        'content' => ['lt' => 'Lietuviškas turinys', 'en' => 'English content']
    ]))
    ->toRedirect();
```

## Performance Testing

### Database Query Testing
```php
// Test for N+1 queries
test('index page does not have N+1 queries')
    ->expect(fn() => asUser($user)->get('/mano/news'))
    ->toHaveQueryCount(lessThan(10));

// Test eager loading
test('news with comments loads efficiently')
    ->expect(fn() => News::with('comments')->get())
    ->toHaveQueryCount(2);
```

## Testing Best Practices

### Do's
- Test both positive and negative scenarios
- Use factories for consistent test data
- Test permission scenarios with different roles
- Mock external services
- Focus on critical user workflows
- Use descriptive test names

### Don'ts
- Don't test framework functionality
- Don't create overly complex test setups
- Don't ignore failing tests
- Don't skip security tests
- Don't test implementation details


## Remember

This is a **student-run project** with security-critical features. Always prioritize:
1. **Security testing** - Test all permission scenarios
2. **Data isolation** - Ensure proper tenant boundaries
3. **User safety** - Prevent unauthorized access
4. **Code quality** - Maintain clean, readable tests