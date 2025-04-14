import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { capitalize } from "@/Utils/String";
import { type ColumnDef } from "@tanstack/vue-table";

/**
 * Creates a tenant filter column for data tables
 */
export function useTenantColumn<TData extends { tenant?: { id: number, shortname: string } }>(
  tenants: { id: number, shortname: string }[]
): ColumnDef<TData, unknown> {
  return {
    id: "tenant.id",
    accessorFn: (row) => row.tenant?.id,
    header: () => capitalize($tChoice("entities.tenant.model", 1)),
    size: 120,
    cell: ({ row }) => row.original.tenant?.shortname || '-',
    filterFn: (row, id, value) => {
      if (!value.length) return true;
      return value.includes(row.original.tenant?.id);
    },
    meta: {
      filterOptions: tenants.map((tenant) => ({
        label: $t(tenant.shortname),
        value: tenant.id,
      }))
    }
  };
}

/**
 * Creates a language filter column for data tables
 */
export function useLangColumn<TData extends { lang?: string }>(): ColumnDef<TData, unknown> {
  return {
    id: "lang",
    accessorFn: (row) => row.lang,
    header: () => $t("Kalba"),
    size: 100,
    cell: ({ row }) => {
      const lang = row.original.lang;
      return lang === 'lt' ? 'Lietuvių' : lang === 'en' ? 'Anglų' : lang;
    },
    filterFn: (row, id, value) => {
      if (!value.length) return true;
      return value.includes(row.original.lang);
    },
    meta: {
      filterOptions: [
        {
          label: "Lietuvių",
          value: "lt",
        },
        {
          label: "Anglų",
          value: "en",
        },
      ]
    }
  };
}

/**
 * Creates a timestamp column that formats the date
 */
export function useTimestampColumn<TData extends Record<string, any>>(
  accessor: keyof TData | string,
  headerLabel: string,
  options?: {
    dateFormat?: 'full' | 'date' | 'time';
    locale?: string;
  }
): ColumnDef<TData, unknown> {
  const { 
    dateFormat = 'full',
    locale = 'lt-LT'
  } = options || {};

  return {
    id: accessor.toString(),
    accessorKey: accessor.toString(),
    header: () => headerLabel,
    cell: ({ row }) => {
      const value = row.getValue(accessor.toString());
      if (!value) return '-';
      
      const date = new Date(value);
      
      if (dateFormat === 'date') {
        return date.toLocaleDateString(locale);
      } else if (dateFormat === 'time') {
        return date.toLocaleTimeString(locale);
      } else {
        return date.toLocaleString(locale);
      }
    },
    sortingFn: 'datetime'
  };
}

/**
 * Creates a simple text column
 */
export function useTextColumn<TData extends Record<string, any>>(
  accessor: keyof TData | string,
  headerLabel: string
): ColumnDef<TData, unknown> {
  return {
    id: accessor.toString(),
    accessorKey: accessor.toString(),
    header: () => headerLabel,
    cell: ({ row }) => {
      const value = row.getValue(accessor.toString());
      return value !== null && value !== undefined ? value : '-';
    }
  };
}

/**
 * Creates a column for relationship data with custom rendering
 */
export function useRelationshipColumn<TData extends Record<string, any>>(
  accessor: keyof TData | string,
  headerLabel: string,
  renderFn: (value: any, row: TData) => any
): ColumnDef<TData, unknown> {
  return {
    id: accessor.toString(),
    accessorKey: accessor.toString(),
    header: () => headerLabel,
    cell: ({ row }) => {
      const value = row.getValue(accessor.toString());
      return renderFn(value, row.original);
    }
  };
}

/**
 * Creates an actions column for common CRUD operations
 */
export function useActionsColumn<TData extends { id: number | string }>(
  modelName: string,
  options?: {
    canView?: boolean;
    canEdit?: boolean;
    canDelete?: boolean;
    extraActions?: (row: TData) => any;
  }
): ColumnDef<TData, unknown> {
  const {
    canView = true,
    canEdit = true,
    canDelete = true,
    extraActions,
  } = options || {};

  return {
    id: 'actions',
    cell: ({ row }) => row.original.id, // We don't render directly in the column def
    header: () => $t('Veiksmai'),
    enableSorting: false,
    meta: {
      className: 'w-[100px]',
      actions: {
        canView,
        canEdit,
        canDelete,
        modelName,
        extraActions
      }
    }
  };
}

/**
 * Common hook to create data table columns
 */
export function useDataTableColumns() {
  return {
    useTenantColumn,
    useLangColumn,
    useTimestampColumn,
    useTextColumn,
    useRelationshipColumn,
    useActionsColumn
  };
}

export default useDataTableColumns;