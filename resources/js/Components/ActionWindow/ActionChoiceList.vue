<template>
  <div data-slot="action-choice-list" :class="cn('flex flex-col gap-2', props.class)">
    <p v-if="heading" class="px-1 pb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
      {{ heading }}
    </p>
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  heading?: string;
  class?: HTMLAttributes['class'];
}>();
</script>
