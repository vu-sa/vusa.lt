import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import IndexResource from '../IndexResource.vue';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const state = vi.hoisted(() => ({ initialFilters: {} as Record<string, unknown>, setFilter: vi.fn() }));

vi.mock('@/Composables/useCollectionSource', () => ({
  useTypesenseCollectionSource: () => {
    const filters = ref({ ...state.initialFilters });
    state.setFilter.mockImplementation((field: string, value: unknown) => { filters.value[field] = value; });

    return { filters, items: ref([]), setFilter: state.setFilter };
  },
}));

vi.mock('@/Features/Admin/AdminSearch/Composables/useResourceAvailability', () => ({
  useResourceAvailability: () => ({ availability: ref(new Map()), ensure: vi.fn() }),
}));

vi.mock('@/Composables/useRowAbilities', () => ({
  useRowAbilities: () => ({ can: () => false, invalidate: vi.fn() }),
}));

const CollectionPage = {
  props: ['quickFilters', 'rowActions', 'columns'],
  emits: ['quickFilter'],
  template: '<div />',
};

const render = (managedTenantIds: number[]) => mount(IndexResource, {
  props: { reservationCart: null, mainTenantId: 7, managedTenantIds },
  global: { stubs: { ...commonStubs, CollectionPage, ReservationCartBar: true, ReservationCartSheet: true, CollectionConfirmAction: true } },
});

describe('resource managed quick filter', () => {
  beforeEach(() => {
    state.initialFilters = {};
    state.setFilter.mockReset();
    vi.mocked(usePage).mockReturnValue(createMockPage({ reservationCart: null }));
    window.history.replaceState({}, '', '/mano/resources');
  });

  it('hides the quick filter when there are no managed tenants', () => {
    const wrapper = render([]);

    expect(wrapper.findComponent(CollectionPage).props('quickFilters')).toEqual([]);
    wrapper.unmount();
  });

  it('toggles all managed tenants without clearing other filters', async () => {
    state.initialFilters = { is_reservable: ['true'] };
    const wrapper = render([2, 7]);
    const collection = wrapper.findComponent(CollectionPage);

    expect(collection.props('quickFilters')).toEqual([expect.objectContaining({ id: 'managed', label: 'Mano administruojami', active: false })]);
    collection.vm.$emit('quickFilter', 'managed');
    await wrapper.vm.$nextTick();

    expect(state.setFilter).toHaveBeenLastCalledWith('tenant_id', ['2', '7']);
    expect(collection.props('quickFilters')[0].active).toBe(true);

    collection.vm.$emit('quickFilter', 'managed');
    expect(state.setFilter).toHaveBeenLastCalledWith('tenant_id', undefined);
    expect(state.setFilter).toHaveBeenCalledTimes(2);
    wrapper.unmount();
  });

  it('recognizes restored tenant filters regardless of their order', () => {
    state.initialFilters = { tenant_id: ['7', '2'] };
    const wrapper = render([2, 7]);

    expect(wrapper.findComponent(CollectionPage).props('quickFilters')[0].active).toBe(true);
    wrapper.unmount();
  });
  it('keeps opening primary and offers hidden timestamp columns', () => {
    const wrapper = render([]);
    const collection = wrapper.findComponent(CollectionPage);
    const actions = collection.props('rowActions')({ id: 'r1', name_lt: 'Išteklius' });

    expect(actions[0]).toMatchObject({ role: 'open', primary: true, href: route('resources.show', 'r1') });
    expect(collection.props('columns')).toEqual(expect.arrayContaining([
      expect.objectContaining({ key: 'created_at', defaultHidden: true, sortField: 'created_at' }),
      expect.objectContaining({ key: 'updated_at', defaultHidden: true, sortField: 'updated_at' }),
    ]));
    wrapper.unmount();
  });

});
