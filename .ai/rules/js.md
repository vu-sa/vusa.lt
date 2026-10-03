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

Why: bespoke factories are the only thing blocking `isolate: false` in vitest.config.ts, which measured ~9s vs ~41s for the suite under the old `forks` pool (now ~13s under `vmThreads`, so the remaining gain is smaller). With a shared module registry each module binds to whichever factory reached it first, so failures move around with --maxWorkers and CI disagrees with a laptop. 73 of 227 spec files currently mock a module another file also mocks (43 of them `@inertiajs/vue3`); converting them is the unlock.

## Specs must run under pool: 'vmThreads'
The unit/component projects run with `pool: 'vmThreads'`: each file gets its own VM context over the real jsdom window, not Node's global with jsdom copied onto it.
- Never `Object.defineProperty(window, 'location', …)` or `vi.stubGlobal('location', …)` — jsdom's Location is non-configurable there. Set the URL with `jsdom.reconfigure({ url })`, and keep navigation code testable by returning the URL from a function (see `tenantSwitchUrl` in useTenantOptions).
- Don't construct a second `new JSDOM()` in a spec; use the environment's `document`.
- Node-only globals (web streams, full `performance` timeline) may be missing; polyfill in tests/setup.ts, not per file.
- tests/setup.ts awaits `vi.dynamicImportSettled()` after each file, since pending async-component imports otherwise reject with "Vite module runner has been closed". Don't remove it.
Why: ~13s vs ~38s for the suite, with per-file mock isolation kept (unlike `isolate: false`).
