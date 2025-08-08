# Controllers Documentation

This document provides guidelines for working with controllers in the application.

## AdminControllerInterface

All admin controllers should implement `AdminControllerInterface` to ensure consistent behavior and proper return types.

**See**: @app/Contracts/AdminControllerInterface.php

### Standard Admin Controller Methods

```php
public function index(Request $request): InertiaResponse;    // Display listing
public function create(): InertiaResponse;                  // Show create form
public function store(Request $request): RedirectResponse;  // Store new resource
public function show(mixed $model): InertiaResponse;        // Display resource
public function edit(mixed $model): InertiaResponse;        // Show edit form  
public function update(Request $request, mixed $model): RedirectResponse; // Update resource
public function destroy(mixed $model): RedirectResponse;    // Delete resource
```

## Authorization in Controllers

### Using Permission Checks

```php
// Using the Permission facade
if (Permission::check('news.create.padalinys')) {
    // User can create news for their tenant
}

// Using Laravel's built-in authorization
$this->authorize('create', News::class);
$this->authorize('update', $newsArticle);
```

### Policy Implementation

Controllers rely on policies that extend `ModelPolicy` base class:

```php
class ExamplePolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::EXAMPLE()->label);
    }
    
    // Override methods only when custom logic is needed
    public function view(User $user, Example $example)
    {
        if ($this->commonChecker($user, $example, 'read', $this->pluralModelName)) {
            return true;
        }
        
        return false;
    }
}
```

### Route Protection

Protect routes using the `tenant.permission` middleware:

```php
Route::get('/admin/news', [NewsController::class, 'index'])
    ->middleware('tenant.permission:news.read.padalinys');
```

## Controller Best Practices

1. **Implement AdminControllerInterface** for all admin controllers
2. **Use Permission facade** for authorization checks
3. **Follow consistent return types** as defined in the interface
4. **Use Form Request classes** for complex validation
5. **Return proper Inertia responses** for views and redirects for actions
