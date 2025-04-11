# Authorization System Documentation

This document explains the authorization system implemented in the application, which focuses on tenant-based permissions and role-based access control.

## Overview

The authorization system is designed with the following key components:

1. **ModelAuthorizer Service**: Core service that handles permission checks with caching
2. **Permission Facade**: Provides a clean interface for checking permissions throughout the application
3. **HasCommonChecks Trait**: Shared policy logic to reduce code duplication
4. **ModelPolicy Base Class**: Common policy functionality shared across all model policies
5. **TenantPermission Middleware**: For protecting routes based on permission requirements

## Permission Structure

Permissions follow a standardized format: `resource.action.scope`

- **Resource**: The plural name of the model (e.g., `news`, `users`, `documents`)
- **Action**: The operation being performed (`read`, `create`, `update`, `delete`)
- **Scope**: The context of the permission:
  - `all`: Global access to all resources of this type
  - `own`: Access only to resources directly associated with the user/duties
  - `padalinys`: Access to resources associated with the user's tenant

## Authorization Flow

1. **Super Admin Check**: Super admins automatically have all permissions
2. **Direct User Permission Check**: Check if user has been directly assigned the permission
3. **Duty-Based Permission Check**: Check permissions through user's duties and their roles
4. **Scope Evaluation**: 
   - For `all` scope: Access is granted to all resources
   - For `own` scope: Access is limited to resources associated with user's duties
   - For `padalinys` scope: Access is limited to resources within user's tenant

## Using the Permission System

### In Controllers

```php
// Using the Permission facade
if (Permission::check('news.create.padalinys')) {
    // User can create news for their tenant
}

// Or using Laravel's built-in authorization
$this->authorize('create', News::class);
$this->authorize('update', $newsArticle);
```

### In Routes

```php
// Protect routes with the tenant.permission middleware
Route::get('/admin/news', [NewsController::class, 'index'])
    ->middleware('tenant.permission:news.read.padalinys');
```

### In Blade Templates

```blade
@can('update', $newsArticle)
    <a href="{{ route('news.edit', $newsArticle) }}">Edit</a>
@endcan
```

### In Vue Components

```javascript
// The permission is available in the auth.can object
<button v-if="$page.props.auth.can.update.news">Edit</button>
```

## Policy Implementation

Policies now use the ModelPolicy base class and the HasCommonChecks trait to reduce code duplication.

To implement a new policy:

```php
class ExamplePolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::EXAMPLE()->label);
    }
    
    // Only override methods that need custom logic
    public function view(User $user, Example $example)
    {
        // Custom logic here if needed
        if ($this->commonChecker($user, $example, 'read', $this->pluralModelName)) {
            return true;
        }
        
        return false;
    }
}
```

## Cache Invalidation

The permission cache is automatically invalidated when:
- User roles change
- User duties change
- Role permissions change
- Duty roles change

## Testing

The system includes comprehensive tests in `tests/Unit/AuthorizationTest.php`. Run the tests with:

```bash
php artisan test --filter=AuthorizationTest
```

## Best Practices

1. **Use ModelPolicy**: Extend ModelPolicy for all new policies
2. **Be Specific with Scopes**: Use the most restrictive scope that meets your requirements
3. **Check for Permission in Routes**: Use the tenant.permission middleware to protect routes
4. **Test Policy Changes**: Update the AuthorizationTest when making significant policy changes
5. **Use Permission Facade**: When checking permissions directly, use the Permission facade instead of direct ModelAuthorizer usage