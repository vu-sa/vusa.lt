<template>
  <RecordPage
    v-model:section="currentSection"
    :history-subject="{ type: 'goal', id: goal.id }"
    :title="goalTitle"
    :entity-type="ModelEnum.GOAL"
    :eyebrow-suffix="$t('goals.experimental')"
    :facts
    :sections
    :primary-action
    :overflow-actions
    actions-beside-title
    @action="handleAction"
  >

    <template v-if="goal.responsible_duty" #fact-duty>
      <Link :href="route('duties.show', goal.responsible_duty.id)" class="hover:underline">
        <InflectedDutyName :name="goal.responsible_duty.name" />
      </Link>
      <span v-if="goal.responsible_duty.holders.length"> · {{ goal.responsible_duty.holders.join(' · ') }}</span>
    </template>

    <template #veiksmai>
      <GoalStepList
        :steps
        :can-update="can.update"
        other-side="problem"
        @edit="openStep"
        @delete="stepToDelete = $event"
      />
      <EmptyState
        v-if="!steps.length"
        :icon="ListChecks"
        :title="$t('goals.steps.empty_title')"
        :description="$t('goals.steps.empty_description')"
        :action-label="can.update ? $t('goals.steps.add') : undefined"
        @action="openStep(null)"
      />
    </template>

    <template #problemos>
      <div class="space-y-4">
        <ul v-if="problems.length" class="divide-y divide-border">
          <li v-for="problem in problems" :key="problem.id" class="flex min-h-11 items-center gap-3 px-2 py-2 sm:px-3">
            <Link :href="route('problems.show', problem.id)" class="min-w-0 flex-1">
              <span class="block truncate text-sm font-medium hover:text-brand">{{ problem.title }}</span>
              <span v-if="problem.tenant" class="block text-xs text-muted-foreground">{{ problem.tenant }}</span>
            </Link>
            <StatusBadge :status="problemStatuses[problem.status as ProblemStatus]" />
            <Button
              v-if="can.update"
              variant="ghost"
              size="sm"
              voice="sentence"
              class="pointer-coarse:min-h-11"
              @click="unlinkProblem(problem.id)"
            >
              {{ $t('goals.problems.unlink') }}
            </Button>
          </li>
        </ul>
        <EmptyState
          v-else
          :icon="ProblemIcon"
          :title="$t('goals.problems.empty_title')"
          :description="$t('goals.problems.empty_description')"
          :action-label="can.update ? $t('goals.problems.link') : undefined"
          @action="linkOpen = true"
        />
        <Button v-if="can.update && problems.length" variant="outline" voice="sentence" @click="linkOpen = true">
          <Plus class="size-4" />
          {{ $t('goals.problems.link') }}
        </Button>
      </div>
    </template>

    <template #apie>
      <div :class="['grid gap-10', (expectedResult || evaluation) && 'xl:grid-cols-2 xl:gap-16']">
        <OverviewSection variant="home" :title="$t('entities.goal.description')" :icon="FileText">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div v-if="description" class="prose prose-zinc dark:prose-invert max-w-none text-sm" v-html="description" />
          <p v-else class="text-sm italic text-muted-foreground">
            {{ $t('Aprašymas nepateiktas.') }}
          </p>
        </OverviewSection>

        <div v-if="expectedResult || evaluation" class="flex min-w-0 flex-col gap-10">
          <OverviewSection v-if="expectedResult" variant="home" :title="$t('entities.goal.expected_result')" :icon="Target">
            <p class="text-sm leading-relaxed">
              {{ expectedResult }}
            </p>
          </OverviewSection>
          <OverviewSection v-if="evaluation" variant="home" :title="$t('entities.goal.evaluation')" :icon="ClipboardCheck">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div class="prose prose-zinc dark:prose-invert max-w-none text-sm" v-html="evaluation" />
          </OverviewSection>
        </div>
      </div>
    </template>
  </RecordPage>

  <StepSheetForm
    v-model:open="stepOpen"
    :parent="{ type: 'goal', id: goal.id }"
    :step="editingStep"
    :link-options="problems.map(problem => ({ id: problem.id, title: problem.title }))"
  />

  <LinkProblemSheet v-model:open="linkOpen" :goal-id="goal.id" :options="problemOptions" />

  <ConfirmDialog
    :open="stepToDelete !== null"
    :title="$t('goals.steps.delete_confirm')"
    :description="$t('goals.steps.delete_description')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="!$event && (stepToDelete = null)"
    @confirm="deleteStep"
  />

  <ConfirmDialog
    v-model:open="deleteOpen"
    :title="$t('goals.delete_confirm')"
    :description="$t('goals.delete_description')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="router.delete(route('goals.destroy', goal.id))"
  />
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ClipboardCheck, ExternalLink, FileText, ListChecks, Pencil, Plus, Target, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { ProblemIcon } from '@/Components/icons';
import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog, EmptyState, OverviewSection, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { goalStatuses, problemStatuses, type ProblemStatus } from '@/Constants/statuses';
import GoalStepList from '@/Features/Admin/Goals/GoalStepList.vue';
import LinkProblemSheet from '@/Features/Admin/Goals/LinkProblemSheet.vue';
import StepSheetForm from '@/Features/Admin/Goals/StepSheetForm.vue';
import { translatedText, type GoalStep, type Translated } from '@/Features/Admin/Goals/types';
import type { GoalStatus } from '@/Types/enums';
import { ModelEnum } from '@/Types/enums';

const props = defineProps<{
  goal: {
    id: string;
    title: Translated;
    description: Translated | null;
    expected_result: Translated | null;
    evaluation: Translated | null;
    status: GoalStatus;
    is_public: boolean;
    tenant: { id: number; shortname: string };
    cadence: { id: string; label: string } | null;
    responsible_duty: { id: string; name: string; holders: string[] } | null;
    created_by: { id: string; name: string } | null;
    public_url: string | null;
  };
  steps: GoalStep[];
  problems: { id: string; title: string; status: string; tenant: string | null }[];
  problemOptions?: { id: string; title: string; tenant: string | null }[];
  can: { update: boolean; delete: boolean };
}>();

const locale = getActiveLanguage();
const currentSection = ref('veiksmai');
const stepOpen = ref(false);
const editingStep = ref<GoalStep | null>(null);
const stepToDelete = ref<GoalStep | null>(null);
const linkOpen = ref(false);
const deleteOpen = ref(false);

const goalTitle = computed(() => translatedText(props.goal.title, locale) || '—');
const description = computed(() => translatedText(props.goal.description, locale));
const expectedResult = computed(() => translatedText(props.goal.expected_result, locale));
const evaluation = computed(() => translatedText(props.goal.evaluation, locale));

const facts = computed<RecordFact[]>(() => [
  { key: 'status', label: $t('Būsena'), status: goalStatuses[props.goal.status] },
  { key: 'tenant', label: $tChoice('entities.tenant.model', 1), value: props.goal.tenant.shortname },
  { key: 'cadence', label: $t('entities.goal.cadence'), value: props.goal.cadence?.label ?? '—' },
  {
    key: 'duty',
    label: $t('entities.goal.responsible_duty'),
    value: props.goal.responsible_duty
      ? [props.goal.responsible_duty.name, ...props.goal.responsible_duty.holders].join(' · ')
      : '—',
    href: props.goal.responsible_duty ? route('duties.show', props.goal.responsible_duty.id) : undefined,
  },
  {
    key: 'public',
    label: $t('goals.filters.visibility'),
    value: props.goal.is_public ? $t('goals.filters.public') : $t('goals.filters.internal'),
    href: props.goal.public_url ?? undefined,
    external: Boolean(props.goal.public_url),
  },
]);

const sections = computed<RecordPageSection[]>(() => [
  { value: 'veiksmai', label: $t('goals.sections.steps'), count: props.steps.length },
  { value: 'problemos', label: $t('goals.sections.problems'), count: props.problems.length },
  { value: 'apie', label: $t('goals.sections.about') },
]);

const primaryAction = computed<RecordAction | undefined>(() => (props.can.update
  ? { key: 'add-step', label: $t('goals.steps.add'), icon: Plus }
  : undefined));

const overflowActions = computed<RecordAction[]>(() => [
  ...(props.can.update ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil, href: route('goals.edit', props.goal.id) }] : []),
  ...(props.goal.public_url ? [{ key: 'public', label: $t('goals.public_link'), icon: ExternalLink, href: props.goal.public_url, external: true }] : []),
  ...(props.can.delete ? [{ key: 'delete', label: $t('goals.delete'), icon: Trash2, destructive: true }] : []),
]);

function handleAction(key: string): void {
  if (key === 'add-step') openStep(null);
  if (key === 'delete') deleteOpen.value = true;
}

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
  router.delete(route('goals.steps.destroy', { goal: props.goal.id, step: id }), { preserveScroll: true });
}

function unlinkProblem(problemId: string): void {
  router.delete(route('goals.problems.unlink', { goal: props.goal.id, problem: problemId }), { preserveScroll: true });
}
</script>
