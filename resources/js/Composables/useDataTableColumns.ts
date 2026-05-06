import { h } from 'vue';
import type { ColumnDef } from '@tanstack/vue-table';
import { format, isValid } from 'date-fns';
import { trans as $t, transChoice as $tChoice, getActiveLanguage } from 'laravel-vue-i18n';

import { LocaleEnum } from '@/Types/enums';
import {
  DateCell,
  TagList,
  TruncatedBadge,
  TruncatedLink,
  TruncatedText,
} from '@/Components/ui/data-table/cells';
import type { BadgeVariants } from '@/Components/ui/badge';
import { capitalize } from '@/Utils/String';

/**
 * Resolve a translatable value from toFullArray() format.
 * If the value is a translation object like {lt: "...", en: "..."}, returns the string
 * for the current active language. Otherwise returns the value as-is.
 */
export function resolveTranslatable(value: unknown): string {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    const lang = getActiveLanguage();
    const obj = value as Record<string, string>;

    return obj[lang] ?? obj['lt'] ?? Object.values(obj)[0] ?? '';
  }

  return (value as string) ?? '';
}

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
    cell: options.cell ?? (({ row }) => h(TruncatedText, { text: String(row.getValue('id')) })),
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
  lines?: 1 | 2 | 3;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  const accessorKey = options.accessorKey || 'name';
  const routeName = options.routeName || 'edit';

  return {
    accessorKey,
    header: () => $t(accessorKey === 'name' ? 'forms.fields.name' : 'forms.fields.title'),
    cell: options.cell ?? (({ row }) => {
      const value = resolveTranslatable(row.getValue(accessorKey));
      const { id } = row.original;
      const modelName = (row.original as any)?.type || '';
      const baseRouteName = modelName ? `${modelName}s.${routeName}` : `${routeName}`;

      return h(TruncatedLink, {
        href: route(baseRouteName, { id }),
        text: value,
        lines: options.lines ?? 1,
      });
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
    cell: options.cell ?? (({ row }) => {
      const value = row.getValue(accessorKey);
      if (!value) return h(TruncatedText, { text: null });

      try {
        const date = new Date(value as string);
        if (!isValid(date)) return h(TruncatedText, { text: String(value) });

        return h(DateCell, {
          date,
          mode: 'absolute',
          format: {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
          },
        });
      }
      catch (e) {
        return h(TruncatedText, { text: String(value) });
      }
    }),
    size: options.width || 160,
    enableSorting: options.enableSorting !== false,
    sortDescFirst: options.sortDescFirst,
  };
}

/**
 * Create a date column using DateCell component
 */
export function createDateColumn<T>(accessorKey: string, options: {
  title?: string;
  mode?: 'absolute' | 'relative';
  format?: Intl.DateTimeFormatOptions;
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey,
    header: () => options.title || $t(accessorKey),
    cell: options.cell ?? (({ row }) => {
      const value = row.getValue(accessorKey);
      if (!value) return h(TruncatedText, { text: null });

      return h(DateCell, {
        date: value as string | Date,
        mode: options.mode ?? 'absolute',
        format: options.format,
      });
    }),
    size: options.width || 150,
    enableSorting: options.enableSorting !== false,
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
    id: 'tenant.name',
    header: () => capitalize($tChoice('entities.tenant.model', 1)),
    cell: options.cell ?? (({ row }) => {
      const { tenant } = row.original as any;
      if (!tenant) return h(TruncatedText, { text: null });

      return h(TruncatedText, { text: $t(tenant.shortname) });
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
    cell: options.cell ?? (({ row }) => h(TruncatedText, { text: row.getValue('language') as string })),
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
    cell: options.cell ?? (({ row }) => {
      const value = row.getValue(accessorKey);
      const label = value
        ? (options.trueLabel || $t('Yes'))
        : (options.falseLabel || $t('No'));

      return h(TruncatedBadge, {
        text: label,
        variant: value ? 'default' : 'secondary',
      });
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
  maxVisible?: number;
  width?: number;
  enableSorting?: boolean;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey,
    header: () => options.title || $t(accessorKey),
    cell: options.cell ?? (({ row }) => {
      const items = row.getValue(accessorKey) as any[] || [];

      if (!items || items.length === 0) return null;

      return h(TagList, {
        items,
        labelKey: options.labelKey || 'title',
        maxVisible: options.maxVisible ?? 3,
      });
    }),
    size: options.width || 200,
    enableSorting: options.enableSorting !== false,
  };
}

/**
 * Create a standardized text column with truncation and tooltip
 */
export function createTextColumn<T>(accessorKey: string, options: {
  title?: string;
  width?: number;
  enableSorting?: boolean;
  lines?: 1 | 2 | 3;
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey,
    header: () => options.title || $t(accessorKey),
    cell: options.cell ?? (({ row }) => h(TruncatedText, {
      text: resolveTranslatable(row.getValue(accessorKey)),
      lines: options.lines ?? 1,
    })),
    size: options.width || 150,
    enableSorting: options.enableSorting !== false,
  };
}

/**
 * Create a badge column with truncation and tooltip
 */
export function createBadgeColumn<T>(accessorKey: string, options: {
  title?: string;
  width?: number;
  enableSorting?: boolean;
  variant?: BadgeVariants['variant'];
  cell?: ColumnDef<T, any>['cell'];
} = {}): ColumnDef<T, any> {
  return {
    accessorKey,
    header: () => options.title || $t(accessorKey),
    cell: options.cell ?? (({ row }) => h(TruncatedBadge, {
      text: resolveTranslatable(row.getValue(accessorKey)),
      variant: options.variant ?? 'secondary',
    })),
    size: options.width || 150,
    enableSorting: options.enableSorting !== false,
  };
}
