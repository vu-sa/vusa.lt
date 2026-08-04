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
      <MultiCollectionSelectContent
        :collections
        :multiple="isMultiple"
        :selected-ids
        :disabled-ids
        :pinned-hits
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
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

import SearchSelectDialog from './SearchSelectDialog.vue';
import MultiCollectionSelectContent from './MultiCollectionSelectContent.vue';

withDefaults(defineProps<{
  open: boolean;
  /** Which collections a search hits and which of the results are offered — everything
   * outside this list is filtered out, since the underlying search spans every
   * collection the user can access. */
  collections: AdminCollection[];
  title: string;
  multiple?: boolean;
  description?: string;
  confirmLabel?: string;
  disabledIds?: Set<string>;
  initialHits?: NormalizedSearchHit[];
  allowEmpty?: boolean;
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
