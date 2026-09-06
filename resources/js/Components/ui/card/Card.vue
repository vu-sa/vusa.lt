<template>
  <div
    data-slot="card"
    :class="
      cn(
        // Tokens, not raw zinc: the values resolve identically on the admin surface but let the
        // public surface repaint the card (warm paper, hairline border, zero radius) from
        // the public surface scope in app.css.
        'bg-card text-card-foreground rounded-xl border border-border',
        'flex flex-col h-full', // Ensure card takes full height in grid and can flex its children
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
