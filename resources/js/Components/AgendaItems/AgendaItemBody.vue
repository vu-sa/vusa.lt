<template>
  <div class="space-y-8">
    <!-- Type + student question -->
    <div v-if="editing" class="flex flex-wrap items-center gap-x-6 gap-y-3">
      <div class="flex flex-wrap items-center gap-2">
        <span class="mr-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $t('Klausimo tipas') }}</span>
        <button
          v-for="option in typeOptions"
          :key="option.value"
          type="button"
          class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
          :class="form.type === option.value
            ? 'bg-primary text-primary-foreground'
            : 'bg-muted text-muted-foreground hover:bg-muted/80'"
          @click="form.type = option.value"
        >
          {{ option.label }}
        </button>
        <button
          v-if="form.type"
          type="button"
          class="rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:text-destructive"
          :title="$t('Išvalyti')"
          @click="form.type = null"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
      <label class="flex cursor-pointer items-center gap-2">
        <Switch v-model="form.brought_by_students" />
        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $t('Atstovų iškeltas klausimas') }}</span>
      </label>
    </div>
    <div v-else class="flex flex-wrap items-center gap-2">
      <span class="inline-flex items-center gap-1.5 rounded-md bg-muted px-2 py-1 text-xs font-medium text-zinc-700 dark:text-zinc-300">
        <component :is="typeIcon" class="h-3.5 w-3.5" />
        {{ typeLabel }}
      </span>
      <span
        v-if="form.brought_by_students"
        class="inline-flex items-center gap-1.5 rounded-md bg-red-50 dark:bg-red-900/20 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400"
      >
        <Users class="h-3.5 w-3.5" />
        {{ $t('Atstovų iškeltas klausimas') }}
      </span>
    </div>

    <!-- Votes (voting type) -->
    <div v-if="form.type === 'voting'" class="space-y-3">
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $t('Balsavimo klausimai') }}</span>
        <span v-if="form.votes.length" class="text-sm text-zinc-400 dark:text-zinc-500">{{ form.votes.length }}</span>
        <VisibilityIndicator :public="meetingIsPublic" />
        <AdminVotingHelpButton v-if="editing" class="ml-auto" />
      </div>

      <p v-if="form.votes.length === 0" class="text-sm italic text-muted-foreground">
        {{ editing ? $t('Balsavimų dar nėra.') : $t('Neaptarta') }}
      </p>

      <div
        v-for="(vote, index) in form.votes"
        :key="index"
        class="space-y-3 rounded-lg border border-zinc-200 dark:border-zinc-800 p-4"
      >
        <!-- Vote header -->
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex min-w-0 items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
              {{ $t('Balsavimas') }} {{ index + 1 }}<template v-if="form.votes.length > 1">/{{ form.votes.length }}</template>
            </span>
            <span v-if="vote.title && !editing" class="truncate text-sm text-zinc-700 dark:text-zinc-300">
              · {{ vote.title }}
            </span>
            <span
              v-if="vote.is_main"
              class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400 ring-1 ring-amber-200/70 dark:ring-amber-900/50"
            >
              <Star class="h-3 w-3" />
              {{ $t('Pagrindinis') }}
            </span>
            <span
              v-if="vote.is_consensus && !editing"
              class="inline-flex items-center gap-1 rounded-full bg-teal-50 dark:bg-teal-900/20 px-2 py-0.5 text-[11px] font-medium text-teal-700 dark:text-teal-300"
            >
              <Handshake class="h-3 w-3" />
              {{ $t('Bendru sutarimu') }}
            </span>
          </div>
          <div v-if="editing" class="flex items-center gap-3">
            <label class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
              <Switch :model-value="vote.is_consensus ?? false" @update:model-value="(v: boolean) => setConsensus(index, v)" />
              {{ $t('Bendru sutarimu') }}
            </label>
            <button
              type="button"
              class="text-zinc-400 hover:text-destructive disabled:cursor-not-allowed disabled:opacity-40"
              :title="isLockedMainVote(vote) ? $t('Pagrindinio balsavimo šalinti negalima') : $t('Šalinti balsavimą')"
              :disabled="isLockedMainVote(vote)"
              @click="removeVote(index)"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Optional title (edit) -->
        <Input
          v-if="editing"
          v-model="vote.title"
          class="h-8 max-w-sm text-sm"
          maxlength="200"
          :placeholder="$t('Pridėti pavadinimą (nebūtina)')"
        />

        <!-- Label → value grid (same in both modes) -->
        <div class="space-y-2">
          <div
            v-for="row in voteRows"
            :key="row.key"
            class="grid grid-cols-[6rem_1fr] items-center gap-x-4 sm:grid-cols-[8rem_1fr]"
          >
            <span class="text-xs text-muted-foreground">{{ row.label }}</span>
            <div v-if="editing" class="flex gap-2">
              <button
                v-for="opt in row.options"
                :key="opt.value"
                type="button"
                class="flex flex-1 items-center justify-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors"
                :class="vote[row.key] === opt.value ? opt.activeClass : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                @click="setVoteValue(vote, row.key, opt.value)"
              >
                <component :is="opt.icon" v-if="opt.icon" class="h-3.5 w-3.5" />
                {{ opt.label }}
              </button>
            </div>
            <span v-else :class="valueClass(vote[row.key])">{{ readLabel(row, vote[row.key]) }}</span>
          </div>
        </div>
      </div>

      <Button v-if="editing" type="button" variant="outline" size="sm" @click="addVote">
        <Plus class="mr-1 h-4 w-4" />
        {{ $t('Pridėti balsavimo klausimą') }}
      </Button>
    </div>

    <!-- Description + student position -->
    <AgendaItemTextTabs
      :editable="editing"
      :description="form.description"
      :student-position="form.student_position"
      @update:description="(v) => form.description = v"
      @update:student-position="(v) => form.student_position = v"
    >
      <template #trailing>
        <VisibilityIndicator :public="meetingIsPublic" />
      </template>
    </AgendaItemTextTabs>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Clock, Handshake, HelpCircle, Info, Plus, Star, ThumbsDown, ThumbsUp, Minus, Trash2, Users, Vote, X } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import AdminVotingHelpButton from '@/Components/AgendaItems/AdminVotingHelpButton.vue';
import AgendaItemTextTabs from '@/Components/AgendaItems/AgendaItemTextTabs.vue';
import VisibilityIndicator from '@/Components/AgendaItems/VisibilityIndicator.vue';
import { getVoteTextColorClass, type VoteValue } from '@/Composables/useAgendaItemStyling';
import type { AgendaItemFormData, EditableVote } from '@/Composables/useAgendaItemAutosave';

const props = withDefaults(defineProps<{
  form: InertiaForm<AgendaItemFormData>;
  editing?: boolean;
  meetingIsPublic?: boolean;
}>(), {
  editing: false,
  meetingIsPublic: false,
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

const typeOptions = [
  { value: 'voting' as const, label: $t('Balsavimas') },
  { value: 'informational' as const, label: $t('Informacinis') },
  { value: 'deferred' as const, label: $t('Atidėtas') },
];

const decisionOptions: VoteOption[] = [
  { value: 'positive', label: $t('Priimtas'), activeClass: 'bg-emerald-600 text-white' },
  { value: 'negative', label: $t('Atmestas'), activeClass: 'bg-red-600 text-white' },
  { value: 'neutral', label: $t('Susilaikyta'), activeClass: 'bg-zinc-500 text-white' },
];

const studentVoteOptions: VoteOption[] = [
  { value: 'positive', label: $t('Pritarė'), activeClass: 'bg-emerald-600 text-white' },
  { value: 'negative', label: $t('Nepritarė'), activeClass: 'bg-red-600 text-white' },
  { value: 'neutral', label: $t('Susilaikyta'), activeClass: 'bg-zinc-500 text-white' },
];

const benefitOptions: VoteOption[] = [
  { value: 'positive', label: $t('Palanku'), icon: ThumbsUp, activeClass: 'bg-emerald-600 text-white' },
  { value: 'negative', label: $t('Nepalanku'), icon: ThumbsDown, activeClass: 'bg-red-600 text-white' },
  { value: 'neutral', label: $t('Neutralu'), icon: Minus, activeClass: 'bg-zinc-500 text-white' },
];

const voteRows: VoteRow[] = [
  { key: 'decision', label: $t('Rezultatas'), options: decisionOptions },
  { key: 'student_vote', label: $t('Studentai'), options: studentVoteOptions },
  { key: 'student_benefit', label: $t('Nauda'), options: benefitOptions },
];

const typeLabel = computed(() => {
  switch (props.form.type) {
    case 'voting': return $t('Balsavimas');
    case 'informational': return $t('Informacinis');
    case 'deferred': return $t('Atidėtas');
    default: return $t('Nepažymėtas');
  }
});

const typeIcon = computed(() => {
  switch (props.form.type) {
    case 'voting': return Vote;
    case 'informational': return Info;
    case 'deferred': return Clock;
    default: return HelpCircle;
  }
});

const readLabel = (row: VoteRow, value: VoteValue): string =>
  row.options.find(opt => opt.value === value)?.label ?? '—';

const valueClass = (value: VoteValue): string =>
  value ? `font-medium ${getVoteTextColorClass(value)}` : 'text-muted-foreground';

const setVoteValue = (vote: EditableVote, key: VoteField, value: VoteOption['value']) => {
  vote[key] = value;
};

const isLockedMainVote = (vote: EditableVote): boolean =>
  Boolean(vote.is_main) && props.form.type === 'voting' && props.form.votes.length === 1;

const addVote = () => {
  props.form.votes.push({
    id: null,
    is_main: props.form.votes.length === 0,
    is_consensus: false,
    title: null,
    student_vote: null,
    decision: null,
    student_benefit: null,
    order: props.form.votes.length,
  });
};

const removeVote = (index: number) => {
  const removed = props.form.votes[index];
  props.form.votes.splice(index, 1);

  // Promote a remaining vote to main if we removed the main one
  if (removed?.is_main && props.form.type === 'voting' && props.form.votes.length > 0) {
    props.form.votes[0].is_main = true;
  }
};

const setConsensus = (index: number, value: boolean) => {
  const vote = props.form.votes[index];
  vote.is_consensus = value;
  if (value) {
    vote.decision = 'positive';
    vote.student_vote = 'positive';
    vote.student_benefit = 'positive';
  }
};
</script>
