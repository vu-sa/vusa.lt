<template>
  <SearchSelectDialog
    :open="open"
    :multiple="multiple"
    :title="title"
    :description="description"
    :confirm-label="confirmLabel"
    :initial-hits="initialHits"
    :allow-empty="allowEmpty"
    @update:open="$emit('update:open', $event)"
    @confirm="$emit('confirm', $event)"
  >
    <template v-if="$slots.trigger" #trigger>
      <slot name="trigger" />
    </template>
    <template #default="{ selectedIds, multiple: isMultiple, toggle, pinnedHits }">
      <CollectionSelectContent
        :collection="collection"
        :base-filter-by="baseFilterBy"
        :multiple="isMultiple"
        :selected-ids="selectedIds"
        :disabled-ids="disabledIds"
        :pinned-hits="pinnedHits"
        :mapper-ctx="mapperCtx"
        :empty-message="emptyMessage ?? $t('Rezultatų nerasta')"
        :search-placeholder="searchPlaceholder ?? $t('Ieškoti...')"
        @toggle="toggle"
      />
    </template>
  </SearchSelectDialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import SearchSelectDialog from './SearchSelectDialog.vue';
import CollectionSelectContent from './CollectionSelectContent.vue';
import type { AdminCollection } from '../../Types/AdminSearchTypes';
import type { MapperContext, NormalizedSearchHit } from '../../Utils/searchHitMappers';

withDefaults(defineProps<{
  open: boolean;
  collection: AdminCollection;
  title: string;
  multiple?: boolean;
  description?: string;
  confirmLabel?: string;
  baseFilterBy?: string;
  disabledIds?: Set<string>;
  initialHits?: NormalizedSearchHit[];
  allowEmpty?: boolean;
  mapperCtx?: MapperContext;
  emptyMessage?: string;
  searchPlaceholder?: string;
}>(), {
  multiple: false,
});

defineEmits<{
  'update:open': [open: boolean];
  confirm: [hits: NormalizedSearchHit[]];
}>();
</script>
