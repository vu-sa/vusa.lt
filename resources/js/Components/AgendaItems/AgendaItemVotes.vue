<template>
  <section class="space-y-4">
    <!-- Section header -->
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $t('Balsavimo klausimai') }}</span>
      <span v-if="form.votes.length" class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ form.votes.length }}</span>
      <AdminVotingHelpButton class="ml-auto" />
    </div>

    <p v-if="form.votes.length === 0" class="text-sm italic text-muted-foreground">
      {{ editing ? $t('Balsavimų dar nėra.') : $t('Neaptarta') }}
    </p>

    <div ref="listContainer" class="space-y-3">
      <div
        v-for="(vote, index) in form.votes"
        :key="keyFor(vote)"
        class="flex items-start gap-2 sm:gap-3"
      >
        <!-- Gutter: which vote counts as the item's outcome, and the drag grip -->
        <div class="flex w-5 shrink-0 flex-col items-center gap-2 pt-4">
          <button
            type="button"
            :disabled="!editing || vote.is_main"
            :class="[
              'flex h-5 w-5 items-center justify-center text-xs font-semibold transition-colors',
              vote.is_main ? 'text-amber-500' : 'text-muted-foreground',
              editing && !vote.is_main ? 'hover:text-amber-500' : '',
            ]"
            :title="vote.is_main ? $t('Pagrindinis balsavimas') : $t('Žymėti pagrindiniu')"
            @click="setMain(index)"
          >
            <Star v-if="vote.is_main" class="h-4 w-4 fill-amber-400" />
            <span v-else>{{ index + 1 }}</span>
          </button>
          <span
            v-if="editing && form.votes.length > 1"
            class="vote-drag-handle cursor-grab text-zinc-300 transition-colors hover:text-zinc-500 dark:text-zinc-600 dark:hover:text-zinc-400"
            :aria-label="$t('Tempti')"
          >
            <GripVertical class="h-4 w-4" />
          </span>
        </div>

        <!-- Vote card. The main vote is marked by its gold star alone; tinting the whole
             card gold competed with the outcome colours inside it. -->
        <div class="min-w-0 flex-1 rounded-lg border border-zinc-200 bg-zinc-50/70 dark:bg-zinc-900/40 dark:border-zinc-800">
          <div class="flex flex-wrap items-start gap-x-3 gap-y-2 p-3.5">
            <button
              type="button"
              class="flex min-w-0 flex-1 basis-48 items-start gap-2.5 text-left"
              :aria-expanded="isExpanded(vote)"
              @click="toggle(vote)"
            >
              <component :is="isExpanded(vote) ? ChevronUp : ChevronDown" class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
              <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-2">
                  <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ $t('Balsavimas') }} {{ index + 1 }}
                  </span>
                  <span
                    v-if="vote.is_consensus"
                    class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-700 dark:bg-teal-900/20 dark:text-teal-300"
                  >
                    <Handshake class="h-3 w-3" />
                    {{ $t('Bendru sutarimu') }}
                  </span>
                </span>
                <span
                  class="mt-1 block truncate text-sm"
                  :class="vote.title ? 'text-zinc-700 dark:text-zinc-300' : 'italic text-muted-foreground'"
                >
                  {{ vote.title || $t('Be pavadinimo') }}
                </span>
              </span>
            </button>

            <!-- Recorded values, so a collapsed vote still reads at a glance. Too narrow
                 to sit beside the title, they take a row of their own rather than vanish. -->
            <div
              v-if="!isExpanded(vote)"
              class="order-last flex w-full flex-wrap items-center gap-1.5 sm:order-none sm:ml-auto sm:w-auto sm:justify-end"
            >
              <span
                v-for="summary in summaryOf(vote)"
                :key="summary.key"
                :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium', summary.pillClass]"
                :title="summary.rowLabel"
              >
                <span :class="['h-1.5 w-1.5 rounded-full', summary.dotClass]" />
                {{ summary.label }}
              </span>
            </div>

            <div v-if="editing" class="flex shrink-0 items-center gap-0.5">
              <button
                v-if="!vote.is_main"
                type="button"
                class="rounded-md p-1.5 text-zinc-400 transition-colors hover:text-amber-500"
                :title="$t('Žymėti pagrindiniu')"
                @click="setMain(index)"
              >
                <Star class="h-4 w-4" />
              </button>
              <button
                type="button"
                class="rounded-md p-1.5 text-zinc-400 transition-colors hover:text-destructive"
                :title="$t('Šalinti balsavimą')"
                @click="removeVote(index)"
              >
                <Trash2 class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div v-if="isExpanded(vote)" class="space-y-4 border-t border-zinc-200 px-3.5 pb-4 pt-4 dark:border-zinc-800">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
              <Input
                v-model="vote.title"
                class="h-9 min-w-0 flex-1 basis-48 bg-white dark:bg-zinc-950/40 text-sm"
                maxlength="200"
                :readonly="!editing"
                :placeholder="$t('Pridėti pavadinimą (nebūtina)')"
              />
              <label class="flex shrink-0 items-center gap-2.5 text-sm text-zinc-600 dark:text-zinc-400" :class="editing ? 'cursor-pointer' : ''">
                <Switch
                  :model-value="vote.is_consensus ?? false"
                  :disabled="!editing"
                  @update:model-value="(v: boolean) => setConsensus(index, v)"
                />
                {{ $t('Bendru sutarimu') }}
              </label>
            </div>

            <div class="space-y-3">
              <!-- The label sits above its options until there is room for a column. -->
              <div
                v-for="row in voteRows"
                :key="row.key"
                class="grid gap-1.5 sm:grid-cols-[7rem_1fr] sm:items-center sm:gap-x-4"
              >
                <span class="text-xs font-medium text-muted-foreground">{{ row.label }}</span>
                <div class="grid grid-cols-3 gap-1.5">
                  <button
                    v-for="opt in row.options"
                    :key="opt.value"
                    type="button"
                    :disabled="!editing"
                    class="flex min-w-0 items-center justify-center gap-1.5 rounded-md border px-2 py-2 text-xs font-medium transition-colors disabled:cursor-default"
                    :class="vote[row.key] === opt.value ? opt.activeClass : INACTIVE_OPTION_CLASS"
                    @click="vote[row.key] = opt.value"
                  >
                    <component :is="opt.icon" v-if="opt.icon" class="h-3.5 w-3.5 shrink-0" />
                    <span class="truncate">{{ opt.label }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Button v-if="editing" type="button" variant="outline" size="sm" @click="addVote">
      <Plus class="mr-1 h-4 w-4" />
      {{ $t('Pridėti balsavimo klausimą') }}
    </Button>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch, type Component } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { useSortable } from '@vueuse/integrations/useSortable';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, ChevronUp, GripVertical, Handshake, Minus, Plus, Star, ThumbsDown, ThumbsUp, Trash2 } from 'lucide-vue-next';

import AdminVotingHelpButton from '@/Components/AgendaItems/AdminVotingHelpButton.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import type { VoteValue } from '@/Composables/useAgendaItemStyling';
import type { AgendaItemFormData, EditableVote } from '@/Composables/useAgendaItemAutosave';

const props = withDefaults(defineProps<{
  form: InertiaForm<AgendaItemFormData>;
  editing?: boolean;
  /**
   * False for VU SA's own bodies: the representatives *are* the organisation, so there is no
   * separate student position or student benefit to record — only the outcome.
   */
  requiresStudentPerspective?: boolean;
}>(), {
  editing: false,
  requiresStudentPerspective: true,
});

type VoteField = 'decision' | 'student_vote' | 'student_benefit';
interface VoteOption {
  value: Exclude<VoteValue, null | undefined>;
  label: string;
  icon?: Component;
  activeClass: string;
}
interface VoteRow {
  key: VoteField;
  label: string;
  options: VoteOption[];
}

/** Unchosen options are outlined, not filled, so only the recorded answer carries weight. */
const INACTIVE_OPTION_CLASS
  = 'border-zinc-200 bg-white dark:bg-zinc-950/40 text-zinc-500 enabled:hover:border-zinc-400 enabled:hover:text-foreground '
    + 'disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-400 dark:enabled:hover:border-zinc-500';

const POSITIVE_CLASS = 'border-emerald-600 bg-emerald-600 text-white';
const NEGATIVE_CLASS = 'border-red-600 bg-red-600 text-white';
const NEUTRAL_CLASS = 'border-zinc-500 bg-zinc-500 text-white';

const decisionOptions: VoteOption[] = [
  { value: 'positive', label: $t('Priimtas'), activeClass: POSITIVE_CLASS },
  { value: 'negative', label: $t('Atmestas'), activeClass: NEGATIVE_CLASS },
  { value: 'neutral', label: $t('Susilaikyta'), activeClass: NEUTRAL_CLASS },
];

const studentVoteOptions: VoteOption[] = [
  { value: 'positive', label: $t('Pritarė'), activeClass: POSITIVE_CLASS },
  { value: 'negative', label: $t('Nepritarė'), activeClass: NEGATIVE_CLASS },
  { value: 'neutral', label: $t('Susilaikyta'), activeClass: NEUTRAL_CLASS },
];

const benefitOptions: VoteOption[] = [
  { value: 'positive', label: $t('Palanku'), icon: ThumbsUp, activeClass: POSITIVE_CLASS },
  { value: 'negative', label: $t('Nepalanku'), icon: ThumbsDown, activeClass: NEGATIVE_CLASS },
  { value: 'neutral', label: $t('Neutralu'), icon: Minus, activeClass: NEUTRAL_CLASS },
];

const voteRows = computed<VoteRow[]>(() => {
  const rows: VoteRow[] = [
    { key: 'decision', label: $t('Rezultatas'), options: decisionOptions },
  ];

  if (props.requiresStudentPerspective) {
    rows.push(
      { key: 'student_vote', label: $t('Studentai'), options: studentVoteOptions },
      { key: 'student_benefit', label: $t('Nauda'), options: benefitOptions },
    );
  }

  return rows;
});

/**
 * Identity that survives reordering. A vote's array index cannot be the key (drag
 * would reuse the wrong card) and a new vote has no id until it is saved.
 */
const keys = new WeakMap<object, string>();
let keySeq = 0;
const keyFor = (vote: EditableVote): string => {
  let key = keys.get(vote);
  if (!key) {
    key = `vote-${(keySeq += 1)}`;
    keys.set(vote, key);
  }
  return key;
};

const expandedKeys = ref(new Set<string>());

const isExpanded = (vote: EditableVote) => expandedKeys.value.has(keyFor(vote));

const toggle = (vote: EditableVote) => {
  const key = keyFor(vote);
  const next = new Set(expandedKeys.value);
  if (next.has(key)) {
    next.delete(key);
  }
  else {
    next.add(key);
  }
  expandedKeys.value = next;
};

/** A vote still missing an answer opens on its own; a recorded one reads from its summary. */
const isIncomplete = (vote: EditableVote) => voteRows.value.some(row => !vote[row.key]);

watch(() => props.form.votes.length, () => {
  const next = new Set(expandedKeys.value);
  props.form.votes.forEach((vote) => {
    if (isIncomplete(vote)) {
      next.add(keyFor(vote));
    }
  });
  expandedKeys.value = next;
}, { immediate: true });

const readLabel = (row: VoteRow, value: VoteValue): string =>
  row.options.find(opt => opt.value === value)?.label ?? '—';

const PILL_CLASSES: Record<string, { pill: string; dot: string }> = {
  positive: {
    pill: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/25 dark:text-emerald-300',
    dot: 'bg-emerald-500',
  },
  negative: {
    pill: 'bg-red-50 text-red-700 dark:bg-red-900/25 dark:text-red-300',
    dot: 'bg-red-500',
  },
  neutral: {
    pill: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
    dot: 'bg-zinc-400',
  },
};

const summaryOf = (vote: EditableVote) =>
  voteRows.value
    .filter(row => Boolean(vote[row.key]))
    .map((row) => {
      const styling = PILL_CLASSES[String(vote[row.key])] ?? PILL_CLASSES.neutral;
      return {
        key: row.key,
        rowLabel: row.label,
        label: readLabel(row, vote[row.key]),
        pillClass: styling.pill,
        dotClass: styling.dot,
      };
    });

const addVote = () => {
  const vote: EditableVote = {
    id: null,
    is_main: props.form.votes.length === 0,
    is_consensus: false,
    title: null,
    student_vote: null,
    decision: null,
    student_benefit: null,
    order: props.form.votes.length,
  };
  props.form.votes.push(vote);
};

const removeVote = (index: number) => {
  const removed = props.form.votes[index];
  props.form.votes.splice(index, 1);

  // Promote a remaining vote to main if we removed the main one
  if (removed?.is_main && props.form.votes.length > 0) {
    props.form.votes[0].is_main = true;
  }
};

/** Exactly one vote is the item's outcome — the backend enforces the same invariant. */
const setMain = (index: number) => {
  if (!props.editing) {
    return;
  }
  props.form.votes.forEach((vote, position) => {
    vote.is_main = position === index;
  });
};

const setConsensus = (index: number, value: boolean) => {
  const vote = props.form.votes[index];
  vote.is_consensus = value;
  if (!value) {
    return;
  }

  vote.decision = 'positive';
  if (props.requiresStudentPerspective) {
    vote.student_vote = 'positive';
    vote.student_benefit = 'positive';
  }
};

// Drag reordering. `order` is derived from array position on submit
// (useAgendaItemAutosave), so moving an element is all that has to happen here.
const listContainer = ref<HTMLElement | null>(null);

const sortable = useSortable(listContainer, () => props.form.votes, {
  handle: '.vote-drag-handle',
  animation: 200,
  disabled: !props.editing,
});

watch(() => props.editing, (editing) => {
  sortable.option('disabled', !editing);
});
</script>
