# Model Implementation Prompt for vusa.lt

You are helping implement a new translatable model with full admin CRUD interface in the vusa.lt Laravel application. This is a student-run project built with Laravel 12+, Vue 3, Inertia.js, Tailwind CSS, Shadcn Vue, and MySQL.

## Context & Architecture

- **Admin routes**: Prefixed with `/mano` (NOT `/admin`) and defined in `routes/admin.php`
- **Route names**: NO "admin." prefix - use `route('models.index')` → `/mano/models`
- **Permission format**: `{resource}.{action}.{scope}` (e.g., `news.update.padalinys`)
- **Permission hierarchy**: `own` < `padalinys` < `all`
- **Languages**: Support both lt/en with `HasTranslations` trait
- **Commands**: Always use Laravel Sail: `./vendor/bin/sail artisan [command]`

## Implementation Requirements

Follow this exact checklist for implementing a new model:

### 1. Database Layer
- [ ] **Migration**: Create with proper indexes, foreign keys, and JSON columns for translatable fields
- [ ] **Factory**: 
  - Location: `database/factories/` (or `database/factories/Pivots/` for pivot models)
  - Include translation arrays: `'name' => ['lt' => '...', 'en' => '...']`
  - Add factory methods for different states
  - Add relationships and realistic test data

### 2. Model Layer
- [ ] **Model Class**:
  - Traits: `HasFactory`, `HasTranslations`, `HasUlids` (if needed)
  - Define `$translatable = ['name', ...]` array
  - Define `$fillable` array
  - Add relationship methods
- [ ] **Policy**:
  - Extend `ModelPolicy` class
  - Set `$pluralModelName` using `ModelEnum` in constructor
  - For single-tenant models, use `hasManyTenants=false` in commonChecker calls

### 3. Request Validation
- [ ] Create `Store[Model]Request` and `Update[Model]Request`
- [ ] Validation for translatable fields: `'name.lt' => 'required|string'`
- [ ] Add unique constraints where appropriate

### 4. Controller Layer
- [ ] **Admin Controller**:
  - Constructor: Inject `Authorizer` and `TanstackTableService`
  - Use `HasTanstackTables` trait
  - **CRITICAL**: Fix `GetTenantsForUpserts::execute()` calls with required parameters:
    ```php
    GetTenantsForUpserts::execute('[models].create.padalinys', $this->authorizer)
    ```
  - **Data formatting for admin**: Use `toFullArray()` for form pages, `toArray()` for index:
    ```php
    // Index controller (display only)
    'data' => $models->getCollection()->map->toArray()
    
    // Create/Edit controller (forms need full translation objects)  
    'model' => $model->toFullArray()
    ```
  - Add manual filtering for simple fields before TanStack filters
  - Use proper authorization in each method

### 5. Frontend Layer
- [ ] **Vue Components**:
  - Create `[Model]Form.vue` using `MultiLocaleInput` for translatable fields
  - Create index, create, edit pages in `resources/js/Pages/Admin/[Category]/`
  - Add proper validation and error handling
- [ ] **Admin UI Integration**:
  - Add model to `ModelEnum` in `resources/js/Types/enums.ts`
  - Add icon mapping in `resources/js/Types/Icons/regular.ts`
  - Add menu item to appropriate category in `ShowAdministration.vue`
  - Update category `show` condition to include new model permission
  - Add relationship types to model interface in `models.d.ts`
  - Create description files in `docs/_parts/[model]/[lt|en]/description.md`
  - Update `canUseRoutes` object in index component

### 6. Testing Layer
- [ ] **Feature Tests**:
  - Use `RefreshDatabase` trait
  - Create admin user with domain-appropriate role:
    ```php
    function makeModelAdmin($tenant): User {
        $user = makeUser($tenant);
        $user->duties()->first()->assignRole('Communication Coordinator'); // For duties/content
        return $user;
    }
    ```
  - Test permission scenarios (positive and negative)
  - Test CRUD operations with validation
  - Test translations using `getTranslations()` and `getTranslation()`
  - Test relationships and factories

## Critical Implementation Patterns

### Admin Data Formatting
```php
// ❌ Wrong - returns localized strings
'data' => $models->items()

// ✅ Correct - returns full translation arrays for admin
'data' => $models->getCollection()->map->toFullArray()
```

### Translation Testing
```php
// ❌ Wrong - toArray() returns localized value
expect($model->name)->toBeArray()

// ✅ Correct - use translation methods
expect($model->getTranslations('name'))->toBeArray()
expect($model->getTranslation('name', 'lt'))->toBe('Expected')
```

### Single-Tenant Policy Pattern
```php
// For models that belong to a single tenant
public function view(User $user, Model $model): bool
{
    return $this->commonChecker($user, $model, CRUDEnum::READ()->label, null, false);
}
```

### GetTenantsForUpserts Usage
```php
// ✅ Always include both parameters
GetTenantsForUpserts::execute('models.create.padalinys', $this->authorizer)
```

## Reference Implementation

**Study Programs** is the complete reference example with all patterns implemented correctly:
- Model: `app/Models/StudyProgram.php`
- Policy: `app/Policies/StudyProgramPolicy.php` 
- Controller: `app/Http/Controllers/Admin/StudyProgramController.php`
- Requests: `app/Http/Requests/Store/Update/MergeStudyProgramRequest.php`
- Factory: `database/factories/StudyProgramFactory.php`
- Tests: `tests/Feature/StudyProgramTest.php`

## Quality Checklist

Before completing:
- [ ] All tests pass: `./vendor/bin/sail artisan test`
- [ ] Code follows project patterns
- [ ] Translations work in both admin and public interfaces
- [ ] Permissions properly implemented and tested
- [ ] Factory creates realistic test data
- [ ] Vue components follow Shadcn patterns
- [ ] Routes use correct naming (no "admin." prefix)
- [ ] Admin interface uses `toFullArray()` for forms
- [ ] Model added to ModelEnum if needed

Remember: This is a student-run project - prioritize maintainability and clear patterns over complex solutions.
