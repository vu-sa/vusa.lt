<template>
  <CollectionPage
    :row-actions="item => actionsFor(item)"
    :source
    collection="news"
    :eyebrow
    :title="$t('Naujienos')"
    :lead="isTrash
      ? $t('Ištrintos naujienos. Atkurk tai, ko dar reikia.')
      : $t('Visos padalinių naujienos ir jų juodraščiai. Filtruok pagal padalinį, kalbą ar būseną.')"
    default-view="table"
    :item-key="newsKey"
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[56rem]"
    @row-action="(item, key) => actions.select(key, item.force_delete_blocked_reason)"
    :selectable="canBulkEdit"
    :trash="{ count: deletedCount, active: isTrash }"
    :search-placeholder="$t('Ieškoti naujienų')"
  >
    <template #actions>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('news.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja naujiena') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow :actions="view === 'preview' ? [] : recordActions" :spotlight="item === source?.items.value[0]" class="px-3 py-2.5 sm:px-4" @select="key => actions.select(key, item.force_delete_blocked_reason)">
        <template v-if="view !== 'preview'" #lead>
          <CollectionStatusIcon :status="newsStatus(item)" />
        </template>
        <div class="flex items-start justify-between gap-3">
          <CollectionPrimaryCell :match="item._searchMatch" :title-matches="item._searchTitleMatches" :title="item.title" :href="isTrash ? undefined : route('news.edit', item.id)" :sub="stripHtml(item.short)" />
        </div>
        <p class="mt-0.5 text-xs text-muted-foreground">{{ [dateOf(item), item.tenant_shortname, languageLabel(item.lang)].filter(Boolean).join(' · ') }}</p>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'title'" :match="item._searchMatch"
        :title-matches="item._searchTitleMatches"
        :title="item.title"
        title-lines="wrap"
        :href="isTrash ? undefined : route('news.edit', item.id)"
      />
      <span v-else-if="column.key === 'tenant'" class="text-muted-foreground">{{ item.tenant_shortname ?? '—' }}</span>
      <span v-else-if="column.key === 'lang'" class="text-muted-foreground">{{ languageLabel(item.lang) }}</span>
      <div v-else-if="column.key === 'status'">
        <CollectionStatusMenu
          :status="newsStatus(item)"
          :model-value="publishing.statusValue(item)"
          :options="publishing.statusOptions"
          :editable="canBulkEdit"
          @update:model-value="value => publishing.setPublished([String(item.id)], value === 'published')"
        />
      </div>
      <span v-else-if="column.key === 'date'" class="tabular-nums text-muted-foreground">{{ dateOf(item) }}</span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
    </template>

    <template #preview="{ item, actions: recordActions }">
      <NewsPreview
        :key="item.id"
        :news="item"
        :trashed="isTrash"
        :date="dateOf(item)"
        :date-label="isTrash ? $t('Ištrinta') : undefined"
      >
        <template v-if="!isTrash" #status>
          <CollectionStatusMenu
            :status="newsStatus(item)"
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
      </NewsPreview>
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
        :icon="NewsIcon"
        :title="isTrash ? $t('Ištrintų naujienų nėra') : $t('Naujienų dar nėra')"
        :description="isTrash ? undefined : $t('Parašyk pirmą naujieną – ji atsiras padalinio puslapyje, kai ją paskelbsi.')"
        :action-label="canCreate && !isTrash ? $t('Nauja naujiena') : undefined"
        :action-href="canCreate && !isTrash ? route('news.create') : undefined"
      />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
  <CollectionConfirmAction :dialog="publishing.deleteDialog.value" @confirm="publishing.confirmDelete" @cancel="publishing.pendingDelete.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionPublishActions from '@/Components/Collection/CollectionPublishActions.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import CollectionStatusIcon from '@/Components/Collection/CollectionStatusIcon.vue';
import CollectionStatusMenu from '@/Components/Collection/CollectionStatusMenu.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { NewsIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import NewsPreview from '@/Components/News/NewsPreview.vue';
import { newsStatus } from '@/Components/News/newsStatus';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useCollectionPublishing } from '@/Composables/useCollectionPublishing';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import {
  isTrashView,
  useTrashAwareSource,
  useTrashCollectionSource,
  useTypesenseCollectionSource,
} from '@/Composables/useCollectionSource';
import { stripHtml } from '@/Features/Admin/AdminSearch/Utils/html';
import type { NewsSearchResult } from '@/Shared/Search/types';
import { formatDate, formatRecordDate } from '@/Utils/dateTime';

type NewsRow = NewsSearchResult & {
  tenant_shortname?: string;
  deleted_at?: string | null;
  force_delete_blocked_reason?: string | null;
};

defineProps<{
  /** Soft-deleted news the viewer could restore. */
  deletedCount: number;
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.news));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.news));

const eyebrow = computed(() => $t('shell.workspaces.svetaine.title'));

const source = useTrashAwareSource<NewsRow>(
  () => useTypesenseCollectionSource<NewsRow>({ collection: 'news', preserveUrlKeys: ['view', 'item'] }),
  () => useTrashCollectionSource<NewsRow>('news'),
);

const actions = useCollectionRecordActions({
  source,
  routePrefix: 'news',
  canDuplicate: () => canCreate.value,
  canDelete: () => canCreate.value,
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});

// Bulk actions and the status menu share one gate with delete; the server re-checks every record.
const canBulkEdit = computed(() => canCreate.value && !isTrash);

const publishing = useCollectionPublishing<NewsRow>({
  routePrefix: 'news',
  source,
  isPublished: item => !item.draft,
  patchFor: published => ({ draft: !published }),
});

const newsKey = (item: NewsRow) => String(item.id);
const actionsFor = (item: NewsRow) => actions.rowActions(item, item.title, isTrash);

const languageLabel = (lang?: string) => (lang === 'en' ? 'English' : 'Lietuvių');

function dateOf(item: NewsRow): string {
  if (isTrash && item.deleted_at) {
    return formatDate(item.deleted_at);
  }

  return item.publish_time ? formatDate(new Date(item.publish_time * 1000)) : '—';
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Naujiena'), class: 'w-72', sortField: 'title' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'lang', label: $t('Kalba'), class: 'w-28' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  { key: 'date', label: isTrash ? $t('Ištrinta') : $t('Paskelbta'), class: 'w-32', sortField: isTrash ? 'deleted_at' : 'publish_time' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
]);
</script>
