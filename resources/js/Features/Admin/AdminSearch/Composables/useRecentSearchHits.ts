/**
 * useRecentSearchHits — turns server-backed recent pages into normalized hits
 * that the AdminSearch list can render identically to search results.
 *
 * Collection is inferred from the route name so recents land in the right
 * tab / get the right icon and colour.
 */

import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import {
  COLLECTION_META,
  type NormalizedSearchHit,
  type SearchCollectionKey,
} from '../Utils/searchHitMappers';

import { useUIPreferences } from '@/Composables/useUIPreferences';
import type { RecentItem } from '@/Composables/useCommandPalette';

/**
 * Infer the search collection from a Ziggy route name.
 * Falls back to `pages` when the route doesn't match a known collection.
 */
function routeToCollection(routeName: string): SearchCollectionKey {
  const prefix = routeName.split('.')[0];
  switch (prefix) {
    case 'meetings': return 'meetings';
    case 'agendaItems': return 'agendaItems';
    case 'institutions': return 'institutions';
    case 'resources':
    case 'reservationResources':
      return 'resources';
    case 'duties': return 'duties';
    case 'documents':
    case 'files':
    case 'sharepointFiles':
      return 'documents';
    case 'news': return 'news';
    case 'pages':
    case 'navigation':
    case 'quickLinks':
      return 'pages';
    case 'calendar':
    case 'events':
      return 'calendar';
    default:
      return 'pages';
  }
}

function recentItemToHit(item: RecentItem): NormalizedSearchHit {
  const collection = item.routeName ? routeToCollection(item.routeName) : 'pages';
  const meta = COLLECTION_META[collection];

  return {
    id: `recent-${collection}-${item.id}`,
    recordId: item.id,
    collection,
    icon: meta.icon,
    title: item.title || $t('Be pavadinimo'),
    href: item.href,
    isRecent: true,
    raw: item,
  };
}

export function useRecentSearchHits() {
  const { recentPages } = useUIPreferences();

  const allHits = computed<NormalizedSearchHit[]>(() =>
    recentPages.value.map(recentItemToHit),
  );

  const hitsForCollection = (key: SearchCollectionKey): NormalizedSearchHit[] =>
    allHits.value.filter(h => h.collection === key);

  return {
    allHits,
    hitsForCollection,
  };
}
