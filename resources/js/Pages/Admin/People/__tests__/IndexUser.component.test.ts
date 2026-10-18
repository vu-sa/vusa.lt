import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import IndexUser from '../IndexUser.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const state = vi.hoisted(() => ({
  trash: false,
  initialFilters: {} as Record<string, unknown>,
  setFilter: vi.fn(),
}));

vi.mock('@/Composables/useCollectionSource', () => ({
  isTrashView: () => state.trash,
  useDatabaseCollectionSource: () => ({
    items: ref([]),
    filters: ref(state.initialFilters),
    setFilter: state.setFilter,
    refresh: vi.fn(),
  }),
}));

vi.mock('@/Composables/useAdminNavigation', () => ({
  useAdminNavigation: () => ({ hasCollectionAction: () => true }),
}));

const CollectionPage = {
  props: ['quickFilters', 'source'],
  emits: ['quickFilter'],
  template: '<div />',
};

const render = () => mount(IndexUser, {
  props: { users: { data: [], meta: { total: 0, per_page: 20, current_page: 1, last_page: 1 } }, deletedCount: 0 },
  global: { stubs: { ...commonStubs, CollectionPage, MergeRecordsDialog: true, CollectionConfirmAction: true } },
});

describe('IndexUser duplicate filter', () => {
  beforeEach(() => {
    state.trash = false;
    state.initialFilters = {};
    state.setFilter.mockReset();
    window.history.replaceState({}, '', '/mano/users');
  });

  it('offers duplicates alongside membership and changes only the duplicate selection', () => {
    const wrapper = render();
    const page = wrapper.findComponent(CollectionPage);

    expect(page.props('quickFilters').map((filter: { id: string }) => filter.id)).toEqual(['current', 'former', 'duplicates']);
    page.vm.$emit('quickFilter', 'duplicates');
    expect(state.setFilter).toHaveBeenCalledWith('duplicates', 'possible');
  });

  it('clears an active duplicate filter independently of membership', () => {
    state.initialFilters = { duplicates: 'possible', membership: 'former' };
    const wrapper = render();

    wrapper.findComponent(CollectionPage).vm.$emit('quickFilter', 'duplicates');
    expect(state.setFilter).toHaveBeenCalledWith('duplicates', undefined);
  });

  it('hides and clears the duplicate filter in trash', () => {
    state.trash = true;
    window.history.replaceState({}, '', '/mano/users?showDeleted=true&duplicates=possible');
    const wrapper = render();

    expect(wrapper.findComponent(CollectionPage).props('quickFilters').map((filter: { id: string }) => filter.id)).toEqual(['current', 'former']);
    expect(state.setFilter).toHaveBeenCalledWith('duplicates', undefined);
  });
});
