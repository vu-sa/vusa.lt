<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-white text-zinc-950 rounded-xl border border-zinc-200 shadow-sm dark:bg-zinc-950 dark:text-zinc-50 dark:border-zinc-800',
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
