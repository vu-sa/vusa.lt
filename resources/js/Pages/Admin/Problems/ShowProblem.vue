<template>
  <RecordPage
    v-model:section="currentSection"
    :title="localizedTitle"
    :entity-type="ModelEnum.PROBLEM"
    :status="problemStatuses[problem.status as ProblemStatus]"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    @action="handleRecordAction"
  >
    <template #subtitle>
      <div class="flex flex-wrap items-center gap-2">
        <div v-if="problem.categories?.length" class="flex flex-wrap gap-1">
          <Badge v-for="cat in problem.categories" :key="cat.id" variant="secondary" class="text-xs">
            {{ cat.name }}
          </Badge>
        </div>
        <div v-if="problem.institutions?.length" class="flex flex-wrap gap-1">
          <Badge v-for="inst in problem.institutions" :key="inst.id" variant="outline" class="text-xs">
            {{ inst.name }}
          </Badge>
        </div>
      </div>
    </template>

    <template #alert>
      <div class="flex flex-wrap items-center justify-between gap-4 border border-border bg-card p-3">
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            {{ $t('Būsena') }}:
          </span>
          <div class="inline-flex border border-border bg-secondary">
            <button
              v-for="step in statusSteps"
              :key="step.value"
              type="button"
              :disabled="!canUpdate || statusChanging"
              :class="[
                'u-touch flex items-center gap-1.5 px-3 py-1 text-xs font-semibold uppercase tracking-wider transition-colors border-r last:border-r-0 border-border',
                step.isActive
                  ? 'bg-primary text-primary-foreground'
                  : step.isCompleted
                    ? 'bg-[var(--status-success-surface)] text-[var(--status-success)]'
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
          {{ $t('Aprašymas nepateiktas.') }}
        </p>
      </div>
    </template>

    <template #veiksmai>
      <div class="max-w-3xl space-y-4">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="hasStepsTaken" class="prose prose-zinc dark:prose-invert max-w-none text-sm" v-html="localizedStepsTaken" />
        <EmptyState
          v-else
          :icon="List"
          :title="$t('Žingsniai dar neaprašyti')"
          :description="$t('Aprašykite veiksmus, kurie jau buvo atlikti bandant išspręsti šią problemą.')"
          :action-label="canUpdate ? $t('Pridėti žingsnius') : undefined"
          @action="router.visit(route('problems.edit', problem.id))"
        />
      </div>
    </template>

    <template #sprendimas>
      <div class="max-w-3xl space-y-4">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="hasSolution" class="border border-[var(--status-success-border)] bg-[var(--status-success-surface)] p-4 text-sm text-foreground" v-html="localizedSolution" />
        <EmptyState
          v-else
          :icon="Lightbulb"
          :title="$t('Problema dar neišspręsta')"
          :description="$t('Kai problema bus išspręsta, aprašykite sprendimą čia.')"
          :action-label="canUpdate ? $t('Pridėti sprendimą') : undefined"
          @action="router.visit(route('problems.edit', problem.id))"
        />
      </div>
    </template>

    <template #institucijos>
      <div class="max-w-3xl">
        <div v-if="problem.institutions?.length" class="grid gap-2 sm:grid-cols-2">
          <div
            v-for="inst in problem.institutions"
            :key="inst.id"
            class="flex items-center justify-between border border-border bg-card p-3"
          >
            <span class="text-sm font-medium">{{ inst.name }}</span>
            <Button as-child variant="ghost" size="icon-sm">
              <Link :href="route('institutions.show', inst.id)">
                <ChevronRight class="size-4" />
              </Link>
            </Button>
          </div>
        </div>
        <EmptyState
          v-else
          :icon="Building2"
          :title="$t('Susijusių institucijų nėra')"
          :description="$t('Prie šios problemos nėra priskirtų institucijų.')"
        />
      </div>
    </template>

    <template #activity>
      <RecordActivity
        subject-type="problem"
        :subject-id="problem.id"
        commentable-type="problem"
        :commentable-id="problem.id"
      />
    </template>
  </RecordPage>

  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="$t('Šalinti problemą?')"
    :description="$t('Ar tikrai norite ištrinti šią problemą? Problema bus perkelta į šiukšlinę.')"
    :confirm-label="$t('Šalinti')"
    destructive
    @confirm="handleDelete"
  />
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
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
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ProblemIcon } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { problemStatuses, type ProblemStatus } from '@/Constants/statuses';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import { ModelEnum } from '@/Types/enums';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  problem: App.Entities.Problem;
  canUpdate: boolean;
  canDelete: boolean;
}>();

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
    return $t('Išspręsta per :count d.', { count: String(diffDays) });
  }
  return $t('Atvira jau :count d.', { count: String(diffDays) });
});

const recordFacts = computed<RecordFact[]>(() => [
  { key: 'occurred_at', label: $t('entities.problem.occurred_at'), value: formatDate(new Date(props.problem.occurred_at)) },
  ...(props.problem.resolved_at ? [{ key: 'resolved_at', label: $t('entities.problem.resolved_at'), value: formatDate(new Date(props.problem.resolved_at)) }] : []),
  { key: 'duration', label: $t('Trukmė'), value: durationText.value },
  ...(props.problem.tenant ? [{ key: 'tenant', label: $tChoice('entities.tenant.model', 1), value: props.problem.tenant.shortname }] : []),
  { key: 'responsible', label: $t('entities.problem.responsible_user'), value: props.problem.responsible_user?.name ?? '—' },
  ...(createdByUser.value ? [{ key: 'creator', label: $t('Sukūrė'), value: createdByUser.value.name }] : []),
]);

const tabs = computed<RecordPageSection[]>(() => [
  { value: 'aprasymas', label: $t('Aprašymas') },
  { value: 'veiksmai', label: $t('Atlikti žingsniai'), count: hasStepsTaken.value ? 1 : 0 },
  { value: 'sprendimas', label: $t('Sprendimas'), count: hasSolution.value ? 1 : 0 },
  ...(props.problem.institutions?.length ? [{ value: 'institucijos', label: $tChoice('entities.institution.model', 2), count: props.problem.institutions.length }] : []),
]);

const allStatusDefinitions = [
  { value: 'open', label: $t('Atvira'), icon: CircleDot },
  { value: 'in_progress', label: $t('Vykdoma'), icon: LoaderCircle },
  { value: 'resolved', label: $t('Išspręsta'), icon: CircleCheck },
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

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    $tChoice('entities.problem.model', 2),
    'problems.index',
    {},
    localizedTitle.value,
    ProblemIcon,
    ProblemIcon,
  ),
);
</script>
