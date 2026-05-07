# Storybook — AI Guidance

For full configuration, story-writing, and Playwright setup, see [README.md](README.md). This file covers what to do (and what to avoid) when writing stories.

## When to use Storybook

- Visual components (buttons, modals, cards) and component documentation.
- User-flow tests that need a real browser environment.
- Accessibility checks via the a11y addon.

**Don't** use Storybook for services, composables, or utilities — those go in unit/component tests under Vitest. See the decision tree:

```
Visual appearance      → Storybook story
User interactions      → Storybook story with `play` function
Business logic         → Unit test (*.test.ts)
Component API          → Component test (*.component.test.ts)
```

## Mocks

Mocks live in `resources/js/mocks/` — **not** in `.storybook/mocks/`. Available:

- `inertia.mock.ts` — `usePage`, `router`, `useForm`
- `i18n.ts` — `trans`, `transChoice`, `$t` (uses real translations from `lang/*.json`)
- `route.ts` — `route()` returning predictable mock URLs

`$t()` and `route()` are registered globally in `.storybook/preview.ts` — you don't import them in stories.

Override per-story via mock methods:

```typescript
import { usePage } from '@/mocks/inertia.mock';

usePage.mockImplementation(() => ({
  props: {
    auth: { user: { id: 1, name: 'Test User' }, can: { create: { meeting: true } } },
    flash: { success: 'Operation successful' },
  },
}));
```

**Always import the full filename** — `@/mocks/inertia.mock`, never `@/mocks/inertia`.

## Story patterns

```typescript
import type { Meta, StoryObj } from '@storybook/vue3-vite';
import ComponentName from './ComponentName.vue';

const meta: Meta<typeof ComponentName> = {
  title: 'Components/ComponentName',
  component: ComponentName,
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = { args: { title: 'Example' } };
```

Interactive variant:

```typescript
import { userEvent, within } from 'storybook/test';

export const Interactive: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    await userEvent.click(canvas.getByRole('button'));
    await canvas.findByText('Expected result');
  },
};
```

Clear mocks between stories: `beforeEach(() => vi.clearAllMocks())`.

## Common gotchas

- **Checkbox**: bind via `v-model` / `model-value`, never `v-model:checked`.
- **Top-level `await` in components**: stalls the story. Accept data as a prop with a fallback fetch in `onMounted` instead.
- **"Failed to resolve import '@/mocks/...'"**: aliases in `.storybook/main.ts` must match `vite.config.mts` exactly.
- **"Tests failing with undefined globals"**: use `globalThis.route = route`, not `global.route`.
- **Browser tests not running**: install Playwright browsers with `npx playwright install`.

## Component design for testability

- Accept data via props with sensible defaults.
- Avoid top-level `await`.
- Inject side-effectful dependencies (`onSave`, `fetcher`) so tests can stub them.
- Render explicit loading and error states.

## Running

```bash
npm run test            # daily (skips browser tests)
npm run test:storybook  # Storybook tests only
npm run test:all        # includes browser tests
npm run storybook       # interactive UI
```
