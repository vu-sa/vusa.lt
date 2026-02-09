<template>
  <div class="space-y-4">
    <!-- Header with Add button -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h4 class="text-sm font-medium">
          {{ $t('Balsavimai') }}
        </h4>
        <AdminVotingHelpButton class="h-4 w-4" />
      </div>
      <Button
        v-if="!disabled"
        type="button"
        variant="outline"
        size="sm"
        class="h-7"
        @click="addVote"
      >
        <Plus class="mr-1 h-3 w-3" />
        {{ $t('Pridėti balsavimą') }}
      </Button>
    </div>

    <!-- No votes message -->
    <div
      v-if="localVotes.length === 0"
      class="flex flex-col items-center justify-center rounded-lg border border-dashed py-8 text-center"
    >
      <Vote class="mb-2 h-8 w-8 text-muted-foreground" />
      <p class="text-sm text-muted-foreground">
        {{ $t('Nėra balsavimų') }}
      </p>
      <Button
        v-if="!disabled"
        type="button"
        variant="outline"
        size="sm"
        class="mt-3"
        @click="addVote"
      >
        <Plus class="mr-1 h-3 w-3" />
        {{ $t('Pridėti pirmą balsavimą') }}
      </Button>
    </div>

    <!-- Main Vote (always first if exists) -->
    <VoteForm
      v-if="mainVote"
      :vote="mainVote"
      :vote-index="0"
      is-main
      :can-set-main="false"
      :can-delete="localVotes.length > 1 || !requireMainVote"
      @update="(data) => updateVote(mainVote!.id || mainVoteIndex, data)"
      @delete="deleteVote(mainVote!.id || mainVoteIndex)"
    />

    <!-- Additional Votes -->
    <div v-if="additionalVotes.length > 0" class="space-y-3">
      <div v-if="mainVote" class="flex items-center gap-2 text-xs text-muted-foreground">
        <div class="h-px flex-1 bg-border" />
        <span>{{ $t('Papildomi balsavimai') }}</span>
        <div class="h-px flex-1 bg-border" />
      </div>

      <VoteForm
        v-for="(vote, index) in additionalVotes"
        :key="vote.id || `additional-${index}`"
        :vote
        :vote-index="index + 1"
        :is-main="false"
        can-set-main
        can-delete
        @update="(data) => updateVote(vote.id || getOriginalIndex(vote), data)"
        @delete="deleteVote(vote.id || getOriginalIndex(vote))"
        @set-main="setAsMain(vote.id || getOriginalIndex(vote))"
      />
    </div>

    <!-- Summary stats if multiple votes -->
    <div
      v-if="localVotes.length > 1"
      class="flex items-center gap-4 rounded-lg bg-muted/50 px-3 py-2 text-xs"
    >
      <span class="font-medium">{{ $t('Santrauka') }}:</span>
      <span class="flex items-center gap-1">
        <div class="h-2 w-2 rounded-full bg-green-500" />
        {{ stats.aligned }} {{ $t('sutampa') }}
      </span>
      <span class="flex items-center gap-1">
        <div class="h-2 w-2 rounded-full bg-red-500" />
        {{ stats.misaligned }} {{ $t('nesutampa') }}
      </span>
      <span class="flex items-center gap-1">
        <div class="h-2 w-2 rounded-full bg-zinc-400" />
        {{ stats.incomplete }} {{ $t('neužpildyta') }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, Vote } from 'lucide-vue-next';

// Simple ID generator for temporary votes
const generateTempId = () => `temp_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;

import AdminVotingHelpButton from '../AgendaItems/AdminVotingHelpButton.vue';

import VoteForm from './VoteForm.vue';

import { Button } from '@/Components/ui/button';

const props = defineProps<{
  votes: App.Entities.Vote[];
  disabled?: boolean;
  requireMainVote?: boolean;
}>();

const emit = defineEmits<(e: 'update:votes', votes: App.Entities.Vote[]) => void>();

// Local copy for editing
const localVotes = ref<App.Entities.Vote[]>([...props.votes]);

// Watch for prop changes
watch(() => props.votes, (newVotes) => {
  localVotes.value = [...newVotes];
}, { deep: true });

// Computed: Find main vote
const mainVoteIndex = computed(() => {
  return localVotes.value.findIndex(v => v.is_main);
});

const mainVote = computed(() => {
  const index = mainVoteIndex.value;
  return index >= 0 ? localVotes.value[index] : null;
});

// Computed: Additional votes (non-main)
const additionalVotes = computed(() => {
  return localVotes.value.filter(v => !v.is_main);
});

// Get original index for a vote in localVotes array
const getOriginalIndex = (vote: App.Entities.Vote): number => {
  return localVotes.value.indexOf(vote);
};

// Stats for summary
const stats = computed(() => {
  let aligned = 0;
  let misaligned = 0;
  let incomplete = 0;

  localVotes.value.forEach((vote) => {
    if (!vote.decision && !vote.student_vote) {
      incomplete++;
    }
    else if (vote.decision && vote.student_vote) {
      if (vote.decision === vote.student_vote) {
        aligned++;
      }
      else {
        misaligned++;
      }
    }
    else {
      incomplete++;
    }
  });

  return { aligned, misaligned, incomplete };
});

// Add a new vote
const addVote = () => {
  const newVote: App.Entities.Vote = {
    id: generateTempId(),
    agenda_item_id: '',
    is_main: localVotes.value.length === 0, // First vote is main
    title: null,
    student_vote: null,
    decision: null,
    student_benefit: null,
    note: null,
    order: localVotes.value.length,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  };

  localVotes.value.push(newVote);
  emitUpdate();
};

// Update a vote by ID or index
const updateVote = (idOrIndex: string | number, data: Partial<App.Entities.Vote>) => {
  const index = typeof idOrIndex === 'number'
    ? idOrIndex
    : localVotes.value.findIndex(v => v.id === idOrIndex);

  if (index >= 0 && localVotes.value[index]) {
    localVotes.value[index] = { ...localVotes.value[index], ...data } as App.Entities.Vote;
    emitUpdate();
  }
};

// Delete a vote by ID or index
const deleteVote = (idOrIndex: string | number) => {
  const index = typeof idOrIndex === 'number'
    ? idOrIndex
    : localVotes.value.findIndex(v => v.id === idOrIndex);

  if (index >= 0 && localVotes.value[index]) {
    const wasMain = localVotes.value[index]!.is_main;
    localVotes.value.splice(index, 1);

    // If we deleted the main vote, make the first remaining vote main
    if (wasMain && localVotes.value.length > 0 && localVotes.value[0]) {
      localVotes.value[0].is_main = true;
    }

    emitUpdate();
  }
};

// Set a vote as main
const setAsMain = (idOrIndex: string | number) => {
  const index = typeof idOrIndex === 'number'
    ? idOrIndex
    : localVotes.value.findIndex(v => v.id === idOrIndex);

  if (index >= 0) {
    // Unset current main
    localVotes.value.forEach((v, i) => {
      v.is_main = i === index;
    });
    emitUpdate();
  }
};

const emitUpdate = () => {
  emit('update:votes', [...localVotes.value]);
};
</script>
