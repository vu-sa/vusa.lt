<template>
  <SpotlightPopover
    :title="spotlight.title"
    :description="spotlight.description"
    :is-dismissed="feature.isDismissed.value"
    @dismiss="feature.dismiss"
  >
    <button
      type="button"
      :data-filter-id="filter.id"
      :aria-pressed="filter.active"
      :class="controlVariants({ size: 'sm', active: filter.active, voice: 'sentence' })"
      @click="select"
    >
      {{ filter.label }}
    </button>
  </SpotlightPopover>
</template>

<script setup lang="ts">
import type { CollectionQuickFilter } from './types';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { controlVariants } from '@/Components/ui/control';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const props = defineProps<{
  filter: CollectionQuickFilter;
  spotlight: NonNullable<CollectionQuickFilter['spotlight']>;
}>();

const emit = defineEmits<{ toggle: [id: string] }>();
const feature = useFeatureSpotlight(props.spotlight.id);

function select(): void {
  void feature.dismiss();
  emit('toggle', props.filter.id);
}
</script>
