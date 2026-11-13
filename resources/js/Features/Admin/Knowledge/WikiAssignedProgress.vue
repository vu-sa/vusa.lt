<template>
  <dl class="w-full" data-slot="wiki-progress" :aria-label="$t('learning.path.summary')">
    <div class="flex min-w-0 flex-col" data-slot="wiki-progress-assigned">
      <dt class="sr-only">
        {{ $t('learning.wiki.progress_assigned') }}
      </dt>
      <dd class="u-display order-1 text-4xl leading-none tabular-nums">
        {{ done }}<span class="text-muted-foreground">/{{ total }}</span>
      </dd>
      <dd class="order-3 mt-2 flex h-2.5 gap-1" :aria-label="$t('learning.wiki.progress_assigned')" role="group">
        <template v-for="priority in priorities" :key="priority.importance">
          <div
            v-if="priority.total"
            :class="['min-w-0 border', priority.border]"
            :style="{ flexGrow: priority.total, flexBasis: '0%' }"
            role="progressbar"
            :aria-label="$t(`learning.documents.${priority.importance}`)"
            :aria-valuenow="priority.done"
            :aria-valuemin="0"
            :aria-valuemax="priority.total"
            :data-priority="priority.importance"
          >
            <div :class="['h-full transition-[width]', priority.fill]" :style="{ width: `${percent(priority.done, priority.total)}%` }" />
          </div>
        </template>
        <div v-if="!total" class="w-full border border-border" />
      </dd>
      <dd v-if="showLegend" class="order-4 mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] leading-snug" data-slot="wiki-progress-legend">
        <span v-for="priority in priorities" :key="priority.importance" :class="priority.text">
          {{ $t(`learning.documents.${priority.importance}`) }} <span class="tabular-nums">{{ priority.done }}/{{ priority.total }}</span>
        </span>
      </dd>
    </div>
  </dl>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { LearningImportance } from '@/Types/enums';
import type { KnowledgePriorityProgress } from '@/Types/learning';

const props = withDefaults(defineProps<{
  progress: KnowledgePriorityProgress[];
  showLegend?: boolean;
}>(), { showLegend: true });
const done = computed(() => props.progress.reduce((sum, priority) => sum + priority.done, 0));
const total = computed(() => props.progress.reduce((sum, priority) => sum + priority.total, 0));
const percent = (done: number, total: number) => total === 0 ? 0 : Math.min(100, Math.round(done / total * 100));
const priorities = computed(() => [
  { importance: LearningImportance.Essential, border: 'border-brand', fill: 'bg-brand-fill', text: 'text-brand' },
  { importance: LearningImportance.Recommended, border: 'border-foreground', fill: 'bg-foreground', text: 'text-foreground' },
  { importance: LearningImportance.Optional, border: 'border-dashed border-muted-foreground', fill: 'bg-muted-foreground', text: 'text-muted-foreground' },
].map(priority => ({ ...priority, done: 0, total: 0, ...props.progress.find(item => item.importance === priority.importance) })));
</script>
