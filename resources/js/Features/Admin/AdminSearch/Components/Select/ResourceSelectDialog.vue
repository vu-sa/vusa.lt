<template>
  <SearchSelectDialog
    :open
    :multiple
    :title="$t('Pasirinkti išteklius')"
    :description="$t('Ieškokite ir filtruokite išteklius; laisvas kiekis rodomas pagal pasirinktą laikotarpį.')"
    :confirm-label="$t('Pridėti pasirinktus')"
    :initial-hits
    @update:open="$emit('update:open', $event)"
    @confirm="$emit('confirm', $event)"
  >
    <template v-if="$slots.trigger" #trigger>
      <slot name="trigger" />
    </template>
    <template #default="{ selectedIds, multiple: isMultiple, toggle, pinnedHits }">
      <ResourceSelectContent
        :selected-ids
        :multiple="isMultiple"
        :date-time-range
        :excluded-ids
        :pinned-hits
        @toggle="toggle"
      />
    </template>
  </SearchSelectDialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import type { DateTimeRange } from '../../Composables/useResourceAvailability';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

import SearchSelectDialog from './SearchSelectDialog.vue';
import ResourceSelectContent from './ResourceSelectContent.vue';

withDefaults(defineProps<{
  open: boolean;
  dateTimeRange: DateTimeRange;
  multiple?: boolean;
  /** Resource ids already added by the caller — shown disabled. */
  excludedIds?: string[];
  /** Currently-selected resource hits (pre-checked + pinned first). */
  initialHits?: NormalizedSearchHit[];
}>(), {
  multiple: true,
});

defineEmits<{
  'update:open': [open: boolean];
  'confirm': [hits: NormalizedSearchHit[]];
}>();
</script>
