<template>
  <div class="space-y-4" data-testid="problem-steps-panel">
    <div class="flex items-center justify-between gap-3">
      <h3 class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
        {{ $t('goals.sections.steps') }}
        <Badge variant="outline" class="ml-2 align-middle text-[11px] font-normal normal-case tracking-normal">
          {{ $t('goals.experimental') }}
        </Badge>
      </h3>
      <Button v-if="canUpdate" variant="outline" size="sm" voice="sentence" class="pointer-coarse:min-h-11" @click="openStep(null)">
        <Plus class="size-4" />
        {{ $t('goals.steps.add') }}
      </Button>
    </div>
    <GoalStepList
      :steps
      :can-update="canUpdate"
      other-side="goal"
      @edit="openStep"
      @delete="stepToDelete = $event"
    />
    <p v-if="!steps.length" class="text-sm text-muted-foreground">
      {{ $t('goals.steps.empty_description') }}
    </p>

    <StepSheetForm
      v-model:open="stepOpen"
      :parent="{ type: 'problem', id: problemId }"
      :step="editingStep"
      :link-options="goals.map(goal => ({ id: goal.id, title: goal.title }))"
    />

    <ConfirmDialog
      :open="stepToDelete !== null"
      :title="$t('goals.steps.delete_confirm')"
      :description="$t('goals.steps.delete_description')"
      :confirm-label="$t('Ištrinti')"
      destructive
      @update:open="!$event && (stepToDelete = null)"
      @confirm="deleteStep"
    />
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

import GoalStepList from './GoalStepList.vue';
import StepSheetForm from './StepSheetForm.vue';
import type { GoalStep, LinkedGoal } from './types';

import { ConfirmDialog } from '@/Components/Patterns';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  problemId: string;
  steps: GoalStep[];
  /** The problem's goals, which a step may also count towards. */
  goals: LinkedGoal[];
  canUpdate: boolean;
}>();

const stepOpen = ref(false);
const editingStep = ref<GoalStep | null>(null);
const stepToDelete = ref<GoalStep | null>(null);

function openStep(step: GoalStep | null): void {
  editingStep.value = step;
  stepOpen.value = true;
}

function deleteStep(): void {
  if (!stepToDelete.value) {
    return;
  }
  const { id } = stepToDelete.value;
  stepToDelete.value = null;
  router.delete(route('problems.steps.destroy', { problem: props.problemId, step: id }), { preserveScroll: true });
}
</script>
