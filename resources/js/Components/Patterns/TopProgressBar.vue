<template>
  <div
    v-if="isActive"
    data-slot="top-progress-bar"
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="100"
    :aria-valuenow="isIndeterminate ? undefined : boundedValue"
    :aria-busy="isActive"
    :aria-label
    :class="cn(
      'pointer-events-none relative h-0.5 w-full overflow-hidden bg-transparent',
      props.class,
    )"
  >
    <!-- Determinate progress track -->
    <div
      v-if="!isIndeterminate"
      class="h-full bg-brand-fill transition-all duration-200 ease-out"
      :style="{ width: `${boundedValue}%` }"
    />

    <!-- Indeterminate hairline progress bar -->
    <div
      v-else
      class="top-progress-indeterminate h-full bg-brand-fill"
    />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  indeterminate?: boolean;
  modelValue?: number;
  active?: boolean;
  label?: string;
  class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();

const isIndeterminate = computed(() => props.indeterminate ?? true);
const isActive = computed(() => props.active ?? true);
const ariaLabel = computed(() => props.label || $t('Kraunama...'));

const boundedValue = computed(() => {
  if (props.modelValue === undefined) return 0;
  return Math.min(100, Math.max(0, props.modelValue));
});
</script>

<style scoped>
.top-progress-indeterminate {
  width: 40%;
  animation: top-progress-scan 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

@keyframes top-progress-scan {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(250%);
  }
}

@media (prefers-reduced-motion: reduce) {
  .top-progress-indeterminate {
    animation: none;
    width: 100%;
    opacity: 0.6;
  }
}
</style>
