<template>
  <CollectionPage
    :source
    collection="news"
    entity-type="news"
    :eyebrow
    :title="$t('Naujienos')"
    :lead="isTrash
      ? $t('Ištrintos naujienos. Atkurk tai, ko dar reikia.')
      : $t('Visos padalinių naujienos ir jų juodraščiai. Filtruok pagal padalinį, kalbą ar būseną.')"
    default-view="table"
    :item-key="newsKey"
    :columns
    table-fixed
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

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <CollectionPrimaryCell :title="item.title" :href="isTrash ? undefined : route('news.edit', item.id)" :sub="item.short" />
            <CollectionStatusMenu
              v-if="!isTrash"
              :status="newsStatus(item)"
              :model-value="publishing.statusValue(item)"
              :options="publishing.statusOptions"
              :editable="canBulkEdit"
              @update:model-value="value => publishing.setPublished([String(item.id)], value === 'published')"
            />
          </div>
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <span class="tabular-nums">{{ dateOf(item) }}</span>
            <span v-if="item.tenant_shortname" aria-hidden="true" class="text-border">·</span>
            <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
            <span aria-hidden="true" class="text-border">·</span>
            <span>{{ languageLabel(item.lang) }}</span>
          </p>
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'title'"
        :title="item.title"
        :title-lines="2"
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
      <CollectionRowActions
        v-else-if="column.key === 'actions'"
        :actions="actionsFor(item)"
        @select="key => actions.select(key, item.force_delete_blocked_reason)"
      />
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
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import { newsStatus } from './newsStatus';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionPublishActions from '@/Components/Collection/CollectionPublishActions.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import CollectionStatusMenu from '@/Components/Collection/CollectionStatusMenu.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { NewsIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
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
import type { NewsSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

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

const eyebrow = computed(() => `${$t('shell.workspaces.svetaine.title')} · ${$t('shell.sections.naujienos')}`);

const source = useTrashAwareSource<NewsRow>(
  () => useTypesenseCollectionSource<NewsRow>({ collection: 'news', preserveUrlKeys: ['view', 'item'] }),
  () => useTrashCollectionSource<NewsRow>('news'),
);

const actions = useCollectionRecordActions({
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
  { key: 'title', label: $t('Naujiena'), class: 'w-64', sortField: 'title' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'lang', label: $t('Kalba'), class: 'w-28' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  { key: 'date', label: isTrash ? $t('Ištrinta') : $t('Paskelbta'), class: 'w-32', sortField: isTrash ? 'deleted_at' : 'publish_time' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-56 text-right', pinned: true },
]);
</script>
