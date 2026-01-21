# CLAUDE.md for Tests

This file provides specific guidance for Claude Code when working with tests in this repository.

For a quick overview and basic usage, see @README.md in this directory.

## Testing Framework & Structure

**PHP Framework**: PestPHP with Laravel Sail  
**JavaScript Framework**: Vitest with @vue/test-utils  
**Database**: SQLite in-memory testing  
**Coverage**: 450+ tests with comprehensive security testing  
**Organization**: Feature tests organized by domain area  
**Seeding**: Tests are seeded with duties and roles from the DatabaseSeeder and associated role seeders

### JavaScript Testing Setup
**Mocks Location**: Use `resources/js/mocks/` directory (NOT Storybook mocks):
- `inertia.mock.ts` - for Inertia.js functionality (usePage, router, useForm)
- `i18n.ts` - for Laravel translations (trans, wTrans, $t) - uses actual generated translations
- `route.ts` - for route generation (route() function) - returns predictable mock URLs

**Test Locations**:
- Composables: `resources/js/Composables/__tests__/`
- Components: `resources/js/Components/**/__tests__/`
- Services: `resources/js/Services/__tests__/`

**Testing Commands**: See [tests/README.md](README.md) for all test commands and environment setup.

## Test Directory Structure

```
tests/Feature/
├── Admin/          # MAIN: Comprehensive admin controller tests (use this!)
│   ├── Calendar/   # AgendaItem, Calendar, Meeting controllers
│   ├── Content/    # Banner, Category, News, Page, Tag controllers  
│   ├── Core/       # Dashboard controller
│   ├── Forms/      # Form controller
│   ├── Management/ # Duty, Institution, StudyProgram, Tenant, User controllers
│   ├── Navigation/ # Navigation controller
│   ├── Permissions/# Permission, Role controllers
│   └── Resources/  # Document, Files, Reservation controllers
├── Auth/           # Authentication & Authorization
├── Content/        # DEPRECATED: Legacy model tests (avoid, use Admin/ instead)
├── Forms/          # Dynamic Forms & Registration workflows
├── Management/     # DEPRECATED: Legacy management tests
├── Public/         # Public-facing features
├── Resources/      # DEPRECATED: Legacy resource tests
├── System/         # API, Permissions, Integration, Search
└── Other/          # Legacy tests (to be cleaned up)
```

**Important**: Always use `tests/Feature/Admin/` for new controller tests. Follow the golden standard pattern from `tests/Feature/Admin/Content/PageControllerTest.php`.

## Key Testing Patterns

### Controller Test Pattern (Follow Admin Structure)

**Golden Standard**: `tests/Feature/Admin/Content/PageControllerTest.php`
**Current Example**: `tests/Feature/Admin/Content/NewsControllerTest.php` shows proper authorization testing

**Required Structure**:
```php
<?php
use App\Models\ModelName;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    $this->model = ModelName::factory()->for($this->tenant)->create();
});

describe('unauthorized access', function () {
    test('cannot access index page', function () {
        asUser($this->user)->get(route('route.index'))->assertStatus(403);
    });
    // ... other unauthorized tests
});

describe('authorized access', function () {
    test('can access index page', function () {
        asUser($this->admin)->get(route('route.index'))->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Path/IndexComponent')
                ->has('data')
            );
    });
    // ... other authorized tests
});

describe('tenant isolation', function () {
    // Test cross-tenant access restrictions
});
```

### User Creation & Authentication
```php
// Create test users with specific roles
$user = makeTenantUserWithRole('Communication Coordinator', $tenant);
$resourceManager = makeTenantUserWithRole('Resource Manager', $tenant);
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

### Domain Logic Tests (Tasks, Notifications)

**Mirror Pattern**: Test directories mirror `app/` subdirectory structure exactly.

| App Location | Test Location |
|--------------|---------------|
| `app/Tasks/Handlers/{Name}.php` | `tests/Feature/Tasks/Handlers/{Name}Test.php` |
| `app/Tasks/Subscribers/{Name}.php` | `tests/Feature/Tasks/Subscribers/{Name}Test.php` |
| `app/Notifications/{Name}.php` | Group in `tests/Feature/Notifications/{Behavior}Test.php` |
| `app/Notifications/Subscribers/{Name}.php` | `tests/Feature/Notifications/Subscribers/{Name}Test.php` |

**Task Handler Test Pattern**:
```php
<?php
use App\Tasks\Handlers\{HandlerName};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

describe('{HandlerName}', function () {
    test('creates task for valid scenario', function () {
        // Arrange: Create required models
        // Act: Invoke handler or trigger event
        // Assert: Verify task created with correct properties
    });
});
```

**Notification Behavior Test Pattern** (grouped by behavior, not per-class):
```php
<?php
use Tests\Feature\Notifications\NotificationTestHelpers;

uses(RefreshDatabase::class, NotificationTestHelpers::class);

describe('task notifications', function () {
    test('TaskAssignedNotification fires on task creation', function () {
        // Test notification firing
    });
});
```

**Helper Traits**: Place shared test utilities in `{Domain}TestHelpers.php`:
- `tests/Feature/Tasks/MeetingTaskTestHelpers.php`
- `tests/Feature/Notifications/NotificationTestHelpers.php`

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

## JavaScript Testing Patterns

### Composable Testing
```typescript
import { describe, test, expect, beforeEach, vi } from 'vitest'
import { useComposableName } from '../useComposableName'

// Mock external dependencies  
vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'))
vi.mock('vue-sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }))

describe('useComposableName', () => {
    test('handles basic functionality', () => {
        const { method } = useComposableName()
        
        method('test input')
        
        expect(result).toBe('expected output')
    })
})
```

### Component Testing
```typescript
import { describe, test, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ComponentName from '../ComponentName.vue'

describe('ComponentName', () => {
    test('renders correctly with props', () => {
        const wrapper = mount(ComponentName, {
            props: { title: 'Test Title' }
        })
        
        expect(wrapper.text()).toContain('Test Title')
    })
})
```

### Mock Usage
```typescript
// Use existing mocks from resources/js/mocks/
import { usePage } from '@/mocks/inertia.mock'
import { trans } from '@/mocks/i18n'
import { route } from '@/mocks/route'
```

## Testing Best Practices

### PHP Testing Do's
- Follow Admin/ controller test structure exactly (`tests/Feature/Admin/Content/NewsControllerTest.php`)
- Use `makeTenantUserWithRole()` for user creation
- Test unauthorized (403) and authorized (200) access  
- Include tenant isolation tests
- Use `getControllerTestData()` helper functions
- **Avoid legacy directories**: Use `tests/Feature/Admin/` for new controller tests

### JavaScript Testing Do's
- Use existing mocks from `resources/js/mocks/`
- Test user interactions and state changes
- Mock external dependencies properly
- Follow existing test file patterns

### Universal Don'ts
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