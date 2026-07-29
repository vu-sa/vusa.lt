<template>
  <div class="flex min-h-0 flex-col gap-3">
    <SearchSelectView
      :controller
      :map-hit
      :multiple
      :selected-ids
      :disabled-ids
      :pinned-hits="initialHits ?? []"
      :empty-message="emptyMessage ?? $t('Rezultatų nerasta')"
      :search-placeholder="searchPlaceholder ?? $t('Ieškoti...')"
      @toggle="onToggle"
    />

    <!-- Inline confirm affordance: consumers embed this inside their own surface
         (e.g. a Tiptap link dialog tab) rather than a dedicated Dialog, so the
         "confirm" button lives here instead of a DialogFooter. -->
    <div v-if="confirmLabel" class="flex items-center justify-between gap-3">
      <span class="text-sm text-muted-foreground">
        {{ $t(':count pasirinkta', { count: String(selectedHits.length) }) }}
      </span>
      <Button :disabled="!allowEmpty && selectedHits.length === 0" @click="confirm">
        {{ confirmLabel }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useAdminCollectionSearch } from '../../Composables/useAdminCollectionSearch';
import { adminCollectionToKey, normalizeHit, type MapperContext, type NormalizedSearchHit } from '../../Utils/searchHitMappers';
import type { AdminCollection } from '../../Types/AdminSearchTypes';

import SearchSelectView from './SearchSelectView.vue';
import { Button } from '@/Components/ui/button';

const props = withDefaults(defineProps<{
  collection: AdminCollection;
  multiple?: boolean;
  /** Always-on Typesense filter scoping the collection to the candidate set. */
  baseFilterBy?: string;
  /** Hits selected on mount (pre-checked + pinned first). */
  initialHits?: NormalizedSearchHit[];
  /** Allow confirming with nothing selected (single-select "clear" support). */
  allowEmpty?: boolean;
  disabledIds?: Set<string>;
  /** Optional mapper context (e.g. duties' cross-tenant flag). */
  mapperCtx?: MapperContext;
  emptyMessage?: string;
  searchPlaceholder?: string;
  /** When provided, a confirm button is rendered that emits `confirm`. */
  confirmLabel?: string;
}>(), {
  multiple: false,
});

const emit = defineEmits<{
  confirm: [hits: NormalizedSearchHit[]];
}>();

const controller = useAdminCollectionSearch({
  collection: props.collection,
  syncToUrl: false,
  loadFacetsOnMount: true,
  searchOnMount: true,
  perPage: 20,
  baseFilterBy: props.baseFilterBy,
});

const collectionKey = adminCollectionToKey(props.collection);
const mapHit = (doc: unknown): NormalizedSearchHit => normalizeHit(collectionKey, doc, props.mapperCtx);

// Selection state mirrors SearchSelectDialog: a Set of record ids + a Map to keep
// the full hit objects around even after a new search removes them from the list.
const selectedIds = ref<Set<string>>(new Set(props.initialHits?.map(hit => hit.id) ?? []));
const selectedMap = ref<Map<string, NormalizedSearchHit>>(new Map((props.initialHits ?? []).map(hit => [hit.id, hit])));
const selectedHits = ref<NormalizedSearchHit[]>(props.initialHits ? [...props.initialHits] : []);

const syncSelectedHits = () => {
  selectedHits.value = Array.from(selectedIds.value)
    .map(id => selectedMap.value.get(id))
    .filter((hit): hit is NormalizedSearchHit => hit != null);
};

const onToggle = (hit: NormalizedSearchHit) => {
  const next = new Set(selectedIds.value);
  if (next.has(hit.id)) {
    next.delete(hit.id);
  }
  else {
    if (!props.multiple) {
      next.clear();
    }
    next.add(hit.id);
    selectedMap.value.set(hit.id, hit);
  }
  selectedIds.value = next;
  syncSelectedHits();
};

const confirm = () => {
  emit('confirm', selectedHits.value);
};
</script>
