<template>
  <CollectionPage
    :row-actions="item => openActions(item)"
    :source
    collection="agenda_items"
    entity-type="agenda_item"
    :eyebrow="`${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.darbotvarkes_klausimai')}`"
    :title="$t('Darbotvarkės klausimai')"
    :lead="$t('Visų posėdžių darbotvarkės klausimai vienoje vietoje.')"
    default-view="rows"
    :item-key="item => String(item.id)"
    :columns
    @row-action="(_item, key) => collectionActions.select(key)"
    :search-placeholder="$t('Ieškoti darbotvarkės klausimų')"
  >
    <template #row="{ item, actions: recordActions }">
      <div class="flex items-center gap-4 px-4 py-4">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :match="item._searchMatch" :title-matches="item._searchTitleMatches" :title="item.title || $t('Be pavadinimo')" :href="route('agendaItems.show', item.id)" :sub="item.meeting_title" />
          <p v-if="getSearchTranslation(item, 'institution_name')" class="mt-1 text-xs text-muted-foreground">
            {{ getSearchTranslation(item, 'institution_name') }}
          </p>
        </div>
        <CollectionRowActions :spotlight="item === source?.items.value[0]" :actions="recordActions" @select="key => collectionActions.select(key)" />
      </div>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'title'" :match="item._searchMatch" :title-matches="item._searchTitleMatches" :title="item.title || $t('Be pavadinimo')" :href="route('agendaItems.show', item.id)" />
      <span v-else-if="column.key === 'meeting'">{{ item.meeting_title || '—' }}</span>
      <span v-else-if="column.key === 'institution'">{{ getSearchTranslation(item, 'institution_name') || '—' }}</span>
    </template>

    <template #preview="{ item, actions: recordActions }">
      <AgendaItemPreview :key="item.id" :item>
        <template #actions>
          <CollectionRowActions :actions="recordActions" full-labels class="flex-wrap" @select="key => collectionActions.select(key)" />
        </template>
      </AgendaItemPreview>
    </template>

    <template #empty>
      <EmptyState mode="empty" :icon="AgendaItemIcon" :title="$t('Nėra darbotvarkės punktų')" />
    </template>
  </CollectionPage>
  <CollectionConfirmAction :dialog="collectionActions.dialog.value" @confirm="collectionActions.confirm" @cancel="collectionActions.pending.value = null" />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Trash2, Pencil, ArrowUpRight } from '@lucide/vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { useRowAbilities } from '@/Composables/useRowAbilities';
import { getSearchTranslation } from '@/Composables/useTranslatedTitle';
import AgendaItemPreview from '@/Components/AgendaItems/AgendaItemPreview.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { AgendaItemIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import type { AgendaItemSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  /** The user's own padaliniai: a first visit starts filtered to them. */
  defaultTenantShortnames: string[];
}>();

const source = useTypesenseCollectionSource<AgendaItemSearchResult>({
  collection: 'agenda_items',
  preserveUrlKeys: ['view', 'item'],
  defaultFilters: { tenant_shortnames: props.defaultTenantShortnames },
});

const columns: CollectionColumn[] = [
  { key: 'title', label: $t('Darbotvarkės klausimas'), sortField: 'title' },
  { key: 'meeting', label: $t('Posėdis') },
  { key: 'institution', label: $t('Institucija') },

];

const openActions = (item: AgendaItemSearchResult) => [
  { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, href: route('agendaItems.show', item.id), role: 'open' as const },
  ...(rowAbilities.can(item.id, 'update') ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil, role: 'edit' as const, href: route('agendaItems.edit', { agendaItem: item.id, action: 'edit' }) }] : []),
  ...(rowAbilities.can(item.id, 'delete') ? [{ key: `delete:${item.id}:${item.title}`, label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
];

const rowAbilities = useRowAbilities('agenda_items', () => source.items.value.map(item => item.id));
const collectionActions = useCollectionRecordActions({
  source,
  deletePermanently: true,
  routePrefix: 'agendaItems',
  onMutated: id => rowAbilities.invalidate([id]),
});
</script>
