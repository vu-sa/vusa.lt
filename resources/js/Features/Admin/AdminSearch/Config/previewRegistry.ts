import { defineAsyncComponent, type Component } from 'vue';

import type { NormalizedSearchHit, SearchCollectionKey } from '../Utils/searchHitMappers';

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
} from '@/Shared/Search/types';

interface PreviewEntry {
  component: Component;
  props: (hit: NormalizedSearchHit) => Record<string, unknown>;
}

/**
 * Search mixes collections in one list, so the pane picks each hit's preview here. Collection
 * pages know their entity and use the same components directly in `#preview`.
 */
export const previewRegistry: Record<SearchCollectionKey, PreviewEntry> = {
  meetings: {
    component: defineAsyncComponent(() => import('@/Components/Meetings/MeetingPreview.vue')),
    props: hit => ({ meeting: hit.raw as MeetingSearchResult }),
  },
  agendaItems: {
    component: defineAsyncComponent(() => import('@/Components/AgendaItems/AgendaItemPreview.vue')),
    props: hit => ({ item: hit.raw as AgendaItemSearchResult }),
  },
  institutions: {
    component: defineAsyncComponent(() => import('@/Components/Institutions/InstitutionPreview.vue')),
    props: hit => ({ institution: hit.raw as InstitutionSearchResult }),
  },
  resources: {
    component: defineAsyncComponent(() => import('@/Components/Reservations/ResourcePreview.vue')),
    props: hit => ({
      resource: hit.raw as ResourceSearchResult,
      showUpcomingReservations: true,
      showPreviousReservations: true,
      showManagers: true,
      allowImageLightbox: true,
    }),
  },
  documents: {
    component: defineAsyncComponent(() => import('@/Components/Files/DocumentPreview.vue')),
    props: hit => ({ document: hit.raw as DocumentSearchResult }),
  },
  duties: {
    component: defineAsyncComponent(() => import('../Components/Detail/DutyDetailPreview.vue')),
    props: hit => ({ duty: hit.raw as DutySearchResult, isExternal: hit.isExternal }),
  },
  news: {
    component: defineAsyncComponent(() => import('../Components/Detail/NewsDetailPreview.vue')),
    props: hit => ({ news: hit.raw as NewsSearchResult }),
  },
  pages: {
    component: defineAsyncComponent(() => import('../Components/Detail/PageDetailPreview.vue')),
    props: hit => ({ page: hit.raw as PageSearchResult }),
  },
  calendar: {
    component: defineAsyncComponent(() => import('../Components/Detail/CalendarDetailPreview.vue')),
    props: hit => ({ event: hit.raw as CalendarSearchResult }),
  },
  users: {
    component: defineAsyncComponent(() => import('../Components/Detail/UserDetailPreview.vue')),
    props: hit => ({ user: hit.raw as UserSearchResult }),
  },
};
