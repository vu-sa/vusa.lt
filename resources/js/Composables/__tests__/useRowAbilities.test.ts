import { effectScope, nextTick, ref } from 'vue';
import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useRowAbilities } from '../useRowAbilities';

const mock = vi.hoisted(() => ({ execute: vi.fn(), abort: vi.fn(), queries: [] as { type: string; ids: string[] }[] }));
const payload = ref<Record<string, { update: boolean; delete: boolean }> | null>(null);
const success = ref(true);
vi.mock('@/Composables/useApi', () => ({
  useApi: (url: { value: string }) => ({
    data: payload, isSuccess: success, abort: mock.abort,
    execute: () => mock.execute(url.value),
  }),
}));

function setup(ids: string[]) {
  const visible = ref(ids);
  const scope = effectScope();
  const abilities = scope.run(() => useRowAbilities('institutions', () => visible.value))!;
  return { visible, scope, ...abilities };
}

beforeEach(() => {
  mock.execute.mockReset();
  mock.abort.mockReset();
  mock.queries = [];
  payload.value = null;
  success.value = true;
  vi.stubGlobal('route', (_name: string, query: { type: string; ids: string[] }) => {
    mock.queries.push(query);
    return JSON.stringify(query);
  });
  mock.execute.mockImplementation(async (url: string) => {
    const { ids } = JSON.parse(url) as { ids: string[] };
    payload.value = Object.fromEntries(ids.map(id => [id, { update: true, delete: false }]));
  });
});

describe('useRowAbilities', () => {
  it('hides actions until the batch resolves, then uses only returned decisions', async () => {
    let resolve!: () => void;
    mock.execute.mockImplementation(() => new Promise<void>(done => { resolve = done; }));
    const state = setup(['one', 'denied']);
    expect(state.can('one', 'update')).toBe(false);
    payload.value = { one: { update: true, delete: false } };
    resolve();
    await flushPromises();
    expect(state.can('one', 'update')).toBe(true);
    expect(state.can('one', 'delete')).toBe(false);
    expect(state.can('denied', 'update')).toBe(false);
    state.visible.value = ['denied', 'one'];
    await nextTick();
    expect(mock.execute).toHaveBeenCalledTimes(1);
    state.scope.stop();
  });

  it('caches previous ids and fetches only newly appended rows', async () => {
    const state = setup(['one', 'one']);
    await flushPromises();
    state.visible.value = ['one', 'two'];
    await nextTick();
    await flushPromises();
    expect(mock.queries.map(query => query.ids)).toEqual([['one'], ['two']]);
    state.visible.value = ['one'];
    await nextTick();
    expect(mock.execute).toHaveBeenCalledTimes(2);
    state.scope.stop();
  });

  it('splits visible rows into batches of at most 100', async () => {
    const state = setup(Array.from({ length: 205 }, (_, index) => String(index)));
    await flushPromises();
    expect(mock.queries.map(query => query.ids.length)).toEqual([100, 100, 5]);
    expect(state.can('204', 'update')).toBe(true);
    state.scope.stop();
  });

  it('invalidates selected ids and all cached ids, hiding actions while reloading', async () => {
    const state = setup(['one', 'two']);
    await flushPromises();
    state.invalidate(['one']);
    expect(state.can('one', 'update')).toBe(false);
    expect(state.can('two', 'update')).toBe(true);
    await nextTick();
    await flushPromises();
    expect(mock.queries.at(-1)?.ids).toEqual(['one']);
    state.invalidate();
    await nextTick();
    await flushPromises();
    expect(mock.queries.at(-1)?.ids).toEqual(['one', 'two']);
    state.scope.stop();
  });

  it('discards an in-flight decision invalidated by a mutation', async () => {
    let resolve!: () => void;
    mock.execute.mockImplementationOnce(() => new Promise<void>(done => { resolve = done; }));
    mock.execute.mockImplementation(async () => { payload.value = {}; });
    const state = setup(['one']);
    state.invalidate(['one']);
    payload.value = { one: { update: true, delete: true } };
    resolve();
    await flushPromises();
    expect(mock.execute).toHaveBeenCalledTimes(2);
    expect(state.can('one', 'update')).toBe(false);
    state.scope.stop();
  });

  it('fails closed and retries a failed batch only after invalidation', async () => {
    success.value = false;
    const state = setup(['one']);
    await flushPromises();
    expect(state.can('one', 'update')).toBe(false);
    expect(mock.execute).toHaveBeenCalledTimes(1);
    success.value = true;
    state.invalidate();
    await nextTick();
    await flushPromises();
    expect(state.can('one', 'update')).toBe(true);
    state.scope.stop();
    expect(mock.abort).toHaveBeenCalledOnce();
  });
});


it('keeps rejected transport requests hidden without an unhandled rejection', async () => {
  mock.execute.mockRejectedValueOnce(new Error('Offline'));
  const state = setup(['one']);
  await flushPromises();
  expect(state.can('one', 'update')).toBe(false);
  expect(mock.execute).toHaveBeenCalledTimes(1);
  state.scope.stop();
});
