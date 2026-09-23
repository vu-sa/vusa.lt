<template>
  <CollectionPage
    :source
    collection="institutions"
    entity-type="institution"
    :eyebrow
    :title="$t('Institucijos')"
    :lead="$t('VU SA ir VU organai, kuriuose dirba studentų atstovai.')"
    default-view="rows"
    :item-key="institutionKey"
    :columns
    :search-placeholder="$t('Ieškoti institucijų')"
    :trash="{ count: deletedCount, active: isTrash }"
  >
    <template #actions>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('institutions.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja institucija') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center" data-slot="institution-collection-row">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="nameOf(item)" :href="isTrash ? undefined : route('institutions.show', item.id)" :sub="item.alias" mono />
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <span v-if="item.tenant_shortname">{{ item.tenant_shortname }}</span>
            <span v-if="item.type_titles?.length">{{ item.type_titles.join(', ') }}</span>
            <span v-if="item.current_user_names?.length">
              {{ $t('Narių: :count', { count: String(item.current_user_names.length) }) }}
            </span>
          </p>
        </div>
        <CollectionRowActions v-if="isTrash" :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
      </div>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'name'"
        :title="nameOf(item)"
        :href="isTrash ? undefined : route('institutions.show', item.id)"
        :sub="item.alias"
        mono
      />
      <template v-else-if="column.key === 'tenant'">
        {{ item.tenant_shortname ?? '—' }}
      </template>
      <template v-else-if="column.key === 'types'">
        {{ item.type_titles?.join(', ') || '—' }}
      </template>
      <span v-else-if="column.key === 'members'" class="tabular-nums">
        {{ item.current_user_names?.length ?? 0 }}
      </span>
      <CollectionRowActions
        v-else-if="column.key === 'actions'"
        :actions="actionsFor(item)"
        @select="key => actions.select(key, item.force_delete_blocked_reason)"
      />
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="InstitutionIcon"
        :title="isTrash ? $t('Ištrintų institucijų nėra') : $t('Institucijų dar nėra')"
        :description="isTrash ? undefined : $t('Čia atsiras VU SA ir VU organai. Sukūrusi instituciją, jos puslapyje pridėsi pareigybes ir narius.')"
        :action-label="canCreate && !isTrash ? $t('Nauja institucija') : undefined"
        :action-href="canCreate && !isTrash ? route('institutions.create') : undefined"
      />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { InstitutionIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import {
  isTrashView,
  useTrashAwareSource,
  useTrashCollectionSource,
  useTypesenseCollectionSource,
} from '@/Composables/useCollectionSource';
import type { InstitutionSearchResult } from '@/Shared/Search/types';

type InstitutionRow = InstitutionSearchResult & { force_delete_blocked_reason?: string | null };

defineProps<{
  /** Soft-deleted institutions the viewer could restore. */
  deletedCount: number;
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.institution));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.institution));

const eyebrow = computed(() => `${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.institucijos')}`);

const source = useTrashAwareSource<InstitutionRow>(
  () => useTypesenseCollectionSource<InstitutionRow>({ collection: 'institutions', preserveUrlKeys: ['view', 'item'] }),
  () => useTrashCollectionSource<InstitutionRow>('institutions'),
);

// Live institutions are edited on their record page; the list only acts on the trash.
const actions = useCollectionRecordActions({
  routePrefix: 'institutions',
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});

const institutionKey = (institution: InstitutionRow) => String(institution.id);
const nameOf = (institution: InstitutionRow) => institution.name_lt || institution.name_en || $t('Be pavadinimo');
const actionsFor = (institution: InstitutionRow) => actions.rowActions(institution, nameOf(institution), true);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Institucija') },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'types', label: $t('Tipas') },
  { key: 'members', label: $t('Nariai'), class: 'w-24' },
  ...(isTrash ? [{ key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true }] : []),
]);
</script>
