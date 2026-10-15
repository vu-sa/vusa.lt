import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import ReservationDecisionDialog from '../ReservationDecisionDialog.vue';
import type { ReservationDecision } from '../types';

import type { DashboardReservation, ReservationPivot, ReservationResourceState } from '@/Utils/ReservationStatus';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string) => `/mocked/${name}`);

function pivot(id: string, state: ReservationResourceState, overrides: Partial<ReservationPivot> = {}): ReservationPivot {
  return {
    id,
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    returned_at: null,
    updated_at: '2026-07-01T10:00:00Z',
    quantity: 1,
    state,
    approvable: true,
    backtrackable: false,
    cancellable: false,
    ...overrides,
  };
}

function reservation(pivots: ReservationPivot[]): DashboardReservation {
  return {
    id: 'res-1',
    name: 'Freshers camp',
    description: null,
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    created_at: '2026-07-01T10:00:00Z',
    users: [],
    resources: pivots.map(p => ({ id: `r-${p.id}`, name: `Resource ${p.id}`, tenant: { id: 't', shortname: 'MIF' }, pivot: p })),
  };
}

// Dialog content renders in a portal; stubbing the primitives keeps it in the wrapper's tree.
const stubs = {
  Dialog: { template: '<div><slot /></div>' },
  DialogContent: { template: '<div role="dialog"><slot /></div>' },
  DialogHeader: { template: '<div><slot /></div>' },
  DialogTitle: { template: '<h2><slot /></h2>' },
  DialogDescription: { template: '<p><slot /></p>' },
};

function mountDialog(decision: ReservationDecision, targets: DashboardReservation[]) {
  return mount(ReservationDecisionDialog, { props: { open: true, decision, targets }, global: { stubs } });
}

const confirmButton = (wrapper: ReturnType<typeof mount>, label: string) =>
  wrapper.findAll('button').find(button => button.text().includes(label))!;

const lastPost = () => vi.mocked(router.post).mock.calls.at(-1);

describe('ReservationDecisionDialog', () => {
  beforeEach(() => vi.mocked(router.post).mockClear());

  it('groups the affected items by the transition they trigger', () => {
    const wrapper = mountDialog('approved', [reservation([pivot('1', 'created'), pivot('2', 'created')])]);

    expect(wrapper.text()).toContain('reservations.bulk.will_approve');
    expect(wrapper.text()).toContain('Resource 1');
    expect(wrapper.text()).toContain('Resource 2');
  });

  it('approves through the bulk endpoint with the pivot ids, never the reservation id', () => {
    const wrapper = mountDialog('approved', [reservation([pivot('7', 'created')])]);

    confirmButton(wrapper, 'reservations.actions.approve').trigger('click');

    expect(lastPost()?.[0]).toBe('/mocked/approvals.bulkStore');
    expect(lastPost()?.[1]).toMatchObject({ approvable_type: 'reservation_resource', approvable_ids: ['7'], decision: 'approved', step: 1 });
  });

  it('fast-forwards through the resolve endpoint without a decision', () => {
    const wrapper = mountDialog('resolved', [reservation([pivot('1', 'reserved')])]);

    confirmButton(wrapper, 'reservations.actions.resolve').trigger('click');

    expect(lastPost()?.[0]).toBe('/mocked/approvals.resolve');
    expect(lastPost()?.[1]).not.toHaveProperty('decision');
  });

  it('undoes through the backtrack endpoint, only for eligible items', () => {
    const wrapper = mountDialog('backtracked', [reservation([pivot('1', 'lent', { backtrackable: true }), pivot('2', 'lent')])]);

    confirmButton(wrapper, 'reservations.actions.backtrack').trigger('click');

    expect(lastPost()?.[0]).toBe('/mocked/approvals.backtrack');
    expect(lastPost()?.[1]).toMatchObject({ approvable_ids: ['1'] });
  });

  it('rejects only what is still pending and says the rest is skipped', () => {
    const wrapper = mountDialog('rejected', [reservation([pivot('1', 'created')]), reservation([pivot('2', 'reserved')])]);

    expect(wrapper.text()).toContain('reservations.bulk.reject_only_pending');

    confirmButton(wrapper, 'reservations.actions.reject').trigger('click');

    expect(lastPost()?.[1]).toMatchObject({ approvable_ids: ['1'], decision: 'rejected' });
  });

  it('cannot submit when there is nothing to change', () => {
    const wrapper = mountDialog('approved', [reservation([pivot('1', 'returned')])]);

    expect(confirmButton(wrapper, 'reservations.actions.approve').attributes('disabled')).toBeDefined();
  });

  it('closes and reports done once the server accepts', () => {
    vi.mocked(router.post).mockImplementationOnce(((_url: string, _data: unknown, options: { onSuccess?: () => void; onFinish?: () => void }) => {
      options.onSuccess?.();
      options.onFinish?.();
    }) as never);

    const wrapper = mountDialog('approved', [reservation([pivot('1', 'created')])]);
    confirmButton(wrapper, 'reservations.actions.approve').trigger('click');

    expect(wrapper.emitted('done')).toHaveLength(1);
    expect(wrapper.emitted('update:open')?.[0]).toEqual([false]);
  });
});
