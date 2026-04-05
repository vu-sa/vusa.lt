<template>
  <tr
    data-slot="table-row"
    :class="cn('hover:bg-zinc-100/50 data-[state=selected]:bg-zinc-100 border-b transition-colors dark:hover:bg-zinc-800/50 dark:data-[state=selected]:bg-zinc-800', props.class)"
  >
    <slot />
  </tr>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
