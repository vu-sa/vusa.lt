<script setup lang="ts">
import { computed } from 'vue';
import { CheckCheck } from '@lucide/vue';

import WikiProgressSummary from './WikiProgressSummary.vue';
import type { WikiProgressFlash } from './wikiProgressToast';

const props = defineProps<{ progress: WikiProgressFlash }>();
const complete = computed(() => props.progress.total > 0 && props.progress.done >= props.progress.total);
</script>

<template>
  <!-- The Wiki home's own meter, counting up from where you were. -->
  <div class="w-[min(356px,calc(100vw-2rem))] border border-border bg-popover p-4 text-popover-foreground" role="status" data-slot="wiki-progress-toast">
    <WikiProgressSummary :done="progress.done" :total="progress.total" :from="progress.previousDone" block />
    <p class="mt-3 flex items-start gap-2 text-sm leading-snug">
      <CheckCheck class="mt-0.5 size-4 shrink-0 text-status-success" aria-hidden="true" />
      <span class="min-w-0"><span class="font-semibold">{{ $t('learning.wiki.toast_learned') }}</span> {{ progress.title }}</span>
    </p>
    <p v-if="complete" class="mt-1 pl-6 text-xs font-semibold text-foreground">{{ $t('learning.wiki.toast_complete') }}</p>
  </div>
</template>
