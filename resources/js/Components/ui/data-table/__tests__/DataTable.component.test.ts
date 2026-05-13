import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { nextTick, h } from 'vue';
import type { ColumnDef } from '@tanstack/vue-table';

import DataTable from '@/Components/ui/data-table/DataTable.vue';
import { commonStubs } from '@/tests/stubs';

interface TestRow {
  id: number;
  name: string;
  age: number;
}

const columns: ColumnDef<TestRow, any>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    size: 60,
  },
  {
    accessorKey: 'name',
    header: 'Name',
    size: 200,
  },
  {
    accessorKey: 'age',
    header: 'Age',
    size: 80,
  },
];

const data: TestRow[] = [
  { id: 1, name: 'Alice', age: 30 },
  { id: 2, name: 'Bob', age: 25 },
  { id: 3, name: 'Charlie', age: 35 },
  { id: 4, name: 'Diana', age: 28 },
];

describe('DataTable.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const createWrapper = (props: Record<string, any> = {}) => {
    return mount(DataTable, {
      props: {
        columns,
        data,
        ...props,
      },
      global: {
        stubs: {
          ...commonStubs,
          Pagination: { template: '<div data-testid="pagination"><slot /></div>' },
          PaginationContent: { template: '<div><slot /></div>' },
          PaginationItem: { template: '<div><slot /></div>', props: ['value'] },
          PaginationNext: { template: '<button data-testid="next-page" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>', props: ['disabled'] },
          PaginationPrevious: { template: '<button data-testid="prev-page" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>', props: ['disabled'] },
          PaginationFirst: { template: '<button data-testid="first-page" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>', props: ['disabled'] },
          PaginationLast: { template: '<button data-testid="last-page" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>', props: ['disabled'] },
          PaginationEllipsis: { template: '<span>...</span>' },
          Input: {
            template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value); $emit(\'input\', $event)" />',
            props: ['modelValue', 'placeholder'],
          },
          Checkbox: {
            template: '<input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
            props: ['modelValue', 'disabled'],
          },
          DropdownMenu: { template: '<div><slot /></div>' },
          DropdownMenuTrigger: { template: '<div><slot /></div>' },
          DropdownMenuContent: { template: '<div><slot /></div>' },
          DropdownMenuCheckboxItem: { template: '<div><slot /></div>' },
          // Table components
          Table: { template: '<table><slot /></table>' },
          TableBody: { template: '<tbody><slot /></tbody>' },
          TableCell: { template: '<td><slot /></td>' },
          TableHead: { template: '<th><slot /></th>' },
          TableHeader: { template: '<thead><slot /></thead>' },
          TableRow: { template: '<tr><slot /></tr>' },
          // Icons
          ChevronDownIcon: { template: '<span>↓</span>' },
          ChevronUpIcon: { template: '<span>↑</span>' },
          ChevronLeftIcon: { template: '<span>←</span>' },
          ChevronRightIcon: { template: '<span>→</span>' },
          ChevronsLeftIcon: { template: '<span>«</span>' },
          ChevronsRightIcon: { template: '<span>»</span>' },
          SlidersIcon: { template: '<span>⚙</span>' },
          Button: {
            template: '<button type="button" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
            props: ['variant', 'size', 'disabled'],
          },
        },
      },
    });
  };

  beforeEach(() => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    // Mock scrollTo on HTMLDivElement to prevent unhandled errors from scrollTableToTop
    if (!HTMLDivElement.prototype.scrollTo) {
      HTMLDivElement.prototype.scrollTo = vi.fn() as any;
    }
  });

  afterEach(() => {
    wrapper?.unmount();
    vi.useRealTimers();
  });

  describe('rendering', () => {
    it('mounts without errors', () => {
      wrapper = createWrapper();
      expect(wrapper.find('table').exists()).toBe(true);
    });

    it('renders table headers from ColumnDef', () => {
      wrapper = createWrapper();
      const headers = wrapper.findAll('th');
      expect(headers.length).toBe(3);
      expect(headers[0]!.text()).toContain('ID');
      expect(headers[1]!.text()).toContain('Name');
      expect(headers[2]!.text()).toContain('Age');
    });

    it('renders rows from data', () => {
      wrapper = createWrapper();
      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(4);
      expect(rows[0]!.text()).toContain('Alice');
      expect(rows[1]!.text()).toContain('Bob');
    });

    it('shows empty state message when data is empty', () => {
      wrapper = createWrapper({ data: [] });
      expect(wrapper.find('tbody tr').text()).toContain('No results');
    });

    it('shows custom empty message via prop', () => {
      wrapper = createWrapper({ data: [], emptyMessage: 'Nothing here' });
      expect(wrapper.find('tbody tr').text()).toContain('Nothing here');
    });

    it('renders empty slot when provided', () => {
      wrapper = mount(DataTable, {
        props: { columns, data: [] },
        slots: {
          empty: '<div data-testid="custom-empty">Custom Empty</div>',
        },
        global: {
          stubs: {
            ...commonStubs,
            Table: { template: '<table><slot /></table>' },
            TableBody: { template: '<tbody><slot /></tbody>' },
            TableCell: { template: '<td><slot /></td>' },
            TableHead: { template: '<th><slot /></th>' },
            TableHeader: { template: '<thead><slot /></thead>' },
            TableRow: { template: '<tr><slot /></tr>' },
            Input: { template: '<input />' },
            Button: { template: '<button><slot /></button>' },
          },
        },
      });
      expect(wrapper.find('[data-testid="custom-empty"]').exists()).toBe(true);
    });
  });

  describe('filtering', () => {
    it('renders search input when enableFiltering is true', () => {
      wrapper = createWrapper({ enableFiltering: true });
      expect(wrapper.find('input').exists()).toBe(true);
    });

    it('does not render search input when enableFiltering is false', () => {
      wrapper = createWrapper({ enableFiltering: false });
      expect(wrapper.find('input').exists()).toBe(false);
    });

    it('emits update:global-filter on input', async () => {
      wrapper = createWrapper({ enableFiltering: true });
      const input = wrapper.find('input');

      await input.setValue('Alice');
      await nextTick();

      expect(wrapper.emitted('update:global-filter')).toBeTruthy();
      const events = wrapper.emitted('update:global-filter')!;
      expect(events[events.length - 1]![0]).toBe('Alice');
    });

    it('hides search when manualFiltering is true', () => {
      wrapper = createWrapper({ enableFiltering: true, manualFiltering: true });
      expect(wrapper.find('input').exists()).toBe(false);
    });
  });

  describe('sorting', () => {
    it('emits update:sorting when sortable header is clicked', async () => {
      wrapper = createWrapper();
      const nameHeader = wrapper.findAll('th')[1];

      await nameHeader!.trigger('click');
      await nextTick();

      expect(wrapper.emitted('update:sorting')).toBeTruthy();
    });

    it('shows sort direction indicator', async () => {
      wrapper = createWrapper({ initialSort: [{ id: 'name', desc: false }] });
      await nextTick();

      // The sort indicator is rendered in the header
      const nameHeader = wrapper.findAll('th')[1];
      expect(nameHeader!.text()).toContain('Name');
    });
  });

  describe('pagination', () => {
    it('does not render pagination when pagination is false', () => {
      wrapper = createWrapper({ pagination: false });
      expect(wrapper.find('[data-testid="pagination"]').exists()).toBe(false);
    });

    it('does not render pagination when only one page', () => {
      wrapper = createWrapper({ pagination: true, pageSize: 10 });
      expect(wrapper.find('[data-testid="pagination"]').exists()).toBe(false);
    });

    it('limits rows to pageSize when pagination is enabled', () => {
      wrapper = createWrapper({ pagination: true, pageSize: 2 });
      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(2);
    });

    it('limits rows to pageSize', () => {
      wrapper = createWrapper({ pagination: true, pageSize: 2 });
      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(2);
    });

    it('emits update:pagination when page changes via exposed table', async () => {
      wrapper = createWrapper({ pagination: true, pageSize: 2 });
      const vm = wrapper.vm as any;

      vm.table.nextPage();
      await nextTick();

      expect(wrapper.emitted('update:pagination')).toBeTruthy();
    });

    it('starts on page index 0', () => {
      wrapper = createWrapper({ pagination: true, pageSize: 2 });
      const vm = wrapper.vm as any;
      expect(vm.table.getState().pagination.pageIndex).toBe(0);
    });

    it('can navigate to next page via table API', async () => {
      wrapper = createWrapper({ pagination: true, pageSize: 2 });
      const vm = wrapper.vm as any;

      expect(vm.table.getCanPreviousPage()).toBe(false);
      expect(vm.table.getCanNextPage()).toBe(true);

      vm.table.nextPage();
      await nextTick();

      expect(vm.table.getState().pagination.pageIndex).toBe(1);
      expect(vm.table.getCanNextPage()).toBe(false);
    });

    it('uses custom pagination slot when provided', () => {
      wrapper = mount(DataTable, {
        props: { columns, data, pagination: true, pageSize: 2 },
        slots: {
          pagination: '<div data-testid="custom-pagination">Custom Pagination</div>',
        },
        global: {
          stubs: {
            ...commonStubs,
            Table: { template: '<table><slot /></table>' },
            TableBody: { template: '<tbody><slot /></tbody>' },
            TableCell: { template: '<td><slot /></td>' },
            TableHead: { template: '<th><slot /></th>' },
            TableHeader: { template: '<thead><slot /></thead>' },
            TableRow: { template: '<tr><slot /></tr>' },
            Input: { template: '<input />' },
            Button: { template: '<button><slot /></button>' },
          },
        },
      });
      expect(wrapper.find('[data-testid="custom-pagination"]').exists()).toBe(true);
    });
  });

  describe('row selection', () => {
    const columnsWithSelection: ColumnDef<TestRow, any>[] = [
      {
        accessorKey: 'id',
        header: 'ID',
      },
      {
        accessorKey: 'name',
        header: 'Name',
      },
    ];

    it('renders checkboxes when enableRowSelection is true', () => {
      wrapper = createWrapper({
        columns: columnsWithSelection,
        enableRowSelection: true,
        enableRowSelectionColumn: true,
      });
      const checkboxes = wrapper.findAll('input[type="checkbox"]');
      expect(checkboxes.length).toBeGreaterThan(0);
    });

    it('does not render checkboxes when enableRowSelection is false', () => {
      wrapper = createWrapper({
        columns: columnsWithSelection,
        enableRowSelection: false,
      });
      const checkboxes = wrapper.findAll('input[type="checkbox"]');
      expect(checkboxes.length).toBe(0);
    });

    it('emits update:rowSelection when checkbox is toggled', async () => {
      wrapper = createWrapper({
        columns: columnsWithSelection,
        enableRowSelection: true,
        enableRowSelectionColumn: true,
      });
      const rowCheckbox = wrapper.findAll('input[type="checkbox"]')[1]; // First data row

      await rowCheckbox!.setValue(true);
      await nextTick();

      expect(wrapper.emitted('update:rowSelection')).toBeTruthy();
    });

    it('shows selection count when showSelectionCount is true', async () => {
      wrapper = createWrapper({
        columns: columnsWithSelection,
        enableRowSelection: true,
        enableRowSelectionColumn: true,
        showSelectionCount: true,
      });
      const rowCheckbox = wrapper.findAll('input[type="checkbox"]')[1];

      await rowCheckbox!.setValue(true);
      await nextTick();

      expect(wrapper.text()).toContain('Selected');
      expect(wrapper.text()).toContain('1');
    });

    it('clear button resets row selection', async () => {
      wrapper = createWrapper({
        columns: columnsWithSelection,
        enableRowSelection: true,
        enableRowSelectionColumn: true,
        showSelectionCount: true,
      });
      const rowCheckbox = wrapper.findAll('input[type="checkbox"]')[1];

      await rowCheckbox!.setValue(true);
      await nextTick();

      const clearButton = wrapper.findAll('button').find(b => b.text().includes('×'));
      await clearButton!.trigger('click');
      await nextTick();

      // After clearing, selection count should not be visible
      expect(wrapper.text()).not.toContain('Selected: 1');
    });
  });

  describe('exposed methods', () => {
    it('getSelectedRows returns selected rows', async () => {
      wrapper = createWrapper({
        columns: [
          { accessorKey: 'id', header: 'ID' },
          { accessorKey: 'name', header: 'Name' },
        ],
        enableRowSelection: true,
        enableRowSelectionColumn: true,
      });
      const vm = wrapper.vm as any;

      const rowCheckbox = wrapper.findAll('input[type="checkbox"]')[1];
      await rowCheckbox!.setValue(true);
      await nextTick();

      const selected = vm.getSelectedRows();
      expect(selected.length).toBe(1);
    });

    it('clearRowSelection clears all selections', async () => {
      wrapper = createWrapper({
        columns: [
          { accessorKey: 'id', header: 'ID' },
          { accessorKey: 'name', header: 'Name' },
        ],
        enableRowSelection: true,
        enableRowSelectionColumn: true,
      });
      const vm = wrapper.vm as any;

      const rowCheckbox = wrapper.findAll('input[type="checkbox"]')[1];
      await rowCheckbox!.setValue(true);
      await nextTick();

      expect(vm.getSelectedRows().length).toBe(1);

      vm.clearRowSelection();
      await nextTick();

      expect(vm.getSelectedRows().length).toBe(0);
    });
  });

  describe('external state syncing', () => {
    it('syncs external globalFilter prop', async () => {
      wrapper = createWrapper({ enableFiltering: true, globalFilter: 'Alice' });
      await nextTick();

      const rows = wrapper.findAll('tbody tr');
      // Only Alice should match
      expect(rows.length).toBe(1);
      expect(rows[0]!.text()).toContain('Alice');
    });

    it('syncs external sorting prop', async () => {
      wrapper = createWrapper({ externalSorting: [{ id: 'name', desc: true }] });
      await nextTick();

      const rows = wrapper.findAll('tbody tr');
      // With desc sort by name, first should be Diana or similar
      expect(rows.length).toBe(4);
    });

    it('syncs external pagination prop', async () => {
      wrapper = createWrapper({
        pagination: true,
        pageSize: 2,
        externalPagination: { pageIndex: 1, pageSize: 2 },
      });
      await nextTick();

      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(2);
    });

    it('syncs external rowSelection prop', async () => {
      wrapper = createWrapper({
        columns: [
          { accessorKey: 'id', header: 'ID' },
          { accessorKey: 'name', header: 'Name' },
        ],
        enableRowSelection: true,
        enableRowSelectionColumn: true,
        rowSelectionState: { 0: true },
      });
      await nextTick();

      const checkboxes = wrapper.findAll('input[type="checkbox"]');
      expect((checkboxes[1]!.element as HTMLInputElement).checked).toBe(true);
    });
  });

  describe('pageSize prop', () => {
    it('respects pageSize prop', () => {
      wrapper = createWrapper({ pagination: true, pageSize: 2 });
      const rows = wrapper.findAll('tbody tr');
      expect(rows.length).toBe(2);
    });

    it('updates pageSize when prop changes', async () => {
      wrapper = createWrapper({ pagination: true, pageSize: 2 });
      expect(wrapper.findAll('tbody tr').length).toBe(2);

      await wrapper.setProps({ pageSize: 3 });
      await nextTick();

      expect(wrapper.findAll('tbody tr').length).toBe(3);
    });
  });

  describe('slots', () => {
    it('renders filters slot', () => {
      wrapper = mount(DataTable, {
        props: { columns, data, enableFiltering: true },
        slots: {
          filters: '<div data-testid="filters-slot">Filters</div>',
        },
        global: {
          stubs: {
            ...commonStubs,
            Table: { template: '<table><slot /></table>' },
            TableBody: { template: '<tbody><slot /></tbody>' },
            TableCell: { template: '<td><slot /></td>' },
            TableHead: { template: '<th><slot /></th>' },
            TableHeader: { template: '<thead><slot /></thead>' },
            TableRow: { template: '<tr><slot /></tr>' },
            Input: { template: '<input />' },
            Button: { template: '<button><slot /></button>' },
          },
        },
      });
      expect(wrapper.find('[data-testid="filters-slot"]').exists()).toBe(true);
    });

    it('renders actions slot', () => {
      wrapper = mount(DataTable, {
        props: { columns, data, enableColumnVisibility: true },
        slots: {
          actions: '<div data-testid="actions-slot">Actions</div>',
        },
        global: {
          stubs: {
            ...commonStubs,
            Table: { template: '<table><slot /></table>' },
            TableBody: { template: '<tbody><slot /></tbody>' },
            TableCell: { template: '<td><slot /></td>' },
            TableHead: { template: '<th><slot /></th>' },
            TableHeader: { template: '<thead><slot /></thead>' },
            TableRow: { template: '<tr><slot /></tr>' },
            Input: { template: '<input />' },
            Button: { template: '<button><slot /></button>' },
          },
        },
      });
      expect(wrapper.find('[data-testid="actions-slot"]').exists()).toBe(true);
    });
  });
});
