<template>
  <ShowPageLayout
    :title="localizedTitle"
    :subtitle="problem.tenant?.shortname"
    :icon="ProblemIcon"
    :model="problem"
    audit-subject-type="problem"
  >
    <!-- statusBadge uses the `warning`/`success` badge variants, which the hero's
         BadgeConfig union doesn't cover — so render it through the slot. -->
    <template #badge>
      <Badge :variant="statusBadge.variant">
        {{ statusBadge.label }}
      </Badge>
    </template>

    <template #info>
      <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-sm">
        <div class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
          <Calendar class="h-4 w-4 text-zinc-400 shrink-0" />
          <span>{{ new Date(problem.occurred_at).toLocaleDateString('lt-LT') }}</span>
        </div>
        <div v-if="problem.resolved_at" class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
          <CheckCircle2 class="h-4 w-4 text-green-500 shrink-0" />
          <span>{{ new Date(problem.resolved_at).toLocaleDateString('lt-LT') }}</span>
        </div>
        <Separator orientation="vertical" class="h-4" />
        <div class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
          <Clock class="h-4 w-4 text-zinc-400 shrink-0" />
          <span>{{ durationText }}</span>
        </div>
      </div>
    </template>

    <template #actions>
      <Button v-if="canUpdate" variant="outline" size="icon" class="h-9 w-9" as-child>
        <Link :href="route('problems.edit', problem.id)">
          <Edit class="h-4 w-4" />
        </Link>
      </Button>
      <DropdownMenu v-if="canUpdate || canDelete">
        <DropdownMenuTrigger as-child>
          <Button variant="outline" size="icon" class="h-9 w-9">
            <MoreHorizontal class="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <template v-if="canUpdate">
            <DropdownMenuItem as-child>
              <Link :href="route('problems.edit', problem.id)">
                <Edit class="h-4 w-4 mr-2" />
                {{ $t('Redaguoti') }}
              </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuLabel class="text-xs text-zinc-500">
              {{ $t('Keisti būseną') }}
            </DropdownMenuLabel>
            <DropdownMenuItem
              v-for="s in availableStatuses"
              :key="s.value"
              :disabled="statusChanging"
              @click="handleStatusChange(s.value)"
            >
              <component :is="s.icon" class="h-4 w-4 mr-2" :class="s.iconClass" />
              {{ s.label }}
            </DropdownMenuItem>
          </template>
          <template v-if="canDelete">
            <DropdownMenuSeparator v-if="canUpdate" />
            <DropdownMenuItem class="text-destructive focus:text-destructive" @click="showDeleteDialog = true">
              <Trash2 class="h-4 w-4 mr-2" />
              {{ $t('Šalinti problemą') }}
            </DropdownMenuItem>
          </template>
        </DropdownMenuContent>
      </DropdownMenu>
    </template>

    <!-- Status progress: a domain control with exactly one caller, so it stays inline. -->
    <div class="flex items-center gap-0">
      <button
        v-for="step in statusSteps"
        :key="step.value"
        type="button"
        :disabled="!canUpdate || statusChanging"
        :class="[
          'relative flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors',
          'first:rounded-l-lg last:rounded-r-lg',
          'border border-r-0 last:border-r',
          'disabled:cursor-default',
          step.isActive
            ? step.activeClass + ' z-10'
            : step.isCompleted
              ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800'
              : 'bg-zinc-50 text-zinc-400 border-zinc-200 dark:bg-zinc-800/50 dark:text-zinc-500 dark:border-zinc-700',
          canUpdate && !step.isActive ? 'hover:bg-zinc-100 dark:hover:bg-zinc-700/50 cursor-pointer' : '',
        ]"
        @click="canUpdate && !step.isActive && handleStatusChange(step.value)"
      >
        <component :is="step.icon" class="h-4 w-4" />
        <span class="hidden sm:inline">{{ step.label }}</span>
      </button>
    </div>

    <ShowPageGrid class="mt-6">
      <template #main>
        <SectionCard :title="$t('Aprašymas')" :icon="AlignLeft">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="max-w-none" v-html="localizedDescription" />
        </SectionCard>

        <SectionCard v-if="hasStepsTaken" :title="$t('Atlikti žingsniai')" :icon="List">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="max-w-none" v-html="localizedStepsTaken" />
        </SectionCard>

        <SectionCard
          :title="$t('Sprendimas')"
          :icon="hasSolution ? CircleCheck : CircleX"
          :empty="!hasSolution"
          :class="hasSolution
            ? 'border-green-200 dark:border-green-900/50 bg-green-50/30 dark:bg-green-900/10'
            : 'border-dashed'"
        >
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="max-w-none" v-html="localizedSolution" />

          <template #empty>
            <EmptyState
              :title="$t('Problema dar neišspręsta')"
              :description="$t('Kai problema bus išspręsta, aprašykite sprendimą čia.')"
            >
              <template #icon>
                <Lightbulb class="h-10 w-10 text-muted-foreground" />
              </template>
              <Button v-if="canUpdate" variant="outline" size="sm" as-child>
                <Link :href="route('problems.edit', problem.id)">
                  {{ $t('Pridėti sprendimą') }}
                </Link>
              </Button>
            </EmptyState>
          </template>
        </SectionCard>
      </template>

      <template #sidebar>
        <SectionCard
          v-if="problem.responsible_user"
          :title="$t('entities.problem.responsible_user')"
          :icon="User"
          content-size="sm"
        >
          <span class="text-sm font-medium">{{ problem.responsible_user.name }}</span>
        </SectionCard>

        <SectionCard v-if="createdByUser" :title="$t('Sukūrė')" :icon="UserCheck" content-size="sm">
          <span class="text-sm font-medium">{{ createdByUser.name }}</span>
        </SectionCard>

        <SectionCard
          v-if="problem.categories?.length"
          :title="$t('entities.problem.categories')"
          :icon="Tags"
          :count="problem.categories.length"
          content-size="sm"
        >
          <div class="flex flex-wrap gap-1.5">
            <Badge v-for="cat in problem.categories" :key="cat.id" variant="secondary">
              {{ cat.name }}
            </Badge>
          </div>
        </SectionCard>

        <SectionCard
          v-if="problem.institutions?.length"
          :title="$tChoice('entities.institution.model', 2)"
          :icon="Building2"
          :count="problem.institutions.length"
          content-size="sm"
        >
          <div class="flex flex-wrap gap-1.5">
            <Badge v-for="inst in problem.institutions" :key="inst.id" variant="outline">
              {{ inst.name }}
            </Badge>
          </div>
        </SectionCard>

        <!-- A prompt rather than a section, so it renders bare instead of inside a card. -->
        <EmptyState
          v-if="!hasMetadata && canUpdate"
          :title="$t('Trūksta informacijos')"
          :description="$t('Pridėkite kategoriją, atsakingą asmenį ar instituciją.')"
        >
          <template #icon>
            <Info class="h-10 w-10 text-muted-foreground" />
          </template>
          <Button variant="outline" size="sm" as-child>
            <Link :href="route('problems.edit', problem.id)">
              <Edit class="h-3.5 w-3.5 mr-1.5" />
              {{ $t('Redaguoti') }}
            </Link>
          </Button>
        </EmptyState>
      </template>
    </ShowPageGrid>

    <Dialog v-model:open="showDeleteDialog">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-destructive">
            <Trash2 class="h-5 w-5" />
            {{ $t('Šalinti problemą?') }}
          </DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $t('Ar tikrai norite ištrinti šią problemą? Šis veiksmas negrįžtamas.') }}
          </p>
          <div class="flex justify-end gap-3">
            <Button variant="outline" @click="showDeleteDialog = false">
              {{ $t('Atšaukti') }}
            </Button>
            <Button variant="destructive" @click="handleDelete">
              <Trash2 class="h-4 w-4 mr-2" />
              {{ $t('Šalinti') }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </ShowPageLayout>
</template>

<script setup lang="ts">
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice, getActiveLanguage } from 'laravel-vue-i18n';
import {
  Edit, Trash2, MoreHorizontal, User, UserCheck, Calendar,
  Building2, CheckCircle2, Clock, CircleDot, Loader2, Info,
  Lightbulb, AlignLeft, List, CircleCheck, CircleX, Tags,
} from 'lucide-vue-next';

import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import ShowPageLayout from '@/Components/Layouts/ShowPageLayout.vue';
import { EmptyState, SectionCard, ShowPageGrid } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Separator } from '@/Components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ProblemIcon } from '@/Components/icons';

const props = defineProps<{
  problem: App.Entities.Problem;
  canUpdate: boolean;
  canDelete: boolean;
}>();

const showDeleteDialog = ref(false);
const statusChanging = ref(false);

const getLocalized = (field: string[] | null | undefined): string => {
  if (!field) return '';
  const locale = getActiveLanguage() as 'lt' | 'en';
  const obj = field as unknown as Record<string, string>;
  return getTranslatedValue(obj, locale);
};

const localizedTitle = computed(() => getLocalized(props.problem.title) || '—');
const localizedDescription = computed(() => getLocalized(props.problem.description));
const localizedStepsTaken = computed(() => getLocalized(props.problem.steps_taken));
const localizedSolution = computed(() => getLocalized(props.problem.solution));

const stripHtml = (html: string): string => html.replace(/<[^>]*>/g, '').trim();

const hasStepsTaken = computed(() => stripHtml(localizedStepsTaken.value).length > 0);
const hasSolution = computed(() => stripHtml(localizedSolution.value).length > 0);

// When the createdBy relation is loaded, Laravel serializes it as `created_by` (snake_case),
// overwriting the FK column value. Cast to User when it's an object.
const createdByUser = computed(() => {
  const val = props.problem.created_by;
  return val && typeof val === 'object' ? (val as unknown as App.Entities.User) : null;
});

const hasMetadata = computed(() =>
  !!props.problem.responsible_user
  || !!createdByUser.value
  || (props.problem.categories && props.problem.categories.length > 0)
  || (props.problem.institutions && props.problem.institutions.length > 0),
);

// Duration indicator
const durationText = computed(() => {
  const occurredAt = new Date(props.problem.occurred_at);
  const endDate = props.problem.resolved_at ? new Date(props.problem.resolved_at) : new Date();
  const diffDays = Math.round((endDate.getTime() - occurredAt.getTime()) / (1000 * 60 * 60 * 24));

  if (props.problem.status === 'resolved' && props.problem.resolved_at) {
    return $t('Išspręsta per :count d.', { count: String(diffDays) });
  }
  return $t('Atvira jau :count d.', { count: String(diffDays) });
});

// Status badge
const statusBadge = computed(() => {
  const map: Record<string, { label: string; variant: 'destructive' | 'warning' | 'success' }> = {
    open: { label: $t('Atvira'), variant: 'destructive' },
    in_progress: { label: $t('Vykdoma'), variant: 'warning' },
    resolved: { label: $t('Išspręsta'), variant: 'success' },
  };
  return map[props.problem.status] ?? { label: props.problem.status, variant: 'destructive' as const };
});

// Quick status change
const allStatusDefinitions = [
  {
    value: 'open',
    label: $t('Atvira'),
    icon: CircleDot,
    iconClass: 'text-red-500',
    activeClass: 'bg-red-500 text-white border-red-500 dark:bg-red-600 dark:border-red-600',
    activeCaret: 'bg-red-500 border-red-500 dark:bg-red-600 dark:border-red-600',
  },
  {
    value: 'in_progress',
    label: $t('Vykdoma'),
    icon: Loader2,
    iconClass: 'text-yellow-500',
    activeClass: 'bg-yellow-500 text-white border-yellow-500 dark:bg-yellow-600 dark:border-yellow-600',
    activeCaret: 'bg-yellow-500 border-yellow-500 dark:bg-yellow-600 dark:border-yellow-600',
  },
  {
    value: 'resolved',
    label: $t('Išspręsta'),
    icon: CheckCircle2,
    iconClass: 'text-green-500',
    activeClass: 'bg-green-500 text-white border-green-500 dark:bg-green-600 dark:border-green-600',
    activeCaret: 'bg-green-500 border-green-500 dark:bg-green-600 dark:border-green-600',
  },
];

const availableStatuses = computed(() => {
  return allStatusDefinitions.filter(s => s.value !== props.problem.status);
});

// Status progress steps
const statusOrder = ['open', 'in_progress', 'resolved'];

const statusSteps = computed(() => {
  const currentIndex = statusOrder.indexOf(props.problem.status);
  return allStatusDefinitions.map((s, index) => ({
    ...s,
    isActive: s.value === props.problem.status,
    isCompleted: index < currentIndex,
  }));
});

const handleStatusChange = (status: string) => {
  statusChanging.value = true;
  router.patch(route('problems.updateStatus', props.problem.id), { status }, {
    preserveScroll: true,
    onFinish: () => {
      statusChanging.value = false;
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

const handleDelete = () => {
  router.delete(route('problems.destroy', props.problem.id), {
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};
</script>
