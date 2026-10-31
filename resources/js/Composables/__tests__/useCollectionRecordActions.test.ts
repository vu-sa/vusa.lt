import { beforeEach, describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

import { useCollectionRecordActions } from '../useCollectionRecordActions';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('useCollectionRecordActions', () => {
  it('describes open and edit separately and keeps destructive actions out of the primary slot', () => {
    const actions = useCollectionRecordActions({ routePrefix: 'goals', source: { refresh: vi.fn() }, openHref: id => `/mano/goals/${id}` });
    expect(actions.rowActions({ id: 'g1' }, 'Tikslas', false)).toEqual([
      expect.objectContaining({ role: 'open', href: '/mano/goals/g1' }),
      expect.objectContaining({ role: 'edit' }),
      expect.objectContaining({ destructive: true }),
    ]);
    expect(actions.rowActions({ id: 'g1' }, 'Tikslas', true)[0].primary).toBe(false);
  });

  it('labels restoration when permanent deletion is unavailable', () => {
    const actions = useCollectionRecordActions({ routePrefix: 'goals', source: { refresh: vi.fn() }, canForceDelete: () => false });
    expect(actions.rowActions({ id: 'g1' }, 'Tikslas', true)).toEqual([expect.objectContaining({ primary: true })]);
  });

  beforeEach(() => vi.mocked(router.delete).mockClear());

  it.each(['delete', 'forceDelete'])('refreshes only after %s succeeds', (kind) => {
    const onSuccess = vi.fn();
    const actions = useCollectionRecordActions({ routePrefix: 'institutions', source: { refresh: onSuccess } });

    actions.select(`${kind}:i1:Senatas`);
    actions.confirm();

    expect(onSuccess).not.toHaveBeenCalled();
    vi.mocked(router.delete).mock.calls.at(-1)?.[1].onSuccess();
    expect(onSuccess).toHaveBeenCalledOnce();
  });

  it('does not send a blocked permanent deletion or refresh the list', () => {
    const onSuccess = vi.fn();
    const actions = useCollectionRecordActions({ routePrefix: 'institutions', source: { refresh: onSuccess } });

    actions.select('forceDelete:i1:Senatas', 'Has related records');
    actions.confirm();

    expect(router.delete).not.toHaveBeenCalled();
    expect(onSuccess).not.toHaveBeenCalled();
  });

  it.each([false, true])('refreshes restored rows unless the record opens (%s)', (restoreOpensRecord) => {
    const onSuccess = vi.fn();
    const actions = useCollectionRecordActions({ routePrefix: 'institutions', source: { refresh: onSuccess }, restoreOpensRecord });

    actions.select('restore:i1');
    const options = vi.mocked(router.patch).mock.calls.at(-1)?.[2];
    options.onSuccess?.();

    expect(onSuccess).toHaveBeenCalledTimes(restoreOpensRecord ? 0 : 1);
  });
});

it('invalidates only after a successful row mutation', () => {
  const invalidate = vi.fn();
  const actions = useCollectionRecordActions({ routePrefix: 'institutions', source: { refresh: vi.fn() }, onMutated: invalidate });
  actions.select('delete:i1:Senatas');
  actions.confirm();
  expect(invalidate).not.toHaveBeenCalled();
  vi.mocked(router.delete).mock.calls.at(-1)?.[1].onSuccess();
  expect(invalidate).toHaveBeenCalledWith('i1');
});

it('does not promise restoration for records deleted permanently', () => {
  const actions = useCollectionRecordActions({ routePrefix: 'responsibilities', source: { refresh: vi.fn() }, deletePermanently: true });
  actions.select('delete:r1:Atsakomybė');
  expect(actions.dialog.value?.description).toBe('Šis veiksmas negrįžtamas.');
});
