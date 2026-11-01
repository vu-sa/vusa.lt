<template>
  <CollectionPage
    :row-actions="item => openActions(item)"
    :source
    collection="agenda_items"
    :eyebrow="$t('shell.workspaces.atstovavimas.title')"
    :title="$t('Darbotvarkės klausimai')"
    :lead="$t('Visų posėdžių darbotvarkės klausimai vienoje vietoje.')"
    default-view="rows"
    :item-key="item => String(item.id)"
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[72rem]"
    @row-action="(_item, key) => collectionActions.select(key)"
    :search-placeholder="$t('Ieškoti darbotvarkės klausimų')"
  >
    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow :spotlight="item === source?.items.value[0]" :actions="view === 'preview' ? [] : recordActions" @select="key => collectionActions.select(key)" class="px-3 py-3 sm:px-4">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :match="item._searchMatch" :title-matches="item._searchTitleMatches" :title="item.title || $t('Be pavadinimo')" :href="route('agendaItems.show', item.id)" :sub="[item.meeting_title, getSearchTranslation(item, 'institution_name')].filter(Boolean).join(' · ')" />
          <AgendaItemTypeMark class="mt-1 text-xs" :type="item.type" />
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'title'" title-lines="wrap" :match="item._searchMatch" :title-matches="item._searchTitleMatches" :title="item.title || $t('Be pavadinimo')" :href="route('agendaItems.show', item.id)" />
      <AgendaItemTypeMark v-else-if="column.key === 'type'" :type="item.type" />
      <span v-else-if="column.key === 'meeting'">{{ item.meeting_title || '—' }}</span>
      <span v-else-if="column.key === 'institution'">{{ getSearchTranslation(item, 'institution_name') || '—' }}</span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
    </template>

    <template #preview="{ item, actions: recordActions }">
      <AgendaItemPreview :key="item.id" :item>
        <template #actions>
          <CollectionRowActions :actions="recordActions" emphasis full-labels class="flex-wrap" @select="key => collectionActions.select(key)" />
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
import AgendaItemTypeMark from '@/Components/AgendaItems/AgendaItemTypeMark.vue';
import { agendaItemTypeOptions } from '@/Components/AgendaItems/agendaItemTypes';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { AgendaItemIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import type { AgendaItemSearchResult } from '@/Shared/Search/types';
import { formatRecordDate } from '@/Utils/dateTime';

const props = defineProps<{
  /** The user's own padaliniai: a first visit starts filtered to them. */
  defaultTenantShortnames: string[];
}>();

const source = useTypesenseCollectionSource<AgendaItemSearchResult>({
  collection: 'agenda_items',
  preserveUrlKeys: ['view', 'item'],
  defaultFilters: { tenant_shortnames: props.defaultTenantShortnames },
  valueLabel: (field, value) => (field === 'type' ? agendaItemTypeOptions().find(option => option.value === value)?.label : undefined),
});

const columns: CollectionColumn[] = [
  { key: 'title', label: $t('Darbotvarkės klausimas'), class: 'w-80', sortField: 'title' },
  { key: 'type', label: $t('Tipas'), class: 'w-36' },
  { key: 'meeting', label: $t('Posėdis') },
  { key: 'institution', label: $t('Institucija'), class: 'w-48' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', defaultHidden: true, sortField: 'created_at' },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', defaultHidden: true, sortField: 'updated_at' },
];

const openActions = (item: AgendaItemSearchResult) => [
  { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, href: route('agendaItems.show', item.id), role: 'open' as const, primary: true },
  ...(rowAbilities.can(item.id, 'update') ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil, role: 'edit' as const, menuOnly: true, href: route('agendaItems.edit', { agendaItem: item.id, action: 'edit' }) }] : []),
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
