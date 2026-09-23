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
    table-fixed
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

    <template #row="{ item, view }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell
            :title="item.title"
            :href="isTrash ? undefined : route('pages.edit', item.id)"
            :clickable="isTrash && view === 'preview'"
          />
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <span>{{ languageLabel(item.lang) }}</span>
            <span v-if="item.tenant_shortname" aria-hidden="true" class="text-border">·</span>
            <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
            <span aria-hidden="true" class="text-border">·</span>
            <span class="tabular-nums">{{ dateOf(item) }}</span>
          </p>
        </div>
        <div class="flex shrink-0 items-center gap-3">
          <CollectionStatusMenu
            :status="pageStatus(item)"
            :model-value="publishing.statusValue(item)"
            :options="publishing.statusOptions"
            :editable="canBulkEdit"
            @update:model-value="value => publishing.setPublished([String(item.id)], value === 'published')"
          />
          <CollectionRowActions
            v-if="view !== 'preview'"
            :actions="actionsFor(item)"
            @select="key => actions.select(key, item.force_delete_blocked_reason)"
          />
        </div>
      </article>
    </template>

    <template #preview="{ item }">
      <section class="flex flex-col gap-5 p-5">
        <div>
          <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
              {{ $t('Puslapis') }}
            </p>
            <CollectionStatusMenu
              :status="pageStatus(item)"
              :model-value="publishing.statusValue(item)"
              :options="publishing.statusOptions"
              :editable="canBulkEdit"
              @update:model-value="value => publishing.setPublished([String(item.id)], value === 'published')"
            />
          </div>
          <Link
            v-if="!isTrash"
            :href="route('pages.edit', item.id)"
            class="mt-1.5 block text-lg font-semibold leading-snug hover:text-brand"
          >
            {{ item.title }}
          </Link>
          <h2 v-else class="mt-1.5 text-lg font-semibold leading-snug">
            {{ item.title }}
          </h2>
          <p v-if="item.meta_description" class="mt-3 text-sm text-muted-foreground">
            {{ item.meta_description }}
          </p>
        </div>
        <dl class="grid gap-3 border-t border-border pt-4 text-sm">
          <div>
            <dt class="text-xs text-muted-foreground">
              {{ $t('Padalinys') }}
            </dt>
            <dd class="mt-1">
              {{ item.tenant_shortname ?? '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs text-muted-foreground">
              {{ $t('Kalba') }}
            </dt>
            <dd class="mt-1">
              {{ languageLabel(item.lang) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs text-muted-foreground">
              {{ isTrash ? $t('Ištrinta') : $t('Sukurta') }}
            </dt>
            <dd class="mt-1 tabular-nums">
              {{ dateOf(item) }}
            </dd>
          </div>
        </dl>
        <CollectionRowActions
          :actions="previewActionsFor(item)"
          class="flex-wrap border-t border-border pt-4"
          @select="key => actions.select(key, item.force_delete_blocked_reason)"
        />
      </section>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'title'"
        :title="item.title"
        :title-lines="2"
        :href="isTrash ? undefined : route('pages.edit', item.id)"
      />
      <span v-else-if="column.key === 'tenant'" class="block truncate text-muted-foreground" :title="item.tenant_shortname">{{ item.tenant_shortname ?? '—' }}</span>
      <span v-else-if="column.key === 'lang'" class="text-muted-foreground">{{ languageLabel(item.lang) }}</span>
      <component
        :is="item.id === publishing.spotlightId.value ? SpotlightPopover : 'div'"
        v-else-if="column.key === 'status'"
        v-bind="item.id === publishing.spotlightId.value ? publishing.spotlightProps.value : {}"
      >
        <CollectionStatusMenu
          :status="pageStatus(item)"
          :model-value="publishing.statusValue(item)"
          :options="publishing.statusOptions"
          :editable="canBulkEdit"
          @open="publishing.dismissSpotlight"
          @update:model-value="value => publishing.setPublished([String(item.id)], value === 'published')"
        />
      </component>
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
import { Eye, House, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import { pageStatus } from './pageStatus';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import CollectionPublishActions from '@/Components/Collection/CollectionPublishActions.vue';
import CollectionStatusMenu from '@/Components/Collection/CollectionStatusMenu.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { PageIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
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

function previewActionsFor(item: PageRow): CollectionRowAction[] {
  const rowActions = actionsFor(item);
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
  ].map(action => ({ ...action, labelled: true }));
}

// Bulk actions and the status menu share one gate with delete; the server re-checks every record.
const canBulkEdit = computed(() => canCreate.value && !isTrash);

const publishing = useCollectionPublishing<PageRow>({
  routePrefix: 'pages',
  source,
  isPublished: item => Boolean(item.is_active),
  patchFor: published => ({ is_active: published }),
  enabled: () => canBulkEdit.value,
});

const languageLabel = (lang?: string) => (lang === 'en' ? 'English' : 'Lietuvių');

function dateOf(item: PageRow): string {
  if (isTrash && item.deleted_at) {
    return formatDate(item.deleted_at);
  }

  return item.created_at ? formatDate(new Date(item.created_at * 1000)) : '—';
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Puslapis'), class: 'w-56', sortField: 'title' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-28' },
  { key: 'lang', label: $t('Kalba'), class: 'w-24' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  { key: 'date', label: isTrash ? $t('Ištrinta') : $t('Sukurta'), class: 'w-28', sortField: isTrash ? 'deleted_at' : 'created_at' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-44 text-right', pinned: true },
]);
</script>
