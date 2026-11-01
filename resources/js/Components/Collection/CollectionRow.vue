<template>
  <article ref="row" class="min-w-0" data-slot="collection-row">
    <div class="flex items-center gap-3">
      <div v-if="$slots.lead" class="shrink-0">
        <slot name="lead" />
      </div>
      <div class="min-w-0 flex-1">
        <slot />
      </div>
      <CollectionRowActions
        :actions
        :spotlight
        stacked
        :fold-primary="width < WIDE_ROW"
        @select="emit('select', $event)"
        @navigate="emit('navigate', $event)"
      />
    </div>
  </article>
</template>

<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { useTemplateRef } from 'vue';

import CollectionRowActions from './CollectionRowActions.vue';

import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';

/**
 * A rows-view item whose title must keep the width. A narrow row is content plus ⋯, with the primary
 * action first in that menu; a wide one shows it beside ⋯. Measured, not a breakpoint, because the
 * same row runs full-width, beside a preview and inside a record section, and a menu item in a
 * portal cannot follow a container query.
 */
withDefaults(defineProps<{
  actions?: AdminRecordAction[];
  spotlight?: boolean;
}>(), {
  actions: () => [],
});

const emit = defineEmits<{
  select: [key: string];
  navigate: [key: string];
}>();

const WIDE_ROW = 672;
const { width } = useElementSize(useTemplateRef<HTMLElement>('row'));
</script>
