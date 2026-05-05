# JavaScript Testing Conventions

This project uses **Vitest** with **@vue/test-utils** for component and unit testing.

## Test Location

Place tests in `__tests__/` directories adjacent to the source files they test:

```
resources/js/Components/AdminForms/
├── AdminForm.vue
└── __tests__/
    └── AdminForm.component.test.ts
```

Shared test helpers go in `resources/js/tests/helpers/`.
Shared component stubs go in `resources/js/tests/stubs/`.

## Stubbing Policy

Follow the **"real components by default"** rule:

| Component Type | Stub? | Reason |
|---------------|-------|--------|
| Dialog (Dialog, DialogContent, etc.) | **Yes** | Focus traps and Teleport behave unpredictably in jsdom |
| Tooltip (Tooltip, TooltipContent, etc.) | **Yes** | Popper positioning fails in jsdom |
| Icons (IFluent*, etc.) | **Yes** | Reduces noise; auto-imported by unplugin-icons |
| Button, Label, Input, Select | **No** | Render fine in jsdom; test real DOM behavior |
| Complex third-party (TiptapEditor, SortableTable) | **Yes** | Heavy DOM manipulation or browser APIs |

When in doubt, try rendering the real component first. Only stub if the test fails due to jsdom limitations.

### Shared stub registry

Import `commonStubs` from `@/tests/stubs` instead of redefining Dialog/Tooltip stubs in every test:

```typescript
import { commonStubs } from '@/tests/stubs';

mount(MyComponent, {
  global: {
    stubs: {
      ...commonStubs,
      // Add test-specific stubs below
      IFluentSave24Filled: { template: '<span class="icon-save" />' },
    },
  },
});
```

## Mock Inertia Forms

Use `createMockForm()` for components that expect an Inertia `useForm` instance:

```typescript
import { createMockForm } from '@/tests/helpers/createMockForm';

const form = createMockForm({ name: 'Test' });
form.processing = true;  // Fully reactive — Vue picks up the change
```

## Mock Inertia Page Data

Use `createMockPage()` to customize `usePage()` return values without re-mocking the module:

```typescript
import { createMockPage } from '@/tests/helpers/createMockPage';
import { usePage } from '@inertiajs/vue3';

vi.mocked(usePage).mockReturnValue(
  createMockPage({ app: { path: '/mano/forms/create' } })
);
```

This deep-merges your overrides with the default mock page props.

## Navigation Guard Testing

The global `inertia.mock.ts` provides `router.__triggerBefore(event)` for testing Inertia `before` event listeners:

```typescript
import { router } from '@inertiajs/vue3';

const event = { detail: { visit: { prefetch: false } }, preventDefault: vi.fn() };
(router as any).__triggerBefore(event);
expect(event.preventDefault).toHaveBeenCalled();
```

## Type Safety

Always type `wrapper` with `ReturnType<typeof mount>` instead of `any`:

```typescript
import { mount } from '@vue/test-utils';

let wrapper: ReturnType<typeof mount>;
```

## Running Tests

```bash
# Component tests only
vendor/bin/sail npx vitest run --project component

# Specific component test file
vendor/bin/sail npx vitest run --project component resources/js/Components/AdminForms/__tests__/AdminForm.component.test.ts

# Unit tests
vendor/bin/sail npx vitest run --project unit
```

## Key Principles

1. **Test inputs and outputs**, not implementation details — focus on props, user interactions, and rendered DOM
2. **Interact through the DOM** — use `trigger('click')`, `setValue()`, etc. rather than calling component methods directly (except when DOM event bubbling through stubs is unreliable)
3. **Minimize stubs** — the more real components you render, the more confidence your tests give you
4. **Use fake timers** for debounced behavior: `vi.useFakeTimers()` + `vi.advanceTimersByTime(5000)`
5. **Avoid `wrapper: any`** — use `ReturnType<typeof mount>` for type safety and autocomplete
6. **Reuse shared stubs and helpers** — don't redefine Dialog/Tooltip stubs or page mocks in every test file
