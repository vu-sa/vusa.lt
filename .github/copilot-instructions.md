# GitHub Copilot Instructions for vusa.lt

## Application Purpose & Overview

vusa.lt serves as:
1. **Public Website**: Information about VU Students' Representation
2. **Internal System**: Student representation management platform that handles:
   - Meeting management with university administration
   - Student representation activities tracking
   - Goal and task management
   - Opinion collection and representation outcomes
   - Organization physical and human resource management

Key technologies: Laravel 12+, Vue 3, Inertia.js, Tailwind CSS, Shadcn Vue (based on Reka UI), MySQL

## Core Development Patterns

| ✅ DO                                           | ❌ DON'T                                      |
|------------------------------------------------|-----------------------------------------------|
| Use Laravel's built-in features when available | Create custom implementations of core features |
| Follow existing component patterns             | Introduce new UI patterns without need        |
| Support both lt/en languages                   | Hardcode language strings                     |
| Use Shadcn Vue for new UI components           | Mix UI library styles                         |
| Reuse components rather than duplicate code    | Create one-off specialized components         |

## Required Patterns by Task

### When Creating New Models
```php
class ExampleModel extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;
    
    protected $translatable = ['name', 'description'];
    
    // Always define relationships with proper return types
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
```

### When Creating New Policies
```php
class ExamplePolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::EXAMPLE()->label);
    }
}
```

### When Creating Localized Components
```vue
<template>
  <div>
    <!-- Always use $t for user-visible text -->
    <h1>{{ $t("Page Title") }}</h1>
    
    <!-- Use MultiLocaleInput for translatable fields -->
    <MultiLocaleInput v-model:input="form.name" />
  </div>
</template>
```

## Key System Architecture

### 1. Permission System

**Permission Hierarchy**: `own` < `padalinys` < `all`

Permission format: `{resource}.{action}.{scope}`
- resource: plural model name (e.g., `news`, `events`)
- action: `create`, `read`, `update`, `delete`
- scope: `own`, `padalinys`, `*` (all)

**Permission Check Implementation**:
```php
// CORRECT: Using ModelPolicy
$this->authorize('update', $newsArticle);

// CORRECT: Using Permission facade
Permission::check('news.update.padalinys')

// INCORRECT: Direct check without scope
$user->can('update', $newsArticle)
```

### 2. Internationalization

- All user-facing text must support both Lithuanian (lt) and English (en)
- User multilingual form inputs with `MultiLocaleInput` or `MultiLocaleTiptapFormItem`
- Models with translatable fields use `HasTranslations` trait with `$translatable` property
- Some content (Pages, News) has separate records per language with relationships

### 3. Form Validation & Error Handling

- Use Form Request classes for complex validation
- Return validation errors with clear, localized messages
- Use Inertia flash messaging system for user feedback
- Handle API failures with appropriate fallback behaviors

### 4. State Management for Workflows

- Follow existing State pattern for models with workflows
- Define clear state transitions and validation in state classes
- Ensure proper auditing of state changes
- Use consistent UI patterns for state representation

## Code Quality Requirements

### Performance Best Practices
1. Prevent N+1 queries with eager loading (`with()` method)
2. Cache frequently accessed data
3. Use code splitting with `defineAsyncComponent`
4. Optimize images with proper lazy loading

### Testing Requirements
1. Use database transactions in tests
2. Test with different user permission scenarios using `asUser()`
3. Mock external services and APIs
4. Test both allowed and denied permission scenarios

### Accessibility & SEO
1. Use semantic HTML elements
2. Add alt text for images
3. Implement proper meta tags
4. Ensure keyboard navigability
5. Meet WCAG 2.1 AA standards

## Project Structure Quick Reference

- `app/` - Laravel application code
- `resources/js/` - Vue components and frontend code
- `resources/css/` - Tailwind CSS files
- `lang/` - Localization files
- `docs/` - User documentation (VitePress)
- `tests/` - Test files

## Remember

This is a **student-run project** with limited resources. Prioritize maintainability and approachability over complex solutions.

When adding dependencies, consider:
- Long-term maintenance status
- Performance impact
- Learning curve for future contributors