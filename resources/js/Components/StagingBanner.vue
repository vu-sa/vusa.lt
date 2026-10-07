<template>
  <div
    v-if="isStaging && !compact && !dismissed"
    data-slot="staging-status"
    role="status"
    class="rounded-xl border border-amber-300/70 bg-amber-100 text-amber-950 shadow-sm print:hidden dark:border-amber-800/70 dark:bg-amber-950/60 dark:text-amber-100"
  >
    <div class="flex min-h-11 items-center justify-between gap-2 px-3 py-1 text-sm sm:px-4">
      <div class="flex min-w-0 items-center gap-3">
        <AlertTriangle class="size-4 shrink-0 text-amber-800 dark:text-amber-300" aria-hidden="true" />
        <span class="min-w-0 truncate">
          <span class="font-bold uppercase">{{ $t('staging.title') }}</span>
          <span class="hidden text-amber-800 sm:inline dark:text-amber-200"> · {{ $t('staging.summary') }}</span>
        </span>
      </div>
      <div class="flex shrink-0 items-center">
        <button
          type="button"
          data-slot="staging-details-button"
          :class="[actionClass, 'px-3 font-medium underline underline-offset-2']"
          @click="detailsOpen = true"
        >
          {{ $t('staging.details') }}
        </button>
        <button
          type="button"
          :class="[actionClass, 'min-w-11 justify-center']"
          :aria-label="$t('staging.collapse')"
          @click="emit('update:dismissed', true)"
        >
          <X class="size-4" />
        </button>
      </div>
    </div>

    <Dialog v-model:open="detailsOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{{ $t('staging.details_title') }}</DialogTitle>
          <DialogDescription>{{ $t('staging.details_description') }}</DialogDescription>
        </DialogHeader>
        <ul class="divide-y divide-border border-y border-border" data-slot="staging-details">
          <li v-for="note in notes" :key="note.topic" class="flex items-start gap-3 py-3 text-sm">
            <component :is="topicIcons[note.topic]" class="mt-0.5 size-4 shrink-0 text-status-attention" aria-hidden="true" />
            {{ $t(note.key) }}
          </li>
        </ul>
      </DialogContent>
    </Dialog>
  </div>
  <button
    v-else-if="isStaging && compact && dismissed"
    type="button"
    data-slot="staging-warning-button"
    :aria-label="`${$t('staging.expand')}: ${$t('staging.summary')}`"
    :title="`${$t('staging.title')}: ${$t('staging.summary')}`"
    :class="[
      'flex size-11 shrink-0 items-center justify-center',
      'border border-status-attention-border bg-status-attention-surface text-status-attention',
      'transition-colors hover:bg-secondary focus-visible:outline-2 focus-visible:outline-ring',
    ]"
    @click="emit('update:dismissed', false)"
  >
    <AlertTriangle class="size-5" aria-hidden="true" />
  </button>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { AlertTriangle, X, FileWarning, CloudOff, RotateCcw, Mail } from 'lucide-vue-next';

import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { useStaging, type StagingTopic } from '@/Composables/useStaging';

defineProps<{ dismissed?: boolean; compact?: boolean }>();
const emit = defineEmits<{ 'update:dismissed': [value: boolean] }>();

const { isStaging, notes } = useStaging();
const detailsOpen = ref(false);

const topicIcons: Record<StagingTopic, typeof Mail> = { reset: RotateCcw, files: FileWarning, sharepoint: CloudOff, mail: Mail };

const actionClass = [
  'flex min-h-11 items-center rounded-lg transition-colors',
  'hover:bg-amber-200/70 dark:hover:bg-amber-900/60',
  'focus-visible:outline-none focus-visible:ring-2',
  'focus-visible:ring-amber-600 dark:focus-visible:ring-amber-400',
];
</script>
