import { computed, toValue, type MaybeRefOrGetter } from 'vue';

import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import { highlightText, matchTitle, type SearchMatchDocument } from '@/Shared/Search/matches';

export function useSearchHitMatches(hit: MaybeRefOrGetter<NormalizedSearchHit>) {
  const matches = computed(() => toValue(hit).raw as SearchMatchDocument);
  const titleMatch = computed(() => matchTitle(toValue(hit).title, matches.value._searchTitleMatches));
  const fieldMatches = computed(() => Object.values(matches.value._searchFieldMatches ?? {}));
  const highlightIn = (text?: string) => text ? fieldMatches.value.map(match => highlightText(text, match)).find(Boolean) : undefined;
  const metaMatch = computed(() => highlightIn(toValue(hit).meta));
  const contextMatch = computed(() => {
    const inSubtitle = highlightIn(toValue(hit).subtitle);
    if (inSubtitle || titleMatch.value || metaMatch.value) return inSubtitle;
    // A hidden-field match explains why the result is here when no visible text matched.
    return matches.value._searchMatch ?? fieldMatches.value[0];
  });

  return { matches, titleMatch, metaMatch, contextMatch };
}
