<template>
  <SearchSelectView
    :controller="controller"
    :map-hit="mapHit"
    :multiple="multiple"
    :selected-ids="selectedIds"
    :disabled-ids="disabledIds"
    :pinned-hits="pinnedHits"
    :empty-message="$t('Išteklių nerasta')"
    :search-placeholder="$t('Ieškoti išteklių pagal pavadinimą, vietą...')"
    @toggle="$emit('toggle', $event)"
  >
    <template #detail="{ hit }">
      <ResourceSelectDetail
        v-if="hit"
        :resource="hit.raw as ResourceSearchResult"
        :availability="availability.get(hit.recordId)"
      />
    </template>
  </SearchSelectView>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import SearchSelectView from './SearchSelectView.vue';
import ResourceSelectDetail from './ResourceSelectDetail.vue';
import { useAdminCollectionSearch } from '../../Composables/useAdminCollectionSearch';
import { useResourceAvailability, type DateTimeRange } from '../../Composables/useResourceAvailability';
import { normalizeHit, type NormalizedSearchHit } from '../../Utils/searchHitMappers';
import type { ResourceSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  selectedIds: Set<string>;
  multiple: boolean;
  dateTimeRange: DateTimeRange;
  /** Resource ids already added by the caller — shown disabled. */
  excludedIds?: string[];
  /** Currently-selected hits pinned to the top of the list. */
  pinnedHits?: NormalizedSearchHit[];
}>();

defineEmits<{
  toggle: [hit: NormalizedSearchHit];
}>();

const controller = useAdminCollectionSearch({
  collection: 'resources',
  syncToUrl: false,
  loadFacetsOnMount: true,
  searchOnMount: true,
  perPage: 30,
});

const { availability, ensure } = useResourceAvailability(() => props.dateTimeRange);

// Fetch date-range availability for each page of results as it arrives.
watch(
  () => controller.results.value,
  (results) => {
    const ids = (results as ResourceSearchResult[]).map(r => r.id);
    if (ids.length > 0) {
      ensure(ids);
    }
  },
  { immediate: true },
);

const mapHit = (doc: unknown): NormalizedSearchHit => {
  const resource = doc as ResourceSearchResult;
  const hit = normalizeHit('resources', doc);
  const info = availability.value.get(resource.id);
  if (info) {
    hit.meta = `${info.lowestCapacityAtDateTimeRange} ${$t('iš')} ${info.capacity}`;
  }
  return hit;
};

// Selection/disabled sets are keyed by the collection-prefixed hit id.
const rowId = (resourceId: string) => `resources-${resourceId}`;

const disabledIds = computed<Set<string>>(() => {
  const disabled = new Set<string>((props.excludedIds ?? []).map(rowId));
  for (const doc of controller.results.value as ResourceSearchResult[]) {
    const info = availability.value.get(doc.id);
    if (!doc.is_reservable || (info && info.lowestCapacityAtDateTimeRange <= 0)) {
      disabled.add(rowId(doc.id));
    }
  }
  return disabled;
});
</script>
