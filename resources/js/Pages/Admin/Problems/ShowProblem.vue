<template>
  <RecordPage
    v-model:section="currentSection"
    :history-subject="{ type: 'problem', id: problem.id }"
    :title="localizedTitle"
    :entity-type="ModelEnum.PROBLEM"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    actions-beside-title
    @action="handleRecordAction"
  >
    <template #alert>
      <div class="flex flex-wrap items-center justify-between gap-4 border border-border bg-card p-3">
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            {{ $t('problems.show.status') }}:
          </span>
          <div class="inline-flex border border-border bg-secondary">
            <button
              v-for="step in statusSteps"
              :key="step.value"
              type="button"
              :disabled="!canUpdate || statusChanging"
              :class="[
                'u-touch flex items-center gap-1.5 px-3 py-1 text-xs font-semibold transition-colors border-r last:border-r-0 border-border',
                step.isActive
                  ? 'bg-primary text-primary-foreground'
                  : step.isCompleted
                    ? 'bg-status-success-surface text-status-success'
                    : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                !canUpdate && 'cursor-default opacity-80',
              ]"
              @click="canUpdate && !step.isActive && handleStatusChange(step.value)"
            >
              <component :is="step.icon" class="size-3.5" />
              <span>{{ step.label }}</span>
            </button>
          </div>
        </div>
        <span class="text-xs text-muted-foreground">
          {{ durationText }}
        </span>
      </div>
    </template>

    <template #aprasymas>
      <div class="max-w-3xl space-y-4">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="localizedDescription" class="prose prose-zinc dark:prose-invert max-w-none text-sm" v-html="localizedDescription" />
        <p v-else class="text-sm italic text-muted-foreground">
          {{ $t('problems.show.no_description') }}
        </p>
      </div>
    </template>

    <template #veiksmai>
      <!-- Goals pilot: steps are the record of what was done; the older free text stays above as a summary. -->
      <div v-if="goalsExperiment" class="space-y-8">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="hasStepsTaken" class="prose prose-zinc dark:prose-invert max-w-3xl text-sm" v-html="localizedStepsTaken" />
        <Deferred data="goalLinks">
          <template #fallback>
            <div class="space-y-2">
              <div class="h-11 animate-pulse bg-muted" />
              <div class="h-11 animate-pulse bg-muted" />
            </div>
          </template>
          <ProblemStepsPanel
            v-if="goalLinks"
            :problem-id="problem.id"
            :steps="goalLinks.steps"
            :goals="goalLinks.goals"
            :can-update
          />
        </Deferred>
      </div>
      <div v-else class="max-w-3xl space-y-4">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="hasStepsTaken" class="prose prose-zinc dark:prose-invert max-w-none text-sm" v-html="localizedStepsTaken" />
        <EmptyState
          v-else
          :icon="List"
          :title="$t('problems.show.no_steps_title')"
          :description="$t('problems.show.no_steps_description')"
          :action-label="canUpdate ? $t('problems.show.add_steps') : undefined"
          @action="router.visit(route('problems.edit', problem.id))"
        />
      </div>
    </template>

    <template #sprendimas>
      <div class="max-w-3xl space-y-4">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="hasSolution" class="border border-status-success-border bg-status-success-surface p-4 text-sm text-foreground" v-html="localizedSolution" />
        <EmptyState
          v-else
          :icon="Lightbulb"
          :title="$t('problems.show.unresolved_title')"
          :description="$t('problems.show.unresolved_description')"
          :action-label="canUpdate ? $t('problems.show.add_solution') : undefined"
          @action="router.visit(route('problems.edit', problem.id))"
        />
      </div>
    </template>

    <template #institucijos>
      <div class="max-w-3xl">
        <div v-if="problem.institutions?.length" class="-mx-2">
          <Link
            v-for="inst in problem.institutions"
            :key="inst.id"
            :href="route('institutions.show', inst.id)"
            class="flex min-h-11 items-center justify-between gap-3 px-2 py-2 transition-colors hover:bg-accent"
          >
            <span class="text-sm font-medium">{{ inst.name }}</span>
            <ChevronRight class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          </Link>
        </div>
        <EmptyState
          v-else
          :icon="Building2"
          :title="$t('problems.show.no_institutions_title')"
          :description="$t('problems.show.no_institutions_description')"
        />
      </div>
    </template>

    <template #posedziai>
      <ul class="max-w-3xl divide-y divide-border" data-testid="problem-agenda-items">
        <li v-for="item in agendaItems" :key="item.id">
          <Link
            :href="route('agendaItems.show', item.id)"
            class="flex min-h-11 items-center gap-4 px-2 py-3 transition-colors hover:bg-accent sm:px-3"
          >
            <span class="w-24 shrink-0 text-sm tabular-nums text-muted-foreground">
              {{ item.start_time ? formatDate(new Date(item.start_time)) : '—' }}
            </span>
            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm font-medium text-foreground">{{ item.title }}</span>
              <span v-if="item.institutions.length" class="block truncate text-xs text-muted-foreground">{{ item.institutions.join(', ') }}</span>
            </span>
            <ChevronRight class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          </Link>
        </li>
      </ul>
    </template>

    <template v-if="goalsExperiment" #tikslai>
      <Deferred data="goalLinks">
        <template #fallback>
          <div class="h-11 animate-pulse bg-muted" />
        </template>
        <LinkedGoalList v-if="goalLinks?.goals.length" :goals="goalLinks.goals" />
        <p v-else class="text-sm text-muted-foreground">
          {{ $t('goals.problem_panel.no_goals') }}
        </p>
      </Deferred>
    </template>

    <template #activity>
      <RecordActivity commentable-type="problem" :commentable-id="problem.id" />
    </template>
  </RecordPage>

  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="$t('Šalinti problemą?')"
    :description="$t('Problema bus perkelta į šiukšlinę.')"
    :confirm-label="$t('Šalinti')"
    destructive
    @confirm="handleDelete"
  />
</template>

<script setup lang="ts">
import { Deferred, Link, router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import {
  Building2,
  ChevronRight,
  CircleCheck,
  CircleDot,
  Edit,
  Lightbulb,
  List,
  LoaderCircle,
  Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { problemStatuses, type ProblemStatus } from '@/Constants/statuses';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import LinkedGoalList from '@/Features/Admin/Goals/LinkedGoalList.vue';
import ProblemStepsPanel from '@/Features/Admin/Goals/ProblemStepsPanel.vue';
import type { ProblemGoalLinks } from '@/Features/Admin/Goals/types';
import { ModelEnum } from '@/Types/enums';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  problem: App.Entities.Problem;
  /** Where representatives raised the problem, limited to meetings the reader may open. */
  agendaItems?: { id: string; title: string; meeting_id: string; start_time: string | null; institutions: string[] }[];
  canUpdate: boolean;
  canDelete: boolean;
  /** Goals pilot: set only inside it, with `goalLinks` deferred. */
  goalsExperiment?: boolean;
  goalLinks?: ProblemGoalLinks;
}>();

const agendaItems = computed(() => props.agendaItems ?? []);
const showDeleteDialog = ref(false);
const statusChanging = ref(false);
const currentSection = ref('aprasymas');

const getLocalized = (field: string[] | Record<string, string> | null | undefined): string => {
  if (!field) return '';
  const locale = getActiveLanguage() as 'lt' | 'en';
  if (typeof field === 'object') {
    return getTranslatedValue(field as Record<string, string>, locale);
  }
  return String(field);
};

const localizedTitle = computed(() => getLocalized(props.problem.title as Record<string, string>) || '—');
const localizedDescription = computed(() => getLocalized(props.problem.description as Record<string, string>));
const localizedStepsTaken = computed(() => getLocalized(props.problem.steps_taken as Record<string, string>));
const localizedSolution = computed(() => getLocalized(props.problem.solution as Record<string, string>));

const stripHtml = (html: string): string => html.replace(/<[^>]*>/g, '').trim();

const hasStepsTaken = computed(() => stripHtml(localizedStepsTaken.value).length > 0);
const hasSolution = computed(() => stripHtml(localizedSolution.value).length > 0);

const createdByUser = computed(() => {
  const val = props.problem.created_by;
  return val && typeof val === 'object' ? (val as unknown as App.Entities.User) : null;
});

const durationText = computed(() => {
  const occurredAt = new Date(props.problem.occurred_at);
  const endDate = props.problem.resolved_at ? new Date(props.problem.resolved_at) : new Date();
  const diffDays = Math.round((endDate.getTime() - occurredAt.getTime()) / (1000 * 60 * 60 * 24));

  if (props.problem.status === 'resolved' && props.problem.resolved_at) {
    return $t('problems.show.duration_resolved', { count: String(diffDays) });
  }
  return $t('problems.show.duration_open', { count: String(diffDays) });
});

const recordFacts = computed<RecordFact[]>(() => [
  { key: 'occurred_at', label: $t('entities.problem.occurred_at'), value: formatDate(new Date(props.problem.occurred_at)) },
  ...(props.problem.resolved_at ? [{ key: 'resolved_at', label: $t('entities.problem.resolved_at'), value: formatDate(new Date(props.problem.resolved_at)) }] : []),
  { key: 'duration', label: $t('problems.show.duration_label'), value: durationText.value },
  ...(props.problem.categories?.length ? [{ key: 'categories', label: $t('Kategorijos'), value: props.problem.categories.map(category => category.name).join(', ') }] : []),
  ...(props.problem.tenant ? [{ key: 'tenant', label: $tChoice('entities.tenant.model', 1), value: props.problem.tenant.shortname }] : []),
  { key: 'responsible', label: $t('entities.problem.responsible_user'), value: props.problem.responsible_user?.name ?? '—' },
  ...(createdByUser.value ? [{ key: 'creator', label: $t('problems.show.created_by_label'), value: createdByUser.value.name }] : []),
]);

const tabs = computed<RecordPageSection[]>(() => [
  { value: 'aprasymas', label: $t('problems.show.tab_description') },
  { value: 'veiksmai', label: $t('problems.show.tab_steps'), count: props.goalLinks ? props.goalLinks.steps.length : hasStepsTaken.value ? 1 : 0 },
  { value: 'sprendimas', label: $t('problems.show.tab_solution'), count: hasSolution.value ? 1 : 0 },
  ...(agendaItems.value.length ? [{ value: 'posedziai', label: $t('problems.show.tab_meetings'), count: agendaItems.value.length }] : []),
  ...(props.problem.institutions?.length ? [{ value: 'institucijos', label: $tChoice('entities.institution.model', 2), count: props.problem.institutions.length }] : []),
  ...(props.goalsExperiment ? [{ value: 'tikslai', label: $t('goals.problem_panel.goals'), count: props.goalLinks?.goals.length }] : []),
]);

const allStatusDefinitions = [
  { value: 'open', label: $t('problems.show.status_open'), icon: CircleDot },
  { value: 'in_progress', label: $t('problems.show.status_in_progress'), icon: LoaderCircle },
  { value: 'resolved', label: $t('problems.show.status_resolved'), icon: CircleCheck },
];

const statusOrder = ['open', 'in_progress', 'resolved'];

const statusSteps = computed(() => {
  const currentIndex = statusOrder.indexOf(props.problem.status);
  return allStatusDefinitions.map((s, index) => ({
    ...s,
    isActive: s.value === props.problem.status,
    isCompleted: index < currentIndex,
  }));
});

const primaryAction = computed<RecordAction | undefined>(() => {
  if (props.canUpdate) {
    return {
      key: 'edit',
      label: $t('Redaguoti'),
      icon: Edit,
      href: route('problems.edit', props.problem.id),
    };
  }
  return undefined;
});

const overflowActions = computed<RecordAction[]>(() => {
  const actions: RecordAction[] = [];

  if (props.canUpdate) {
    allStatusDefinitions
      .filter(s => s.value !== props.problem.status)
      .forEach((s) => {
        actions.push({
          key: `status-${s.value}`,
          label: `${$t('Pažymėti kaip')}: ${s.label}`,
          icon: s.icon,
        });
      });
  }

  if (props.canDelete) {
    actions.push({
      key: 'delete',
      label: $t('Šalinti problemą'),
      icon: Trash2,
      destructive: true,
    });
  }

  return actions;
});

function handleRecordAction(key: string): void {
  if (key === 'delete') {
    showDeleteDialog.value = true;
  }
  else if (key.startsWith('status-')) {
    const nextStatus = key.replace('status-', '');
    handleStatusChange(nextStatus);
  }
}

const handleStatusChange = (status: string) => {
  statusChanging.value = true;
  router.patch(route('problems.updateStatus', props.problem.id), { status }, {
    preserveScroll: true,
    onFinish: () => {
      statusChanging.value = false;
    },
  });
};

const handleDelete = () => {
  router.delete(route('problems.destroy', props.problem.id), {
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};

</script>
