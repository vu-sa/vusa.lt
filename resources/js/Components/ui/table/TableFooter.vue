<template>
  <tfoot
    data-slot="table-footer"
    :class="cn('bg-secondary/70 border-t font-medium [&>tr]:last:border-b-0', props.class)"
  >
    <slot />
  </tfoot>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
