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

## What CI actually runs

Pull requests run `vitest --changed origin/<base>`: only specs whose dependency graph reaches a
changed file. **A green PR is not a full run.** Pushes to `main` run everything — the same contract
Pest TIA gives the PHP suite, and the same backstop.

Escalation back to a full run is driven by `forceRerunTriggers` in `vitest.config.ts`
(`package.json`, `vitest.config.*`, `tests/setup.ts`, `resources/js/mocks/**`). If you add another
file that every spec depends on implicitly, add it there — the import graph cannot attribute a
setup file to any individual test.

## Never re-mock a shared module with a bespoke factory

`vi.mock('@inertiajs/vue3', () => ({ ...actual, usePage: () => ({ props: myProps }) }))` bakes one
file's page props into the module. It works today only because each test file gets a private module
registry — which is exactly what stops the suite from running with `isolate: false` (measured at
~9s instead of ~41s). 73 of 227 files currently mock a module that another file also mocks.

Use the one shared factory plus per-test state instead:

```typescript
vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// then, per test:
vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'lt' } }));
```

## Key Principles

1. **Test inputs and outputs**, not implementation details — focus on props, user interactions, and rendered DOM
2. **Interact through the DOM** — use `trigger('click')`, `setValue()`, etc. rather than calling component methods directly (except when DOM event bubbling through stubs is unreliable)
3. **Minimize stubs** — the more real components you render, the more confidence your tests give you
4. **Use fake timers** for debounced behavior: `vi.useFakeTimers()` + `vi.advanceTimersByTime(5000)`
5. **Avoid `wrapper: any`** — use `ReturnType<typeof mount>` for type safety and autocomplete
6. **Reuse shared stubs and helpers** — don't redefine Dialog/Tooltip stubs or page mocks in every test file
