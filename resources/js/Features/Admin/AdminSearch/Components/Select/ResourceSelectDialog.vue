<template>
  <SearchSelectDialog
    :open="open"
    :multiple="multiple"
    :title="$t('Pasirinkti išteklius')"
    :description="$t('Ieškokite ir filtruokite išteklius; laisvas kiekis rodomas pagal pasirinktą laikotarpį.')"
    :confirm-label="$t('Pridėti pasirinktus')"
    :initial-hits="initialHits"
    @update:open="$emit('update:open', $event)"
    @confirm="$emit('confirm', $event)"
  >
    <template v-if="$slots.trigger" #trigger>
      <slot name="trigger" />
    </template>
    <template #default="{ selectedIds, multiple: isMultiple, toggle, pinnedHits }">
      <ResourceSelectContent
        :selected-ids="selectedIds"
        :multiple="isMultiple"
        :date-time-range="dateTimeRange"
        :excluded-ids="excludedIds"
        :pinned-hits="pinnedHits"
        @toggle="toggle"
      />
    </template>
  </SearchSelectDialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import SearchSelectDialog from './SearchSelectDialog.vue';
import ResourceSelectContent from './ResourceSelectContent.vue';
import type { DateTimeRange } from '../../Composables/useResourceAvailability';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

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
  confirm: [hits: NormalizedSearchHit[]];
}>();
</script>
