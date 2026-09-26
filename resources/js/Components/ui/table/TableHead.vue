<template>
  <th
    data-slot="table-head"
    :class="cn('h-11 px-4 text-left align-middle text-[11px] font-bold uppercase tracking-[0.18em] whitespace-nowrap text-muted-foreground [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]', props.class)"
  >
    <slot />
  </th>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
