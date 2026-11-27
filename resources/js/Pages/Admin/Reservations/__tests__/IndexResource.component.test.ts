import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

import IndexResource from '../IndexResource.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const state = vi.hoisted(() => ({ initialFilters: {} as Record<string, unknown>, setFilter: vi.fn() }));

vi.mock('@/Composables/useCollectionSource', () => ({
  useTypesenseCollectionSource: () => {
    const filters = ref({ ...state.initialFilters });
    state.setFilter.mockImplementation((field: string, value: unknown) => {
      filters.value[field] = value;
    });

    return { filters, items: ref([]), setFilter: state.setFilter };
  },
}));

vi.mock('@/Search/admin/Composables/useResourceAvailability', () => ({
  useResourceAvailability: () => ({ availability: ref(new Map()), ensure: vi.fn() }),
}));

vi.mock('@/Composables/useRowAbilities', () => ({
  useRowAbilities: () => ({ can: () => false, invalidate: vi.fn() }),
}));

const CollectionPage = {
  props: ['quickFilters', 'rowActions', 'columns', 'canShowTableAction'],
  emits: ['quickFilter'],
  setup: () => ({ resource: { id: 'r1', name_lt: 'Projektorius', is_reservable: true, capacity: 3 } }),
  template: `
    <div>
      <slot name="card" :item="resource" :actions="rowActions(resource)" />
      <slot name="row" :item="resource" :actions="rowActions(resource)" view="rows" />
      <div data-testid="resource-table-row">
        <slot name="cell" :item="resource" :column="{ key: 'name' }" />
        <slot name="cell" :item="resource" :column="{ key: 'status' }" />
        <slot v-if="canShowTableAction(resource)" name="table-action" :item="resource" />
      </div>
    </div>
  `,
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
  it.each([
    { hasDraft: false, canReserve: true, expected: 0 },
    { hasDraft: true, canReserve: false, expected: 0 },
    { hasDraft: true, canReserve: true, expected: 1 },
  ])('gates cart actions in cards, rows and tables (draft: $hasDraft, permission: $canReserve)', ({ hasDraft, canReserve, expected }) => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: { create: { reservation: canReserve } } },
      reservationCart: hasDraft ? { items: [] } : null,
    }));

    const wrapper = render([]);
    const selectors = ['[data-slot="resource-collection-card"]', '[data-slot="resource-collection-row"]', '[data-testid="resource-table-row"]'];

    for (const selector of selectors) {
      const row = wrapper.get(selector);
      expect(row.findAll('a')).toHaveLength(1);
      expect(row.get('a').attributes('href')).toContain('/resources.show');
      expect(row.findAll('[data-testid="add-to-reservation"]')).toHaveLength(expected);
    }

    wrapper.unmount();
  });

  it('has title links for opening records and offers hidden timestamp columns', () => {
    const wrapper = render([]);
    const collection = wrapper.findComponent(CollectionPage);
    const actions = collection.props('rowActions')({ id: 'r1', name_lt: 'Išteklius' });

    expect(actions).toEqual([]);
    expect(collection.props('columns')).toEqual(expect.arrayContaining([
      expect.objectContaining({ key: 'created_at', defaultHidden: true, sortField: 'created_at' }),
      expect.objectContaining({ key: 'updated_at', defaultHidden: true, sortField: 'updated_at' }),
    ]));
    wrapper.unmount();
  });
});
