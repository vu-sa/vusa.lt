<template>
  <dl
    :class="cn('grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-0.5 border-t border-border/60 pt-2 text-xs', props.class)"
    data-slot="context-facts"
  >
    <slot />
  </dl>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/** Label beside value under a hairline, read top to bottom; PreviewFacts splits them to the edges. */
const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
