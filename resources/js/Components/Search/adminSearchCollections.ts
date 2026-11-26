import { defineAsyncComponent, type Component } from 'vue';

import { calendarEventPreviewFromSearch } from '@/Components/Calendar/calendarEventPreview';
import { dutyPreviewFromSearch } from '@/Components/Duties/dutyPreview';
import { userPreviewFromSearch } from '@/Components/Users/userPreview';
import type { MultiSearchResults } from '@/Search/core/types';
import { hasExactNameMatch, normalizeSearchName } from '@/Search/core/matches';
import {
  getSearchHitMatchTier,
  getSearchHitTextMatch,
  getSearchHitTokens,
  mapMeeting,
  mapAgendaItem,
  mapInstitution,
  mapResource,
  mapDuty,
  mapDocument,
  mapNews,
  mapPage,
  mapCalendar,
  mapUser,
  type MapperContext,
  type SearchHit,
} from '@/Search/admin/Utils/searchHitMappers';

type HitFields = Omit<SearchHit<string>, 'id' | 'collection' | 'raw'>;

interface SearchCollection<Doc> {
  entityType: string;
  limit: number;
  rank: number;
  dateField?: keyof Doc & string;
  preview?: {
    component: Component;
    props: (doc: Doc, hit: SearchHit<string>) => Record<string, unknown>;
  };
}

function defineCollection<Doc>(
  mapHit: (doc: Doc, ctx?: MapperContext) => HitFields,
  { preview, ...options }: SearchCollection<NoInfer<Doc>>,
) {
  return {
    ...options,
    mapHit: (doc: unknown, ctx?: MapperContext) => mapHit(doc as Doc, ctx),
    preview: preview && {
      component: preview.component,
      props: (hit: SearchHit<string>) => preview.props(hit.raw as Doc, hit),
    },
  };
}

export const adminSearchCollections = {
  meetings: defineCollection(mapMeeting, {
    entityType: 'meeting',
    limit: 3,
    rank: 3,
    dateField: 'start_time',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/Meetings/MeetingPreview.vue')),
      props: doc => ({ meeting: doc }),
    },
  }),
  agenda_items: defineCollection(mapAgendaItem, {
    entityType: 'agenda_item',
    limit: 3,
    rank: 4,
    dateField: 'meeting_start_time',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/AgendaItems/AgendaItemPreview.vue')),
      props: doc => ({ item: doc }),
    },
  }),
  institutions: defineCollection(mapInstitution, {
    entityType: 'institution',
    limit: 3,
    rank: 1,
    dateField: 'created_at',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/Institutions/InstitutionPreview.vue')),
      props: doc => ({ institution: doc }),
    },
  }),
  resources: defineCollection(mapResource, {
    entityType: 'resource',
    limit: 3,
    rank: 5,
    dateField: 'created_at',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/Reservations/ResourcePreview.vue')),
      props: doc => ({
        resource: doc,
        showUpcomingReservations: true,
        showPreviousReservations: true,
        showManagers: true,
        allowImageLightbox: true,
      }),
    },
  }),
  duties: defineCollection(mapDuty, {
    entityType: 'duty',
    limit: 3,
    rank: 2,
    preview: {
      component: defineAsyncComponent(() => import('@/Components/Duties/DutyPreview.vue')),
      props: (doc, hit) => ({ duty: dutyPreviewFromSearch(doc), isExternal: hit.isExternal }),
    },
  }),
  documents: defineCollection(mapDocument, {
    entityType: 'document',
    limit: 3,
    rank: 6,
    dateField: 'document_date',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/Files/DocumentPreview.vue')),
      props: doc => ({ document: doc }),
    },
  }),
  news: defineCollection(mapNews, {
    entityType: 'news',
    limit: 3,
    rank: 9,
    dateField: 'publish_time',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/News/NewsPreview.vue')),
      props: doc => ({ news: doc }),
    },
  }),
  pages: defineCollection(mapPage, {
    entityType: 'page',
    limit: 3,
    rank: 7,
    dateField: 'created_at',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/ContentPages/PagePreview.vue')),
      props: doc => ({ page: doc }),
    },
  }),
  calendar: defineCollection(mapCalendar, {
    entityType: 'calendar',
    limit: 3,
    rank: 8,
    dateField: 'date',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/Calendar/CalendarEventPreview.vue')),
      props: doc => ({ event: calendarEventPreviewFromSearch(doc) }),
    },
  }),
  users: defineCollection(mapUser, {
    entityType: 'user',
    limit: 3,
    rank: 0,
    dateField: 'created_at',
    preview: {
      component: defineAsyncComponent(() => import('@/Components/Users/UserPreview.vue')),
      props: doc => ({ user: userPreviewFromSearch(doc) }),
    },
  }),
};

export type AdminSearchCollectionId = keyof typeof adminSearchCollections;
export type NormalizedSearchHit = SearchHit<AdminSearchCollectionId>;

const collectionIds = Object.keys(adminSearchCollections) as AdminSearchCollectionId[];

export function adminSearchRequests(collections?: readonly AdminSearchCollectionId[], limit?: number) {
  return collectionIds
    .filter(id => !collections || collections.includes(id))
    .map(id => ({ id, limit: limit ?? adminSearchCollections[id].limit }));
}

export function normalizeHit(collection: AdminSearchCollectionId, doc: unknown, ctx?: MapperContext): NormalizedSearchHit {
  const entry = adminSearchCollections[collection];
  const mapped = entry.mapHit(doc, ctx);
  return {
    ...mapped,
    id: `${collection}-${mapped.recordId}`,
    collection,
    raw: doc,
  };
}

function hitTimestamp(collection: AdminSearchCollectionId, doc: Record<string, unknown>): number {
  const field = adminSearchCollections[collection].dateField;
  const value = field ? doc[field] : undefined;
  return typeof value === 'number' ? value : 0;
}

export function collectAllTabHits(
  results: MultiSearchResults<AdminSearchCollectionId>,
  { query, mapperContext }: { query?: string; mapperContext?: MapperContext } = {},
): NormalizedSearchHit[] {
  const hits = collectionIds.flatMap(id => (results[id]?.hits ?? []).map(doc => ({ id, doc })));
  const normalizedQuery = normalizeSearchName(query ?? '');
  if (normalizedQuery) {
    hits.sort((a, b) => {
      const scoreDifference = getSearchHitTextMatch(b.doc) - getSearchHitTextMatch(a.doc);
      return Number(hasExactNameMatch(b.doc, normalizedQuery)) - Number(hasExactNameMatch(a.doc, normalizedQuery))
        || getSearchHitMatchTier(b.doc) - getSearchHitMatchTier(a.doc)
        || getSearchHitTokens(b.doc) - getSearchHitTokens(a.doc)
        || adminSearchCollections[a.id].rank - adminSearchCollections[b.id].rank
        || (scoreDifference > 0n ? 1 : scoreDifference < 0n ? -1 : 0)
        || hitTimestamp(b.id, b.doc) - hitTimestamp(a.id, a.doc);
    });
  }

  return hits.map(({ id, doc }) => normalizeHit(id, doc, mapperContext));
}
