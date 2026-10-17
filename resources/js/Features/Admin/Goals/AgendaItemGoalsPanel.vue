<template>
  <OverviewSection
    :title="$t('goals.agenda_item.title')"
    :icon="Target"
    variant="home"
    :empty="!goalLinks.goals.length"
    :empty-text="$t('goals.agenda_item.none_linked')"
    :empty-action="emptyGoalAction"
    data-testid="agenda-item-goals"
  >
    <template #actions>
      <Badge variant="outline" class="text-xs font-normal">
        {{ $t('goals.experimental') }}
      </Badge>
      <Button
        v-if="goalLinks.options.length"
        variant="outline"
        size="sm"
        voice="sentence"
        class="pointer-coarse:h-11"
        data-testid="agenda-item-link-goal"
        @click="sheetOpen = true"
      >
        <Link2 class="size-4" aria-hidden="true" />
        {{ $t('goals.agenda_item.link') }}
      </Button>
    </template>
    <LinkedGoalList v-if="goalLinks.goals.length" :goals="goalLinks.goals" removable @remove="unlink" />
  </OverviewSection>

  <SheetForm
    v-model:open="sheetOpen"
    :title="$t('goals.agenda_item.link')"
    :description="$t('goals.agenda_item.link_description')"
    :processing="form.processing"
    :disabled="!form.goal_id"
    @cancel="form.reset()"
    @submit="submit"
  >
    <div class="space-y-2">
      <Label for="agenda-item-goal">{{ $tChoice('entities.goal.model', 1) }}</Label>
      <SingleSelect
        id="agenda-item-goal"
        v-model="selected"
        :options="labelled"
        label-field="label"
        value-field="id"
        :placeholder="$t('goals.agenda_item.pick')"
        variant="surface"
      />
      <p v-if="form.errors.goal_id" class="text-sm text-status-danger">
        {{ form.errors.goal_id }}
      </p>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Link2, Target } from '@lucide/vue';
import { computed, ref } from 'vue';

import LinkedGoalList from './LinkedGoalList.vue';
import type { LinkedGoal } from './types';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import SingleSelect from '@/Components/ui/single-select/SingleSelect.vue';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import { ModelEnum } from '@/Types/enums';

const props = defineProps<{
  agendaItemId: string;
  /** `options`: goals the reader may still add this item to (AgendaItemController::goalLinks). */
  goalLinks: { goals: LinkedGoal[]; options: LinkedGoal[] };
}>();

const sheetOpen = ref(false);
const emptyGoalAction = computed(() => props.goalLinks.options.length
  ? { label: $t('goals.agenda_item.link'), icon: getEntityTypeDefinition(ModelEnum.GOAL)?.icon, run: () => { sheetOpen.value = true; } }
  : undefined);
const form = useForm<{ goal_id: string | null }>({ goal_id: null });

const labelled = computed(() => props.goalLinks.options.map(goal => ({ ...goal, label: `${goal.title} · ${goal.tenant}` })));

const selected = computed({
  get: () => labelled.value.find(goal => goal.id === form.goal_id) ?? null,
  set: (goal: { id: string } | null) => {
    form.goal_id = goal?.id ?? null;
  },
});

function submit(): void {
  form.post(route('agendaItems.goals.store', props.agendaItemId), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      sheetOpen.value = false;
    },
  });
}

function unlink(goal: LinkedGoal): void {
  router.delete(route('agendaItems.goals.destroy', { agendaItem: props.agendaItemId, goal: goal.id }), { preserveScroll: true });
}
</script>
