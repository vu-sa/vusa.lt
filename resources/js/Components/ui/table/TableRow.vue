<template>
  <tr
    data-slot="table-row"
    :class="cn('border-b border-border/60 transition-colors last:border-b-0 hover:bg-secondary/40 data-[state=selected]:bg-brand/5', props.class)"
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
