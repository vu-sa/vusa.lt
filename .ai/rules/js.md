---
paths:
  - 'resources/js/**/*.test.ts'
---

# Js

## Mock shared modules through one shared factory, never a bespoke one
Never write `vi.mock('@inertiajs/vue3', async () => ({ ...await vi.importActual(...), usePage: () => ({ props: myProps }) }))`. A per-file factory bakes that file's state into the module.

Use the shared factory plus per-test state instead:
  vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
  vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'lt' } }));

Why: bespoke factories are the only thing blocking `isolate: false` in vitest.config.ts, which measured ~9s vs ~41s for the suite. With a shared module registry each module binds to whichever factory reached it first, so failures move around with --maxWorkers and CI disagrees with a laptop. 73 of 227 spec files currently mock a module another file also mocks (43 of them `@inertiajs/vue3`); converting them is the unlock.
