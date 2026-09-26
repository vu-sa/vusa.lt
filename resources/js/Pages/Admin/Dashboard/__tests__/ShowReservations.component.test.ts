import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import ShowReservations from '@/Pages/Admin/Dashboard/ShowReservations.vue';
import { commonStubs } from '@/tests/stubs';
import type { DashboardReservation, ReservationPivot, ReservationResourceState } from '@/Utils/ReservationStatus';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, params: Record<string, unknown> = {}) => {
  const query = Object.entries(params).map(([key, value]) => `${key}=${value}`).join('&');

  return `/mocked/${name}${query ? `?${query}` : ''}`;
});

const counts = { waitingForMe: 3, lentOut: 5, overdue: 1, mine: 2, myOverdue: 0 };

function pivot(id: string, state: ReservationResourceState): ReservationPivot {
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
  };
}

function reservation(id: string, name: string): DashboardReservation {
  return {
    id,
    name,
    description: null,
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    created_at: '2026-07-01T10:00:00Z',
    users: [],
    resources: [{ id: `r-${id}`, name: 'Projektorius', tenant: { id: 't', shortname: 'MIF' }, pivot: pivot(id, 'created') }],
  };
}

const mountPage = (props: Record<string, unknown>) => mount(ShowReservations, {
  props: { managesResources: true, counts, waitingForMe: [], myUpcoming: [], ...props },
  global: { stubs: { ...commonStubs } },
});

const hrefs = (wrapper: ReturnType<typeof mount>) =>
  Object.fromEntries(wrapper.findAll('[data-number]').map(link => [link.attributes('data-number'), link.attributes('href')]));

describe('ShowReservations overview', () => {
  it('links every number to the filtered list it counts', () => {
    expect(hrefs(mountPage({}))).toEqual({
      waiting: '/mocked/reservations.index?scope=administered&state=created',
      lent: '/mocked/reservations.index?scope=administered&state=lent',
      overdue: '/mocked/reservations.index?scope=administered&overdue=1',
      mine: '/mocked/reservations.index?scope=mine',
    });
  });

  it('shows a requester only their own two numbers', () => {
    expect(Object.keys(hrefs(mountPage({ managesResources: false })))).toEqual(['mine', 'my_overdue']);
  });

  it('orders the page: numbers, then my reservations beside the sections, then decisions full width', () => {
    const wrapper = mountPage({
      waitingForMe: [reservation('1', 'Kalėdų šventė')],
      myUpcoming: [reservation('2', 'Stovykla')],
      reservationCart: { name: 'Stovykla', count: 1, start_time: null, end_time: null, problemCount: 0, description: null, expiresAt: null, ttlDays: 14, items: [] },
    });
    const page = wrapper.get('[data-slot="overview-page"]').element;
    const numbers = wrapper.get('[data-number]').element;
    const columns = wrapper.get('[data-slot="reservations-overview-columns"]');
    const decisions = wrapper.get('[data-slot="reservations-needing-decision"]').element;

    const position = (element: Element) => Array.from(page.querySelectorAll('*')).indexOf(element);
    expect(position(numbers)).toBeLessThan(position(columns.element));
    expect(position(columns.element)).toBeLessThan(position(decisions));

    const [main, side] = Array.from(columns.element.children);
    expect(main!.querySelector('[data-slot="my-reservations"]')).not.toBeNull();
    expect(main!.querySelector('[data-slot="reservation-draft-summary"]')).not.toBeNull();
    expect(side!.tagName).toBe('ASIDE');
    expect(columns.element.contains(decisions)).toBe(false);
  });

  it('gives the sections the whole row when the user has no reservations of their own', () => {
    const wrapper = mountPage({ counts: { ...counts, mine: 0 }, reservationCart: null });
    const columns = wrapper.get('[data-slot="reservations-overview-columns"]');

    expect(columns.classes()).not.toContain('grid');
    expect(columns.find('aside').exists()).toBe(false);
  });

  it('moves the attention band to the status list when nothing waits', async () => {
    const wrapper = mountPage({});
    await nextTick();

    expect(wrapper.find('[data-slot="reservations-needing-decision"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="overview-status-list"]').text()).toContain('reservations.overview.attention_empty');
  });

  it('lists what waits for a decision, each row with its action', () => {
    const wrapper = mountPage({ waitingForMe: [reservation('1', 'Kalėdų šventė')] });

    expect(wrapper.find('[data-slot="reservations-needing-decision"]').text()).toContain('Kalėdų šventė');
    expect(wrapper.find('[data-slot="reservation-row-actions"]').text()).toContain('reservations.actions.approve');
  });

  it('does not offer the attention band to someone who manages no resources', () => {
    expect(mountPage({ managesResources: false }).text()).not.toContain('reservations.overview.attention');
  });
});
