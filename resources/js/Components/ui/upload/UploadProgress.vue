<template>
  <div
    :class="cn('w-full', props.class)"
    data-slot="upload-progress"
  >
    <div class="flex items-center justify-between">
      <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
        <div
          class="h-full rounded-full bg-vusa-red transition-all duration-300 ease-out"
          :style="{ width: `${Math.min(100, Math.max(0, progress))}%` }"
        />
      </div>
      <span
        v-if="showLabel"
        class="ml-3 min-w-[3rem] text-right text-xs font-medium text-zinc-600 dark:text-zinc-400"
      >
        {{ progress }}%
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  progress: number;
  class?: HTMLAttributes['class'];
  showLabel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showLabel: false,
});
</script>
