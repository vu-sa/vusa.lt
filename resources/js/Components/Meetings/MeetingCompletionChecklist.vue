<template>
  <!-- An empty agenda is already the status badge and the header's "Įklijuoti darbotvarkę". -->
  <section
    v-if="itemActions.length"
    id="meeting-completion"
    data-slot="meeting-completion"
    class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center"
    aria-labelledby="meeting-completion-title"
  >
    <p id="meeting-completion-title" class="flex items-center gap-2 text-sm font-medium text-foreground">
      <CircleDashed class="size-4 shrink-0 text-status-attention" aria-hidden="true" />
      {{ $t('meetings.completion.items_missing', { count: String(itemActions.length) }) }}
    </p>

    <ul class="flex flex-wrap gap-1.5" :aria-label="$t('meetings.completion.items_label')">
      <li v-for="action in itemActions" :key="action.agenda_item_id">
        <button
          type="button"
          class="flex size-8 items-center justify-center border border-status-attention-border text-xs font-semibold tabular-nums text-status-attention transition-colors hover:bg-status-attention-surface pointer-coarse:size-11"
          :title="`${action.title} — ${missingFieldsLabel(action)}`"
          :aria-label="`${action.position}. ${action.title} — ${missingFieldsLabel(action)}`"
          @click="$emit('select', action)"
        >
          {{ action.position }}
        </button>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { CircleDashed } from 'lucide-vue-next';

import { isAgendaItemAction, missingFieldsLabel, type MeetingMissingAction } from './meetingCompletion';

export type { MeetingMissingAction } from './meetingCompletion';

const props = defineProps<{
  actions: MeetingMissingAction[];
}>();

defineEmits<{
  select: [action: MeetingMissingAction];
}>();

const itemActions = computed(() => props.actions.filter(isAgendaItemAction));
</script>
