import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';

import type { ResourceBooking } from '@/Components/Reservations/ResourceBookingRow.vue';
import ShowResource from '@/Pages/Admin/Reservations/ShowResource.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string, param?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}${param ? `/${param}` : ''}`));

const stubs = {
  ...commonStubs,
  RecordPage: {
    props: ['title', 'status', 'sections', 'facts', 'primaryAction', 'overflowActions'],
    emits: ['action'],
    template: `
      <div>
        <slot name="identity" />
        <h1>{{ title }}</h1>
        <div data-testid="status">{{ status?.role ?? '' }}</div>
        <div data-testid="facts">{{ facts.map(f => f.key + '=' + (f.value ?? '')).join('|') }}</div>
        <button v-if="primaryAction" data-testid="primary" @click="$emit('action', primaryAction.key)">{{ primaryAction.label }}</button>
        <button v-for="a in overflowActions" :key="a.key" :data-testid="'overflow-' + a.key" @click="$emit('action', a.key)">{{ a.label }}</button>
        <slot name="alert" />
        <slot name="availability" />
      </div>
    `,
  },
  ReservationCartBar: true,
  ReservationCartSheet: true,
  Deferred: true,
};

const booking = (overrides: Partial<ResourceBooking> = {}): ResourceBooking => ({
  id: 1,
  reservation_id: null,
  name: null,
  href: null,
  quantity: 1,
  state: 'lent',
  start_time: new Date(2026, 8, 20, 9).getTime(),
  end_time: new Date(2026, 8, 22, 17).getTime(),
  overdue: false,
  ...overrides,
});

const mountPage = (overrides: Record<string, unknown> = {}) => mount(ShowResource, {
  props: {
    resource: {
      id: 'r1',
      name: 'Projektorius',
      description: null,
      location: 'Sandėlis',
      identifier: null,
      capacity: 3,
      is_reservable: true,
      tenant: { id: 1, shortname: 'VU SA' },
      category: 'Technika',
      images: [],
    },
    availableNow: 2,
    currentLoans: [],
    upcoming: [],
    managers: [],
    reservationCart: null,
    can: { update: false, delete: false, reserve: true },
    ...overrides,
  },
  global: { stubs },
});

describe('ShowResource.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ reservationCart: null }) as never);
    vi.mocked(router.post).mockClear();
    vi.mocked(router.visit).mockClear();
  });

  it('starts a reservation with the resource when none is started, and opens the form', async () => {
    const wrapper = mountPage();

    expect(wrapper.find('[data-testid="primary"]').text()).toBe('reservations.cart.reserve');
    await wrapper.find('[data-testid="primary"]').trigger('click');

    const [url, data, options] = vi.mocked(router.post).mock.calls.at(-1)! as [string, unknown, { onSuccess: () => void }];
    expect(url).toBe('/mocked/reservationCart.items.store');
    expect(data).toEqual({ resource_id: 'r1', quantity: 1 });

    options.onSuccess();
    expect(router.visit).toHaveBeenCalledWith('/mocked/reservations.create');
  });

  it('adds to the started reservation without leaving the page', async () => {
    const cart = { name: null, description: null, start_time: null, end_time: null, count: 0, problemCount: 0, expiresAt: null, ttlDays: 14, items: [] };
    vi.mocked(usePage).mockReturnValue(createMockPage({ reservationCart: cart }) as never);
    const wrapper = mountPage({ reservationCart: cart });

    expect(wrapper.find('[data-testid="primary"]').text()).toBe('reservations.cart.add_to_reservation');
    await wrapper.find('[data-testid="primary"]').trigger('click');

    const [, , options] = vi.mocked(router.post).mock.calls.at(-1)! as [string, unknown, { onSuccess: () => void }];
    expect(options.onSuccess).toBeDefined();
    expect(router.visit).not.toHaveBeenCalled();
  });

  it('gathers empty reservation lists into one status list', () => {
    const empty = mountPage();
    expect(empty.find('[data-slot="overview-status-list"]').findAll('li')).toHaveLength(2);
    expect(empty.find('[data-testid="resource-current-loans"]').exists()).toBe(false);

    const busy = mountPage({ upcoming: [booking({ state: 'reserved' })] });
    expect(busy.find('[data-testid="resource-upcoming"]').exists()).toBe(true);
    expect(busy.find('[data-slot="overview-status-list"]').findAll('li')).toHaveLength(1);
  });

  it('offers no primary action to someone who cannot reserve it', () => {
    const wrapper = mountPage({ can: { update: false, delete: false, reserve: false } });

    expect(wrapper.find('[data-testid="primary"]').exists()).toBe(false);
  });

  it('keeps editing and deleting to those allowed', () => {
    const plain = mountPage();
    expect(plain.find('[data-testid="overflow-edit"]').exists()).toBe(false);
    expect(plain.find('[data-testid="overflow-delete"]').exists()).toBe(false);

    const manager = mountPage({ can: { update: true, delete: true, reserve: true } });
    expect(manager.find('[data-testid="overflow-edit"]').exists()).toBe(true);
    expect(manager.find('[data-testid="overflow-delete"]').exists()).toBe(true);
  });

  it('mentions an unreturned item once, as a soft notice that does not block reserving', () => {
    const wrapper = mountPage({ currentLoans: [booking({ overdue: true })] });
    const notice = wrapper.find('[data-testid="resource-overdue-alert"]');

    expect(notice.exists()).toBe(true);
    expect(notice.classes()).toContain('border-status-attention');
    expect(wrapper.find('[data-testid="status"]').text()).toBe('');
    expect(wrapper.find('[data-slot="resource-booking-row"]').text()).not.toContain('overdue');
    expect(wrapper.find('[data-testid="primary"]').exists()).toBe(true);
  });

  it('says when everything is out and shows free quantity as a fact', () => {
    const wrapper = mountPage({ availableNow: 0 });

    expect(wrapper.find('[data-testid="status"]').text()).toBe('attention');
    expect(wrapper.find('[data-testid="facts"]').text()).toContain('available=0 / 3');
  });

  it('shows someone else\'s reservation without its name', () => {
    const wrapper = mountPage({ upcoming: [booking({ state: 'reserved' })] });

    expect(wrapper.find('[data-slot="resource-booking-row"]').text()).toContain('reservations.resource.other_reservation');
  });

  it('uses the photo as the identity anchor and repeats nothing without one', () => {
    expect(mountPage().find('[data-slot="entity-type-mark"]').exists()).toBe(false);
    expect(mountPage().find('[data-testid="resource-identity-image"]').exists()).toBe(false);

    const withPhoto = mountPage({
      resource: { id: 'r1', name: 'Projektorius', description: null, location: null, identifier: null, capacity: 3, is_reservable: true, tenant: null, category: null, images: ['/p.jpg'] },
    });
    expect(withPhoto.find('[data-testid="resource-identity-image"]').attributes('src')).toBe('/p.jpg');
  });
});
