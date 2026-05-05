/**
 * useGanttLayout - Gantt chart row layout calculations
 *
 * Extracted from MeetingsGantt.vue to reduce component complexity.
 * Handles:
 * - Row generation with tenant grouping
 * - Variable row heights (normal vs expanded)
 * - Position calculations (top, height, center)
 * - Container height computation
 */
import { computed, type ComputedRef, type Ref } from 'vue';

/** Base row type - either a tenant header or an institution row */
export type GanttRow
  = | { type: 'tenant'; key: string; tenantId: string | number }
    | {
      type: 'institution';
      key: string | number;
      institutionId: string | number;
      isRelated?: boolean;
      relationshipDirection?: 'outgoing' | 'incoming' | 'sibling';
      authorized?: boolean;
      sourceInstitutionId?: string;
    };

/** Layout row with computed position and height */
export interface LayoutRow {
  key: string | number;
  type: GanttRow['type'];
  tenantId?: string | number;
  institutionId?: string | number;
  isRelated?: boolean;
  relationshipDirection?: 'outgoing' | 'incoming' | 'sibling';
  authorized?: boolean;
  sourceInstitutionId?: string;
  top: number;
  height: number;
}

export interface GanttLayoutOptions {
  /** Base row height in pixels */
  rowHeight: () => number;
  /** Expanded row height in pixels */
  expandedRowHeight: () => number | undefined;
  /** Whether details are expanded */
  detailsExpanded: () => boolean;
  /** Container height prop (e.g., '400px' or '100%') */
  height: () => string | undefined;
  /** Whether to show tenant headers */
  showTenantHeaders: Ref<boolean>;
}

export interface GanttLayoutData {
  /** Filtered institution IDs in display order */
  institutions: ComputedRef<Array<string | number>>;
  /** Institution metadata from props (getter for reactivity) */
  institutionsMeta: () => Array<{
    id: string | number;
    is_related?: boolean;
    relationship_direction?: 'outgoing' | 'incoming' | 'sibling';
    authorized?: boolean;
    source_institution_id?: string;
  }> | undefined;
  /** Institution to tenant mapping (getter for reactivity) */
  institutionTenant: () => Record<string | number, string | number> | undefined;
  /** Merged tenant names lookup */
  mergedTenantNames: ComputedRef<Record<string | number, string>>;
}

export function useGanttLayout(
  options: GanttLayoutOptions,
  data: GanttLayoutData,
) {
  // Helper to get tenant for an institution
  const tenantFor = (id: string | number) => {
    const mapping = data.institutionTenant();
    return mapping?.[id as keyof typeof mapping];
  };

  /**
   * Generate rows with optional tenant grouping
   */
  const rows = computed<GanttRow[]>(() => {
    const ids = data.institutions.value;

    // Create a map for quick lookup of institution metadata
    const institutionMeta = new Map<string | number, {
      is_related?: boolean;
      relationship_direction?: 'outgoing' | 'incoming' | 'sibling';
      authorized?: boolean;
      source_institution_id?: string;
    }>();

    const meta = data.institutionsMeta();
    meta?.forEach((i) => {
      institutionMeta.set(i.id, {
        is_related: i.is_related,
        relationship_direction: i.relationship_direction,
        authorized: i.authorized,
        source_institution_id: i.source_institution_id,
      });
    });

    // Only show tenant headers if the setting is enabled and we have tenant data
    if (
      options.showTenantHeaders.value
      && data.institutionTenant()
      && Object.keys(data.mergedTenantNames.value).length > 0
    ) {
      const byTenant = new Map<string | number, Array<string | number>>();
      for (const id of ids) {
        const t = tenantFor(id) ?? 'unknown';
        const arr = byTenant.get(t) ?? [];
        arr.push(id);
        byTenant.set(t, arr);
      }

      const tenantOrder = Array.from(byTenant.keys()).sort((a, b) =>
        String(data.mergedTenantNames.value[a] ?? a).localeCompare(
          String(data.mergedTenantNames.value[b] ?? b),
        ),
      );

      const out: GanttRow[] = [];
      for (const t of tenantOrder) {
        out.push({ type: 'tenant', key: `__tenant__:${t}`, tenantId: t });
        for (const iid of byTenant.get(t) ?? []) {
          const meta = institutionMeta.get(iid);
          out.push({
            type: 'institution',
            key: iid,
            institutionId: iid,
            isRelated: meta?.is_related,
            relationshipDirection: meta?.relationship_direction,
            authorized: meta?.authorized,
            sourceInstitutionId: meta?.source_institution_id,
          });
        }
      }
      return out;
    }

    // No tenant grouping - just institution rows
    return ids.map((iid) => {
      const meta = institutionMeta.get(iid);
      return {
        type: 'institution',
        key: iid,
        institutionId: iid,
        isRelated: meta?.is_related,
        relationshipDirection: meta?.relationship_direction,
        authorized: meta?.authorized,
        sourceInstitutionId: meta?.source_institution_id,
      } as GanttRow;
    });
  });

  /**
   * Layout rows with computed positions and heights
   */
  const layoutRows = computed<LayoutRow[]>(() => {
    const out: LayoutRow[] = [];
    let y = 0;
    const rowHeight = options.rowHeight();
    const expandedRowHeight = options.expandedRowHeight();
    const detailsExpanded = options.detailsExpanded();

    for (const r of rows.value) {
      const isInst = r.type === 'institution';
      const h = isInst
        ? (detailsExpanded
            ? (expandedRowHeight || rowHeight)
            : rowHeight)
        : rowHeight;

      out.push({
        key: r.key,
        type: r.type,
        tenantId: (r as any).tenantId,
        institutionId: (r as any).institutionId,
        isRelated: (r as any).isRelated,
        relationshipDirection: (r as any).relationshipDirection,
        authorized: (r as any).authorized,
        sourceInstitutionId: (r as any).sourceInstitutionId,
        top: y,
        height: h,
      });
      y += h;
    }

    return out;
  });

  /** Height lookup by row key */
  const heightByKey = computed(() => {
    const m = new Map<string | number, number>();
    for (const r of layoutRows.value) m.set(r.key, r.height);
    return m;
  });

  /** Top position lookup by row key */
  const topByKey = computed(() => {
    const m = new Map<string | number, number>();
    for (const r of layoutRows.value) m.set(r.key, r.top);
    return m;
  });

  /** Get top position for a row key */
  const rowTop = (key: string | number) => topByKey.value.get(key) ?? 0;

  /** Get height for a row key */
  const rowHeightFor = (key: string | number) => heightByKey.value.get(key) ?? options.rowHeight();

  /** Get vertical center for a row key */
  const rowCenter = (key: string | number) => rowTop(key) + rowHeightFor(key) / 2;

  /**
   * Container height - handles fullscreen mode and calculated heights
   */
  const containerHeight = computed(() => {
    const height = options.height();
    // For fullscreen mode with 100% height, don't set an explicit height
    if (height === '100%') {
      return undefined;
    }

    // If height prop is provided (e.g., other specific values), use it
    if (height) {
      return height;
    }

    // Otherwise calculate based on content
    const rowsH = layoutRows.value.reduce((acc, r) => acc + r.height, 0);
    const contentHeight = rowsH + 22; // header height
    // Add extra padding to prevent overflow when horizontal scrollbar appears
    const paddedHeight = contentHeight + 20;
    return `${Math.max(200, paddedHeight)}px`; // Ensure minimum height
  });

  return {
    rows,
    layoutRows,
    heightByKey,
    topByKey,
    rowTop,
    rowHeightFor,
    rowCenter,
    containerHeight,
    tenantFor,
  };
}
