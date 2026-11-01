<template>
  <CollectionPage
    :row-actions="item => actionsFor(item)"
    :source
    collection="pages"
    :eyebrow
    :title="$t('Puslapiai')"
    :lead="isTrash
      ? $t('Ištrinti svetainės puslapiai. Atkurk tai, ko dar reikia.')
      : $t('Visi svetainės puslapiai vienoje vietoje. Filtruok pagal padalinį ar kalbą ir redaguok turinį.')"
    default-view="table"
    :item-key="pageKey"
    :columns
    table-fixed
    actions-width="w-44"
    :table-min-width="isTrash ? 'min-w-[55rem]' : 'min-w-[48rem]'"
    @row-action="(item, key) => actions.select(key, item.force_delete_blocked_reason)"
    :selectable="canBulkEdit"
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

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow :actions="view === 'preview' ? [] : recordActions" :spotlight="item === source?.items.value[0]" class="px-3 py-2.5 sm:px-4" @select="key => actions.select(key, item.force_delete_blocked_reason)">
        <template v-if="view !== 'preview'" #lead>
          <CollectionStatusIcon :status="pageStatus(item)" />
        </template>
        <div class="flex items-start justify-between gap-3">
          <CollectionPrimaryCell :match="item._searchMatch" :title-matches="item._searchTitleMatches" :title="item.title" :href="isTrash ? undefined : route('pages.edit', item.id)" :clickable="isTrash && view === 'preview'" />
        </div>
        <p class="mt-0.5 text-xs text-muted-foreground">{{ [dateOf(item), item.tenant_shortname, languageLabel(item.lang)].filter(Boolean).join(' · ') }}</p>
      </CollectionRow>
    </template>

    <template #preview="{ item, actions: recordActions }">
      <PagePreview
        :key="item.id"
        :page="item"
        :trashed="isTrash"
        :date="dateOf(item)"
        :date-label="isTrash ? $t('Ištrinta') : $t('Sukurta')"
      >
        <template #status>
          <CollectionStatusMenu
            :status="pageStatus(item)"
            :model-value="publishing.statusValue(item)"
            :options="publishing.statusOptions"
            :editable="canBulkEdit"
            @update:model-value="value => publishing.setPublished([String(item.id)], value === 'published')"
          />
        </template>
        <template #actions>
          <CollectionRowActions
            :actions="recordActions"
            emphasis full-labels
            @select="key => actions.select(key, item.force_delete_blocked_reason)"
          />
        </template>
      </PagePreview>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'title'" :match="item._searchMatch"
        :title-matches="item._searchTitleMatches"
        :title="item.title"
        title-lines="wrap"
        :href="isTrash ? undefined : route('pages.edit', item.id)"
      />
      <span v-else-if="column.key === 'tenant'" class="block truncate text-muted-foreground" :title="item.tenant_shortname">{{ item.tenant_shortname ?? '—' }}</span>
      <span v-else-if="column.key === 'lang'" class="text-muted-foreground">{{ languageLabel(item.lang) }}</span>
      <div v-else-if="column.key === 'status'">
        <CollectionStatusMenu
          :status="pageStatus(item)"
          :model-value="publishing.statusValue(item)"
          :options="publishing.statusOptions"
          :editable="canBulkEdit"
          @update:model-value="value => publishing.setPublished([String(item.id)], value === 'published')"
        />
      </div>
      <span v-else-if="column.key === 'date'" class="tabular-nums text-muted-foreground">{{ dateOf(item) }}</span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
    </template>

    <template #bulk-actions="{ selected, clear }">
      <CollectionPublishActions
        :can-publish="publishing.bulkChoices(selected).publish"
        :can-draft="publishing.bulkChoices(selected).draft"
        @publish="publishing.setPublished(publishing.idsOf(selected), true, clear)"
        @draft="publishing.setPublished(publishing.idsOf(selected), false, clear)"
        @delete="publishing.pendingDelete.value = { ids: publishing.idsOf(selected), clear }"
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
  <CollectionConfirmAction :dialog="publishing.deleteDialog.value" @confirm="publishing.confirmDelete" @cancel="publishing.pendingDelete.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, House, Plus } from '@lucide/vue';
import { computed } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import CollectionPublishActions from '@/Components/Collection/CollectionPublishActions.vue';
import CollectionStatusIcon from '@/Components/Collection/CollectionStatusIcon.vue';
import CollectionStatusMenu from '@/Components/Collection/CollectionStatusMenu.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { PageIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import PagePreview from '@/Components/Pages/PagePreview.vue';
import { pageStatus } from '@/Components/Pages/pageStatus';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { useCollectionPublishing } from '@/Composables/useCollectionPublishing';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import {
  isTrashView,
  useTrashAwareSource,
  useTrashCollectionSource,
  useTypesenseCollectionSource,
} from '@/Composables/useCollectionSource';
import type { PageSearchResult } from '@/Shared/Search/types';
import { formatDate, formatRecordDate } from '@/Utils/dateTime';

type PageRow = PageSearchResult & {
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

const eyebrow = computed(() => $t('shell.workspaces.svetaine.title'));

const source = useTrashAwareSource<PageRow>(
  () => useTypesenseCollectionSource<PageRow>({ collection: 'pages', preserveUrlKeys: ['view', 'item'] }),
  () => useTrashCollectionSource<PageRow>('pages'),
);

const actions = useCollectionRecordActions({
  source,
  routePrefix: 'pages',
  canDelete: () => canCreate.value,
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});

const pageKey = (item: PageRow) => String(item.id);
function actionsFor(item: PageRow): CollectionRowAction[] {
  const rowActions = actions.rowActions(item, item.title, isTrash);
  const viewUrl = !isTrash && item.is_active && item.permalink && item.tenant_id
    ? route('page', {
        subdomain: resolveTenantSubdomain(item.tenant_id),
        lang: item.lang,
        permalink: item.permalink,
      })
    : undefined;

  return [
    ...rowActions.filter(action => action.key.startsWith('edit:')),
    ...(viewUrl ? [{ key: `view:${item.id}`, label: $t('Peržiūrėti'), icon: Eye, href: viewUrl, external: true }] : []),
    ...rowActions.filter(action => !action.key.startsWith('edit:')),
  ];
}

// Bulk actions and the status menu share one gate with delete; the server re-checks every record.
const canBulkEdit = computed(() => canCreate.value && !isTrash);

const publishing = useCollectionPublishing<PageRow>({
  routePrefix: 'pages',
  source,
  isPublished: item => Boolean(item.is_active),
  patchFor: published => ({ is_active: published }),
});

const languageLabel = (lang?: string) => (lang === 'en' ? 'English' : 'Lietuvių');

function dateOf(item: PageRow): string {
  if (isTrash && item.deleted_at) {
    return formatDate(item.deleted_at);
  }

  return formatRecordDate(item.created_at);
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Puslapis'), class: 'w-72', sortField: 'title' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-28' },
  { key: 'lang', label: $t('Kalba'), class: 'w-24' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  ...(isTrash ? [{ key: 'date', label: $t('Ištrinta'), class: 'w-28', sortField: 'deleted_at' }] : []),
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
]);
</script>
