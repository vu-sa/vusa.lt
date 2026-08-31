import type { CellContext, ColumnDef } from '@tanstack/vue-table';
import type { VNode } from 'vue';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/Components/ui/data-table/DataTableActionsColumn.vue', () => ({
  default: { name: 'DataTableActionsColumn' },
}));

vi.mock('@/Components/ui/dropdown-menu', () => ({
  DropdownMenuItem: { name: 'DropdownMenuItem' },
}));

vi.mock('laravel-vue-i18n', () => ({
  trans: (key: string) => key,
}));

import { createStandardActionsColumn } from '@/Composables/useTableActions';

interface FormRow {
  id: string;
  meeting_id?: string | null;
  can: {
    view: boolean;
    update: boolean;
    delete: boolean;
  };
}

const Icon = { name: 'Icon' };

function renderCell(column: ColumnDef<FormRow, unknown>, row: FormRow): VNode {
  if (typeof column.cell !== 'function') {
    throw new Error('Expected a functional cell renderer.');
  }

  return column.cell({
    row: { original: row },
  } as CellContext<FormRow, unknown>) as VNode;
}

describe('createStandardActionsColumn', () => {
  it('resolves action permissions for each row', () => {
    const column = createStandardActionsColumn<FormRow>('forms', {
      canView: row => row.can.view,
      canEdit: row => row.can.update,
      canDelete: row => row.can.delete,
    });

    const readOnlyCell = renderCell(column, {
      id: 'read-only',
      can: { view: true, update: false, delete: false },
    });
    const editableCell = renderCell(column, {
      id: 'editable',
      can: { view: true, update: true, delete: true },
    });

    expect(readOnlyCell.props).toMatchObject({
      canView: true,
      canEdit: false,
      canDelete: false,
    });
    expect(editableCell.props).toMatchObject({
      canView: true,
      canEdit: true,
      canDelete: true,
    });
  });

  /**
   * Page-defined actions live in the overflow menu, and a row they make no sense on
   * must not get an entry that quietly does nothing.
   */
  it('renders a custom action only for the rows it applies to', () => {
    const onSelect = vi.fn();
    const column = createStandardActionsColumn<FormRow>('calendar', {
      customActions: [{
        key: 'create-meeting',
        label: 'Sukurti posėdį',
        icon: Icon,
        isAvailable: row => !row.meeting_id,
        onSelect,
      }],
    });

    const unlinked = renderCell(column, { id: '1', meeting_id: null, can: { view: true, update: true, delete: true } });
    const linked = renderCell(column, { id: '2', meeting_id: 'abc', can: { view: true, update: true, delete: true } });

    expect(unlinked.children).not.toBeNull();
    expect(linked.children).toBeNull();
  });

  it('hands the custom action the row it was invoked on', () => {
    const onSelect = vi.fn();
    const column = createStandardActionsColumn<FormRow>('calendar', {
      customActions: [{ key: 'create-meeting', label: 'Sukurti posėdį', icon: Icon, onSelect }],
    });

    const row: FormRow = { id: '1', can: { view: true, update: true, delete: true } };
    const cell = renderCell(column, row);
    const slots = cell.children as { 'custom-actions': () => VNode[] };
    const [item] = slots['custom-actions']();

    (item!.props as { onSelect: () => void }).onSelect();

    expect(onSelect).toHaveBeenCalledWith(row);
  });
});
