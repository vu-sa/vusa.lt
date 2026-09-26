<template>
  <div data-slot="agenda-item-votes" class="space-y-3">
    <p v-if="form.votes.length === 0" class="border-y border-border py-3 text-sm text-muted-foreground">
      {{ $t('Neaptarta') }}
    </p>

    <ol v-else class="space-y-3">
      <li
        v-for="(vote, index) in form.votes"
        :key="keyFor(vote)"
        class="space-y-4 border border-border bg-card p-4"
        data-slot="agenda-item-vote"
      >
        <div v-if="form.votes.length > 1 || voteTitle(vote)" class="space-y-1.5">
          <p v-if="form.votes.length > 1" class="flex items-center gap-2 text-sm font-semibold text-foreground">
            {{ $t('Balsavimas') }} {{ index + 1 }}
            <span
              v-if="vote.is_main"
              class="inline-flex items-center gap-1 border border-border px-1.5 py-0.5 text-xs font-medium text-brand"
            >
              <Star class="size-3.5 fill-current" aria-hidden="true" />
              {{ $t('meetings.item.main_vote') }}
            </span>
          </p>

          <!-- Titles are rare, so they are edited in "Tvarkyti balsavimus" rather than on every card. -->
          <p v-if="voteTitle(vote)" class="text-sm text-foreground">
            {{ voteTitle(vote) }}
          </p>
        </div>

        <label v-if="editable" class="flex w-fit cursor-pointer items-center gap-3 text-sm text-foreground pointer-coarse:min-h-11">
          <Switch
            :model-value="vote.is_consensus ?? false"
            @update:model-value="(value: boolean) => setConsensus(vote, value)"
          />
          <Handshake class="size-4 text-muted-foreground" aria-hidden="true" />
          {{ $t('meetings.item.consensus') }}
        </label>
        <p v-else-if="vote.is_consensus" class="flex items-center gap-2 text-sm text-foreground">
          <Handshake class="size-4 text-muted-foreground" aria-hidden="true" />
          {{ $t('meetings.item.consensus') }}
        </p>

        <div class="space-y-3">
          <div
            v-for="row in voteRows"
            :key="row.key"
            class="grid gap-2 sm:grid-cols-[10rem_1fr] sm:items-start sm:gap-x-4"
            :data-testid="`vote-row-${row.key}`"
          >
            <span class="text-sm font-medium text-muted-foreground sm:pt-3">{{ row.label }}</span>

            <template v-if="editable">
              <!-- An answered row collapses to its answer; tapping it brings the choices back. -->
              <button
                v-if="!isOpen(vote, row.key)"
                type="button"
                :class="[
                  'flex min-h-11 min-w-0 items-center gap-2 border px-3 py-2 text-left text-sm font-medium transition-colors pointer-coarse:min-h-12',
                  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40',
                  statusRoleClasses[chosen(row, vote[row.key])!.role],
                ]"
                :aria-label="`${row.label}: ${chosen(row, vote[row.key])!.label}. ${$t('meetings.item.change_answer')}`"
                :data-testid="`vote-${row.key}-answer`"
                @click="open(vote, row.key)"
              >
                <component :is="chosen(row, vote[row.key])!.icon" class="size-4 shrink-0" aria-hidden="true" />
                <span class="min-w-0 flex-1 truncate">{{ chosen(row, vote[row.key])!.label }}</span>
                <span class="flex shrink-0 items-center gap-1 text-xs font-normal opacity-70">
                  {{ $t('meetings.item.change_answer') }}
                  <ChevronDown class="size-3.5" aria-hidden="true" />
                </span>
              </button>

              <div v-else class="space-y-1.5">
                <div class="grid grid-cols-3 gap-1.5" role="group" :aria-label="row.label">
                  <button
                    v-for="option in row.options"
                    :key="String(option.value)"
                    type="button"
                    :aria-pressed="vote[row.key] === option.value"
                    :data-testid="`vote-${row.key}-${option.value}`"
                    :class="[
                      'flex min-h-11 min-w-0 items-center justify-center gap-1.5 border px-2 py-2 text-sm font-medium transition-colors pointer-coarse:min-h-12',
                      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40',
                      vote[row.key] === option.value ? statusRoleClasses[option.role] : INACTIVE_OPTION_CLASS,
                    ]"
                    @click="answer(vote, row.key, option.value)"
                  >
                    <component :is="option.icon" class="size-4 shrink-0" aria-hidden="true" />
                    <span class="truncate">{{ option.label }}</span>
                  </button>
                </div>
                <button
                  v-if="vote[row.key] !== null && vote[row.key] !== undefined"
                  type="button"
                  class="text-xs text-muted-foreground underline underline-offset-4 hover:text-foreground pointer-coarse:min-h-11"
                  :data-testid="`vote-${row.key}-clear`"
                  @click="answer(vote, row.key, null)"
                >
                  {{ $t('meetings.item.clear_answer') }}
                </button>
              </div>
            </template>
            <StatusBadge v-else :status="readStatus(row, vote[row.key])" class="sm:mt-2.5" />
          </div>
        </div>

        <p v-if="voteNote(vote)" class="whitespace-pre-line text-sm text-muted-foreground">
          {{ voteNote(vote) }}
        </p>
      </li>
    </ol>

    <div v-if="editable" class="flex flex-wrap items-center gap-2">
      <Button variant="outline" size="sm" voice="sentence" class="pointer-coarse:h-11" @click="addVote">
        <Plus class="size-4" />
        {{ $t('meetings.item.add_vote') }}
      </Button>
      <Button
        v-if="form.votes.length"
        variant="ghost"
        size="sm"
        voice="sentence"
        class="text-muted-foreground pointer-coarse:h-11"
        @click="emit('manage')"
      >
        <Settings2 class="size-4" />
        {{ $t('meetings.item.manage_votes') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  ChevronDown,
  CircleCheck,
  CircleDashed,
  CircleMinus,
  CircleX,
  Handshake,
  Plus,
  Settings2,
  Star,
  ThumbsDown,
  ThumbsUp,
} from 'lucide-vue-next';

import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { statusRoleClasses, type StatusPresentation, type StatusRole } from '@/Constants/statuses';
import { createVote, type AgendaItemFormData, type EditableVote, type VoteValue } from '@/Composables/useAgendaItemAutosave';

const props = withDefaults(defineProps<{
  form: InertiaForm<AgendaItemFormData>;
  /** Big tap choices that autosave; otherwise each answer reads as a status badge. */
  editable?: boolean;
  /** False for VU SA's own bodies: they record only the outcome, not a student position. */
  requiresStudentPerspective?: boolean;
}>(), {
  editable: false,
  requiresStudentPerspective: true,
});

const emit = defineEmits<{
  manage: [];
}>();

type VoteField = 'decision' | 'student_vote' | 'student_benefit';

interface VoteOption {
  value: VoteValue;
  label: string;
  icon: Component;
  role: StatusRole;
}

interface VoteRow {
  key: VoteField;
  label: string;
  /** The recordable answers; an empty row already means "not recorded". */
  options: VoteOption[];
  unanswered: VoteOption;
}

/** Unchosen options are outlined, so only the recorded answer carries colour. */
const INACTIVE_OPTION_CLASS = 'border-border bg-background text-muted-foreground hover:border-foreground/30 hover:text-foreground';

const option = (value: VoteValue, label: string, icon: Component, role: StatusRole): VoteOption => ({ value, label, icon, role });

const decisionRow: VoteRow = {
  key: 'decision',
  label: $t('Sprendimas'),
  options: [
    option('positive', $t('Priimtas'), CircleCheck, 'success'),
    option('negative', $t('Atmestas'), CircleX, 'danger'),
    option('neutral', $t('Susilaikyta'), CircleMinus, 'neutral'),
  ],
  unanswered: option(null, $t('Nefiksuota'), CircleDashed, 'attention'),
};

const studentVoteRow: VoteRow = {
  key: 'student_vote',
  label: $t('Studentų balsas'),
  options: [
    option('positive', $t('Pritarė'), CircleCheck, 'success'),
    option('negative', $t('Nepritarė'), CircleX, 'danger'),
    option('neutral', $t('Susilaikyta'), CircleMinus, 'neutral'),
  ],
  unanswered: option(null, $t('Nebalsuota'), CircleDashed, 'attention'),
};

const benefitRow: VoteRow = {
  key: 'student_benefit',
  label: $t('Nauda studentams'),
  options: [
    option('positive', $t('Palanku'), ThumbsUp, 'success'),
    option('negative', $t('Nepalanku'), ThumbsDown, 'danger'),
    option('neutral', $t('Neutralu'), CircleMinus, 'neutral'),
  ],
  unanswered: option(null, $t('Nežinoma'), CircleDashed, 'attention'),
};

const voteRows = computed<VoteRow[]>(() => (props.requiresStudentPerspective
  ? [decisionRow, studentVoteRow, benefitRow]
  : [decisionRow]));

const chosen = (row: VoteRow, value: VoteValue | undefined): VoteOption | undefined =>
  row.options.find(candidate => candidate.value === value);

const readStatus = (row: VoteRow, value: VoteValue | undefined): StatusPresentation => {
  const picked = chosen(row, value) ?? row.unanswered;

  return { label: picked.label, role: picked.role, icon: picked.icon as StatusPresentation['icon'] };
};

// The read view is Lithuanian-first; English only fills a vote that has no Lithuanian text.
const voteTitle = (vote: EditableVote) => vote.title.lt || vote.title.en;
const voteNote = (vote: EditableVote) => vote.note.lt || vote.note.en;

/** Identity that survives reordering; a new vote has no id until it is saved. */
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

/** One answered row reopened at a time, so the card never shows every choice at once. */
const openRow = ref<string | null>(null);
const rowKey = (vote: EditableVote, field: VoteField) => `${keyFor(vote)}:${field}`;

const isOpen = (vote: EditableVote, field: VoteField) =>
  vote[field] === null || vote[field] === undefined || openRow.value === rowKey(vote, field);

const open = (vote: EditableVote, field: VoteField) => {
  openRow.value = rowKey(vote, field);
};

/** Consensus means adopted with the students for it; benefit is a separate judgement. */
const matchesConsensus = (vote: EditableVote) =>
  vote.decision === 'positive' && (!props.requiresStudentPerspective || vote.student_vote === 'positive');

const answer = (vote: EditableVote, field: VoteField, value: VoteValue) => {
  vote[field] = value;
  openRow.value = null;
  if (vote.is_consensus && !matchesConsensus(vote)) {
    vote.is_consensus = false;
  }
};

const setConsensus = (vote: EditableVote, value: boolean) => {
  vote.is_consensus = value;
  openRow.value = null;
  if (!value) {
    return;
  }

  vote.decision = 'positive';
  if (props.requiresStudentPerspective) {
    vote.student_vote = 'positive';
    vote.student_benefit = 'positive';
  }
};

const addVote = () => {
  props.form.votes.push(createVote(props.form.votes.length === 0));
};
</script>
