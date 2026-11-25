import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { newsStatus } from '@/Search/admin/Utils/newsStatus';
import { getTranslatedValue, getSearchTranslation, getSearchTranslations } from '@/Composables/useTranslatedTitle';
import {
  contentStatuses,
  deletedMemberStatus,
  meetingCompletionStatuses,
  notReservableStatus,
  statusFor,
  voteStatuses,
  type StatusPresentation,
} from '@/Constants/statusIcons';
import { hasIdentityMatch, PROSE_FIELDS, type SearchMatchDocument } from '@/Search/core/matches';
import { changeDutyNameEndings } from '@/Utils/String';
import { termYearSpan } from '@/Utils/occupancy';
import type {
  AgendaItemSearchResult,
  CalendarSearchResult,
  DocumentSearchResult,
  DutySearchResult,
  InstitutionSearchResult,
  MeetingSearchResult,
  NewsSearchResult,
  PageSearchResult,
  ResourceSearchResult,
  UserSearchResult,
} from '@/Search/core/types';
import { formatDateWith } from '@/Utils/dateTime';

export function getUserDuties(user: UserSearchResult, kind: 'current' | 'previous', locale: string) {
  return (user[`${kind}_duty_names`] ?? []).map((storedName, index) => ({
    id: user[`${kind}_duty_ids`]?.[index],
    name: changeDutyNameEndings(
      user,
      getTranslatedValue({ lt: user[`${kind}_duty_names_lt`]?.[index], en: user[`${kind}_duty_names_en`]?.[index] }, locale, storedName),
      locale,
      { lt: user.pronouns_lt, en: user.pronouns_en },
      user[`${kind}_duty_use_original_names`]?.[index] ?? false,
    ),
  }));
}

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
export interface SearchHit<Collection extends string> {
  /** Stable, collection-prefixed row id (e.g. `meeting-123`). */
  id: string;
  /** The underlying record id. */
  recordId: string;
  collection: Collection;
  /** Thumbnail URL rendered in place of the icon when present (e.g. resource photo). */
  imageUrl?: string;
  title: string;
  subtitle?: string;
  /** Tenant shortname; kept for the detail pane, not rendered in the row. */
  badge?: string;
  meta?: string;
  /** Belongs to the record itself, so it sits beside the title (a member's active years), not on the context line. */
  aside?: string;
  href?: string;
  /**
   * "View" / "Edit" links for the command palette's two-button row. A collection with no
   * admin show page (news, pages, calendar) or a stubbed one (resources) omits `viewHref`.
   */
  viewHref?: string;
  editHref?: string;
  /** Contextual colored status badge shown on the list row. */
  statusBadge?: StatusPresentation | null;
  inactiveInstitution?: boolean;
  /** Context shown as a neutral chip rather than a status. */
  contextBadge?: string;
  /** Duties only: the row belongs to a tenant outside the user's own scope. */
  isExternal?: boolean;
  /** Meetings / agenda items: result comes from a related (non-direct) institution. */
  isRelated?: boolean;
  /** True when the hit represents a recently visited page (not a search result). */
  isRecent?: boolean;
  /** Original Typesense document for the detail pane. */
  raw: unknown;
}

export function normalizeUserApiHit(user: {
  id: string;
  name: string;
  email?: string;
  profile_photo_path?: string | null;
  tenants?: string[];
  is_current?: boolean;
  last_term_year?: number | null;
}, { taken = false, takenLabel = $t('Jau eina šias pareigas') }: { taken?: boolean; takenLabel?: string } = {}): SearchHit<'users'> {
  const tenants = user.tenants?.join(', ') || undefined;
  return {
    id: `users-${user.id}`,
    recordId: String(user.id),
    collection: 'users',
    title: user.name,
    imageUrl: user.profile_photo_path || undefined,
    subtitle: taken ? takenLabel : user.email,
    meta: user.is_current === false && user.last_term_year ? `${$t('Buvęs narys')} · ${user.last_term_year}` : undefined,
    badge: tenants,
    contextBadge: tenants,
    raw: user,
  };
}

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
  return formatDateWith(timestamp * 1000, { year: 'numeric', month: 'short', day: 'numeric' });
}

/** Format a Unix (seconds) timestamp as a localized date + time, or undefined. */
export function formatSearchDateTime(timestamp?: number): string | undefined {
  if (!timestamp) {
    return undefined;
  }
  return formatDateWith(timestamp * 1000, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

type Mapper<T> = (doc: T, ctx?: MapperContext) => Omit<SearchHit<string>, 'id' | 'collection' | 'raw'>;

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

export const mapMeeting: Mapper<MeetingSearchResult> = (m: MeetingSearchResult, ctx?: MapperContext) => ({
  recordId: String(m.id),
  title: m.title ?? $t('Be pavadinimo'),
  subtitle: getSearchTranslation(m, 'institution_name') || undefined,
  badge: m.tenant_shortname,
  meta: formatSearchDate(m.start_time),
  href: route('meetings.show', m.id),
  viewHref: route('meetings.show', m.id),
  editHref: route('meetings.edit', m.id),
  statusBadge: statusFor(meetingCompletionStatuses, m.completion_status),
  inactiveInstitution: Boolean(m.inactive_institution_ids?.length),
  isRelated: computeIsRelated(m.institution_ids, ctx?.directInstitutionIds),
});
export const mapAgendaItem: Mapper<AgendaItemSearchResult> = (a: AgendaItemSearchResult, ctx?: MapperContext) => ({
  recordId: String(a.id),
  title: a.title ?? $t('Be pavadinimo'),
  subtitle: getSearchTranslation(a, 'institution_name') || undefined,
  meta: formatSearchDate(a.meeting_start_time),
  href: route('agendaItems.edit', a.id),
  // Agenda items have no dedicated show page — "view" opens their parent meeting.
  viewHref: a.meeting_id ? route('meetings.show', a.meeting_id) : undefined,
  editHref: route('agendaItems.edit', a.id),
  statusBadge: statusFor(voteStatuses, a.decision),
  inactiveInstitution: Boolean(a.inactive_institution_ids?.length),
  isRelated: computeIsRelated(a.institution_ids, ctx?.directInstitutionIds),
});
export const mapInstitution: Mapper<InstitutionSearchResult> = (i: InstitutionSearchResult) => ({
  recordId: String(i.id),
  title: getSearchTranslation(i, 'name') || $t('Be pavadinimo'),
  subtitle: i.tenant_shortname,
  badge: i.tenant_shortname,
  href: route('institutions.show', i.id),
  viewHref: route('institutions.show', i.id),
  editHref: route('institutions.edit', i.id),
  inactiveInstitution: i.is_active === false,
});
export const mapResource: Mapper<ResourceSearchResult> = (r: ResourceSearchResult) => ({
  recordId: String(r.id),
  title: getSearchTranslation(r, 'name') || $t('Be pavadinimo'),
  subtitle: [r.location, getSearchTranslation(r, 'category_name')].filter(Boolean).join(' • ') || undefined,
  imageUrl: r.image_url || undefined,
  badge: r.tenant_shortname,
  href: route('resources.show', r.id),
  editHref: route('resources.edit', r.id),
  // Reservable is the healthy default and gets no badge.
  statusBadge: r.is_reservable ? undefined : notReservableStatus,
});
export const mapDuty: Mapper<DutySearchResult> = (d: DutySearchResult, ctx?: MapperContext) => {
  const external = isDutyExternal(d, ctx);
  const memberMeta = d.current_user_names?.length
    ? d.current_user_names.slice(0, 2).join(', ') + (d.current_user_names.length > 2 ? ` +${d.current_user_names.length - 2}` : '')
    : getSearchTranslations(d, 'type_titles')[0];
  return {
    recordId: String(d.id),
    title: getTranslatedValue({ lt: d.name_lt, en: d.name_en }, undefined, $t('Be pavadinimo')),
    subtitle: getSearchTranslation(d, 'institution_name') || undefined,
    badge: d.tenant_shortname,
    meta: memberMeta,
    href: route('duties.show', d.id),
    viewHref: route('duties.show', d.id),
    editHref: route('duties.edit', d.id),
    isExternal: external,
    inactiveInstitution: d.institution_is_active === false,
    contextBadge: external ? d.tenant_shortname : undefined,
  };
};
export const mapDocument: Mapper<DocumentSearchResult> = (d: DocumentSearchResult) => ({
  recordId: String(d.id),
  title: d.title ?? $t('Be pavadinimo'),
  subtitle: d.content_type,
  badge: d.tenant_shortname,
  meta: formatSearchDate(d.document_date),
  href: d.anonymous_url,
  viewHref: route('documents.show', d.id),
});
export const mapNews: Mapper<NewsSearchResult> = (n: NewsSearchResult) => ({
  recordId: String(n.id),
  title: n.title ?? $t('Be pavadinimo'),
  badge: n.tenant_name,
  meta: formatSearchDate(n.publish_time),
  href: route('news.edit', n.id),
  // No admin show page for news — only the public page and the editor.
  editHref: route('news.edit', n.id),
  // The admin index (unlike the public one) also contains drafts and
  // scheduled articles — flag them so they aren't mistaken for live ones.
  // The admin index also holds drafts and scheduled articles; only those are flagged.
  statusBadge: newsStatus(n) === contentStatuses.published ? undefined : newsStatus(n),
});
export const mapPage: Mapper<PageSearchResult> = (p: PageSearchResult) => ({
  recordId: String(p.id),
  title: p.title ?? $t('Be pavadinimo'),
  badge: p.tenant_name,
  href: route('pages.edit', p.id),
  // No admin show page for pages — only the public page and the editor.
  editHref: route('pages.edit', p.id),
});
export const mapCalendar: Mapper<CalendarSearchResult> = (c: CalendarSearchResult) => ({
  recordId: String(c.id),
  title: c.title_lt || c.title || $t('Be pavadinimo'),
  badge: c.tenant_name,
  meta: formatSearchDate(c.date),
  href: route('calendar.edit', c.id),
  editHref: route('calendar.edit', c.id),
});
export const mapUser: Mapper<UserSearchResult> = (u: UserSearchResult) => ({
  recordId: String(u.id),
  title: u.name || $t('Be pavadinimo'),
  subtitle: getUserDuties(u, 'current', usePage().props.app.locale)[0]?.name ?? u.email,
  badge: u.tenant_shortname,
  aside: userYearSpan(u) ?? undefined,
  href: route('users.show', u.id),
  viewHref: route('users.show', u.id),
  editHref: route('users.edit', u.id),
  statusBadge: u.is_active === false ? deletedMemberStatus : undefined,
});

/** The years someone has held duties, as in the members list ("2021–dabar"). */
function userYearSpan(u: UserSearchResult): string | null {
  const ends = u.term_end_dates ?? [];
  return termYearSpan((u.term_start_dates ?? []).map((start, index) => ({ start_date: start, end_date: ends[index] || null })), $t('dabar'));
}

const IDENTIFIER_FIELDS = new Set(['email', 'alias', 'phone', 'short_name_lt', 'short_name_en']);

/**
 * How directly a hit matched: 3 its own name or title, 2 an identifier (email, alias),
 * 1 a related record's name, 0 only its prose.
 */
export function getSearchHitMatchTier(doc: unknown): number {
  if (typeof doc !== 'object' || doc === null) return 0;
  if (hasIdentityMatch(doc as Record<string, unknown>)) return 3;
  const fields = Object.keys((doc as SearchMatchDocument)._searchFieldMatches ?? {});
  if (fields.some(field => IDENTIFIER_FIELDS.has(field))) return 2;
  if (fields.some(field => !PROSE_FIELDS.has(field))) return 1;
  return 0;
}

export function getSearchHitTokens(doc: unknown): number {
  const value = typeof doc === 'object' && doc !== null ? (doc as Record<string, unknown>)._tokens_matched : 0;
  return typeof value === 'number' ? value : 0;
}
