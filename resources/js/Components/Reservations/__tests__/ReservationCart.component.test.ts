import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';

import AddToReservationButton from '@/Components/Reservations/AddToReservationButton.vue';
import ReservationCartBar from '@/Components/Reservations/ReservationCartBar.vue';
import ReservationCartItemRow from '@/Components/Reservations/ReservationCartItemRow.vue';
import ReservationDraftSummary from '@/Components/Reservations/ReservationDraftSummary.vue';
import type { ReservationCart, ReservationCartItem } from '@/Components/Reservations/types';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string, param?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}${param ? `/${param}` : ''}`));

const stubs = { ...commonStubs, EntityTypeMark: true, NumberField: true };

const item = (overrides: Partial<ReservationCartItem> = {}): ReservationCartItem => ({
  id: 1,
  resource_id: 'r1',
  name: 'Projektorius',
  tenant_shortname: 'VU SA',
  image_url: null,
  capacity: 3,
  quantity: 2,
  available: 3,
  problem: null,
  ...overrides,
});

/** `null` items: no reservation started at all; an array (even empty): a draft exists. */
const withCart = (items: ReservationCartItem[] | null) => {
  const cart: ReservationCart | null = items === null
    ? null
    : { name: null, description: null, start_time: null, end_time: null, count: items.length, problemCount: 0, expiresAt: null, ttlDays: 14, items };

  vi.mocked(usePage).mockReturnValue(createMockPage({ reservationCart: cart }) as never);
};

describe('reservation cart', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.mocked(router.post).mockClear();
    vi.mocked(router.patch).mockClear();
    vi.mocked(router.delete).mockClear();
    vi.mocked(router.visit).mockClear();
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('AddToReservationButton', () => {
    it('only offers to look at the resource before any reservation is started', async () => {
      withCart(null);
      wrapper = mount(AddToReservationButton, { props: { resourceId: 'r1' }, global: { stubs } });

      const button = wrapper.find('[data-testid="add-to-reservation"]');
      expect(button.attributes('data-state')).toBe('view');
      expect(wrapper.findComponent({ name: 'InertiaLink' }).props('href')).toContain('resources.show');

      await button.trigger('click');
      expect(router.post).not.toHaveBeenCalled();
    });

    it('can start a reservation with the resource instead, then opens the form', async () => {
      withCart(null);
      wrapper = mount(AddToReservationButton, { props: { resourceId: 'r1', withoutDraft: 'reserve' }, global: { stubs } });

      await wrapper.find('[data-state="reserve"]').trigger('click');

      const [url, data, options] = vi.mocked(router.post).mock.calls.at(-1)! as [string, unknown, { onSuccess: () => void }];
      expect(url).toBe('/mocked/reservationCart.items.store');
      expect(data).toEqual({ resource_id: 'r1', quantity: 1 });

      options.onSuccess();
      expect(router.visit).toHaveBeenCalledWith('/mocked/reservations.create');
    });

    it('adds one of a resource that is not in the cart yet', async () => {
      withCart([]);
      wrapper = mount(AddToReservationButton, { props: { resourceId: 'r1' }, global: { stubs } });

      await wrapper.find('[data-testid="add-to-reservation"]').trigger('click');

      expect(router.post).toHaveBeenCalledWith(
        '/mocked/reservationCart.items.store',
        { resource_id: 'r1', quantity: 1 },
        expect.objectContaining({ only: ['reservationCart'] }),
      );
      expect(wrapper.emitted('added')).toHaveLength(1);
    });

    it('shows the quantity already in the cart instead of adding again', async () => {
      withCart([item({ quantity: 2 })]);
      wrapper = mount(AddToReservationButton, { props: { resourceId: 'r1' }, global: { stubs } });

      const button = wrapper.find('[data-testid="add-to-reservation"]');
      await button.trigger('click');

      expect(button.attributes('data-state')).toBe('in-cart');
      expect(router.post).not.toHaveBeenCalled();
    });
  });

  describe('ReservationCartItemRow', () => {
    it('offers to lower an item someone else took part of to what is still free', async () => {
      withCart([item({ problem: 'unavailable', available: 1 })]);
      wrapper = mount(ReservationCartItemRow, { props: { item: item({ problem: 'unavailable', available: 1 }) }, global: { stubs } });

      expect(wrapper.attributes('data-problem')).toBe('unavailable');

      const reduce = wrapper.findAll('button').find(button => button.text().includes('reduce_to'));
      await reduce!.trigger('click');

      expect(router.patch).toHaveBeenCalledWith('/mocked/reservationCart.items.update/r1', { quantity: 1 }, expect.anything());
    });

    it('offers no reduction when nothing is left', () => {
      withCart([item({ problem: 'unavailable', available: 0 })]);
      wrapper = mount(ReservationCartItemRow, { props: { item: item({ problem: 'unavailable', available: 0 }) }, global: { stubs } });

      expect(wrapper.findAll('button').some(button => button.text().includes('reduce_to'))).toBe(false);
    });

    it('shows a bare icon when the resource has no photo, not the type name again', () => {
      withCart([item()]);
      wrapper = mount(ReservationCartItemRow, { props: { item: item() }, global: { stubs: { ...commonStubs, NumberField: true } } });

      expect(wrapper.find('[data-slot="entity-type-mark"]').exists()).toBe(true);
      expect(wrapper.find('[data-slot="entity-type-mark"]').text()).toBe('');
    });

    it('removes the line', async () => {
      withCart([item()]);
      wrapper = mount(ReservationCartItemRow, { props: { item: item() }, global: { stubs } });

      await wrapper.find('button[title]').trigger('click');

      expect(router.delete).toHaveBeenCalledWith('/mocked/reservationCart.items.destroy/r1', expect.anything());
    });
  });

  describe('ReservationCartBar', () => {
    it('stays hidden while the cart is empty', () => {
      withCart(null);
      wrapper = mount(ReservationCartBar, { global: { stubs } });

      expect(wrapper.find('[data-slot="reservation-cart-bar"]').exists()).toBe(false);
    });

    it('shows how many resources are waiting and links to the checkout', () => {
      withCart([item(), item({ id: 2, resource_id: 'r2' })]);
      wrapper = mount(ReservationCartBar, { global: { stubs } });

      expect(wrapper.find('[data-slot="reservation-cart-bar"]').exists()).toBe(true);
      expect(wrapper.findComponent({ name: 'InertiaLink' }).props('href')).toContain('reservations.create');
    });
  });

  describe('ReservationDraftSummary', () => {
    it('leads back to the checkout and warns when something no longer fits', () => {
      withCart(null);
      wrapper = mount(ReservationDraftSummary, {
        props: { draft: { name: 'Stovykla', count: 2, start_time: null, end_time: null, problemCount: 1 } },
        global: { stubs },
      });

      expect(wrapper.text()).toContain('reservations.cart.finish_named');
      expect(wrapper.text()).toContain('reservations.cart.conflicts_title');
      expect(wrapper.findComponent({ name: 'InertiaLink' }).props('href')).toContain('reservations.create');
    });

    it('raises no warning while everything still fits', () => {
      withCart(null);
      wrapper = mount(ReservationDraftSummary, {
        props: { draft: { name: null, count: 0, start_time: null, end_time: null } },
        global: { stubs },
      });

      expect(wrapper.text()).not.toContain('reservations.cart.conflicts_title');
    });
  });
});
