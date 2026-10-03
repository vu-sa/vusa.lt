<template>
  <figure class="flex flex-col gap-2" data-slot="overview-chart" :aria-describedby="captionId">
    <slot />
    <figcaption :id="captionId" class="text-sm text-muted-foreground" data-testid="chart-summary">
      {{ summary }}
    </figcaption>
  </figure>
</template>

<script setup lang="ts">
import { useId } from 'vue';

defineProps<{
  /** Required: a chart is never the only carrier of its message (O17). */
  summary: string;
}>();

const captionId = useId();
</script>
