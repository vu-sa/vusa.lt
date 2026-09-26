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
          <CollectionPrimaryCell :title="item.title || $t('Be pavadinimo')" :href="route('agendaItems.edit', item.id)" :sub="item.meeting_title" />
          <p v-if="item.institution_name_lt || item.institution_name_en" class="mt-1 text-xs text-muted-foreground">
            {{ item.institution_name_lt || item.institution_name_en }}
          </p>
        </div>
        <CollectionRowActions :actions="openActions(item)" />
      </div>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'title'" :title="item.title || $t('Be pavadinimo')" :href="route('agendaItems.edit', item.id)" />
      <span v-else-if="column.key === 'meeting'">{{ item.meeting_title || '—' }}</span>
      <span v-else-if="column.key === 'institution'">{{ item.institution_name_lt || item.institution_name_en || '—' }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="openActions(item)" />
    </template>

    <template #preview="{ item }">
      <div class="flex flex-col gap-4 p-5">
        <h2 class="text-lg font-semibold">
          {{ item.title || $t('Be pavadinimo') }}
        </h2>
        <p v-if="item.meeting_title" class="text-sm text-muted-foreground">
          {{ item.meeting_title }}
        </p>
        <p v-if="item.description" class="whitespace-pre-line text-sm text-muted-foreground">
          {{ item.description }}
        </p>
        <Button as-child variant="outline" voice="sentence">
          <Link :href="route('agendaItems.edit', item.id)">
            {{ $t('Atidaryti') }}
          </Link>
        </Button>
      </div>
    </template>

    <template #empty>
      <EmptyState mode="empty" :icon="AgendaItemIcon" :title="$t('Nėra darbotvarkės punktų')" />
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight } from 'lucide-vue-next';

import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import { AgendaItemIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
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
