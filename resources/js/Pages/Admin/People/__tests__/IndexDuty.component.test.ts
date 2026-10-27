import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { router, usePage } from '@inertiajs/vue3';

import IndexDuty from '../IndexDuty.vue';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const state = vi.hoisted(() => ({
  initialFilters: {} as Record<string, unknown>,
  setFilter: vi.fn(),
  sourceOptions: {} as Record<string, unknown>,
  refresh: vi.fn(),
}));

vi.mock('@/Composables/useCollectionSource', () => ({
  useDatabaseCollectionSource: (options: Record<string, unknown>) => {
    state.sourceOptions = options;
    const items = ref((options.initial as { items: unknown[] }).items);
    state.refresh.mockImplementation(() => { items.value = []; });
    return {
      items,
      filters: ref(state.initialFilters),
      query: ref(''),
      activeFilterCount: ref(0),
      setFilter: state.setFilter,
      refresh: state.refresh,
    };
  },
}));

vi.mock('@/Composables/useAdminNavigation', () => ({
  useAdminNavigation: () => ({ hasCollectionAction: () => true }),
}));

const CollectionPage = {
  props: ['quickFilters', 'source'],
  emits: ['quickFilter'],
  template: '<div><div v-for="item in source.items.value" :key="item.id" data-testid="row"><slot name="row" :item="item" /></div></div>',
};

const MergeRecordsDialog = {
  props: ['open', 'records', 'initialTargetId'],
  emits: ['close', 'merged'],
  template: '<div />',
};

const duty = (id: string, lt: string) => ({ id, name: { lt, en: lt }, dutiables_count: 0, institution: { id: 'i1', name: { lt: 'VU SA MIF' } } });

const render = (props: Record<string, unknown> = {}) => mount(IndexDuty, {
  props: { duties: { data: [], meta: { total: 0, per_page: 20, current_page: 1, last_page: 1 } }, dutyTypes: [], deletedCount: 0, ...props },
  global: { stubs: { ...commonStubs, CollectionPage, MergeRecordsDialog, ConfirmDialog: {
    props: ['open'],
    emits: ['confirm'],
    template: '<button v-if="open" data-testid="confirm-deletion" @click="$emit(\'confirm\')">Confirm deletion</button>',
  } } },
});

const quickFilterIds = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findComponent(CollectionPage).props('quickFilters').map((filter: { id: string }) => filter.id);

describe('IndexDuty', () => {
  beforeEach(() => {
    state.initialFilters = {};
    state.setFilter.mockReset();
    state.refresh.mockReset();
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { forceDelete: { duty: true } } } }));
    window.history.replaceState({}, '', '/mano/duties');
  });

  it('refreshes the trash rows after permanently deleting a duty', async () => {
    const wrapper = render({ showDeleted: true, duties: {
      data: [duty('d1', 'Koordinatorius')],
      meta: { total: 1, per_page: 20, current_page: 1, last_page: 1 },
    } });

    await wrapper.get('button[aria-label="Ištrinti visam laikui"]').trigger('click');
    await wrapper.get('[data-testid="confirm-deletion"]').trigger('click');
    const options = vi.mocked(router.delete).mock.calls.at(-1)?.[1];
    expect(wrapper.findAll('[data-testid="row"]')).toHaveLength(1);

    options.onSuccess();
    await wrapper.vm.$nextTick();

    expect(state.refresh).toHaveBeenCalledOnce();
    expect(wrapper.findAll('[data-testid="row"]')).toHaveLength(0);
    wrapper.unmount();
  });

  it('offers possible duplicates as a data-quality quick filter', () => {
    const wrapper = render();

    expect(quickFilterIds(wrapper)).toEqual(['vacant', 'untyped', 'history', 'possible_duplicates']);
    wrapper.findComponent(CollectionPage).vm.$emit('quickFilter', 'possible_duplicates');
    expect(state.setFilter).toHaveBeenCalledWith('data_quality', 'possible_duplicates');
  });

  it('toggles duties with a changed history', () => {
    const wrapper = render();

    wrapper.findComponent(CollectionPage).vm.$emit('quickFilter', 'history');
    expect(state.setFilter).toHaveBeenCalledWith('history', 'any');
  });

  it('offers editing on the rows the user may edit', () => {
    const wrapper = mount(IndexDuty, {
      props: { duties: { data: [], meta: { total: 0, per_page: 20, current_page: 1, last_page: 1 } }, dutyTypes: [], deletedCount: 0 },
      global: { stubs: { ...commonStubs, MergeRecordsDialog, ConfirmDialog: true, CollectionPage: {
        template: `<div>
          <slot name="cell" :item="{ ...row, id: 'editable', can_update: true }" :column="{ key: 'actions' }" />
          <slot name="cell" :item="{ ...row, id: 'readonly', can_update: false }" :column="{ key: 'actions' }" />
        </div>`,
        data: () => ({ row: duty('x', 'Koordinatorius') }),
      } } },
    });

    const keys = wrapper.findAllComponents({ name: 'CollectionRowActions' }).map(actions => actions.props('actions')[0].key);
    expect(keys).toEqual(['edit', 'open']);
  });

  it('hides and clears the duplicate filter in trash', () => {
    state.initialFilters = { data_quality: 'possible_duplicates' };
    const wrapper = render({ showDeleted: true });

    expect(quickFilterIds(wrapper)).toEqual(['vacant', 'untyped', 'history']);
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
