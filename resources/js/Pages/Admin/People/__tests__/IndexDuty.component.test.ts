import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import IndexDuty from '../IndexDuty.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const state = vi.hoisted(() => ({
  initialFilters: {} as Record<string, unknown>,
  setFilter: vi.fn(),
  sourceOptions: {} as Record<string, unknown>,
}));

vi.mock('@/Composables/useCollectionSource', () => ({
  useDatabaseCollectionSource: (options: Record<string, unknown>) => {
    state.sourceOptions = options;
    return {
      items: ref([]),
      filters: ref(state.initialFilters),
      query: ref(''),
      activeFilterCount: ref(0),
      setFilter: state.setFilter,
      refresh: vi.fn(),
    };
  },
}));

vi.mock('@/Composables/useAdminNavigation', () => ({
  useAdminNavigation: () => ({ hasCollectionAction: () => true }),
}));

const CollectionPage = {
  props: ['quickFilters', 'source'],
  emits: ['quickFilter'],
  template: '<div />',
};

const MergeRecordsDialog = {
  props: ['open', 'records', 'initialTargetId'],
  emits: ['close', 'merged'],
  template: '<div />',
};

const duty = (id: string, lt: string) => ({ id, name: { lt, en: lt }, dutiables_count: 0, institution: { id: 'i1', name: { lt: 'VU SA MIF' } } });

const render = (props: Record<string, unknown> = {}) => mount(IndexDuty, {
  props: { duties: { data: [], meta: { total: 0, per_page: 20, current_page: 1, last_page: 1 } }, dutyTypes: [], deletedCount: 0, ...props },
  global: { stubs: { ...commonStubs, CollectionPage, MergeRecordsDialog, ConfirmDialog: true } },
});

const quickFilterIds = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findComponent(CollectionPage).props('quickFilters').map((filter: { id: string }) => filter.id);

describe('IndexDuty', () => {
  beforeEach(() => {
    state.initialFilters = {};
    state.setFilter.mockReset();
    window.history.replaceState({}, '', '/mano/duties');
  });

  it('offers possible duplicates as a data-quality quick filter', () => {
    const wrapper = render();

    expect(quickFilterIds(wrapper)).toEqual(['vacant', 'untyped', 'possible_duplicates']);
    wrapper.findComponent(CollectionPage).vm.$emit('quickFilter', 'possible_duplicates');
    expect(state.setFilter).toHaveBeenCalledWith('data_quality', 'possible_duplicates');
  });

  it('hides and clears the duplicate filter in trash', () => {
    state.initialFilters = { data_quality: 'possible_duplicates' };
    const wrapper = render({ showDeleted: true });

    expect(quickFilterIds(wrapper)).toEqual(['vacant', 'untyped']);
    expect(state.setFilter).toHaveBeenCalledWith('data_quality', undefined);
  });

  it('offers the available types and no type through the shared filter panel', () => {
    const wrapper = render({ dutyTypes: [{ id: 10, title: { lt: 'Studentų atstovas', en: 'Student representative' } }] });

    expect(state.sourceOptions.facets).toEqual(expect.arrayContaining([
      expect.objectContaining({ field: 'duty_type_id', single: true, values: [
        { value: 'none', label: expect.any(String) },
        { value: '10', label: 'Studentų atstovas' },
      ] }),
    ]));
    wrapper.findComponent(CollectionPage).vm.$emit('quickFilter', 'untyped');
    expect(state.setFilter).toHaveBeenCalledWith('duty_type_id', 'none');
  });

  it('clears the no-type quick filter when selected again', () => {
    state.initialFilters = { duty_type_id: 'none' };
    const wrapper = render();

    wrapper.findComponent(CollectionPage).vm.$emit('quickFilter', 'untyped');
    expect(state.setFilter).toHaveBeenCalledWith('duty_type_id', undefined);
  });

  it('opens the merge dialog from a "merge into this one" link, then drops the link from the URL', async () => {
    window.history.replaceState({}, '', '/mano/duties?merge=1&source=d2&target=d1');
    const wrapper = render({ mergePreset: { target_id: 'd1', duties: [duty('d1', 'Koordinatorius'), duty('d2', 'Koordinatorė')] } });
    const dialog = wrapper.findComponent(MergeRecordsDialog);

    expect(dialog.props('open')).toBe(true);
    expect(dialog.props('initialTargetId')).toBe('d1');
    expect(dialog.props('records').map((record: { id: string }) => record.id)).toEqual(['d1', 'd2']);

    dialog.vm.$emit('close');
    await wrapper.vm.$nextTick();

    expect(wrapper.findComponent(MergeRecordsDialog).props('open')).toBe(false);
    expect(window.location.search).toBe('');
  });
});
