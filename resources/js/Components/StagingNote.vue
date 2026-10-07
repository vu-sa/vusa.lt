<template>
  <p
    v-if="note"
    data-slot="staging-note"
    :data-topic="note.topic"
    :class="[
      'flex items-start gap-2 border px-3 py-2 text-xs text-foreground',
      'border-status-attention-border bg-status-attention-surface',
    ]"
  >
    <FlaskConical class="mt-px size-3.5 shrink-0 text-status-attention" aria-hidden="true" />
    <span><span class="font-bold">{{ $t('staging.title') }}:</span> {{ $t(note.key) }}</span>
  </p>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { FlaskConical } from 'lucide-vue-next';

import { useStaging, type StagingTopic } from '@/Composables/useStaging';

const props = defineProps<{ topic: StagingTopic }>();

const { notes } = useStaging();
const note = computed(() => notes.value.find(candidate => candidate.topic === props.topic));
</script>
