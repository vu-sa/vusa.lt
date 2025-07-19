import { type ColumnDef } from '@tanstack/vue-table';
import { format, isValid } from 'date-fns';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Badge } from '@/Components/ui/badge';
import { capitalize } from './String';

/**
 * Create a standardized ID column
 */
export function createIdColumn<T>(options: { 
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey: 'id',
    header: () => 'ID',
    cell: options.cell,
    size: options.width || 60,
    enableSorting: options.enableSorting !== false,
  };
}

/**
 * Create a standardized title/name column with link to edit page
 */
export function createTitleColumn<T extends { id: string | number }>(options: { 
  accessorKey?: string; 
  routeName?: string;
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  const accessorKey = options.accessorKey || 'name';
  const routeName = options.routeName || 'edit';
  
  return {
    accessorKey,
    header: () => $t(accessorKey === 'name' ? 'forms.fields.name' : 'forms.fields.title'),
    cell: options.cell || (({ row }) => {
      const value = row.getValue(accessorKey);
      const id = row.original.id;
      const modelName = (row.original as any)?.type || '';
      const baseRouteName = modelName ? `${modelName}s.${routeName}` : `${routeName}`;
      
      return (
        <a href={route(baseRouteName, { id })} class="font-medium hover:underline">
          {value}
        </a>
      );
    }),
    size: options.width || 250,
    enableSorting: options.enableSorting !== false,
  };
}

/**
 * Create a standardized timestamp column
 */
export function createTimestampColumn<T>(accessorKey: string, options: { 
  title?: string;
  format?: string; 
  width?: number;
  enableSorting?: boolean;
  sortDescFirst?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey,
    header: () => options.title || $t(accessorKey),
    cell: options.cell || (({ row }) => {
      const value = row.getValue(accessorKey);
      if (!value) return null;
      
      try {
        const date = new Date(value as string);
        if (!isValid(date)) return value;
        return format(date, options.format || 'yyyy-MM-dd HH:mm');
      } catch (e) {
        return value;
      }
    }),
    size: options.width || 160,
    enableSorting: options.enableSorting !== false,
    sortDescFirst: options.sortDescFirst,
  };
}

/**
 * Create a tenant column
 */
export function createTenantColumn<T>(options: { 
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey: 'tenant',
    id: 'tenant.name', // Use this for sorting relationship columns
    header: () => capitalize($tChoice('entities.tenant.model', 1)),
    cell: options.cell || (({ row }) => {
      const tenant = row.original.tenant;
      if (!tenant) return '';
      return $t(tenant.shortname);
    }),
    size: options.width || 150,
    enableSorting: options.enableSorting !== false,
  };
}

/**
 * Create a language column
 */
export function createLanguageColumn<T>(options: { 
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey: 'language',
    header: () => $t('Kalba'),
    cell: options.cell || (({ row }) => row.getValue('language') as string),
    size: options.width || 100,
    enableSorting: options.enableSorting !== false,
  };
}

/**
 * Create a boolean column with optional custom labels
 */
export function createBooleanColumn<T>(accessorKey: string, options: { 
  title?: string;
  trueLabel?: string;
  falseLabel?: string;
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey,
    header: () => options.title || $t(accessorKey),
    cell: options.cell || (({ row }) => {
      const value = row.getValue(accessorKey);
      const label = value 
        ? (options.trueLabel || $t('Yes')) 
        : (options.falseLabel || $t('No'));
        
      return (
        <Badge variant={value ? 'default' : 'secondary'} class="text-xs">
          {label}
        </Badge>
      );
    }),
    size: options.width || 100,
    enableSorting: options.enableSorting !== false,
  };
}

/**
 * Create a tags/types column for displaying multiple related entities
 */
export function createTagsColumn<T>(accessorKey: string, options: { 
  title?: string;
  labelKey?: string;
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey,
    header: () => options.title || $t(accessorKey),
    cell: options.cell || (({ row }) => {
      const items = row.getValue(accessorKey) as any[] || [];
      const labelKey = options.labelKey || 'title';
      
      if (!items || items.length === 0) return null;
      
      return (
        <div class="flex flex-wrap gap-1">
          {items.map((item) => (
            <Badge variant="secondary" key={item.id} class="text-xs">
              {$t(item[labelKey])}
            </Badge>
          ))}
        </div>
      );
    }),
    size: options.width || 200,
    enableSorting: options.enableSorting !== false,
  };
}

/**
 * Create a standardized text column
 */
export function createTextColumn<T>(accessorKey: string, options: { 
  title?: string;
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey,
    header: () => options.title || $t(accessorKey),
    cell: options.cell || (({ row }) => row.getValue(accessorKey) as string),
    size: options.width || 150,
    enableSorting: options.enableSorting !== false,
  };
}