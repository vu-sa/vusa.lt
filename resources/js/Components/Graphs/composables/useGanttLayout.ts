/**
 * Row layout for the meetings Gantt: optional group header rows (padalinys or institution type)
 * and institution rows with their positions. Grouped by type, an institution with several types
 * gets a row in each, so row keys are `<group>::<institution>`, never the bare institution id.
 */
import { computed, type ComputedRef, type Ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { horizontalScrollbarSize } from '../scrollbarSize';

import type { GanttGroupBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

export const GANTT_ROW_HEIGHT = 28;
export const GANTT_AXIS_HEIGHT = 22;

const UNTYPED_GROUP = '__untyped__';

export interface GanttInstitutionMeta {
  id: string | number;
  tenant_id?: string | number;
  types?: Array<{ id: string | number; title?: string | null }>;
  is_related?: boolean;
  relationship_direction?: 'outgoing' | 'incoming' | 'mutual';
  authorized?: boolean;
  source_institution_id?: string;
}

export interface LayoutRow {
  key: string;
  type: 'group' | 'institution';
  /** Header text for group rows */
  label?: string;
  institutionId?: string | number;
  isRelated?: boolean;
  relationshipDirection?: 'outgoing' | 'incoming' | 'mutual';
  authorized?: boolean;
  sourceInstitutionId?: string;
  top: number;
  height: number;
}

export interface GanttLayoutOptions {
  groupBy: Ref<GanttGroupBy>;
  /** Container height prop: '100%' in focus mode, otherwise sized to the rows up to `maxHeight` */
  height: () => string | undefined;
  maxHeight?: number;
}

export interface GanttLayoutData {
  /** Filtered institution ids in display order */
  institutions: ComputedRef<Array<string | number>>;
  institutionsMeta: () => GanttInstitutionMeta[] | undefined;
  tenantNames: ComputedRef<Record<string | number, string>>;
}

interface Group {
  id: string;
  label: string;
  institutionIds: Array<string | number>;
}

export function useGanttLayout(options: GanttLayoutOptions, data: GanttLayoutData) {
  const metaById = computed(() => {
    const map = new Map<string, GanttInstitutionMeta>();
    for (const institution of data.institutionsMeta() ?? []) {
      map.set(String(institution.id), institution);
    }
    return map;
  });

  const groups = computed<Group[] | null>(() => {
    const ids = data.institutions.value;
    const byGroup = new Map<string, Group>();
    const add = (id: string, label: string, institutionId: string | number) => {
      const group = byGroup.get(id) ?? { id, label, institutionIds: [] };
      group.institutionIds.push(institutionId);
      byGroup.set(id, group);
    };

    switch (options.groupBy.value) {
      case 'tenant':
        for (const id of ids) {
          const tenantId = metaById.value.get(String(id))?.tenant_id;
          const key = tenantId == null ? UNTYPED_GROUP : String(tenantId);
          add(key, tenantId == null ? '—' : (data.tenantNames.value[tenantId] ?? String(tenantId)), id);
        }
        break;
      case 'type':
        for (const id of ids) {
          const types = metaById.value.get(String(id))?.types ?? [];
          if (types.length === 0) {
            add(UNTYPED_GROUP, $t('Be tipo'), id);
          }
          for (const type of types) {
            add(String(type.id), type.title || String(type.id), id);
          }
        }
        break;
      default:
        return null;
    }

    return Array.from(byGroup.values()).sort((a, b) => {
      if (a.id === UNTYPED_GROUP) return 1;
      if (b.id === UNTYPED_GROUP) return -1;
      return a.label.localeCompare(b.label);
    });
  });

  const institutionRow = (key: string, institutionId: string | number): Omit<LayoutRow, 'top' | 'height'> => {
    const meta = metaById.value.get(String(institutionId));
    return {
      key,
      type: 'institution',
      institutionId,
      isRelated: meta?.is_related,
      relationshipDirection: meta?.relationship_direction,
      authorized: meta?.authorized,
      sourceInstitutionId: meta?.source_institution_id,
    };
  };

  const layoutRows = computed<LayoutRow[]>(() => {
    const rows: Array<Omit<LayoutRow, 'top' | 'height'>> = [];

    if (groups.value) {
      for (const group of groups.value) {
        const groupKey = `__group__:${options.groupBy.value}:${group.id}`;
        rows.push({ key: groupKey, type: 'group', label: group.label });
        for (const id of group.institutionIds) {
          rows.push(institutionRow(`${groupKey}::${id}`, id));
        }
      }
    }
    else {
      for (const id of data.institutions.value) {
        rows.push(institutionRow(`::${id}`, id));
      }
    }

    return rows.map((row, index) => ({ ...row, top: index * GANTT_ROW_HEIGHT, height: GANTT_ROW_HEIGHT }));
  });

  /** Every row an institution appears on — more than one when grouped by type. */
  const rowKeysByInstitution = computed(() => {
    const map = new Map<string, string[]>();
    for (const row of layoutRows.value) {
      if (row.type !== 'institution') continue;
      const id = String(row.institutionId);
      const keys = map.get(id) ?? [];
      keys.push(row.key);
      map.set(id, keys);
    }
    return map;
  });

  const topByKey = computed(() => new Map(layoutRows.value.map(row => [row.key, row.top])));

  const rowTop = (key: string | number) => topByKey.value.get(String(key)) ?? 0;
  const rowHeightFor = (_key?: string | number) => GANTT_ROW_HEIGHT;
  const rowCenter = (key: string | number) => rowTop(key) + GANTT_ROW_HEIGHT / 2;

  const contentHeight = computed(() => layoutRows.value.length * GANTT_ROW_HEIGHT);

  const containerHeight = computed(() => {
    const height = options.height();
    if (height === '100%') {
      return undefined;
    }
    if (height) {
      return height;
    }

    // Borders plus the horizontal scrollbar's strip; without them a vertical scrollbar appears on a chart that fits.
    const padded = contentHeight.value + GANTT_AXIS_HEIGHT + 3 + horizontalScrollbarSize();
    return `${Math.min(Math.max(60, padded), options.maxHeight ?? Infinity)}px`;
  });

  return {
    layoutRows,
    rowKeysByInstitution,
    rowTop,
    rowHeightFor,
    rowCenter,
    contentHeight,
    containerHeight,
  };
}
