<template>
  <SearchSelectView
    :controller
    :map-hit
    :multiple
    :selected-ids
    :disabled-ids
    :pinned-hits
    :empty-message
    :search-placeholder
    @toggle="$emit('toggle', $event)"
  />
</template>

<script setup lang="ts">
import { useAdminCollectionSearch } from '../../Composables/useAdminCollectionSearch';
import { adminCollectionToKey, normalizeHit, type MapperContext, type NormalizedSearchHit } from '../../Utils/searchHitMappers';
import type { AdminCollection } from '../../Types/AdminSearchTypes';

import SearchSelectView from './SearchSelectView.vue';

const props = defineProps<{
  collection: AdminCollection;
  multiple: boolean;
  selectedIds: Set<string>;
  emptyMessage: string;
  searchPlaceholder: string;
  /** Always-on Typesense filter scoping the collection to the form's candidate set. */
  baseFilterBy?: string;
  disabledIds?: Set<string>;
  pinnedHits?: NormalizedSearchHit[];
  /** Optional mapper context (e.g. duties' cross-tenant flag). */
  mapperCtx?: MapperContext;
}>();

defineEmits<{
  toggle: [hit: NormalizedSearchHit];
}>();

const controller = useAdminCollectionSearch({
  collection: props.collection,
  syncToUrl: false,
  loadFacetsOnMount: true,
  searchOnMount: true,
  perPage: 30,
  baseFilterBy: props.baseFilterBy,
});

// Real Typesense documents → the rich per-collection detail preview via
// SearchSelectView's default detail pane (SearchDetailPane).
const collectionKey = adminCollectionToKey(props.collection);
const mapHit = (doc: unknown): NormalizedSearchHit => normalizeHit(collectionKey, doc, props.mapperCtx);
</script>
