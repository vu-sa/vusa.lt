<template>
  <div class="space-y-4 rounded-lg border p-4" :class="voteContainerClass">
    <!-- Vote Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-1.5">
          <div
            class="h-2.5 w-2.5 rounded-full"
            :class="voteStatusColor"
          />
          <span class="text-sm font-medium">
            {{ isMain ? $t('Pagrindinis balsavimas') : `${$t('Balsavimas')} ${voteIndex + 1}` }}
          </span>
        </div>
        <Badge v-if="isMain" variant="secondary" class="text-xs">
          {{ $t('Pagrindinis') }}
        </Badge>
      </div>
      <div class="flex items-center gap-1">
        <Button
          v-if="!isMain && canSetMain"
          variant="ghost"
          size="icon"
          class="h-7 w-7"
          :title="$t('Padaryti pagrindiniu')"
          @click="$emit('set-main')"
        >
          <Star class="h-3.5 w-3.5" />
        </Button>
        <Button
          v-if="canDelete"
          variant="ghost"
          size="icon"
          class="h-7 w-7 text-destructive hover:text-destructive"
          :title="$t('Šalinti')"
          @click="$emit('delete')"
        >
          <Trash2 class="h-3.5 w-3.5" />
        </Button>
      </div>
    </div>

    <!-- Vote Title (optional) -->
    <div>
      <label class="text-xs font-medium text-muted-foreground">
        {{ $t('Balsavimo pavadinimas') }}
        <span class="text-muted-foreground/60">({{ $t('neprivaloma') }})</span>
      </label>
      <Input
        v-model="localVote.title"
        :placeholder="$t('+ Pridėti pavadinimą')"
        class="mt-1 h-8 text-sm"
        @change="emitUpdate"
      />
    </div>

    <!-- Decision -->
    <div>
      <label class="flex items-center gap-1 text-xs font-medium text-muted-foreground">
        {{ $t('voting_field_decision_label') }}
        <FieldTooltip :text="$t('voting_field_decision_tooltip')" />
      </label>
      <ButtonGroup orientation="horizontal" class="mt-1">
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.decision === 'positive' && 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-400'
          ]"
          @click="setField('decision', localVote.decision === 'positive' ? null : 'positive')">
          <Check class="h-3 w-3 mr-1" />
          {{ $t('Priimtas') }}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.decision === 'negative' && 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-400'
          ]"
          @click="setField('decision', localVote.decision === 'negative' ? null : 'negative')">
          <X class="h-3 w-3 mr-1" />
          {{ $t('Nepriimtas') }}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.decision === 'neutral' && 'bg-zinc-100 border-zinc-300 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300'
          ]"
          @click="setField('decision', localVote.decision === 'neutral' ? null : 'neutral')">
          <Minus class="h-3 w-3 mr-1" />
          {{ $t('Susilaikyta') }}
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="h-8 w-8"
          :title="$t('Išvalyti')"
          @click="setField('decision', null)"
        >
          <X class="h-3 w-3" />
        </Button>
      </ButtonGroup>
    </div>

    <!-- Student Vote -->
    <div>
      <label class="flex items-center gap-1 text-xs font-medium text-muted-foreground">
        {{ $t('voting_field_student_vote_label') }}
        <FieldTooltip :text="$t('voting_field_student_vote_tooltip')" />
      </label>
      <ButtonGroup orientation="horizontal" class="mt-1">
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.student_vote === 'positive' && 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-400'
          ]"
          @click="setField('student_vote', localVote.student_vote === 'positive' ? null : 'positive')">
          <Check class="h-3 w-3 mr-1" />
          {{ $t('Pritarė') }}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.student_vote === 'negative' && 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-400'
          ]"
          @click="setField('student_vote', localVote.student_vote === 'negative' ? null : 'negative')">
          <X class="h-3 w-3 mr-1" />
          {{ $t('Nepritarė') }}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.student_vote === 'neutral' && 'bg-zinc-100 border-zinc-300 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300'
          ]"
          @click="setField('student_vote', localVote.student_vote === 'neutral' ? null : 'neutral')">
          <Minus class="h-3 w-3 mr-1" />
          {{ $t('Susilaikė') }}
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="h-8 w-8"
          :title="$t('Išvalyti')"
          @click="setField('student_vote', null)"
        >
          <X class="h-3 w-3" />
        </Button>
      </ButtonGroup>
    </div>

    <!-- Student Benefit -->
    <div>
      <label class="flex items-center gap-1 text-xs font-medium text-muted-foreground">
        {{ $t('voting_field_student_benefit_label') }}
        <FieldTooltip :text="$t('voting_field_student_benefit_tooltip')" />
      </label>
      <ButtonGroup orientation="horizontal" class="mt-1">
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.student_benefit === 'positive' && 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-400'
          ]"
          @click="setField('student_benefit', localVote.student_benefit === 'positive' ? null : 'positive')">
          <ThumbsUp class="h-3 w-3 mr-1" />
          {{ $t('Palanku') }}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.student_benefit === 'negative' && 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-400'
          ]"
          @click="setField('student_benefit', localVote.student_benefit === 'negative' ? null : 'negative')">
          <ThumbsDown class="h-3 w-3 mr-1" />
          {{ $t('Nepalanku') }}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          :class="[
            'h-8',
            localVote.student_benefit === 'neutral' && 'bg-zinc-100 border-zinc-300 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300'
          ]"
          @click="setField('student_benefit', localVote.student_benefit === 'neutral' ? null : 'neutral')">
          <Minus class="h-3 w-3 mr-1" />
          {{ $t('Neutralu') }}
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="h-8 w-8"
          :title="$t('Išvalyti')"
          @click="setField('student_benefit', null)"
        >
          <X class="h-3 w-3" />
        </Button>
      </ButtonGroup>
    </div>

    <!-- Note -->
    <div>
      <label class="text-xs font-medium text-muted-foreground">
        {{ $t('Pastaba') }}
        <span class="text-muted-foreground/60">({{ $t('neprivaloma') }})</span>
      </label>
      <Textarea
        v-model="localVote.note"
        :placeholder="$t('Papildoma informacija apie šį balsavimą...')"
        class="mt-1 min-h-16 text-sm"
        @change="emitUpdate"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Check, X, Minus, ThumbsUp, ThumbsDown, Trash2, Star } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import FieldTooltip from '@/Components/ui/FieldTooltip.vue';

const props = defineProps<{
  vote: App.Entities.Vote;
  voteIndex: number;
  isMain?: boolean;
  canSetMain?: boolean;
  canDelete?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update', vote: Partial<App.Entities.Vote>): void;
  (e: 'delete'): void;
  (e: 'set-main'): void;
}>();

// Local copy for editing
const localVote = ref({
  title: props.vote.title || '',
  decision: props.vote.decision || null,
  student_vote: props.vote.student_vote || null,
  student_benefit: props.vote.student_benefit || null,
  note: props.vote.note || '',
});

// Watch for prop changes
watch(() => props.vote, (newVote) => {
  localVote.value = {
    title: newVote.title || '',
    decision: newVote.decision || null,
    student_vote: newVote.student_vote || null,
    student_benefit: newVote.student_benefit || null,
    note: newVote.note || '',
  };
}, { deep: true });

// Vote status color based on alignment
const voteStatusColor = computed(() => {
  const { decision, student_vote } = localVote.value;

  if (!decision && !student_vote) {
    return 'bg-zinc-300 dark:bg-zinc-600';
  }

  if (decision && student_vote) {
    return decision === student_vote
      ? 'bg-green-500'
      : 'bg-red-500';
  }

  return 'bg-amber-500';
});

// Container styling for main vs additional
const voteContainerClass = computed(() => {
  if (props.isMain) {
    return 'border-primary/30 bg-primary/5';
  }
  return 'border-zinc-200 dark:border-zinc-700';
});

const setField = (field: 'decision' | 'student_vote' | 'student_benefit', value: string | null) => {
  localVote.value[field] = value as 'positive' | 'negative' | 'neutral' | null;
  emitUpdate();
};

const emitUpdate = () => {
  emit('update', {
    title: localVote.value.title || null,
    decision: localVote.value.decision,
    student_vote: localVote.value.student_vote,
    student_benefit: localVote.value.student_benefit,
    note: localVote.value.note || null,
  });
};
</script>
