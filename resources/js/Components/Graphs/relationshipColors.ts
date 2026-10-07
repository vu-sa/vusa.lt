/**
 * Shared visual vocabulary for institution relationship graphs: the full force-directed graph
 * colours edges by category, the one-deep radial preview by direction.
 */

export type RelationshipType = 'direct' | 'type' | 'cross-tenant';
export type RelationshipDirection = 'outgoing' | 'incoming' | 'mutual';

export interface RelationshipStyle {
  /** Stroke/marker color, legible on both light and dark backgrounds. */
  color: string;
  /** i18n key for the legend / tooltip label. */
  labelKey: string;
}

/** Edge styling keyed by category (used by the main graph legend). */
export const EDGE_TYPE_STYLES: Record<RelationshipType, RelationshipStyle> = {
  'direct': { color: '#10b981', labelKey: 'relationships.graph.type_direct' },
  'type': { color: '#8b5cf6', labelKey: 'relationships.graph.type_type_based' },
  'cross-tenant': { color: '#f97316', labelKey: 'relationships.graph.type_cross_tenant' },
};

/** Edge styling keyed by `direction` (used by the radial preview). */
export const DIRECTION_STYLES: Record<RelationshipDirection, RelationshipStyle & { arrow: 'out' | 'in' | 'none' }> = {
  outgoing: { color: '#10b981', labelKey: 'relationships.graph.direction_outgoing', arrow: 'out' },
  incoming: { color: '#3b82f6', labelKey: 'relationships.graph.direction_incoming', arrow: 'in' },
  mutual: { color: '#a1a1aa', labelKey: 'relationships.graph.direction_mutual', arrow: 'none' },
};

export type RelationshipScope = 'within-tenant' | 'cross-tenant';

/** Edge styling keyed by `scope` (used by the type-overview graph). */
export const SCOPE_STYLES: Record<RelationshipScope, RelationshipStyle> = {
  'within-tenant': { color: '#3b82f6', labelKey: 'relationships.graph.scope_within_tenant' },
  'cross-tenant': { color: '#f97316', labelKey: 'relationships.graph.scope_cross_tenant' },
};

const FALLBACK_COLOR = '#a1a1aa';

export function edgeCategory(edge: { via?: string | null; cross_tenant?: boolean | null }): RelationshipType {
  if (edge.cross_tenant) {
    return 'cross-tenant';
  }

  return edge.via === 'type' ? 'type' : 'direct';
}

export function edgeTypeColor(type: string | undefined | null): string {
  return EDGE_TYPE_STYLES[type as RelationshipType]?.color ?? FALLBACK_COLOR;
}

export function scopeColor(scope: string | undefined | null): string {
  return SCOPE_STYLES[scope as RelationshipScope]?.color ?? FALLBACK_COLOR;
}

export function directionStyle(direction: string | undefined | null) {
  return DIRECTION_STYLES[direction as RelationshipDirection] ?? DIRECTION_STYLES.mutual;
}
