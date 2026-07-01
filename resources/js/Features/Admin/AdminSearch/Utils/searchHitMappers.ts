/**
 * Search hit mappers
 *
 * Single source of truth for turning a raw Typesense search document into the
 * normalized shape the master-detail UI needs (list rows, tab grouping, detail
 * pane dispatch). Shared by the All tab (flat mixed list), the per-collection
 * tabs, and the detail pane so every surface renders hits identically.
 */

import type { Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import {
  AgendaItemIcon,
  CalendarIcon,
  DocumentIcon,
  DutyIcon,
  InstitutionIcon,
  MeetingIcon,
  NewsIcon,
  PageIcon,
  ResourceIcon,
  UserIcon,
} from '@/Components/icons';
import type {
  AgendaItemSearchResult,
  CalendarSearchResult,
  DocumentSearchResult,
  DutySearchResult,
  InstitutionSearchResult,
  MeetingSearchResult,
  MultiSearchResults,
  NewsSearchResult,
  PageSearchResult,
  ResourceSearchResult,
  UserSearchResult,
} from '@/Shared/Search/types';
import type { AdminCollection } from '../Types/AdminSearchTypes';
import { completionTone, voteTone, type BadgeTone } from './searchBadges';
import { getFacetValueLabel } from '../Config/collectionFacetConfig';

/** Canonical collection keys, matching MultiSearchResults result arrays. */
export type SearchCollectionKey
  = | 'meetings'
    | 'agendaItems'
    | 'institutions'
    | 'resources'
    | 'duties'
    | 'documents'
    | 'news'
    | 'pages'
    | 'calendar'
    | 'users';

/**
 * Optional per-render context passed to mappers that need user-relative state
 * (e.g. duties flagging cross-tenant "external" rows, or meetings flagging
 * related-institution results). Mappers ignore it unless they opt in.
 */
export interface MapperContext {
  /** Tenant ids the current user can access for this collection. */
  ownTenantIds?: number[];
  isSuperAdmin?: boolean;
  /** Institution ids the user has direct duties in (for related-institution badges). */
  directInstitutionIds?: string[];
}

/** A raw document normalized for list rendering + detail dispatch. */
export interface NormalizedSearchHit {
  /** Stable, collection-prefixed row id (e.g. `meeting-123`). */
  id: string;
  /** The underlying record id. */
  recordId: string;
  collection: SearchCollectionKey;
  icon: Component;
  /** Thumbnail URL rendered in place of the icon when present (e.g. resource photo). */
  imageUrl?: string;
  title: string;
  subtitle?: string;
  /** Tenant shortname; kept for the detail pane, not rendered in the row. */
  badge?: string;
  meta?: string;
  href?: string;
  /** Contextual colored status badge shown on the list row. */
  statusBadge?: { label: string; tone: BadgeTone };
  /** Duties only: the row belongs to a tenant outside the user's own scope. */
  isExternal?: boolean;
  /** Meetings / agenda items: result comes from a related (non-direct) institution. */
  isRelated?: boolean;
  /** True when the hit represents a recently visited page (not a search result). */
  isRecent?: boolean;
  /** Original Typesense document for the detail pane. */
  raw: unknown;
}

/** Per-collection display metadata (icon + label + page tab). */
export const COLLECTION_META: Record<SearchCollectionKey, {
  icon: Component;
  /** i18n key (LT source string). */
  label: string;
  /** Unified search page tab value, when the collection has a dedicated tab. */
  tab?: string;
}> = {
  meetings: { icon: MeetingIcon, label: 'Posėdžiai', tab: 'meetings' },
  agendaItems: { icon: AgendaItemIcon, label: 'Darbotvarkės punktai', tab: 'agenda-items' },
  institutions: { icon: InstitutionIcon, label: 'Institucijos', tab: 'institutions' },
  resources: { icon: ResourceIcon, label: 'Ištekliai', tab: 'resources' },
  duties: { icon: DutyIcon, label: 'Pareigybės', tab: 'duties' },
  documents: { icon: DocumentIcon, label: 'Dokumentai', tab: 'documents' },
  news: { icon: NewsIcon, label: 'Naujienos' },
  pages: { icon: PageIcon, label: 'Puslapiai' },
  calendar: { icon: CalendarIcon, label: 'Kalendorius' },
  users: { icon: UserIcon, label: 'Nariai', tab: 'users' },
};

/** Per-collection color scheme for icon containers (less saturated than the palette defaults). */
export const COLLECTION_COLOR: Record<SearchCollectionKey, {
  bg: string;
  text: string;
  hoverBg: string;
  darkBg: string;
  darkText: string;
  darkHoverBg: string;
}> = {
  meetings: {
    bg: 'bg-blue-400/10', text: 'text-blue-500',
    hoverBg: 'group-hover:bg-blue-400/15',
    darkBg: 'dark:bg-blue-500/15', darkText: 'dark:text-blue-400',
    darkHoverBg: 'dark:group-hover:bg-blue-500/25',
  },
  agendaItems: {
    bg: 'bg-violet-400/10', text: 'text-violet-500',
    hoverBg: 'group-hover:bg-violet-400/15',
    darkBg: 'dark:bg-violet-500/15', darkText: 'dark:text-violet-400',
    darkHoverBg: 'dark:group-hover:bg-violet-500/25',
  },
  institutions: {
    bg: 'bg-indigo-400/10', text: 'text-indigo-500',
    hoverBg: 'group-hover:bg-indigo-400/15',
    darkBg: 'dark:bg-indigo-500/15', darkText: 'dark:text-indigo-400',
    darkHoverBg: 'dark:group-hover:bg-indigo-500/25',
  },
  resources: {
    bg: 'bg-orange-400/10', text: 'text-orange-500',
    hoverBg: 'group-hover:bg-orange-400/15',
    darkBg: 'dark:bg-orange-500/15', darkText: 'dark:text-orange-400',
    darkHoverBg: 'dark:group-hover:bg-orange-500/25',
  },
  duties: {
    bg: 'bg-pink-400/10', text: 'text-pink-500',
    hoverBg: 'group-hover:bg-pink-400/15',
    darkBg: 'dark:bg-pink-500/15', darkText: 'dark:text-pink-400',
    darkHoverBg: 'dark:group-hover:bg-pink-500/25',
  },
  documents: {
    bg: 'bg-teal-400/10', text: 'text-teal-500',
    hoverBg: 'group-hover:bg-teal-400/15',
    darkBg: 'dark:bg-teal-500/15', darkText: 'dark:text-teal-400',
    darkHoverBg: 'dark:group-hover:bg-teal-500/25',
  },
  news: {
    bg: 'bg-amber-400/10', text: 'text-amber-500',
    hoverBg: 'group-hover:bg-amber-400/15',
    darkBg: 'dark:bg-amber-500/15', darkText: 'dark:text-amber-400',
    darkHoverBg: 'dark:group-hover:bg-amber-500/25',
  },
  pages: {
    bg: 'bg-sky-400/10', text: 'text-sky-500',
    hoverBg: 'group-hover:bg-sky-400/15',
    darkBg: 'dark:bg-sky-500/15', darkText: 'dark:text-sky-400',
    darkHoverBg: 'dark:group-hover:bg-sky-500/25',
  },
  calendar: {
    bg: 'bg-rose-400/10', text: 'text-rose-500',
    hoverBg: 'group-hover:bg-rose-400/15',
    darkBg: 'dark:bg-rose-500/15', darkText: 'dark:text-rose-400',
    darkHoverBg: 'dark:group-hover:bg-rose-500/25',
  },
  users: {
    bg: 'bg-emerald-400/10', text: 'text-emerald-500',
    hoverBg: 'group-hover:bg-emerald-400/15',
    darkBg: 'dark:bg-emerald-500/15', darkText: 'dark:text-emerald-400',
    darkHoverBg: 'dark:group-hover:bg-emerald-500/25',
  },
};

/** Convenience helper to resolve a collection's color classes for the icon container. */
export function getCollectionColor(key: SearchCollectionKey): typeof COLLECTION_COLOR[SearchCollectionKey] {
  return COLLECTION_COLOR[key];
}

/** Map an AdminCollection (snake_case) to the canonical result key (camelCase). */
export function adminCollectionToKey(collection: AdminCollection): SearchCollectionKey {
  return collection === 'agenda_items' ? 'agendaItems' : (collection as SearchCollectionKey);
}

/**
 * The document field carrying each collection's recency timestamp (Unix seconds).
 * Used as the cross-collection tiebreak when interleaving by relevance. Duties
 * have no meaningful date, so they fall back to 0.
 */
const HIT_DATE_FIELD: Record<SearchCollectionKey, string | null> = {
  meetings: 'start_time',
  agendaItems: 'meeting_start_time',
  institutions: 'created_at',
  resources: 'created_at',
  duties: null,
  documents: 'document_date',
  news: 'publish_time',
  pages: 'created_at',
  calendar: 'date',
  users: 'created_at',
};

/** Extract a comparable recency timestamp (Unix seconds) from a raw hit document. */
export function getSearchHitTimestamp(collection: SearchCollectionKey, doc: unknown): number {
  const field = HIT_DATE_FIELD[collection];
  if (!field || typeof doc !== 'object' || doc === null) {
    return 0;
  }
  const value = (doc as Record<string, unknown>)[field];
  return typeof value === 'number' ? value : 0;
}

/**
 * Read the Typesense relevance score attached by `multiSearch` as a BigInt.
 *
 * The score is an int64 (~1.15e18) that exceeds `Number.MAX_SAFE_INTEGER`, so it
 * must be compared as a BigInt — comparing as a JS number silently collapses
 * distinct scores into ties. Accepts the string form (preferred) or a number.
 */
export function getSearchHitTextMatch(doc: unknown): bigint {
  if (typeof doc !== 'object' || doc === null) {
    return 0n;
  }
  const value = (doc as Record<string, unknown>)._text_match;
  if (typeof value === 'string' && /^\d+$/.test(value)) {
    return BigInt(value);
  }
  if (typeof value === 'number' && Number.isFinite(value)) {
    return BigInt(Math.trunc(value));
  }
  return 0n;
}

/** Format a Unix (seconds) timestamp as a localized date, or undefined. */
export function formatSearchDate(timestamp?: number): string | undefined {
  if (!timestamp) {
    return undefined;
  }
  return new Date(timestamp * 1000).toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

type Mapper<T> = (doc: T, ctx?: MapperContext) => Omit<NormalizedSearchHit, 'id' | 'collection' | 'icon' | 'raw'>;

/**
 * Whether a duty's home tenant falls outside the current user's accessible
 * tenants for the duties collection (i.e. it surfaces only because the user's
 * tenant is an assignable tenant). Super-admins never see anything as external.
 */
export function isDutyExternal(doc: DutySearchResult, ctx?: MapperContext): boolean {
  if (!ctx || ctx.isSuperAdmin || !ctx.ownTenantIds?.length) {
    return false;
  }
  const home = doc.home_tenant_id;
  return home != null && !ctx.ownTenantIds.includes(home);
}

/**
 * Whether a result is from a related (non-direct) institution.
 * Only meaningful when directInstitutionIds are provided and the document has
 * institution_ids. Returns undefined when we can't determine relatedness.
 */
function computeIsRelated(institutionIds?: string[], directIds?: string[]): boolean | undefined {
  if (!directIds || directIds.length === 0 || !institutionIds || institutionIds.length === 0) {
    return undefined;
  }
  const hasDirectMatch = institutionIds.some(id => directIds.includes(id));
  return !hasDirectMatch;
}

const MAPPERS: { [K in SearchCollectionKey]: Mapper<any> } = {
  meetings: (m: MeetingSearchResult, ctx?: MapperContext) => ({
    recordId: String(m.id),
    title: m.title ?? $t('Be pavadinimo'),
    subtitle: m.institution_name_lt || m.institution_name_en,
    badge: m.tenant_shortname,
    meta: formatSearchDate(m.start_time),
    href: route('meetings.show', m.id),
    statusBadge: m.completion_status
      ? { label: getFacetValueLabel('completion_status', m.completion_status), tone: completionTone(m.completion_status) }
      : undefined,
    isRelated: computeIsRelated(m.institution_ids, ctx?.directInstitutionIds),
  }),
  agendaItems: (a: AgendaItemSearchResult, ctx?: MapperContext) => ({
    recordId: String(a.id),
    title: a.title ?? $t('Be pavadinimo'),
    subtitle: a.institution_name_lt || a.institution_name_en,
    meta: formatSearchDate(a.meeting_start_time),
    href: route('agendaItems.edit', a.id),
    statusBadge: a.decision
      ? { label: getFacetValueLabel('decision', a.decision), tone: voteTone(a.decision) }
      : undefined,
    isRelated: computeIsRelated(a.institution_ids, ctx?.directInstitutionIds),
  }),
  institutions: (i: InstitutionSearchResult) => ({
    recordId: String(i.id),
    title: i.name_lt || i.name_en || $t('Be pavadinimo'),
    subtitle: i.tenant_shortname,
    badge: i.tenant_shortname,
    href: route('institutions.show', i.id),
  }),
  resources: (r: ResourceSearchResult) => ({
    recordId: String(r.id),
    title: r.name_lt || r.name_en || $t('Be pavadinimo'),
    subtitle: [r.location, r.category_name].filter(Boolean).join(' • ') || undefined,
    imageUrl: r.image_url || undefined,
    badge: r.tenant_shortname,
    href: route('resources.edit', r.id),
    statusBadge: {
      label: r.is_reservable ? $t('Skolinamas') : $t('Neskolinamas'),
      tone: (r.is_reservable ? 'success' : 'neutral') as BadgeTone,
    },
  }),
  duties: (d: DutySearchResult, ctx?: MapperContext) => {
    const external = isDutyExternal(d, ctx);
    const memberMeta = d.current_user_names?.length
      ? d.current_user_names.slice(0, 2).join(', ') + (d.current_user_names.length > 2 ? ` +${d.current_user_names.length - 2}` : '')
      : d.type_titles?.[0];
    return {
      recordId: String(d.id),
      title: d.name_lt || d.name_en || $t('Be pavadinimo'),
      subtitle: d.institution_name_lt || d.institution_name_en,
      badge: d.tenant_shortname,
      meta: memberMeta,
      href: route('duties.show', d.id),
      isExternal: external,
      // Subtle cross-tenant indicator: only external duties get a badge, labelled
      // with their owning padalinys.
      statusBadge: external && d.tenant_shortname
        ? { label: d.tenant_shortname, tone: 'info' as BadgeTone }
        : undefined,
    };
  },
  documents: (d: DocumentSearchResult) => ({
    recordId: String(d.id),
    title: d.title ?? $t('Be pavadinimo'),
    subtitle: d.content_type,
    badge: d.tenant_shortname,
    meta: formatSearchDate(d.document_date),
    href: d.anonymous_url,
    statusBadge: d.content_type
      ? { label: d.content_type, tone: 'neutral' as BadgeTone }
      : undefined,
  }),
  news: (n: NewsSearchResult) => ({
    recordId: String(n.id),
    title: n.title ?? $t('Be pavadinimo'),
    badge: n.tenant_name,
    meta: formatSearchDate(n.publish_time),
    href: route('news.edit', n.id),
  }),
  pages: (p: PageSearchResult) => ({
    recordId: String(p.id),
    title: p.title ?? $t('Be pavadinimo'),
    subtitle: p.category_name,
    badge: p.tenant_name,
    href: route('pages.edit', p.id),
  }),
  calendar: (c: CalendarSearchResult) => ({
    recordId: String(c.id),
    title: c.title_lt || c.title || $t('Be pavadinimo'),
    badge: c.tenant_name,
    meta: formatSearchDate(c.date),
    href: route('calendar.edit', c.id),
  }),
  users: (u: UserSearchResult) => ({
    recordId: String(u.id),
    title: u.name || $t('Be pavadinimo'),
    subtitle: u.email,
    badge: u.tenant_shortname,
    meta: u.current_duty_names?.[0],
    href: route('users.show', u.id),
    statusBadge: u.is_active === false
      ? { label: $t('Ištrintas'), tone: 'destructive' as BadgeTone }
      : undefined,
  }),
};

/**
 * Fixed collection priority for the "All" tab when there is no query (browse
 * mode). With a query, hits are interleaved by relevance instead — see
 * {@link collectAllTabHits}.
 */
export const ALL_TAB_COLLECTION_ORDER: SearchCollectionKey[] = [
  'meetings', 'agendaItems', 'institutions', 'resources', 'duties', 'documents', 'news', 'pages', 'calendar', 'users',
];

/**
 * Build the flat, normalized hit list for the "All" tab.
 *
 * - With a query, all collections are interleaved by Typesense relevance
 *   (`_text_match`) descending, with each hit's recency timestamp as a tiebreak.
 * - Without a query (browse mode), hits keep the static collection priority order.
 */
export function collectAllTabHits(
  results: MultiSearchResults,
  options: { query?: string; dutyCtx?: MapperContext } = {},
): NormalizedSearchHit[] {
  const hasQuery = !!options.query && options.query.trim() !== '';

  const entries: Array<{ key: SearchCollectionKey; doc: unknown }> = [];
  for (const key of ALL_TAB_COLLECTION_ORDER) {
    for (const doc of results[key] as unknown[]) {
      entries.push({ key, doc });
    }
  }

  if (hasQuery) {
    entries.sort((a, b) => {
      const aMatch = getSearchHitTextMatch(a.doc);
      const bMatch = getSearchHitTextMatch(b.doc);
      if (aMatch !== bMatch) {
        return bMatch > aMatch ? 1 : -1;
      }
      return getSearchHitTimestamp(b.key, b.doc) - getSearchHitTimestamp(a.key, a.doc);
    });
  }

  return entries.map(({ key, doc }) => {
    // Meetings and agenda items need the full context for isRelated badges.
    const needsCtx = key === 'duties' || key === 'meetings' || key === 'agendaItems';
    return normalizeHit(key, doc, needsCtx ? options.dutyCtx : undefined);
  });
}

/**
 * Normalize a raw search document for the given collection.
 *
 * `ctx` carries optional user-relative state (e.g. the user's accessible duty
 * tenants) so mappers can flag rows like cross-tenant duties.
 */
export function normalizeHit(collection: SearchCollectionKey, doc: unknown, ctx?: MapperContext): NormalizedSearchHit {
  const meta = COLLECTION_META[collection];
  const mapped = MAPPERS[collection](doc, ctx);
  return {
    ...mapped,
    id: `${collection}-${mapped.recordId}`,
    collection,
    icon: meta.icon,
    raw: doc,
  };
}
