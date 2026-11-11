<template>
  <div class="flex flex-col gap-4" data-slot="representative-share-bar">
    <!-- The bar shows proportion at a glance; the legend below carries the numbers and the controls. -->
    <div class="flex h-2.5 w-full gap-px bg-muted" aria-hidden="true">
      <span
        v-for="segment in drawn"
        :key="segment.key"
        :class="['h-full transition-opacity', fillClass[segment.tone], activeKey && activeKey !== segment.key && 'opacity-35']"
        :style="{ flexGrow: segment.value }"
        :data-segment="segment.key"
      />
    </div>
    <ul class="grid grid-cols-2 gap-x-6 gap-y-1 sm:grid-cols-4">
      <li v-for="segment in segments" :key="segment.key">
        <button
          type="button"
          :class="[
            'flex min-h-11 w-full flex-col items-start gap-1 border-b-2 py-2 text-left',
            'focus-visible:outline-2 focus-visible:outline-ring',
            activeKey === segment.key ? 'border-foreground' : 'border-transparent hover:border-border',
          ]"
          :aria-pressed="activeKey === segment.key"
          :data-legend="segment.key"
          @click="emit('select', segment.key)"
        >
          <span class="text-2xl font-semibold leading-none tabular-nums">{{ segment.value }}</span>
          <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
            <span :class="['size-2.5 shrink-0', fillClass[segment.tone]]" aria-hidden="true" />
            {{ segment.label }}
          </span>
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import type { StatusRole } from '@/Constants/statuses';

export interface ShareSegment {
  key: string;
  label: string;
  value: number;
  tone: StatusRole;
}

const props = defineProps<{
  segments: ShareSegment[];
  /** The segment the list below is filtered by; the others fade. */
  activeKey?: string | null;
}>();

const emit = defineEmits<{
  select: [key: string];
}>();

const drawn = computed(() => props.segments.filter(segment => segment.value > 0));

const fillClass: Record<StatusRole, string> = {
  neutral: 'bg-status-neutral',
  info: 'bg-status-info',
  progress: 'bg-status-progress',
  attention: 'bg-status-attention',
  success: 'bg-status-success',
  danger: 'bg-status-danger',
};
</script>
