import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import ReservationResourceList from '../ReservationResourceList.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string, params?: Record<string, unknown>) =>
  (name === undefined ? { current: () => false } : `/mocked/${name}${params ? `?${JSON.stringify(params)}` : ''}`));

const stubs = {
  ...commonStubs,
  ReservationPeriod: true,
  ReservationDecisionDialog: {
    props: ['open', 'decision', 'targets'],
    template: '<div data-testid="decision-dialog" :data-open="open" :data-decision="decision" :data-pivots="targets.flatMap(t => t.resources.map(r => r.pivot.id)).join(\',\')" />',
  },
  ConfirmDialog: {
    props: ['open'],
    emits: ['confirm'],
    template: '<div v-if="open" data-testid="confirm"><button data-testid="confirm-yes" @click="$emit(\'confirm\')" /></div>',
  },
};

interface Flags { approvable?: boolean; backtrackable?: boolean; cancellable?: boolean }

const pivot = (id: string, state: string, flags: Flags = {}, extra: Record<string, unknown> = {}) => ({
  id,
  state,
  quantity: 1,
  start_time: '2026-09-20T10:00:00Z',
  end_time: '2026-09-21T10:00:00Z',
  approvable: false,
  backtrackable: false,
  cancellable: false,
  ...flags,
  ...extra,
});

const item = (id: string, state: string, flags: Flags = {}, extra: Record<string, unknown> = {}) => ({
  id: `res-${id}`,
  name: `Resource ${id}`,
  tenant: { id: 1, shortname: 'MIF' },
  can_edit: true,
  lowestCapacityAtDateTimeRange: 5,
  pivot: pivot(id, state, flags, extra),
});

const target = (items: ReturnType<typeof item>[]) => ({
  id: 'r1', name: 'Rezervacija', description: null, start_time: '', end_time: '', created_at: '', users: [], resources: items,
});

const mountList = (items: ReturnType<typeof item>[], props: Record<string, unknown> = {}) =>
  mount(ReservationResourceList, {
    props: { resources: items, target: target(items), canEdit: true, ...props } as never,
    global: { stubs },
  });

describe('ReservationResourceList', () => {
  beforeEach(() => {
    vi.mocked(router.delete).mockClear?.();
  });

  it('teaches an empty reservation to add its first resource', () => {
    const wrapper = mountList([]);

    expect(wrapper.text()).toContain('Ištekliai dar nepridėti');
  });

  it('names each item with its status badge', () => {
    const wrapper = mountList([item('1', 'created'), item('2', 'lent')]);
    const roles = wrapper.findAll('[data-slot="status-badge"]').map(badge => badge.attributes('data-status-role'));

    expect(roles).toEqual(['info', 'progress']);
  });

  it('offers the approve action only where the server flagged the item approvable', () => {
    const wrapper = mountList([item('1', 'created', { approvable: true }), item('2', 'created', { approvable: false })]);
    const rows = wrapper.findAll('[data-slot="reservation-resource-row"]');

    expect(rows[0].find('[data-slot="reservation-row-actions"]').text()).toContain('reservations.actions.approve');
    expect(rows[1].find('[data-slot="reservation-row-actions"]').text()).toBe('');
  });

  it('opens the decision dialog for that one item, not the whole reservation', async () => {
    const wrapper = mountList([item('1', 'created', { approvable: true }), item('2', 'created', { approvable: true })]);

    await wrapper.findAll('[data-slot="reservation-resource-row"]')[1]
      .find('[data-slot="reservation-row-actions"] button').trigger('click');

    const dialog = wrapper.find('[data-testid="decision-dialog"]');
    expect(dialog.attributes('data-open')).toBe('true');
    expect(dialog.attributes('data-decision')).toBe('approved');
    expect(dialog.attributes('data-pivots')).toBe('2');
  });

  it('hides edit and remove for a finished item and for someone who may not edit', () => {
    const finished = mountList([item('1', 'returned')]);
    expect(finished.find('[aria-label="Redaguoti"]').exists()).toBe(false);

    const viewer = mountList([item('1', 'created')], { canEdit: false });
    expect(viewer.find('[aria-label="Redaguoti"]').exists()).toBe(false);
    expect(viewer.find('[aria-label="Pašalinti"]').exists()).toBe(false);

    expect(mountList([item('1', 'created')]).find('[aria-label="Redaguoti"]').exists()).toBe(true);
  });

  it('emits the resource to edit', async () => {
    const wrapper = mountList([item('1', 'created')]);

    await wrapper.find('[aria-label="Redaguoti"]').trigger('click');

    expect((wrapper.emitted('edit')?.[0][0] as { id: string }).id).toBe('res-1');
  });

  it('asks before removing an item, then deletes its pivot', async () => {
    const wrapper = mountList([item('1', 'created')]);

    await wrapper.find('[aria-label="Pašalinti"]').trigger('click');
    expect(router.delete).not.toHaveBeenCalled();

    await wrapper.find('[data-testid="confirm-yes"]').trigger('click');

    expect(router.delete).toHaveBeenCalledWith(
      expect.stringContaining('reservationResources.destroy'),
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('warns about an in-flight item the resource can no longer cover', () => {
    const short = mountList([item('1', 'created')].map(row => ({ ...row, lowestCapacityAtDateTimeRange: -1 })));
    const fine = mountList([item('1', 'created')]);
    const done = mountList([item('1', 'returned')].map(row => ({ ...row, lowestCapacityAtDateTimeRange: -1 })));

    expect(short.find('[data-testid="overbooked"]').exists()).toBe(true);
    expect(fine.find('[data-testid="overbooked"]').exists()).toBe(false);
    expect(done.find('[data-testid="overbooked"]').exists()).toBe(false);
  });

  it('links to the resource only when the user may edit it', () => {
    const wrapper = mountList([item('1', 'created'), { ...item('2', 'created'), can_edit: false }]);
    const rows = wrapper.findAll('[data-slot="reservation-resource-row"]');

    expect(rows[0].find('a').exists()).toBe(true);
    expect(rows[1].find('a').exists()).toBe(false);
  });

  it('tucks the decision history behind a disclosure, crossing out what was undone', () => {
    const wrapper = mountList([item('1', 'reserved', {}, {
      approvals: [
        { id: 'a1', decision: 'approved', reverted_at: null, created_at: '2026-09-20T09:00:00Z', user: { name: 'Ona' } },
        { id: 'a2', decision: 'approved', reverted_at: '2026-09-20T10:00:00Z', created_at: '2026-09-20T08:00:00Z', user: { name: 'Jonas' } },
      ],
    }), item('2', 'created')]);
    const rows = wrapper.findAll('[data-slot="reservation-resource-row"]');

    expect(rows[0].find('details').exists()).toBe(true);
    expect(rows[0].findAll('details li')).toHaveLength(2);
    expect(rows[0].findAll('details li span.line-through')).toHaveLength(1);
    expect(rows[1].find('details').exists()).toBe(false);
  });
});
