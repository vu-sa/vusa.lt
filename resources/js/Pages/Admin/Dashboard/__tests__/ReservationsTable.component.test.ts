import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import ReservationsTable from '@/Pages/Admin/Dashboard/Partials/ReservationsTable.vue';
import { commonStubs } from '@/tests/stubs';
import type { DashboardReservation, ReservationPivot, ReservationResourceState } from '@/Utils/ReservationStatus';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string) => `/mocked/${name}`);

function pivot(
  id: string,
  state: ReservationResourceState,
  overrides: Partial<ReservationPivot> = {},
): ReservationPivot {
  return {
    id,
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    returned_at: null,
    updated_at: '2026-07-01T10:00:00Z',
    quantity: 1,
    state,
    state_properties: { tagType: 'info', description: '' },
    approvable: true,
    cancellable: false,
    ...overrides,
  };
}

function reservation(id: string, pivots: ReservationPivot[], name = 'Fresher\'s camp'): DashboardReservation {
  return {
    id,
    name,
    description: null,
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    created_at: '2026-07-01T10:00:00Z',
    users: [{ id: 1, name: 'Gabija P.' }] as unknown as App.Entities.User[],
    resources: pivots.map((p, index) => ({
      id: `resource-${p.id}`,
      name: `Resource ${index}`,
      tenant: { id: 'tenant-1', shortname: 'VU SA MIF' },
      pivot: p,
    })),
  };
}

function createWrapper(reservations: DashboardReservation[], mode: 'mine' | 'administered' = 'administered') {
  return mount(ReservationsTable, {
    props: { reservations, mode },
    global: { stubs: { ...commonStubs } },
  });
}

const lastPost = () => vi.mocked(router.post).mock.calls.at(-1);

/**
 * laravel-vue-i18n is not booted under test, so $t echoes the key back. Asserting on keys is also
 * what we want: the tests then survive copy changes.
 */
const APPROVE = 'reservations.actions.approve';
const HAND_OVER = 'reservations.actions.hand_over';
const MARK_RETURNED = 'reservations.actions.mark_returned';
const REJECT = 'reservations.actions.reject';
const CANCEL_RESERVATION = 'reservations.actions.cancel_reservation';
const RESOLVE = 'reservations.actions.resolve';

/** Buttons in the table body, excluding the confirmation dialog (which reuses the same labels). */
const findRowButton = (wrapper: ReturnType<typeof mount>, key: string) =>
  wrapper.findAll('button')
    .filter(button => button.element.closest('[role="dialog"]') === null)
    .find(button => button.text().includes(key));

const dialog = (wrapper: ReturnType<typeof mount>) => wrapper.find('[role="dialog"]');

const confirmDialog = async (wrapper: ReturnType<typeof mount>, key: string) => {
  const confirm = dialog(wrapper).findAll('button').find(button => button.text().includes(key));
  expect(confirm, `dialog confirm button for ${key}`).toBeDefined();
  await confirm!.trigger('click');
};

/** Row action → confirmation dialog → confirm. Nothing is submitted before the confirm. */
const act = async (wrapper: ReturnType<typeof mount>, rowKey: string, confirmKey = rowKey) => {
  const button = findRowButton(wrapper, rowKey);
  expect(button, `row button for ${rowKey}`).toBeDefined();

  await button!.trigger('click');
  await confirmDialog(wrapper, confirmKey);
};

beforeEach(() => {
  vi.mocked(router.post).mockClear();
});

describe('row actions', () => {
  it('confirms before submitting, rather than acting on a single click', async () => {
    const wrapper = createWrapper([reservation('res-1', [pivot('p-created', 'created')])]);

    await findRowButton(wrapper, APPROVE)!.trigger('click');

    // The dialog is open and nothing has been sent yet.
    expect(dialog(wrapper).exists()).toBe(true);
    expect(router.post).not.toHaveBeenCalled();

    await confirmDialog(wrapper, APPROVE);
    expect(router.post).toHaveBeenCalledOnce();
  });

  it('labels the action by state and submits only that state\'s pivots', async () => {
    // Pending outranks lent, so the button must approve the pending item and leave the loan alone.
    const wrapper = createWrapper([
      reservation('res-1', [pivot('p-created', 'created'), pivot('p-lent', 'lent')]),
    ]);

    await act(wrapper, APPROVE);

    const [url, payload] = lastPost()!;
    expect(url).toBe('/mocked/approvals.bulkStore');
    expect(payload).toMatchObject({
      approvable_type: 'reservation_resource',
      approvable_ids: ['p-created'],
      decision: 'approved',
    });
  });

  it('relabels to hand over once nothing is pending', () => {
    const wrapper = createWrapper([reservation('res-1', [pivot('p-1', 'reserved')])]);

    expect(findRowButton(wrapper, HAND_OVER)).toBeDefined();
    expect(findRowButton(wrapper, APPROVE)).toBeUndefined();
  });

  it('relabels to mark returned for a lent item', () => {
    const wrapper = createWrapper([reservation('res-1', [pivot('p-1', 'lent')])]);

    expect(findRowButton(wrapper, MARK_RETURNED)).toBeDefined();
  });

  it('never submits pivots belonging to another tenant', async () => {
    const wrapper = createWrapper([
      reservation('res-1', [
        pivot('p-mine', 'created'),
        pivot('p-foreign', 'created', { approvable: false }),
      ]),
    ]);

    await act(wrapper, APPROVE);

    expect(lastPost()![1]).toMatchObject({ approvable_ids: ['p-mine'] });
  });

  it('rejects only the pending pivots', async () => {
    const wrapper = createWrapper([
      reservation('res-1', [pivot('p-created', 'created'), pivot('p-lent', 'lent')]),
    ]);

    // The reject affordance is the icon-only button next to the approve action.
    const reject = wrapper.findAll('button')
      .filter(button => button.element.closest('[role="dialog"]') === null)
      .find(button => button.attributes('title')?.includes(REJECT));

    await reject!.trigger('click');
    await confirmDialog(wrapper, REJECT);

    expect(lastPost()![1]).toMatchObject({
      approvable_ids: ['p-created'],
      decision: 'rejected',
    });
  });

  it('offers no action on a row with nothing left to advance', () => {
    const wrapper = createWrapper([reservation('res-1', [pivot('p-1', 'returned')])]);

    expect(findRowButton(wrapper, APPROVE)).toBeUndefined();
    expect(findRowButton(wrapper, MARK_RETURNED)).toBeUndefined();
  });
});

describe('mine mode', () => {
  it('offers cancel on the owner\'s pending items and submits a cancel decision', async () => {
    const wrapper = createWrapper(
      [reservation('res-1', [pivot('p-1', 'created', { cancellable: true })])],
      'mine',
    );

    await act(wrapper, CANCEL_RESERVATION);

    expect(lastPost()![1]).toMatchObject({
      approvable_ids: ['p-1'],
      decision: 'cancelled',
    });
  });

  it('does not offer approval actions to a requester', () => {
    const wrapper = createWrapper(
      [reservation('res-1', [pivot('p-1', 'created', { cancellable: true })])],
      'mine',
    );

    expect(findRowButton(wrapper, APPROVE)).toBeUndefined();
  });
});

describe('fully resolve', () => {
  it('sends every in-flight pivot to the resolve endpoint, not the decision endpoint', async () => {
    const wrapper = createWrapper([
      reservation('res-1', [
        pivot('p-created', 'created'),
        pivot('p-lent', 'lent'),
        pivot('p-done', 'returned'),
        pivot('p-foreign', 'created', { approvable: false }),
      ]),
    ]);

    // Select the row, then use the bulk bar's resolve action.
    await wrapper.find('table tbody [role="checkbox"], table tbody input[type="checkbox"]').trigger('click');
    await findRowButton(wrapper, RESOLVE)!.trigger('click');
    await confirmDialog(wrapper, RESOLVE);

    const [url, payload] = lastPost()!;

    expect(url).toBe('/mocked/approvals.resolve');
    // Everything still in flight and mine — not the finished one, not the other tenant's.
    expect((payload as { approvable_ids: string[] }).approvable_ids.sort()).toEqual(['p-created', 'p-lent']);
    expect(payload).not.toHaveProperty('decision');
  });
});

describe('bulk selection', () => {
  it('only allows selecting rows that have something to act on', async () => {
    const wrapper = createWrapper([
      reservation('res-actionable', [pivot('p-1', 'created')]),
      reservation('res-done', [pivot('p-2', 'returned')]),
    ]);

    const checkboxes = wrapper.findAll('table tbody [role="checkbox"], table tbody input[type="checkbox"]');

    // One row is terminal, so its checkbox must be disabled.
    const enabled = checkboxes.filter(c => c.attributes('disabled') === undefined
      && c.attributes('data-disabled') === undefined);

    expect(checkboxes.length).toBeGreaterThan(0);
    expect(enabled.length).toBeLessThan(checkboxes.length);
  });
});
