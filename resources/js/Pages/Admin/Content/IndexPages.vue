<template>
  <CollectionPage
    :source
    collection="pages"
    entity-type="page"
    :eyebrow
    :title="$t('Puslapiai')"
    :lead="isTrash
      ? $t('Ištrinti svetainės puslapiai. Atkurk tai, ko dar reikia.')
      : $t('Visi svetainės puslapiai vienoje vietoje. Filtruok pagal padalinį ar kalbą ir redaguok turinį.')"
    default-view="table"
    :item-key="pageKey"
    :columns
    :trash="{ count: deletedCount, active: isTrash }"
    :search-placeholder="$t('Ieškoti pagal pavadinimą ar adresą')"
  >
    <template #actions>
      <DropdownMenu v-if="tenantOptions.length > 0 && !isTrash">
        <DropdownMenuTrigger as-child>
          <Button variant="outline" size="lg">
            <House aria-hidden="true" />
            {{ $t('Pagrindinis puslapis') }}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem v-for="tenant in tenantOptions" :key="tenant.id" as-child>
            <Link :href="route('tenants.editMainPage', { tenant: tenant.id })">
              {{ tenant.shortname }}
            </Link>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('pages.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas puslapis') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <CollectionPrimaryCell :title="item.title" :href="isTrash ? undefined : route('pages.edit', item.id)" :sub="item.permalink" mono />
            <StatusBadge v-if="item.is_active === false" :status="contentStatuses.draft" class="shrink-0" />
          </div>
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <span>{{ languageLabel(item.lang) }}</span>
            <span v-if="item.tenant_name" aria-hidden="true" class="text-border">·</span>
            <span v-if="item.tenant_name">{{ item.tenant_name }}</span>
            <span aria-hidden="true" class="text-border">·</span>
            <span class="tabular-nums">{{ dateOf(item) }}</span>
          </p>
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'title'"
        :title="item.title"
        :href="isTrash ? undefined : route('pages.edit', item.id)"
        :sub="item.permalink"
        mono
      />
      <span v-else-if="column.key === 'tenant'" class="block max-w-40 truncate text-muted-foreground" :title="item.tenant_name">{{ item.tenant_name ?? '—' }}</span>
      <span v-else-if="column.key === 'lang'" class="text-muted-foreground">{{ languageLabel(item.lang) }}</span>
      <StatusBadge v-else-if="column.key === 'status' && item.is_active === false" :status="contentStatuses.draft" />
      <span v-else-if="column.key === 'date'" class="tabular-nums text-muted-foreground">{{ dateOf(item) }}</span>
      <CollectionRowActions
        v-else-if="column.key === 'actions'"
        :actions="actionsFor(item)"
        @select="key => actions.select(key, item.force_delete_blocked_reason)"
      />
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="PageIcon"
        :title="isTrash ? $t('Ištrintų puslapių nėra') : $t('Puslapių dar nėra')"
        :description="isTrash ? undefined : $t('Puslapis – tai ilgesnis, rečiau keičiamas turinys: apie mus, kontaktai, taisyklės.')"
        :action-label="canCreate && !isTrash ? $t('Naujas puslapis') : undefined"
        :action-href="canCreate && !isTrash ? route('pages.create') : undefined"
      />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { House, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { PageIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { contentStatuses } from '@/Constants/statuses';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import {
  isTrashView,
  useTrashAwareSource,
  useTrashCollectionSource,
  useTypesenseCollectionSource,
} from '@/Composables/useCollectionSource';
import type { PageSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

type PageRow = PageSearchResult & {
  created_at?: number;
  deleted_at?: string | null;
  force_delete_blocked_reason?: string | null;
};

defineProps<{
  /** Soft-deleted pages the viewer could restore. */
  deletedCount: number;
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.page));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.page));
const tenantOptions = computed(() => page.props.auth?.user?.tenants ?? []);

const eyebrow = computed(() => `${$t('shell.workspaces.svetaine.title')} · ${$t('shell.sections.puslapiai')}`);

const source = useTrashAwareSource<PageRow>(
  () => useTypesenseCollectionSource<PageRow>({ collection: 'pages', preserveUrlKeys: ['view', 'item'] }),
  () => useTrashCollectionSource<PageRow>('pages'),
);

const actions = useCollectionRecordActions({
  routePrefix: 'pages',
  canDelete: () => canCreate.value,
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});

const pageKey = (item: PageRow) => String(item.id);
const actionsFor = (item: PageRow) => actions.rowActions(item, item.title, isTrash);

const languageLabel = (lang?: string) => (lang === 'en' ? 'English' : 'Lietuvių');

function dateOf(item: PageRow): string {
  if (isTrash && item.deleted_at) {
    return formatDate(item.deleted_at);
  }

  return item.created_at ? formatDate(new Date(item.created_at * 1000)) : '—';
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Puslapis'), sortField: 'title' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-40' },
  { key: 'lang', label: $t('Kalba'), class: 'w-28' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  { key: 'date', label: isTrash ? $t('Ištrinta') : $t('Sukurta'), class: 'w-32', sortField: isTrash ? 'deleted_at' : 'created_at' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
