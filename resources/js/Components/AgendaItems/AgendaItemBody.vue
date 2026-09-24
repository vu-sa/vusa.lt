<template>
  <div data-slot="agenda-item-body" class="space-y-8">
    <section v-if="!editable || typeOpen" id="agenda-item-type" aria-labelledby="agenda-item-type-title" class="space-y-2">
      <div class="flex items-center justify-between gap-3">
        <h3 id="agenda-item-type-title" :class="[LABEL_CLASS, 'flex items-center gap-2']">
          {{ $t('meetings.item.type') }}
          <span v-if="awaitingType" class="size-1.5 bg-status-attention" aria-hidden="true" data-testid="agenda-item-type-pending" />
        </h3>
        <Button
          v-if="editable && form.type"
          variant="ghost"
          size="sm"
          voice="sentence"
          class="text-muted-foreground pointer-coarse:h-11"
          data-testid="agenda-item-type-collapse"
          @click="typeOpen = false"
        >
          <ChevronUp class="size-4" />
          {{ $t('meetings.item.collapse_type') }}
        </Button>
      </div>
      <!-- An unset type is the one thing between the rep and the outcome, so the picker itself asks for it. -->
      <FormSegmentedControl
        v-if="editable"
        v-model="typeModel"
        :options="typeOptions"
        :aria-label="$t('meetings.item.type')"
        test-id-prefix="agenda-item-type"
        stack-on-phone
        :class="awaitingType ? 'border-status-attention-border bg-status-attention-surface' : undefined"
      />
      <p v-else class="flex items-center gap-2 text-sm font-medium text-foreground">
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
      <div class="flex items-center justify-between gap-3">
        <h3 id="agenda-item-votes-title" :class="LABEL_CLASS">
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
import { CalendarClock, ChevronUp, CircleHelp, Coffee, Info, Vote } from 'lucide-vue-next';

import AdminVotingHelpButton from '@/Components/AgendaItems/AdminVotingHelpButton.vue';
import AgendaItemVotes from '@/Components/AgendaItems/AgendaItemVotes.vue';
import { FormSegmentedControl, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { createVote, type AgendaItemFormData } from '@/Composables/useAgendaItemAutosave';

type AgendaItemType = NonNullable<AgendaItemFormData['type']>;

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

/** Once a type is set the picker folds away; the record page offers a way back to it. */
const typeOpen = defineModel<boolean>('typeOpen', { default: true });

const LABEL_CLASS = 'text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground';

const typeOptions: FormSegmentOption<AgendaItemType>[] = [
  { value: 'voting', label: $t('Balsavimas'), icon: Vote },
  { value: 'informational', label: $t('Informacinis'), icon: Info },
  { value: 'deferred', label: $t('Atidėtas'), icon: CalendarClock },
  // A pause is a real agenda entry — excluding it forced editors to mistype it as something else.
  { value: 'break', label: $t('Pertrauka'), icon: Coffee },
];

const awaitingType = computed(() => props.editable && !props.form.type);

const currentType = computed(() => typeOptions.find(option => option.value === props.form.type));

/**
 * Choosing "Balsavimas" opens the first vote straight away, so the outcome is one more tap —
 * not a separate "add a vote" step before anything can be recorded.
 */
const typeModel = computed<AgendaItemType>({
  // FormSegmentedControl needs a value; an unset type simply matches no option.
  get: () => props.form.type as AgendaItemType,
  set: (value) => {
    props.form.type = value;
    if (value === 'voting' && props.form.votes.length === 0) {
      props.form.votes.push(createVote(true));
    }
  },
});

</script>
