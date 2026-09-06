# Storybook — AI Guidance

For full configuration, story-writing, and Playwright setup, see [README.md](README.md). This file covers what to do (and what to avoid) when writing stories.

## What Storybook is for here

It is the **catalogue** for the design system: the place you check before building a new
component, and the only place the rendered result can be verified in both themes. The public
design revamp (see `REDESIGN.md` and `~/.claude/plans/`) rebuilt it around that job — the old
design-surface stories were deleted so the catalogue shows one design language, not two.

## Surface and theme — read this before writing a story

Every story renders under a **Surface** (`public` / `admin`) and a **Theme** (`light` / `dark`)
selected from the toolbar. The decorator in `preview.ts` stamps `data-surface` and `.dark` on the
preview iframe's `<html>`, mirroring `app.blade.php` and the FOUC script in production, so a story
resolves the same design tokens the real page does.

Surface defaults to `public`. An admin component must pin it, or it renders against the wrong
palette:

```typescript
const meta: Meta<typeof AdminThing> = {
  component: AdminThing,
  globals: { surface: 'admin' },
};
```

Colours must come from tokens (`bg-card`, `text-muted-foreground`, `border-border`, `text-brand`)
for the toolbar to mean anything — a hardcoded `bg-white dark:bg-zinc-900` looks identical on both
surfaces and defeats the check.

**`--brand`, not `--accent`.** `--accent` is shadcn's hover/muted surface (`hover:bg-accent` on
ghost buttons, `focus:bg-accent` on dropdown items). The VU SA brand colour — red on light, amber
on dark — is `--brand` / `--brand-fill` / `--brand-foreground`, i.e. `text-brand`, `bg-brand-fill`.

## Accessibility

`preview.ts` sets `a11y.test: 'todo'` globally — violations are reported, not failed, because
legacy components have pre-existing ones. New primitives under `Components/Public/Base/**` opt up
in their own meta, since they carry no such debt:

```typescript
parameters: { a11y: { test: 'error' } },
```

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
- `i18n.ts` — `trans`, `transChoice`, `$t`, `getActiveLanguage`, `loadLanguageAsync`, `i18nVue` (uses real translations from `lang/*.json`)
- `route.ts` — `route()` returning predictable mock URLs

`$t()` and `route()` are registered globally in `.storybook/preview.ts` — you don't import them in stories.

`.storybook/main.ts` aliases both `@inertiajs/vue3` and `laravel-vue-i18n` to those mocks. The
i18n alias is what makes stories render real copy: most components do
`import { trans as $t } from 'laravel-vue-i18n'`, and a `<script setup>` binding **shadows the
global of the same name** that `preview.ts` installs — so without the alias they resolve to the
real package (which has no plugin installed in Storybook) and render raw keys like
`navigation.builder.edit_mode`. That also wrecks layout review, since an untranslated key is a
long unbreakable string. If a component imports something new from `laravel-vue-i18n`, add it to
`resources/js/mocks/i18n.ts` or the story will fail to resolve it.

Stories render in **Lithuanian** — the mock only bundles the `lt` catalogue.

Override per-story via mock methods:

```typescript
import { usePage } from '@/mocks/inertia.storybook';

usePage.mockImplementation(() => ({
  props: {
    auth: { user: { id: 1, name: 'Test User' }, can: { create: { meeting: true } } },
    flash: { success: 'Operation successful' },
  },
}));
```

**Always import the full filename** — `@/mocks/inertia.storybook`, never `@/mocks/inertia`.

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
npm run test:storybook  # Storybook tests only — needs Playwright chromium
npm run test:all        # includes browser tests
npm run storybook       # interactive UI
```

Playwright's chromium must be installed **inside the sail container** (the host cache is not
visible to it): run `./dev/storybook-setup.sh` once.

CI runs these in the `storybook-tests` job, currently `continue-on-error: true` while the public
design system is built out. `coverage:ci` — what `js-tests` runs — deliberately excludes the
storybook project, so this job is the only thing exercising stories.
