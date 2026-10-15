<template>
  <div data-slot="agenda-item-body" class="space-y-8">
    <!-- Editors read the type from the status fact and change it from the record's next actions. -->
    <section v-if="!editable" id="agenda-item-type" aria-labelledby="agenda-item-type-title" class="space-y-3">
      <div :class="HEADER_CLASS">
        <h3 id="agenda-item-type-title" :class="HEADING_CLASS">
          <Shapes class="size-4 shrink-0 text-brand" aria-hidden="true" />
          {{ $t('meetings.item.type') }}
        </h3>
      </div>
      <p class="flex items-center gap-2 text-sm font-medium text-foreground">
        <component :is="currentType?.icon ?? CircleHelp" class="size-4 text-muted-foreground" aria-hidden="true" />
        {{ currentType?.label ?? $t('Nepažymėtas') }}
      </p>
    </section>

    <section
      v-if="form.type === 'voting'"
      id="agenda-item-votes"
      aria-labelledby="agenda-item-votes-title"
      class="space-y-3"
    >
      <div :class="HEADER_CLASS">
        <h3 id="agenda-item-votes-title" :class="HEADING_CLASS">
          <Vote class="size-4 shrink-0 text-brand" aria-hidden="true" />
          {{ $t('meetings.item.outcome') }}
        </h3>
        <AdminVotingHelpButton />
      </div>
      <AgendaItemVotes :form :editable :requires-student-perspective @manage="emit('manageVotes')" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleHelp, Shapes, Vote } from '@lucide/vue';

import AdminVotingHelpButton from '@/Components/AgendaItems/AdminVotingHelpButton.vue';
import AgendaItemVotes from '@/Components/AgendaItems/AgendaItemVotes.vue';
import { agendaItemTypeOptions } from '@/Components/AgendaItems/agendaItemTypes';
import type { AgendaItemFormData } from '@/Composables/useAgendaItemAutosave';

const props = withDefaults(defineProps<{
  form: InertiaForm<AgendaItemFormData>;
  /** Live controls that autosave; without it the same layout reads as text and badges. */
  editable?: boolean;
  /** False for VU SA's own bodies: only the outcome is recorded, not a student position. */
  requiresStudentPerspective?: boolean;
}>(), {
  editable: false,
  requiresStudentPerspective: true,
});

const emit = defineEmits<{
  manageVotes: [];
}>();

// The branded section heading (OverviewSection's home variant), kept inline for the anchors.
const HEADER_CLASS = 'flex items-center justify-between gap-3 border-b border-border pb-3';
const HEADING_CLASS = 'flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em] text-foreground';

const currentType = computed(() => agendaItemTypeOptions().find(option => option.value === props.form.type));
</script>
