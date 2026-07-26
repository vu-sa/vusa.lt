import { h } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';

import DataTableActionsColumn from '@/Components/ui/data-table/DataTableActionsColumn.vue';

type RowPermission<TData> = boolean | ((row: TData) => boolean);

export interface ActionColumnOptions<TData> {
  // Model information
  modelName: string;

  // Route prefixes or explicit routes
  viewRoute?: string;
  editRoute?: string;
  duplicateRoute?: string;
  deleteRoute?: string;
  restoreRoute?: string;

  // Permissions
  canView?: RowPermission<TData>;
  canEdit?: RowPermission<TData>;
  canDuplicate?: RowPermission<TData>;
  canDelete?: RowPermission<TData>;
  canRestore?: RowPermission<TData>;

  // Confirmation settings
  confirmDelete?: boolean;
  deleteConfirmMessage?: string;
  deleteConfirmTitle?: string;

  // Column display options
  header?: string;
  id?: string;
  width?: number;
  enableSorting?: boolean;
}

/**
 * Creates an action column definition for data tables
 *
 * @param options Configuration options for the action column
 * @returns ColumnDef object to add to the table columns
 */
export function createActionsColumn<TData extends { id: string | number; deleted_at?: string | null }>(
  options: ActionColumnOptions<TData>,
): ColumnDef<TData, unknown> {
  return {
    id: options.id || 'actions',
    header: () => options.header || $t('Veiksmai'),
    enableSorting: options.enableSorting === undefined ? false : options.enableSorting,
    size: options.width || 80,
    cell: ({ row }) => {
      const resolvePermission = (permission?: RowPermission<TData>) => {
        return typeof permission === 'function' ? permission(row.original) : permission;
      };

      return h(DataTableActionsColumn, {
        row: row.original,
        modelName: options.modelName,
        viewRoute: options.viewRoute,
        editRoute: options.editRoute,
        duplicateRoute: options.duplicateRoute,
        deleteRoute: options.deleteRoute,
        restoreRoute: options.restoreRoute,
        canView: resolvePermission(options.canView),
        canEdit: resolvePermission(options.canEdit),
        canDuplicate: resolvePermission(options.canDuplicate),
        canDelete: resolvePermission(options.canDelete),
        canRestore: resolvePermission(options.canRestore),
        confirmDelete: options.confirmDelete,
        deleteConfirmMessage: options.deleteConfirmMessage,
        deleteConfirmTitle: options.deleteConfirmTitle,
      });
    },
  };
}

/**
 * Creates standard actions column with conventional route naming
 *
 * @param modelName The name of the model (e.g., 'users', 'institutions')
 * @param permissions Object containing permission flags
 * @returns ColumnDef object for action column
 */
export function createStandardActionsColumn<TData extends { id: string | number; deleted_at?: string | null }>(
  modelName: string,
  permissions: {
    canView?: RowPermission<TData>;
    canEdit?: RowPermission<TData>;
    canDuplicate?: RowPermission<TData>;
    canDelete?: RowPermission<TData>;
    canRestore?: RowPermission<TData>;
    confirmDelete?: boolean;
    deleteConfirmMessage?: string;
    deleteConfirmTitle?: string;
  } = {},
): ColumnDef<TData, unknown> {
  return createActionsColumn({
    modelName,
    // Use conventional route naming: [modelName].show, [modelName].edit, etc.
    viewRoute: permissions.canView ? `${modelName}.show` : undefined,
    editRoute: permissions.canEdit ? `${modelName}.edit` : undefined,
    duplicateRoute: permissions.canDuplicate ? `${modelName}.duplicate` : undefined,
    deleteRoute: permissions.canDelete ? `${modelName}.destroy` : undefined,
    restoreRoute: permissions.canRestore ? `${modelName}.restore` : undefined,
    // Enable confirmation by default if not explicitly disabled
    confirmDelete: permissions.confirmDelete !== false,
    deleteConfirmMessage: permissions.deleteConfirmMessage,
    deleteConfirmTitle: permissions.deleteConfirmTitle,
    // Forward permissions
    ...permissions,
  });
}
