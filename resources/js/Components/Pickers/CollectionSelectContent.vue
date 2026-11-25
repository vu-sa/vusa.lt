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
import { useAdminCollectionSearch } from '@/Search/admin/Composables/useAdminCollectionSearch';
import { normalizeHit, type NormalizedSearchHit } from '@/Components/Search/adminSearchCollections';
import type { MapperContext } from '@/Search/admin/Utils/searchHitMappers';
import type { AdminCollection } from '@/Search/admin/Types/AdminSearchTypes';

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

const mapHit = (doc: unknown): NormalizedSearchHit => normalizeHit(props.collection, doc, props.mapperCtx);
</script>
