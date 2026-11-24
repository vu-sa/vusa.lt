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

import type { AdminSearchCollectionId, NormalizedSearchHit } from '@/Components/Search/adminSearchCollections';

import SearchSelectDialog from './SearchSelectDialog.vue';
import MultiCollectionSelectContent from './MultiCollectionSelectContent.vue';

withDefaults(defineProps<{
  open: boolean;
  collections: AdminSearchCollectionId[];
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
