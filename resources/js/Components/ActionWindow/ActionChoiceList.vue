<template>
  <div data-slot="action-choice-list" :class="props.class">
    <p v-if="heading" class="pb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
      {{ heading }}
    </p>
    <div class="flex flex-col gap-2">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
  heading?: string;
  class?: HTMLAttributes['class'];
}>();
</script>
