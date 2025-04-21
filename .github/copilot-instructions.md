# GitHub Copilot Instructions for vusa.lt

## Application Purpose
- **Public Website**: VU Students' Representation information
- **Internal System**: Student representation management platform (meetings, activities, tasks, resources)

**Tech Stack**: Laravel 12+, Vue 3, Inertia.js, Tailwind CSS, Shadcn Vue, MySQL

## Core Patterns

| ✅ DO                                        | ❌ DON'T                                    |
|---------------------------------------------|---------------------------------------------|
| Use Laravel's built-in features             | Create custom implementations unnecessarily  |
| Follow existing component patterns          | Introduce new UI patterns without need      |
| Support both lt/en languages                | Hardcode language strings                   |
| Use Shadcn Vue for UI components            | Mix UI library styles                       |
| Reuse components                            | Create one-off specialized components       |

## Model & Policy Patterns

```php
// Model pattern
class ExampleModel extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;
    
    protected $translatable = ['name', 'description'];
    
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}

// Policy pattern
class ExamplePolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::EXAMPLE()->label);
    }
}
```

## UI Components Architecture

The project uses **Reka UI** as the base component library wrapped with **Shadcn Vue** styling patterns:

1. **Base Component Pattern**:
```vue
<script setup lang="ts">
import { SomeRekaComponent, type SomeComponentProps } from 'reka-ui'
import { cn } from '@/Utils/Shadcn/utils'

const props = defineProps<SomeComponentProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <SomeRekaComponent
    data-slot="component-name"
    v-bind="props"
    :class="cn('tailwind-styling-classes', props.class)"
  >
    <slot />
  </SomeRekaComponent>
</template>
```

2. **Component Directories**:
   - `/resources/js/Components/ui/` - Base UI components (button, dialog, select, etc.)
   - `/resources/js/Components/Layouts/` - Page layout templates
   - `/resources/js/Components/FormItems/` - Form inputs with multilingual support
   - `/resources/js/Components/AdminForms/` - Model-specific admin forms
   - `/resources/js/Components/Public/` - Public-facing website components
   - `/resources/js/Components/RichContent/` - Rich content editing components
   - `/resources/js/Components/Tables/` - Data table implementations
   - `/resources/js/Components/Buttons/` - Specialized button components

## Vue Component Pattern
```vue
<template>
  <div>
    <h1>{{ $t("Page Title") }}</h1>
    <MultiLocaleInput v-model:input="form.name" />
  </div>
</template>
```

## Key Architecture

### 1. Permission System
- Format: `{resource}.{action}.{scope}` (e.g., `news.update.padalinys`)
- Hierarchy: `own` < `padalinys` < `all`
- Use: `$this->authorize('update', $model)` or `Permission::check('resource.action.scope')`

### 2. Internationalization
- Support both lt/en languages
- Use `MultiLocaleInput` or `MultiLocaleTiptapFormItem` for translatable fields
- Models with translations use `HasTranslations` trait and `$translatable` property

### 3. Form Validation
- Use Form Request classes for complex validation
- Return localized validation errors
- Use Inertia flash messaging for user feedback

### 4. State Management
- Follow existing State pattern for workflows
- Define clear state transitions and validation

## Testing
- Use database transactions in tests
- Test different permission scenarios with `asUser()`
- Test both positive and negative permission cases
- Mock external services
- Test component interactions and state changes
- Create separate test classes for complex features
- Focus on critical user workflows

## Accessibility
- Use semantic HTML elements (nav, main, section, article)
- Include proper ARIA attributes when needed
- Ensure keyboard navigability (focus states, tab order)
- Provide sufficient color contrast
- Add descriptive alt text for images
- Test with screen readers periodically
- Support text resizing

## Working with Larger Components
- Break complex features into smaller, reusable components
- Plan features by mapping data flow and state requirements first
- Use async components for performance: `defineAsyncComponent(() => import('./Component'))`
- Document component props and events
- Limit component responsibilities (single purpose)
- Implement error boundaries for isolated failures

## Performance Best Practices
1. Prevent N+1 queries with eager loading
2. Cache frequently accessed data
3. Use code splitting with `defineAsyncComponent`
4. Optimize images with lazy loading

## Project Structure
- `app/` - Laravel application code
- `resources/js/` - Vue components and frontend code
- `resources/css/` - Tailwind CSS files
- `lang/` - Localization files
- `tests/` - Test files

## Remember
This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.