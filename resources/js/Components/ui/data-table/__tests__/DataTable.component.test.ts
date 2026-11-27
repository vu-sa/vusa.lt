import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ArrowUp } from '@lucide/vue';
import { nextTick } from 'vue';

import DataTable from '@/Components/ui/data-table/DataTable.vue';
import type { DataTableColumn } from '@/Components/ui/data-table/dataTableFeatures';
import { commonStubs } from '@/tests/stubs';

interface TestRow {
  id: number;
  name: string;
  age: number;
}

const TestDataTable = DataTable<TestRow>;
type TestTableProps = Parameters<typeof TestDataTable>[0];

const columns: DataTableColumn<TestRow>[] = [
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

  const createWrapper = (props: Partial<TestTableProps> = {}) => {
    return mount(TestDataTable, {
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
          PaginationNext: { emits: ['click'], template: '<button data-testid="next-page" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>', props: ['disabled'] },
          PaginationPrevious: { emits: ['click'], template: '<button data-testid="prev-page" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>', props: ['disabled'] },
          PaginationFirst: { emits: ['click'], template: '<button data-testid="first-page" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>', props: ['disabled'] },
          PaginationLast: { emits: ['click'], template: '<button data-testid="last-page" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>', props: ['disabled'] },
          PaginationEllipsis: { template: '<span>...</span>' },
          Input: {
            template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value); $emit(\'input\', $event)" />',
            props: ['modelValue', 'placeholder'],
          },
          Checkbox: {
            template: '<input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
            props: ['modelValue', 'disabled'],
          },
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
          Button: {
            template: '<button type="button" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
            props: ['variant', 'size', 'disabled'],
          },
        },
      },
    });
  };

  const scrollTo = Object.getOwnPropertyDescriptor(HTMLDivElement.prototype, 'scrollTo');

  beforeEach(() => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    // Mock scrollTo on HTMLDivElement to prevent unhandled errors from scrollTableToTop
    if (!HTMLDivElement.prototype.scrollTo) {
      HTMLDivElement.prototype.scrollTo = vi.fn();
    }
  });

  afterEach(() => {
    wrapper?.unmount();
    vi.useRealTimers();
    if (!scrollTo) Reflect.deleteProperty(HTMLDivElement.prototype, 'scrollTo');
  });

  describe('rendering', () => {
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
      expect(wrapper.find('tbody tr').text()).toContain('tables.no_results');
    });

    it('shows custom empty message via prop', () => {
      wrapper = createWrapper({ data: [], emptyMessage: 'Nothing here' });
      expect(wrapper.find('tbody tr').text()).toContain('Nothing here');
    });

    it('renders empty slot when provided', () => {
      wrapper = mount(TestDataTable, {
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
  });

  describe('sorting', () => {
    it('keeps the sorting button focusable and changes sorting once per activation', async () => {
      wrapper = createWrapper();
      const button = wrapper.findAll('th')[1]!.get('button');
      expect((button.element as HTMLButtonElement).tabIndex).toBe(0);
      await button.trigger('click');
      expect(wrapper.emitted('update:sorting')).toHaveLength(1);
    });

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

      const nameHeader = wrapper.get('th[aria-sort="ascending"]');
      expect(nameHeader.text()).toContain('Name');
      expect(nameHeader.findComponent(ArrowUp).exists()).toBe(true);
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

    it('moves to the next page using the pagination controls', async () => {
      wrapper = createWrapper({ pagination: true, pageSize: 2 });
      const previous = wrapper.get<HTMLButtonElement>('[data-testid="prev-page"]');
      const next = wrapper.get<HTMLButtonElement>('[data-testid="next-page"]');

      expect(previous.element.disabled).toBe(true);
      expect(next.element.disabled).toBe(false);
      expect(wrapper.findAll('tbody tr td:nth-child(2)').map(cell => cell.text())).toEqual(['Alice', 'Bob']);

      await next.trigger('click');

      expect(wrapper.findAll('tbody tr td:nth-child(2)').map(cell => cell.text())).toEqual(['Charlie', 'Diana']);
      expect(wrapper.emitted('update:pagination')?.at(-1)).toEqual([{ pageIndex: 1, pageSize: 2 }]);
      expect(previous.element.disabled).toBe(false);
      expect(next.element.disabled).toBe(true);
    });

    it('uses custom pagination slot when provided', () => {
      wrapper = mount(TestDataTable, {
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
    const columnsWithSelection: DataTableColumn<TestRow>[] = [
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

      expect(wrapper.text()).toContain('tables.selected');
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

      expect(wrapper.text()).toContain('tables.selected');
      const clearButton = wrapper.findAll('button').find(b => b.text().includes('tables.clear_selection'));
      await clearButton!.trigger('click');
      await nextTick();

      expect(wrapper.text()).not.toContain('tables.selected');
      expect((rowCheckbox!.element as HTMLInputElement).checked).toBe(false);
      expect(wrapper.emitted('update:rowSelection')?.at(-1)).toEqual([{}]);
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
      const vm = wrapper.vm as unknown as { getSelectedRows: () => TestRow[]; clearRowSelection: () => void };

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
      const vm = wrapper.vm as unknown as { getSelectedRows: () => TestRow[]; clearRowSelection: () => void };

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
      wrapper = mount(TestDataTable, {
        props: { columns, data },
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
      wrapper = mount(TestDataTable, {
        props: { columns, data },
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
