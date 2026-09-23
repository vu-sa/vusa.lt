<template>
  <CollectionPage
    :source
    collection="problems"
    entity-type="problem"
    :eyebrow="$t('shell.workspaces.visak.title') + ' · ' + $t('Problemos')"
    :title="$t('Problemos')"
    :lead="$t('Registruok studentų problemas, sek sprendimo eigą ir koordinuok veiksmus su institucijomis.')"
    default-view="preview"
    :available-views="['preview', 'table', 'rows']"
    :item-key="problemKey"
    :trash="{ count: deletedCount ?? 0, active: isDeleted }"
    :columns
    :quick-filters
    :search-placeholder="$t('Ieškoti problemų…')"
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <Button v-if="canCreate && !isDeleted" as-child variant="brand" size="lg">
        <Link :href="route('problems.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja problema') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-14 items-center justify-between gap-4 px-3 py-2.5 sm:px-4">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <Link
              :href="route('problems.show', item.id)"
              data-collection-open
              class="truncate font-medium hover:text-brand"
            >
              {{ localizedTitle(item) }}
            </Link>
            <StatusBadge
              v-if="item.status && problemStatuses[item.status as ProblemStatus]"
              :status="problemStatuses[item.status as ProblemStatus]"
              class="shrink-0"
            />
          </div>
          <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span v-if="item.tenant?.shortname" class="font-medium">{{ item.tenant.shortname }}</span>
            <span v-if="item.tenant?.shortname">·</span>
            <span class="tabular-nums">{{ formatDate(new Date(item.occurred_at)) }}</span>
            <template v-if="item.responsible_user?.name">
              <span>·</span>
              <span>{{ item.responsible_user.name }}</span>
            </template>
          </div>
        </div>
        <div class="flex shrink-0 items-center gap-1">
          <Button as-child variant="outline" size="icon">
            <Link :href="route('problems.show', item.id)">
              <ChevronRight class="size-4" />
            </Link>
          </Button>
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'title'" class="min-w-0">
        <Link
          :href="route('problems.show', item.id)"
          data-collection-open
          class="block truncate font-medium hover:text-brand"
        >
          {{ localizedTitle(item) }}
        </Link>
      </div>

      <div v-else-if="column.key === 'status'">
        <StatusBadge
          v-if="item.status && problemStatuses[item.status as ProblemStatus]"
          :status="problemStatuses[item.status as ProblemStatus]"
        />
      </div>

      <span v-else-if="column.key === 'occurred_at'" class="tabular-nums">
        {{ formatDate(new Date(item.occurred_at)) }}
      </span>

      <span v-else-if="column.key === 'resolved_at'" class="tabular-nums">
        {{ item.resolved_at ? formatDate(new Date(item.resolved_at)) : '—' }}
      </span>

      <span v-else-if="column.key === 'responsible_user'" class="truncate">
        {{ item.responsible_user?.name ?? '—' }}
      </span>

      <div v-else-if="column.key === 'categories'" class="flex flex-wrap gap-1">
        <template v-if="item.categories?.length">
          <Badge v-for="cat in item.categories.slice(0, 2)" :key="cat.id" variant="secondary" class="text-xs">
            {{ cat.name }}
          </Badge>
          <span v-if="item.categories.length > 2" class="text-xs text-muted-foreground">
            +{{ item.categories.length - 2 }}
          </span>
        </template>
        <span v-else class="text-muted-foreground">—</span>
      </div>

      <span v-else-if="column.key === 'tenant'" class="truncate text-xs">
        {{ item.tenant?.shortname ?? '—' }}
      </span>

      <div v-else-if="column.key === 'actions'" class="flex items-center justify-end gap-1">
        <template v-if="isDeleted">
          <Button variant="outline" size="icon" :title="$t('Atkurti')" @click="restoreProblem(item)">
            <RotateCcw class="size-4" />
          </Button>
          <Button
            v-if="canForceDelete"
            variant="outline"
            size="icon"
            class="text-destructive hover:text-destructive"
            :title="$t('Ištrinti visam laikui')"
            @click="targetProblemToForceDelete = item"
          >
            <Trash2 class="size-4" />
          </Button>
        </template>
        <template v-else>
          <Button v-if="canUpdate" as-child variant="outline" size="icon" :title="$t('Redaguoti')">
            <Link :href="route('problems.edit', item.id)">
              <Edit class="size-4" />
            </Link>
          </Button>
          <Button as-child variant="outline" size="icon" :title="$t('Atidaryti')">
            <Link :href="route('problems.show', item.id)">
              <ChevronRight class="size-4" />
            </Link>
          </Button>
        </template>
      </div>
    </template>

    <template #preview="{ item }">
      <section class="flex flex-col gap-5 p-5">
        <div>
          <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
              {{ $t('Problema') }}
            </p>
            <StatusBadge
              v-if="item.status && problemStatuses[item.status as ProblemStatus]"
              :status="problemStatuses[item.status as ProblemStatus]"
            />
          </div>
          <Link
            :href="route('problems.show', item.id)"
            class="mt-1.5 block text-lg font-semibold leading-snug hover:text-brand"
          >
            {{ localizedTitle(item) }}
          </Link>
          <div v-if="item.tenant?.shortname" class="mt-1 text-xs text-muted-foreground">
            {{ item.tenant.shortname }}
          </div>
        </div>

        <dl class="grid gap-3 border-y border-border py-4 text-sm">
          <div class="flex items-center justify-between gap-2">
            <dt class="text-xs text-muted-foreground">
              {{ $t('Atsakingas') }}
            </dt>
            <dd class="text-sm font-medium">
              {{ item.responsible_user?.name ?? '—' }}
            </dd>
          </div>
          <div class="flex items-center justify-between gap-2">
            <dt class="text-xs text-muted-foreground">
              {{ $t('Įvykio data') }}
            </dt>
            <dd class="text-sm tabular-nums">
              {{ formatDate(new Date(item.occurred_at)) }}
            </dd>
          </div>
          <div v-if="item.resolved_at" class="flex items-center justify-between gap-2">
            <dt class="text-xs text-muted-foreground">
              {{ $t('Išspręsta') }}
            </dt>
            <dd class="text-sm tabular-nums">
              {{ formatDate(new Date(item.resolved_at)) }}
            </dd>
          </div>
          <div v-if="item.categories?.length" class="flex flex-col gap-1.5">
            <dt class="text-xs text-muted-foreground">
              {{ $t('Kategorijos') }}
            </dt>
            <dd class="flex flex-wrap gap-1">
              <Badge v-for="cat in item.categories" :key="cat.id" variant="secondary" class="text-xs">
                {{ cat.name }}
              </Badge>
            </dd>
          </div>
          <div v-if="item.institutions?.length" class="flex flex-col gap-1.5">
            <dt class="text-xs text-muted-foreground">
              {{ $tChoice('entities.institution.model', 2) }}
            </dt>
            <dd class="flex flex-wrap gap-1">
              <Badge v-for="inst in item.institutions" :key="inst.id" variant="outline" class="text-xs">
                {{ inst.name }}
              </Badge>
            </dd>
          </div>
        </dl>

        <div v-if="localizedDescription(item)" class="space-y-1.5">
          <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            {{ $t('Aprašymas') }}
          </h4>
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="line-clamp-4 text-sm text-foreground/90" v-html="localizedDescription(item)" />
        </div>

        <div class="mt-auto flex flex-col gap-2 pt-4">
          <template v-if="isDeleted">
            <Button variant="outline" size="sm" @click="restoreProblem(item)">
              <RotateCcw aria-hidden="true" class="size-4" />
              {{ $t('Atkurti') }}
            </Button>
            <Button
              variant="ghost"
              size="sm"
              class="text-destructive hover:text-destructive"
              @click="targetProblemToForceDelete = item"
            >
              <Trash2 aria-hidden="true" class="size-4" />
              {{ $t('Ištrinti visam laikui') }}
            </Button>
          </template>
          <template v-else>
            <Button as-child variant="brand" size="sm">
              <Link :href="route('problems.show', item.id)">
                {{ $t('Atidaryti problemą') }}
                <ArrowRight class="ml-1.5 size-4" />
              </Link>
            </Button>
            <Button v-if="canUpdate" as-child variant="outline" size="sm">
              <Link :href="route('problems.edit', item.id)">
                <Edit class="mr-1.5 size-4" />
                {{ $t('Redaguoti') }}
              </Link>
            </Button>
          </template>
        </div>
      </section>
    </template>

    <template #empty>
      <EmptyState
        :mode="isFiltered ? 'no-results' : 'empty'"
        :icon="ProblemIcon"
        :title="isDeleted ? $t('Ištrintų problemų nėra') : $t('Problemų dar nėra')"
        :description="isDeleted ? $t('Šiukšliadėžėje nėra pašalintų problemų.') : $t('Registruok studentų problemas ir sek sprendimo eigą.')"
        :action-label="canCreate && !isDeleted ? $t('Nauja problema') : undefined"
        @action="router.visit(route('problems.create'))"
      />
    </template>
  </CollectionPage>

  <ConfirmDialog
    :open="targetProblemToForceDelete !== null"
    :title="$t('Ištrinti problemą visam laikui?')"
    :description="$t('Šis veiksmas negrįžtamas. Problema bus visiškai pašalinta.')"
    :confirm-label="$t('Ištrinti visam laikui')"
    destructive
    @update:open="!$event && (targetProblemToForceDelete = null)"
    @confirm="forceDeleteProblem"
  />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ArrowRight, ChevronRight, Edit, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState, StatusBadge } from '@/Components/Patterns';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ProblemIcon } from '@/Components/icons';
import { useDatabaseCollectionSource, type DatabaseFacetDefinition } from '@/Composables/useCollectionSource';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { problemStatuses, type ProblemStatus } from '@/Constants/statuses';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  data: App.Entities.Problem[];
  meta: {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  filters?: Record<string, unknown>;
  sorting?: { id: string; desc: boolean }[];
  categories: App.Entities.ProblemCategory[];
  institutions: App.Entities.Institution[];
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const isDeleted = computed(() => Boolean(props.showDeleted));
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.problem));
const canUpdate = computed(() => Boolean(usePage().props.auth?.can?.update?.problem));
const canForceDelete = computed(() => Boolean(usePage().props.auth?.can?.forceDelete?.problem));

const targetProblemToForceDelete = ref<App.Entities.Problem | null>(null);

const problemKey = (item: App.Entities.Problem) => String(item.id);

const localizedTitle = (item: App.Entities.Problem): string => {
  if (!item.title) return '';
  if (typeof item.title === 'object') {
    const locale = getActiveLanguage() as 'lt' | 'en';
    return getTranslatedValue(item.title as unknown as Record<string, string>, locale);
  }
  return String(item.title);
};

const localizedDescription = (item: App.Entities.Problem): string => {
  if (!item.description) return '';
  if (typeof item.description === 'object') {
    const locale = getActiveLanguage() as 'lt' | 'en';
    return getTranslatedValue(item.description as unknown as Record<string, string>, locale);
  }
  return String(item.description);
};

const myTenantIds = computed<number[]>(() =>
  (usePage().props.auth?.user?.tenants ?? []).map(tenant => tenant.id),
);

const userId = computed(() => usePage().props.auth?.user?.id);

const tenants = computed(() => usePage().props.tenants || []);

const facets = computed<DatabaseFacetDefinition[]>(() => [
  {
    field: 'status',
    label: $t('Būsena'),
    values: [
      { value: 'open', label: $t('Atvira') },
      { value: 'in_progress', label: $t('Vykdoma') },
      { value: 'resolved', label: $t('Išspręsta') },
    ],
  },
  {
    field: 'category',
    label: $t('Kategorija'),
    values: props.categories.map(cat => ({ value: String(cat.id), label: String(cat.name) })),
  },
  {
    field: 'institution',
    label: $tChoice('entities.institution.model', 2),
    values: props.institutions.map(inst => ({ value: String(inst.id), label: String(inst.name) })),
  },
  ...(tenants.value.length > 0
    ? [{
        field: 'tenant.id',
        label: $tChoice('entities.tenant.model', 1),
        values: tenants.value.map(tenant => ({ value: String(tenant.id), label: $t(tenant.shortname) })),
      }]
    : []),
]);

const source = useDatabaseCollectionSource<App.Entities.Problem>({
  endpoint: route('api.v1.admin.problems.index'),
  initial: {
    items: props.data,
    total: props.meta.total,
    perPage: props.meta.per_page,
    currentPage: props.meta.current_page,
    lastPage: props.meta.last_page,
  },
  defaultSort: 'occurred_at:desc',
  sortOptions: [
    { value: 'occurred_at:desc', label: $t('Naujausios problemos') },
    { value: 'occurred_at:asc', label: $t('Seniausios problemos') },
  ],
  preserveUrlKeys: ['showDeleted'],
  facets: facets.value,
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('entities.problem.title') },
  { key: 'status', label: $t('entities.problem.status'), class: 'w-32' },
  { key: 'occurred_at', label: $t('entities.problem.occurred_at'), class: 'w-32' },
  { key: 'resolved_at', label: $t('entities.problem.resolved_at'), class: 'w-32' },
  { key: 'responsible_user', label: $t('entities.problem.responsible_user'), class: 'w-44' },
  { key: 'categories', label: $t('entities.problem.categories'), class: 'w-40' },
  { key: 'tenant', label: $tChoice('entities.tenant.model', 1), class: 'w-24' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-28 text-right' },
]);

const asList = (value: unknown): string[] => (Array.isArray(value) ? value.map(String) : value ? [String(value)] : []);

const quickFilters = computed<CollectionQuickFilter[]>(() => {
  const filters = source.filters.value;
  const statuses = asList(filters.status);
  const tenantIds = asList(filters['tenant.id']);
  const createdBy = asList(filters.created_by);

  const isActiveFilterOn = statuses.length === 2 && statuses.includes('open') && statuses.includes('in_progress');
  const isMineTenantOn = myTenantIds.value.length > 0
    && tenantIds.length === myTenantIds.value.length
    && myTenantIds.value.every(id => tenantIds.includes(String(id)));
  const isCreatedByMeOn = Boolean(userId.value && createdBy.length === 1 && createdBy[0] === String(userId.value));

  return [
    { id: 'active', label: $t('Atviros ir vykdomos'), active: isActiveFilterOn },
    ...(myTenantIds.value.length > 0 ? [{ id: 'mine_tenant', label: $t('Mano padalinio'), active: isMineTenantOn }] : []),
    ...(userId.value ? [{ id: 'created_by_me', label: $t('Mano sukurtos'), active: isCreatedByMeOn }] : []),
  ];
});

function toggleQuickFilter(id: string): void {
  const active = quickFilters.value.find(filter => filter.id === id)?.active ?? false;

  if (id === 'active') {
    source.setFilter('status', active ? undefined : ['open', 'in_progress']);
  }
  else if (id === 'mine_tenant') {
    source.setFilter('tenant.id', active ? undefined : myTenantIds.value.map(String));
  }
  else if (id === 'created_by_me') {
    source.setFilter('created_by', active || !userId.value ? undefined : [String(userId.value)]);
  }
}

const isFiltered = computed(() => source.query.value.trim() !== '' || source.activeFilterCount.value > 0);

function restoreProblem(item: App.Entities.Problem): void {
  router.patch(route('problems.restore', item.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Problema atkurta.'));
    },
    onError: () => {
      toast.error($t('Nepavyko atkurti problemos.'));
    },
  });
}

function forceDeleteProblem(): void {
  if (!targetProblemToForceDelete.value) return;
  const { id } = targetProblemToForceDelete.value;
  targetProblemToForceDelete.value = null;

  router.delete(route('problems.forceDelete', id), {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Problema ištrinta visam laikui.'));
    },
    onError: () => {
      toast.error($t('Nepavyko ištrinti problemos.'));
    },
  });
}
</script>
