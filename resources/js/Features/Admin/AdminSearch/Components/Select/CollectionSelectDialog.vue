<template>
  <SearchSelectDialog
    :open
    :multiple
    :title
    :description
    :confirm-label
    :initial-hits
    :allow-empty
    @update:open="$emit('update:open', $event)"
    @confirm="$emit('confirm', $event)"
  >
    <template v-if="$slots.trigger" #trigger>
      <slot name="trigger" />
    </template>
    <template #default="{ selectedIds, multiple: isMultiple, toggle, pinnedHits }">
      <CollectionSelectContent
        :collection
        :base-filter-by
        :multiple="isMultiple"
        :selected-ids
        :disabled-ids
        :pinned-hits
        :mapper-ctx
        :empty-message="emptyMessage ?? $t('Rezultatų nerasta')"
        :search-placeholder="searchPlaceholder ?? $t('Ieškoti...')"
        @toggle="toggle"
      />
    </template>
  </SearchSelectDialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import type { AdminCollection } from '../../Types/AdminSearchTypes';
import type { MapperContext, NormalizedSearchHit } from '../../Utils/searchHitMappers';

import SearchSelectDialog from './SearchSelectDialog.vue';
import CollectionSelectContent from './CollectionSelectContent.vue';

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
  'confirm': [hits: NormalizedSearchHit[]];
}>();
</script>
