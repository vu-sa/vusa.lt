<template>
  <form class="space-y-6" @submit.prevent="save">
    <!-- Title -->
    <div class="space-y-2">
      <Label for="agenda-title">{{ $t('Pavadinimas') }}</Label>
      <Input id="agenda-title" v-model="form.title" :placeholder="$t('Darbotvarkės punkto pavadinimas')" />
      <p v-if="form.errors.title" class="text-sm text-destructive">
        {{ form.errors.title }}
      </p>
    </div>

    <!-- Type -->
    <div class="space-y-2">
      <Label>{{ $t('Tipas') }}</Label>
      <div class="flex flex-wrap gap-2">
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
    </div>

    <!-- Brought by students -->
    <div class="flex items-center justify-between rounded-lg border border-zinc-200 dark:border-zinc-800 px-4 py-3">
      <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
          {{ $t('Studentų klausimas') }}
        </p>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ $t('Klausimą į darbotvarkę įtraukė studentų atstovai.') }}
        </p>
      </div>
      <Switch v-model="form.brought_by_students" />
    </div>

    <!-- Votes (voting type only) -->
    <div v-if="form.type === 'voting'" class="space-y-3">
      <div class="flex items-center justify-between">
        <Label>{{ $t('Balsavimai') }}</Label>
        <Button type="button" variant="ghost" size="sm" @click="addVote">
          <Plus class="mr-1 h-4 w-4" />
          {{ $t('Pridėti balsavimą') }}
        </Button>
      </div>

      <p v-if="form.votes.length === 0" class="text-sm text-zinc-400 dark:text-zinc-500 italic">
        {{ $t('Balsavimų dar nėra.') }}
      </p>

      <div
        v-for="(vote, index) in form.votes"
        :key="index"
        class="space-y-3 rounded-lg border border-zinc-200 dark:border-zinc-800 p-4"
      >
        <div class="flex items-center justify-between gap-2">
          <Input
            v-model="vote.title"
            class="h-8 max-w-xs text-sm"
            :placeholder="$t('Balsavimo pavadinimas (nebūtina)')"
          />
          <div class="flex items-center gap-2">
            <label class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
              <Switch :model-value="vote.is_consensus ?? false" @update:model-value="(v: boolean) => setConsensus(index, v)" />
              {{ $t('Bendru sutarimu') }}
            </label>
            <button
              type="button"
              class="text-zinc-400 hover:text-destructive"
              :title="$t('Šalinti balsavimą')"
              @click="removeVote(index)"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Decision -->
        <div class="space-y-1.5">
          <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $t('Sprendimas') }}</span>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="opt in decisionOptions"
              :key="opt.value"
              type="button"
              class="rounded-md px-2.5 py-1 text-xs font-medium transition-colors"
              :class="vote.decision === opt.value ? opt.activeClass : 'bg-muted text-muted-foreground hover:bg-muted/80'"
              @click="vote.decision = opt.value"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <!-- Student vote -->
        <div class="space-y-1.5">
          <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $t('Kaip balsavo studentai') }}</span>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="opt in studentVoteOptions"
              :key="opt.value"
              type="button"
              class="rounded-md px-2.5 py-1 text-xs font-medium transition-colors"
              :class="vote.student_vote === opt.value ? opt.activeClass : 'bg-muted text-muted-foreground hover:bg-muted/80'"
              @click="vote.student_vote = opt.value"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <!-- Student benefit -->
        <div class="space-y-1.5">
          <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $t('Palankumas studentams') }}</span>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="opt in benefitOptions"
              :key="opt.value"
              type="button"
              class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium transition-colors"
              :class="vote.student_benefit === opt.value ? opt.activeClass : 'bg-muted text-muted-foreground hover:bg-muted/80'"
              @click="vote.student_benefit = opt.value"
            >
              <component :is="opt.icon" class="h-3.5 w-3.5" />
              {{ opt.label }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Student position -->
    <div class="space-y-2">
      <Label for="student-position">{{ $t('Išsakyta studentų pozicija') }}</Label>
      <Textarea id="student-position" v-model="form.student_position" rows="3" />
    </div>

    <!-- Description -->
    <div class="space-y-2">
      <Label for="agenda-description">{{ $t('Aprašymas') }}</Label>
      <Textarea id="agenda-description" v-model="form.description" rows="3" />
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-3 border-t border-zinc-200 dark:border-zinc-800 pt-4">
      <Button type="submit" :disabled="form.processing">
        <Save class="mr-2 h-4 w-4" />
        {{ $t('Išsaugoti') }}
      </Button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, Save, ThumbsDown, ThumbsUp, Minus, Trash2, X } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';

type VoteValue = 'positive' | 'negative' | 'neutral' | null;

interface EditableVote {
  id?: string | null;
  is_main?: boolean;
  is_consensus?: boolean;
  title?: string | null;
  student_vote?: VoteValue;
  decision?: VoteValue;
  student_benefit?: VoteValue;
  order?: number;
}

const props = defineProps<{
  agendaItem: App.Entities.AgendaItem;
}>();

const form = useForm({
  title: props.agendaItem.title,
  type: (props.agendaItem.type ?? null) as 'voting' | 'informational' | 'deferred' | null,
  brought_by_students: props.agendaItem.brought_by_students ?? false,
  student_position: props.agendaItem.student_position ?? '',
  description: props.agendaItem.description ?? '',
  votes: (props.agendaItem.votes ?? []).map((vote): EditableVote => ({
    id: vote.id,
    is_main: vote.is_main ?? false,
    is_consensus: vote.is_consensus ?? false,
    title: vote.title ?? null,
    student_vote: (vote.student_vote ?? null) as VoteValue,
    decision: (vote.decision ?? null) as VoteValue,
    student_benefit: (vote.student_benefit ?? null) as VoteValue,
    order: vote.order ?? 0,
  })),
});

const typeOptions = [
  { value: 'voting' as const, label: $t('Balsavimas') },
  { value: 'informational' as const, label: $t('Informacinis') },
  { value: 'deferred' as const, label: $t('Atidėtas') },
];

const decisionOptions = [
  { value: 'positive' as const, label: $t('Priimtas'), activeClass: 'bg-emerald-600 text-white' },
  { value: 'negative' as const, label: $t('Atmestas'), activeClass: 'bg-red-600 text-white' },
  { value: 'neutral' as const, label: $t('Susilaikyta'), activeClass: 'bg-zinc-500 text-white' },
];

const studentVoteOptions = [
  { value: 'positive' as const, label: $t('Pritarė'), activeClass: 'bg-emerald-600 text-white' },
  { value: 'negative' as const, label: $t('Nepritarė'), activeClass: 'bg-red-600 text-white' },
  { value: 'neutral' as const, label: $t('Susilaikyta'), activeClass: 'bg-zinc-500 text-white' },
];

const benefitOptions = [
  { value: 'positive' as const, label: $t('Palanku'), icon: ThumbsUp, activeClass: 'bg-emerald-600 text-white' },
  { value: 'negative' as const, label: $t('Nepalanku'), icon: ThumbsDown, activeClass: 'bg-red-600 text-white' },
  { value: 'neutral' as const, label: $t('Neutralu'), icon: Minus, activeClass: 'bg-zinc-500 text-white' },
];

const addVote = () => {
  form.votes.push({
    id: null,
    is_main: form.votes.length === 0,
    is_consensus: false,
    title: null,
    student_vote: null,
    decision: null,
    student_benefit: null,
    order: form.votes.length,
  });
};

const removeVote = (index: number) => {
  form.votes.splice(index, 1);
};

const setConsensus = (index: number, value: boolean) => {
  const vote = form.votes[index];
  vote.is_consensus = value;
  if (value) {
    vote.decision = 'positive';
    vote.student_vote = 'positive';
    vote.student_benefit = 'positive';
  }
};

const save = () => {
  form
    .transform(data => ({
      ...data,
      votes: data.votes.map((vote, index) => ({ ...vote, order: index })),
    }))
    .patch(route('agendaItems.update', props.agendaItem.id), {
      preserveScroll: true,
      onSuccess: () => {
        form.defaults();
      },
    });
};
</script>
