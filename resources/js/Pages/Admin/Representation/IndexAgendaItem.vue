<template>
  <CollectionPage
    :source
    collection="agenda_items"
    entity-type="agenda_item"
    :eyebrow="`${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.darbotvarkes_klausimai')}`"
    :title="$t('Darbotvarkės klausimai')"
    :lead="$t('Visų posėdžių darbotvarkės klausimai vienoje vietoje.')"
    default-view="rows"
    :item-key="item => String(item.id)"
    :columns
    :search-placeholder="$t('Ieškoti darbotvarkės klausimų')"
  >
    <template #row="{ item }">
      <div class="flex items-center gap-4 px-4 py-4">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :match="item._searchMatch" :title-matches="item._searchTitleMatches" :title="item.title || $t('Be pavadinimo')" :href="route('agendaItems.edit', item.id)" :sub="item.meeting_title" />
          <p v-if="getSearchTranslation(item, 'institution_name')" class="mt-1 text-xs text-muted-foreground">
            {{ getSearchTranslation(item, 'institution_name') }}
          </p>
        </div>
        <CollectionRowActions :actions="openActions(item)" />
      </div>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'title'" :match="item._searchMatch" :title-matches="item._searchTitleMatches" :title="item.title || $t('Be pavadinimo')" :href="route('agendaItems.edit', item.id)" />
      <span v-else-if="column.key === 'meeting'">{{ item.meeting_title || '—' }}</span>
      <span v-else-if="column.key === 'institution'">{{ getSearchTranslation(item, 'institution_name') || '—' }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="openActions(item)" />
    </template>

    <template #preview="{ item }">
      <AgendaItemPreview :key="item.id" :item />
    </template>

    <template #empty>
      <EmptyState mode="empty" :icon="AgendaItemIcon" :title="$t('Nėra darbotvarkės punktų')" />
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { getSearchTranslation } from '@/Composables/useTranslatedTitle';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight } from '@lucide/vue';

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
  { key: 'title', label: $t('Darbotvarkės klausimas') },
  { key: 'meeting', label: $t('Posėdis') },
  { key: 'institution', label: $t('Institucija') },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
];

const openActions = (item: AgendaItemSearchResult) => [
  { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, href: route('agendaItems.edit', item.id), labelled: true },
];
</script>
