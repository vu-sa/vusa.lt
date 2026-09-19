<template>
  <tr
    data-slot="table-row"
    :class="cn('hover:bg-accent/70 data-[state=selected]:bg-secondary border-b transition-colors', props.class)"
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
