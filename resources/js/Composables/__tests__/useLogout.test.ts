import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { effectScope, type EffectScope } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import { useLogout } from '../useLogout';
import { useAdminSearch } from '../useAdminSearch';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

let scope: EffectScope;
let userId = 20000;
const config = () => ({
  headerKey: 'transport-key',
  collections: { institutions: { name: 'institutions', key: 'collection-key', hasAccess: true } },
  nodes: [{ host: 'typesense', port: 8108, protocol: 'http' }],
  expiresAt: Date.now() / 1000 + 900,
});

beforeEach(() => {
  scope = effectScope();
  vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: { id: userId++ } } }));
  vi.stubGlobal('route', vi.fn((name: string) => `/${name}`));
});

afterEach(() => {
  scope.stop();
  vi.unstubAllGlobals();
});

it.each(['logout', 'logoutMicrosoft'] as const)('clears shared search credentials when %s starts', async (action) => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => config() }));
  const search = scope.run(() => useAdminSearch())!;
  await search.initialize();
  expect(search.config.value?.headerKey).toBe('transport-key');

  useLogout()[action]();

  expect(search.config.value).toBeNull();
  expect(search.isReady.value).toBe(false);
  expect(vi.mocked(router.post).mock.calls.at(-1)?.[0]).toBe(action === 'logout' ? '/logout' : '/logout.microsoft');
});

it('does not restore credentials from a request that finishes after logout', async () => {
  let resolve!: (response: { ok: boolean; json: () => Promise<ReturnType<typeof config>> }) => void;
  vi.stubGlobal('fetch', vi.fn(() => new Promise(response => { resolve = response; })));
  const search = scope.run(() => useAdminSearch())!;
  const pending = search.initialize();
  const rejected = expect(pending).rejects.toMatchObject({ name: 'AbortError' });

  useLogout().logoutMicrosoft();
  resolve({ ok: true, json: async () => config() });
  await rejected;

  expect(search.config.value).toBeNull();
});
