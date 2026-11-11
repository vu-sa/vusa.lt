<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ done: number; total: number }>();
const percent = computed(() => props.total === 0 ? 0 : Math.min(100, Math.round(props.done / props.total * 100)));
</script>

<template>
  <dl class="w-full sm:w-40" data-slot="wiki-progress" :aria-label="$t('learning.path.summary')">
    <div class="flex min-w-0 flex-col" data-slot="wiki-progress-all">
      <dt class="sr-only">{{ $t('learning.wiki.progress_all') }}</dt>
      <dd class="u-display order-1 text-4xl leading-none tabular-nums">{{ done }}<span class="text-muted-foreground">/{{ total }}</span></dd>
      <dd class="order-3 mt-2 h-2.5 border border-foreground" role="progressbar" :aria-label="$t('learning.wiki.progress_all')" :aria-valuenow="percent" :aria-valuemin="0" :aria-valuemax="100" :aria-valuetext="`${done}/${total}`">
        <div class="h-full bg-foreground transition-[width]" :style="{ width: `${percent}%` }" />
      </dd>
    </div>
  </dl>
</template>
