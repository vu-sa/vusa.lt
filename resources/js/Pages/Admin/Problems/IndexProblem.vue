<template>
  <CollectionPage
    :row-actions="item => rowActions(item)"
    @row-action="(item, key) => selectRowAction(key, item)"
    :source
    collection="problems"
    entity-type="problem"
    :eyebrow="$t('shell.workspaces.atstovavimas.title') + ' · ' + $t('Problemos')"
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
    <template #lead>
      <p>{{ $t('Registruok studentų problemas, sek sprendimo eigą ir koordinuok veiksmus su institucijomis.') }}</p>
      <p class="mt-3 text-sm">
        {{ $t('problems.internal_support.guidance') }}
        <Link :href="route('mySupportRequests.index')" class="text-brand underline underline-offset-4">
          {{ $t('problems.internal_support.link') }}
        </Link>
      </p>
    </template>

    <template #actions>
      <Button v-if="canCreate && !isDeleted" as-child variant="brand" size="lg">
        <Link :href="route('problems.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja problema') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item, actions: recordActions }">
      <article class="flex min-h-14 items-center justify-between gap-4 px-3 py-2.5 sm:px-4">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <Link
              v-if="!isDeleted"
              :href="route('problems.show', item.id)"
              data-collection-open
              class="truncate font-medium hover:text-brand"
            >
              {{ localizedTitle(item) }}
            </Link>
            <span v-else class="truncate font-medium text-muted-foreground">{{ localizedTitle(item) }}</span>
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
        <CollectionRowActions :spotlight="item === source?.items.value[0]" :actions="recordActions" @select="key => selectRowAction(key, item)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'title'" class="min-w-0">
        <Link
          v-if="!isDeleted"
          :href="route('problems.show', item.id)"
          data-collection-open
          class="block truncate font-medium hover:text-brand"
        >
          {{ localizedTitle(item) }}
        </Link>
        <span v-else class="block truncate font-medium text-muted-foreground">{{ localizedTitle(item) }}</span>
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

    </template>

    <template #preview="{ item, actions: recordActions }">
      <ProblemPreview :key="item.id" :problem="item" :trashed="isDeleted">
        <template #actions>
          <CollectionRowActions :actions="recordActions" full-labels class="flex-wrap" @select="key => selectRowAction(key, item)" />
        </template>
      </ProblemPreview>
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
import { ChevronRight, Pencil, Plus, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import CollectionRowActions, { type CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import ProblemPreview from '@/Components/Problems/ProblemPreview.vue';
import { ConfirmDialog, EmptyState, StatusBadge } from '@/Components/Patterns';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ProblemIcon } from '@/Components/icons';
import { useDatabaseCollectionSource, type DatabaseFacetDefinition } from '@/Composables/useCollectionSource';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { problemStatuses, type ProblemStatus } from '@/Constants/statuses';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  data: ProblemRow[];
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
const canForceDelete = computed(() => Boolean(usePage().props.auth?.can?.forceDelete?.problem));

/** The server decides per row: editing is scoped to the problem's padalinys. */
type ProblemRow = App.Entities.Problem & { can_update?: boolean };

const rowActions = (item: ProblemRow): CollectionRowAction[] => isDeleted.value
  ? [
      { key: 'restore', label: $t('Atkurti'), icon: RotateCcw, primary: !canForceDelete.value },
      ...(canForceDelete.value ? [{ key: 'forceDelete', label: $t('Ištrinti visam laikui'), icon: Trash2, destructive: true }] : []),
    ]
  : [
      ...(item.can_update ? [{ key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil, href: route('problems.edit', item.id) }] : []),
      { key: 'open', label: $t('Atidaryti'), icon: ChevronRight, role: 'open' as const, href: route('problems.show', item.id) },
    ];

function selectRowAction(key: string, item: App.Entities.Problem): void {
  if (key === 'restore') restoreProblem(item);
  if (key === 'forceDelete') targetProblemToForceDelete.value = item;
}

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

const source = useDatabaseCollectionSource<ProblemRow>({
  endpoint: route('api.v1.admin.problems.index'),
  initial: {
    items: props.data,
    total: props.meta.total,
    perPage: props.meta.per_page,
    currentPage: props.meta.current_page,
    lastPage: props.meta.last_page,
  },
  defaultSort: 'occurred_at:desc',
  sortOptions: () => [
    { value: 'occurred_at:desc', label: $t('Naujausios problemos') },
    { value: 'occurred_at:asc', label: $t('Seniausios problemos') },
    { value: 'title:asc', label: $t('Pagal pavadinimą (A–Z)') },
    { value: 'title:desc', label: $t('Pagal pavadinimą (Z–A)') },
    { value: 'status:asc', label: $t('Pagal būseną (A–Z)') },
    { value: 'status:desc', label: $t('Pagal būseną (Z–A)') },
    { value: 'resolved_at:desc', label: $t('Neseniai išspręstos') },
    { value: 'resolved_at:asc', label: $t('Seniausiai išspręstos') },
  ],
  preserveUrlKeys: ['showDeleted'],
  facets: facets.value,
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('entities.problem.title'), sortField: 'title' },
  { key: 'status', label: $t('entities.problem.status'), class: 'w-32', sortField: 'status' },
  { key: 'occurred_at', label: $t('entities.problem.occurred_at'), class: 'w-32', sortField: 'occurred_at' },
  { key: 'resolved_at', label: $t('entities.problem.resolved_at'), class: 'w-32', sortField: 'resolved_at' },
  { key: 'responsible_user', label: $t('entities.problem.responsible_user'), class: 'w-44' },
  { key: 'categories', label: $t('entities.problem.categories'), class: 'w-40' },
  { key: 'tenant', label: $tChoice('entities.tenant.model', 1), class: 'w-24' },
]);

const asList = (value: unknown): string[] => (Array.isArray(value) ? value.map(String) : value ? [String(value)] : []);

const quickFilters = computed<CollectionQuickFilter[]>(() => {
  const filters = source.filters.value;
  const statuses = asList(filters.status);
  const tenantIds = asList(filters['tenant.id']);
  const createdBy = asList(filters.created_by);
  const responsible = asList(filters.responsible_user_id);

  const isActiveFilterOn = statuses.length === 2 && statuses.includes('open') && statuses.includes('in_progress');
  const isMineTenantOn = myTenantIds.value.length > 0
    && tenantIds.length === myTenantIds.value.length
    && myTenantIds.value.every(id => tenantIds.includes(String(id)));
  const isCreatedByMeOn = Boolean(userId.value && createdBy.length === 1 && createdBy[0] === String(userId.value));
  const isAssignedToMeOn = Boolean(userId.value && responsible.length === 1 && responsible[0] === String(userId.value));

  return [
    { id: 'active', label: $t('Atviros ir vykdomos'), active: isActiveFilterOn },
    ...(myTenantIds.value.length > 0 ? [{ id: 'mine_tenant', label: $t('Mano padalinio'), active: isMineTenantOn }] : []),
    ...(userId.value
      ? [
          { id: 'assigned_to_me', label: $t('Man priskirtos'), active: isAssignedToMeOn },
          { id: 'created_by_me', label: $t('Mano sukurtos'), active: isCreatedByMeOn },
        ]
      : []),
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
  else if (id === 'assigned_to_me') {
    source.setFilter('responsible_user_id', active || !userId.value ? undefined : [String(userId.value)]);
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
