<template>
  <AdminContentPage>
    <InertiaHead :title="localizedTitle" />

    <ShowPageHero
      :title="localizedTitle"
      :subtitle="problem.tenant?.shortname"
      :icon="Icons.PROBLEM"
      :badge="statusBadge"
    >
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
        <ActivityLogButton :activities="problem.activities ?? []" />
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
    </ShowPageHero>

    <!-- Status Progress -->
    <div class="mt-6 flex items-center gap-0">
      <button
        v-for="(step, index) in statusSteps"
        :key="step.value"
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

    <!-- Main Content: Two-column layout -->
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-[2fr_auto_1fr] gap-6">
      <!-- Left column: Content -->
      <div class="space-y-6">
        <!-- Description -->
        <section>
          <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <AlignLeft class="h-3.5 w-3.5" />
            {{ $t('Aprašymas') }}
          </h2>
          <Card>
            <CardContent class="pt-6">
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div class="prose dark:prose-invert max-w-none" v-html="localizedDescription" />
            </CardContent>
          </Card>
        </section>

        <!-- Steps Taken -->
        <section v-if="hasStepsTaken">
          <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <List class="h-3.5 w-3.5" />
            {{ $t('Atlikti žingsniai') }}
          </h2>
          <Card>
            <CardContent class="pt-6">
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div class="prose dark:prose-invert max-w-none" v-html="localizedStepsTaken" />
            </CardContent>
          </Card>
        </section>

        <!-- Solution -->
        <section>
          <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <CircleCheck v-if="hasSolution" class="h-3.5 w-3.5 text-green-500" />
            <CircleX v-else class="h-3.5 w-3.5 text-red-400" />
            {{ $t('Sprendimas') }}
          </h2>
          <Card v-if="hasSolution" class="border-green-200 dark:border-green-900/50 bg-green-50/30 dark:bg-green-900/10">
            <CardContent class="pt-6">
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div class="prose dark:prose-invert max-w-none" v-html="localizedSolution" />
            </CardContent>
          </Card>
          <Card v-else class="border-dashed">
            <CardContent class="py-8">
              <div class="flex flex-col items-center text-center gap-2">
                <div class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                  <Lightbulb class="h-5 w-5 text-zinc-400" />
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                  {{ $t('Problema dar neišspręsta') }}
                </p>
                <Button v-if="canUpdate" variant="outline" size="sm" as-child class="mt-1">
                  <Link :href="route('problems.edit', problem.id)">
                    {{ $t('Pridėti sprendimą') }}
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </section>
      </div>

      <!-- Vertical separator -->
      <Separator orientation="vertical" class="hidden lg:block" />

      <!-- Right column: Sidebar -->
      <div class="space-y-5 self-start">
        <!-- Responsible User -->
        <section v-if="problem.responsible_user">
          <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <User class="h-3.5 w-3.5" />
            {{ $t('entities.problem.responsible_user') }}
          </h2>
          <Card class="h-auto">
            <CardContent class="py-3" size="sm">
              <span class="text-sm font-medium">{{ problem.responsible_user.name }}</span>
            </CardContent>
          </Card>
        </section>

        <!-- Created By -->
        <section v-if="createdByUser">
          <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <UserCheck class="h-3.5 w-3.5" />
            {{ $t('Sukūrė') }}
          </h2>
          <Card class="h-auto">
            <CardContent class="py-3" size="sm">
              <span class="text-sm font-medium">{{ createdByUser.name }}</span>
            </CardContent>
          </Card>
        </section>

        <!-- Categories -->
        <section v-if="problem.categories && problem.categories.length > 0">
          <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <Tags class="h-3.5 w-3.5" />
            {{ $t('entities.problem.categories') }}
          </h2>
          <Card class="h-auto">
            <CardContent class="py-3" size="sm">
              <div class="flex flex-wrap gap-1.5">
                <Badge v-for="cat in problem.categories" :key="cat.id" variant="secondary">
                  {{ cat.name }}
                </Badge>
              </div>
            </CardContent>
          </Card>
        </section>

        <!-- Institutions -->
        <section v-if="problem.institutions && problem.institutions.length > 0">
          <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <Building2 class="h-3.5 w-3.5" />
            {{ $tChoice('entities.institution.model', 2) }}
          </h2>
          <Card class="h-auto">
            <CardContent class="py-3" size="sm">
              <div class="flex flex-wrap gap-1.5">
                <Badge v-for="inst in problem.institutions" :key="inst.id" variant="outline">
                  {{ inst.name }}
                </Badge>
              </div>
            </CardContent>
          </Card>
        </section>

        <!-- Empty metadata prompt -->
        <Card v-if="!hasMetadata && canUpdate" class="border-dashed h-auto">
          <CardContent class="py-6">
            <div class="flex flex-col items-center text-center gap-2">
              <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $t('Pridėkite kategoriją, atsakingą asmenį ar instituciją.') }}
              </p>
              <Button variant="outline" size="sm" as-child>
                <Link :href="route('problems.edit', problem.id)">
                  <Edit class="h-3.5 w-3.5 mr-1.5" />
                  {{ $t('Redaguoti') }}
                </Link>
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Bottom navigation -->
    <Separator class="mt-8 mb-4" />
    <div class="flex items-center justify-start">
      <Button variant="ghost" size="sm" as-child>
        <Link :href="route('problems.index')">
          <ArrowLeft class="h-4 w-4 mr-1.5" />
          {{ $t('Grįžti į sąrašą') }}
        </Link>
      </Button>
    </div>

    <!-- Delete Confirmation Dialog -->
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
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, Head as InertiaHead, Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice, getActiveLanguage } from 'laravel-vue-i18n';
import {
  Edit, Trash2, MoreHorizontal, User, UserCheck, Calendar,
  Building2, CheckCircle2, Clock, ArrowLeft, CircleDot, Loader2,
  Lightbulb, AlignLeft, List, CircleCheck, CircleX, Tags,
} from 'lucide-vue-next';

import Icons from '@/Types/Icons/regular';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

// Layout
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';

// UI Components
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Card, CardContent } from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

// Custom Components
import ShowPageHero from '@/Components/Hero/ShowPageHero.vue';
import ActivityLogButton from '@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue';

const props = defineProps<{
  problem: App.Entities.Problem & { activities: any[] };
  canUpdate: boolean;
  canDelete: boolean;
}>();

const showDeleteDialog = ref(false);
const statusChanging = ref(false);

const getLocalized = (field: string[] | null | undefined): string => {
  if (!field) return '';
  const locale = getActiveLanguage() as 'lt' | 'en';
  const obj = field as unknown as Record<string, string>;
  return obj[locale] || obj['lt'] || obj['en'] || '';
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

const { problem } = props;

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    $tChoice('entities.problem.model', 2),
    'problems.index',
    {},
    localizedTitle.value,
    Icons.PROBLEM,
    Icons.PROBLEM,
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
