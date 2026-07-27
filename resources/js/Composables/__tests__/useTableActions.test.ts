import type { CellContext, ColumnDef } from '@tanstack/vue-table';
import type { VNode } from 'vue';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/Components/ui/data-table/DataTableActionsColumn.vue', () => ({
  default: { name: 'DataTableActionsColumn' },
}));

vi.mock('laravel-vue-i18n', () => ({
  trans: (key: string) => key,
}));

import { createStandardActionsColumn } from '@/Composables/useTableActions';

interface FormRow {
  id: string;
  can: {
    view: boolean;
    update: boolean;
    delete: boolean;
  };
}

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
});
