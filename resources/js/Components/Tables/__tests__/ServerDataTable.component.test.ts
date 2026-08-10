import { describe, it, expect, afterEach, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';

import ServerDataTable from '@/Components/Tables/ServerDataTable.vue';
import { commonStubs } from '@/tests/stubs';

const spotlightMocks = vi.hoisted(() => ({
  dismiss: vi.fn(),
  useFeatureSpotlight: vi.fn(),
}));

vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: spotlightMocks.useFeatureSpotlight,
}));

interface TestRow {
  id: number;
  name: string;
  deleted_at?: string | null;
}

const columns: ColumnDef<TestRow, any>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
  },
];

const columnsWithActions: ColumnDef<TestRow, any>[] = [
  ...columns,
  {
    id: 'actions',
    header: 'Actions',
  },
];

describe('ServerDataTable', () => {
  let wrapper: ReturnType<typeof mount>;

  const mountTable = (props: Record<string, unknown> = {}, slots: Record<string, string> = {}) => mount(ServerDataTable, {
    props: {
      modelName: 'tests',
      columns: columns as ColumnDef<unknown, any>[],
      data: [],
      totalCount: 0,
      allowToggleDeleted: true,
      ...props,
    },
    slots,
    global: {
      stubs: {
        ...commonStubs,
        DataTableProvider: defineComponent({
          name: 'DataTableProvider',
          props: ['columns'],
          template: `
            <div data-testid="data-table-provider">
              <slot name="filters" />
              <slot name="actions" />
              <slot name="empty" />
            </div>
          `,
        }),
        EmptyState: defineComponent({
          name: 'EmptyState',
          props: ['title', 'description'],
          template: '<div><span>{{ title }}</span><span>{{ description }}</span><slot /></div>',
        }),
        Link: { template: '<a><slot /></a>' },
      },
    },
  });

  /** Column ids as handed to DataTableProvider, so injected columns are observable. */
  const renderedColumnIds = () => {
    const provider = wrapper.findComponent({ name: 'DataTableProvider' });

    return (provider.props('columns') as ColumnDef<unknown, any>[])
      .map(column => column.id ?? (column as { accessorKey?: string }).accessorKey);
  };

  afterEach(() => {
    wrapper?.unmount();
  });

  beforeEach(() => {
    spotlightMocks.dismiss.mockClear();
    spotlightMocks.useFeatureSpotlight.mockReset();
    spotlightMocks.useFeatureSpotlight.mockReturnValue({
      isDismissed: { value: false },
      dismiss: spotlightMocks.dismiss,
    });
  });

  describe('trash view toggle', () => {
    it('renders both segments with the deleted count', () => {
      wrapper = mountTable({ deletedCount: 3 });

      expect(wrapper.text()).toContain('trash.active_records');
      expect(wrapper.text()).toContain('trash.deleted_records');
      expect(wrapper.text()).toContain('3');
    });

    it('dismisses the trash spotlight when the deleted segment is selected', async () => {
      wrapper = mountTable({ deletedCount: 3 });

      await wrapper.find('[data-testid="show-deleted-toggle"]').trigger('click');

      expect(spotlightMocks.dismiss).toHaveBeenCalledOnce();
    });

    it('does not fall back to the active view when the current segment is re-selected', async () => {
      wrapper = mountTable({ deletedCount: 3, showDeleted: true });

      await wrapper.find('[data-testid="show-deleted-toggle"]').trigger('click');

      expect(wrapper.text()).toContain('trash.showing_deleted_only');
    });

    it('still renders the toggle when spotlight state is unavailable', () => {
      spotlightMocks.useFeatureSpotlight.mockReturnValueOnce(undefined);

      wrapper = mountTable({ deletedCount: 2 });

      expect(wrapper.find('[data-testid="show-deleted-toggle"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('2');
    });

    // The toggle stays available at a count of zero so the trash view is a predictable
    // part of every list rather than something that appears only once records are deleted.
    it('still offers the toggle when nothing is deleted, without a noisy zero', () => {
      wrapper = mountTable({ deletedCount: 0, showDeleted: false });

      const deletedSegment = wrapper.find('[data-testid="show-deleted-toggle"]');

      expect(deletedSegment.exists()).toBe(true);
      expect(deletedSegment.text()).not.toContain('0');
    });

    it('omits the count when deletedCount is not provided', () => {
      wrapper = mountTable({ deletedCount: undefined, showDeleted: false });

      expect(wrapper.find('[data-testid="show-deleted-toggle"]').text()).not.toMatch(/\d/);
    });

    it('does not spotlight an empty trash view', () => {
      wrapper = mountTable({ deletedCount: 0, showDeleted: false });

      expect(wrapper.findComponent({ name: 'SpotlightPopover' }).props('isDismissed')).toBe(true);
    });
  });

  describe('trash view banner', () => {
    it('lays the banner out as flex so its text is not squeezed into the alert icon column', () => {
      wrapper = mountTable({ deletedCount: 1, showDeleted: true });

      const alert = wrapper.find('[data-slot="alert"]');

      expect(alert.classes()).toContain('flex');
      expect(alert.classes()).not.toContain('grid');
    });

    it('explains the view and offers a way out', () => {
      wrapper = mountTable({ deletedCount: 0, showDeleted: true });

      expect(wrapper.text()).toContain('trash.showing_deleted_only');
      expect(wrapper.text()).toContain('trash.showing_deleted_only_description');
      expect(wrapper.text()).toContain('trash.exit_trash_view');
    });
  });

  describe('deleted_at column', () => {
    it('is absent outside the trash view', () => {
      wrapper = mountTable({ columns: columnsWithActions as ColumnDef<unknown, any>[] });

      expect(renderedColumnIds()).not.toContain('deleted_at');
    });

    it('is injected before the actions column in the trash view', () => {
      wrapper = mountTable({
        columns: columnsWithActions as ColumnDef<unknown, any>[],
        showDeleted: true,
      });

      expect(renderedColumnIds()).toEqual(['name', 'deleted_at', 'actions']);
    });

    it('is appended when the table has no actions column', () => {
      wrapper = mountTable({ showDeleted: true });

      expect(renderedColumnIds()).toEqual(['name', 'deleted_at']);
    });
  });

  describe('search', () => {
    it('searches as you type, so no separate search button is needed', async () => {
      vi.useFakeTimers();
      wrapper = mountTable();

      expect(wrapper.findAll('button').some(button => button.text() === 'tables.search')).toBe(false);

      await wrapper.find('input').setValue('vardas');
      vi.advanceTimersByTime(500);
      await nextTick();

      expect(vi.mocked(router.visit)).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({ data: expect.objectContaining({ search: 'vardas' }) }),
      );

      vi.useRealTimers();
    });

    it('does not re-request when the input is synced from server props', async () => {
      vi.useFakeTimers();
      wrapper = mountTable({ initialFilters: { search: 'pradinis' } });
      vi.mocked(router.visit).mockClear();

      await wrapper.setProps({ initialFilters: { search: 'is serverio' } });
      vi.advanceTimersByTime(500);
      await nextTick();

      // The props watcher reloads once; the debounced search must not add a second.
      expect(vi.mocked(router.visit)).toHaveBeenCalledTimes(1);

      vi.useRealTimers();
    });
  });

  describe('filter panel', () => {
    const filterSlot = { filters: '<button data-testid="page-filter">Type</button>' };

    it('offers no filter trigger when the page defines no filters', () => {
      wrapper = mountTable();

      expect(wrapper.find('[data-testid="toggle-filters"]').exists()).toBe(false);
    });

    // Page filters used to occupy the toolbar permanently; collapsing them keeps
    // the chrome above the table to one row.
    it('keeps page filters collapsed until asked for', async () => {
      wrapper = mountTable({}, filterSlot);

      expect(wrapper.find('[data-testid="filters-panel"]').exists()).toBe(false);

      await wrapper.find('[data-testid="toggle-filters"]').trigger('click');

      expect(wrapper.find('[data-testid="page-filter"]').exists()).toBe(true);
    });

    // A list that arrives already filtered has to say so, or the missing rows
    // read as missing data.
    it('opens itself and counts the filters already narrowing the list', () => {
      wrapper = mountTable({ initialFilters: { 'types.id': [1, 2], 'data_quality': 'vacant' } }, filterSlot);

      expect(wrapper.find('[data-testid="filters-panel"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="toggle-filters"]').text()).toContain('2');
    });

    it('ignores empty filter values and the table\'s own keys when counting', () => {
      wrapper = mountTable(
        { showDeleted: true, initialFilters: { 'types.id': [], 'search': 'abc', 'language': null } },
        filterSlot,
      );

      expect(wrapper.find('[data-testid="toggle-filters"]').text()).not.toMatch(/\d/);
      expect(wrapper.find('[data-testid="clear-filters"]').exists()).toBe(false);
    });

    // Pages seed their filter controls from props during setup, so clearing has
    // to remount the page rather than preserve its state.
    it('clears filters with a fresh visit that keeps the current view', async () => {
      wrapper = mountTable({ initialFilters: { 'types.id': [1] }, showDeleted: true }, filterSlot);
      vi.mocked(router.visit).mockClear();

      await wrapper.find('[data-testid="clear-filters"]').trigger('click');

      expect(vi.mocked(router.visit)).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          preserveState: false,
          data: { showDeleted: true },
        }),
      );
    });
  });

  describe('empty state', () => {
    it('resolves the model name through a real translation key instead of building one at runtime', () => {
      wrapper = mountTable({ entityName: 'news' });

      expect(wrapper.text()).toContain('tables.empty_title');
      expect(wrapper.text()).not.toContain('No news found');
    });
  });
});
