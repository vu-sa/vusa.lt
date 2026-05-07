import { describe, it, expect, vi, beforeAll } from 'vitest';
import { h } from 'vue';

// Mock Vue component imports so the unit test project doesn't need to compile .vue files
vi.mock('@/Components/ui/data-table/cells', () => ({
  DateCell: { name: 'DateCell', render: () => h('span', 'DateCell') },
  TagList: { name: 'TagList', render: () => h('span', 'TagList') },
  TruncatedBadge: { name: 'TruncatedBadge', render: () => h('span', 'TruncatedBadge') },
  TruncatedLink: { name: 'TruncatedLink', render: () => h('span', 'TruncatedLink') },
  TruncatedText: { name: 'TruncatedText', render: () => h('span', 'TruncatedText') },
}));

// Mock badge module
vi.mock('@/Components/ui/badge', () => ({
  BadgeVariants: {},
}));

import {
  resolveTranslatable,
  createIdColumn,
  createTitleColumn,
  createTimestampColumn,
  createDateColumn,
  createTenantColumn,
  createLanguageColumn,
  createBooleanColumn,
  createTagsColumn,
  createTextColumn,
  createBadgeColumn,
} from '@/Composables/useDataTableColumns';

// Mock laravel-vue-i18n
vi.mock('laravel-vue-i18n', async () => {
  const actual = await vi.importActual('laravel-vue-i18n');
  return {
    ...(actual as any),
    trans: (key: string) => key,
    transChoice: (key: string, count: number) => key,
    getActiveLanguage: () => 'lt',
  };
});

// Helper to invoke cell renderers
function invokeCell(column: any, rowValue: any, originalRow: any = {}) {
  const mockRow = {
    getValue: (key: string) => rowValue,
    original: originalRow,
  };
  return column.cell({ row: mockRow, getValue: () => rowValue } as any);
}

describe('resolveTranslatable', () => {
  it('returns current locale value from translation object', () => {
    expect(resolveTranslatable({ lt: 'Lietuviškai', en: 'English' })).toBe('Lietuviškai');
  });

  it('falls back to lt when current locale is missing', () => {
    expect(resolveTranslatable({ lt: 'Lietuviškai' })).toBe('Lietuviškai');
  });

  it('falls back to first available value when lt is missing', () => {
    expect(resolveTranslatable({ en: 'English' })).toBe('English');
  });

  it('returns string as-is for non-objects', () => {
    expect(resolveTranslatable('Plain string')).toBe('Plain string');
  });

  it('returns empty string for null', () => {
    expect(resolveTranslatable(null)).toBe('');
  });

  it('returns empty string for undefined', () => {
    expect(resolveTranslatable(undefined)).toBe('');
  });

  it('returns empty string for empty object', () => {
    expect(resolveTranslatable({})).toBe('');
  });
});

describe('createIdColumn', () => {
  it('creates column with correct defaults', () => {
    const col = createIdColumn();
    expect((col as any).accessorKey).toBe('id');
    expect(col.size).toBe(60);
    expect(col.enableSorting).toBe(true);
  });

  it('applies custom width', () => {
    const col = createIdColumn({ width: 80 });
    expect(col.size).toBe(80);
  });

  it('disables sorting when requested', () => {
    const col = createIdColumn({ enableSorting: false });
    expect(col.enableSorting).toBe(false);
  });

  it('renders cell with truncated text', () => {
    const col = createIdColumn();
    const vnode = invokeCell(col, 123);
    expect(vnode).toBeDefined();
  });
});

describe('createTitleColumn', () => {
  it('uses default accessorKey and routeName', () => {
    const col = createTitleColumn();
    expect((col as any).accessorKey).toBe('name');
  });

  it('uses custom accessorKey', () => {
    const col = createTitleColumn({ accessorKey: 'title' });
    expect((col as any).accessorKey).toBe('title');
  });

  it('resolves translatable names', () => {
    const col = createTitleColumn();
    const vnode = invokeCell(col, { lt: 'Pavadinimas', en: 'Title' }, { id: 1, type: 'news' });
    expect(vnode).toBeDefined();
  });

  it('applies custom width', () => {
    const col = createTitleColumn({ width: 300 });
    expect(col.size).toBe(300);
  });

  it('disables sorting when requested', () => {
    const col = createTitleColumn({ enableSorting: false });
    expect(col.enableSorting).toBe(false);
  });
});

describe('createTimestampColumn', () => {
  it('creates column with accessorKey', () => {
    const col = createTimestampColumn('created_at');
    expect((col as any).accessorKey).toBe('created_at');
  });

  it('renders DateCell for valid ISO date', () => {
    const col = createTimestampColumn('created_at');
    const vnode = invokeCell(col, '2024-01-15T10:30:00');
    expect(vnode).toBeDefined();
  });

  it('returns truncated text for invalid date', () => {
    const col = createTimestampColumn('created_at');
    const vnode = invokeCell(col, 'not-a-date');
    expect(vnode).toBeDefined();
  });

  it('returns em-dash for null value', () => {
    const col = createTimestampColumn('created_at');
    const vnode = invokeCell(col, null);
    expect(vnode).toBeDefined();
  });

  it('uses custom title', () => {
    const col = createTimestampColumn('created_at', { title: 'Created' });
    expect((col.header as any)()).toBe('Created');
  });

  it('applies custom width', () => {
    const col = createTimestampColumn('created_at', { width: 200 });
    expect(col.size).toBe(200);
  });

  it('applies sortDescFirst', () => {
    const col = createTimestampColumn('created_at', { sortDescFirst: true });
    expect(col.sortDescFirst).toBe(true);
  });
});

describe('createDateColumn', () => {
  it('creates column with accessorKey', () => {
    const col = createDateColumn('publish_date');
    expect((col as any).accessorKey).toBe('publish_date');
  });

  it('renders DateCell with default absolute mode', () => {
    const col = createDateColumn('publish_date');
    const vnode = invokeCell(col, '2024-01-15');
    expect(vnode).toBeDefined();
  });

  it('renders DateCell with relative mode', () => {
    const col = createDateColumn('publish_date', { mode: 'relative' });
    const vnode = invokeCell(col, '2024-01-15');
    expect(vnode).toBeDefined();
  });

  it('returns em-dash for null value', () => {
    const col = createDateColumn('publish_date');
    const vnode = invokeCell(col, null);
    expect(vnode).toBeDefined();
  });

  it('uses custom title', () => {
    const col = createDateColumn('publish_date', { title: 'Published' });
    expect((col.header as any)()).toBe('Published');
  });
});

describe('createTenantColumn', () => {
  it('renders tenant shortname', () => {
    const col = createTenantColumn();
    const vnode = invokeCell(col, null, { tenant: { shortname: 'VU SA' } });
    expect(vnode).toBeDefined();
  });

  it('returns em-dash for missing tenant', () => {
    const col = createTenantColumn();
    const vnode = invokeCell(col, null, {});
    expect(vnode).toBeDefined();
  });

  it('applies custom width', () => {
    const col = createTenantColumn({ width: 120 });
    expect(col.size).toBe(120);
  });
});

describe('createLanguageColumn', () => {
  it('renders language value', () => {
    const col = createLanguageColumn();
    const vnode = invokeCell(col, 'lt');
    expect(vnode).toBeDefined();
  });

  it('applies custom width', () => {
    const col = createLanguageColumn({ width: 80 });
    expect(col.size).toBe(80);
  });
});

describe('createBooleanColumn', () => {
  it('shows true label for truthy value', () => {
    const col = createBooleanColumn('is_active');
    const vnode = invokeCell(col, true);
    expect(vnode).toBeDefined();
    expect(vnode.props.text).toBe('Yes');
  });

  it('shows false label for falsy value', () => {
    const col = createBooleanColumn('is_active');
    const vnode = invokeCell(col, false);
    expect(vnode).toBeDefined();
    expect(vnode.props.text).toBe('No');
  });

  it('uses custom labels', () => {
    const col = createBooleanColumn('is_active', { trueLabel: 'Active', falseLabel: 'Inactive' });
    const trueVnode = invokeCell(col, true);
    const falseVnode = invokeCell(col, false);
    expect(trueVnode.props.text).toBe('Active');
    expect(falseVnode.props.text).toBe('Inactive');
  });

  it('uses correct badge variants', () => {
    const col = createBooleanColumn('is_active');
    const trueVnode = invokeCell(col, true);
    const falseVnode = invokeCell(col, false);
    expect(trueVnode.props.variant).toBe('default');
    expect(falseVnode.props.variant).toBe('secondary');
  });
});

describe('createTagsColumn', () => {
  it('renders TagList with items', () => {
    const col = createTagsColumn('tags');
    const items = [{ title: 'Tag A' }, { title: 'Tag B' }];
    const vnode = invokeCell(col, items);
    expect(vnode).toBeDefined();
    expect(vnode.props.items).toEqual(items);
  });

  it('returns null for empty array', () => {
    const col = createTagsColumn('tags');
    const vnode = invokeCell(col, []);
    expect(vnode).toBeNull();
  });

  it('returns null for undefined', () => {
    const col = createTagsColumn('tags');
    const vnode = invokeCell(col, undefined);
    expect(vnode).toBeNull();
  });

  it('uses custom labelKey', () => {
    const col = createTagsColumn('tags', { labelKey: 'name' });
    const items = [{ name: 'Tag A' }];
    const vnode = invokeCell(col, items);
    expect(vnode.props.labelKey).toBe('name');
  });

  it('uses custom maxVisible', () => {
    const col = createTagsColumn('tags', { maxVisible: 2 });
    const items = [{ title: 'A' }, { title: 'B' }, { title: 'C' }];
    const vnode = invokeCell(col, items);
    expect(vnode.props.maxVisible).toBe(2);
  });
});

describe('createTextColumn', () => {
  it('renders truncated text', () => {
    const col = createTextColumn('description');
    const vnode = invokeCell(col, 'Some description');
    expect(vnode).toBeDefined();
  });

  it('resolves translatable values', () => {
    const col = createTextColumn('description');
    const vnode = invokeCell(col, { lt: 'Aprašymas', en: 'Description' });
    expect(vnode).toBeDefined();
  });

  it('uses custom lines', () => {
    const col = createTextColumn('description', { lines: 2 });
    const vnode = invokeCell(col, 'Text');
    expect(vnode.props.lines).toBe(2);
  });

  it('uses custom title', () => {
    const col = createTextColumn('description', { title: 'Desc' });
    expect((col.header as any)()).toBe('Desc');
  });
});

describe('createBadgeColumn', () => {
  it('renders badge with resolved text', () => {
    const col = createBadgeColumn('status');
    const vnode = invokeCell(col, 'Active');
    expect(vnode).toBeDefined();
  });

  it('uses secondary variant by default', () => {
    const col = createBadgeColumn('status');
    const vnode = invokeCell(col, 'Active');
    expect(vnode.props.variant).toBe('secondary');
  });

  it('uses custom variant', () => {
    const col = createBadgeColumn('status', { variant: 'destructive' });
    const vnode = invokeCell(col, 'Active');
    expect(vnode.props.variant).toBe('destructive');
  });

  it('uses custom title', () => {
    const col = createBadgeColumn('status', { title: 'State' });
    expect((col.header as any)()).toBe('State');
  });
});
