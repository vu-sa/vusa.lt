<template>
  <h3
    :class="cn('flex items-center gap-2 text-base font-semibold text-foreground', props.class)"
    data-slot="section-heading"
  >
    <component :is="icon" v-if="icon" class="icon-inline shrink-0 text-muted-foreground" />
    <span class="truncate">{{ title }}</span>
    <span v-if="count !== undefined && count !== null" class="shrink-0 text-sm font-normal text-muted-foreground">
      ({{ count }})
    </span>
  </h3>
</template>

<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * A section label for content that is already made of cards. `SectionCard` would
 * wrap such content in a second card; this only titles it.
 */
const props = withDefaults(defineProps<{
  title: string;
  icon?: Component;
  count?: number | string | null;
  class?: HTMLAttributes['class'];
}>(), {
  icon: undefined,
  count: undefined,
  class: undefined,
});
</script>
