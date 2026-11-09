<template>
  <div data-slot="institution-meetings-collection">
    <EmbeddedCollection
      :source
      collection="institution-meetings"
      :item-key="meetingKey"
      :row-actions="actionsFor"
      :quick-filters
      :columns
      table-fixed
      actions-width="w-44"
      table-min-width="min-w-[56rem]"
      :search-placeholder="$t('Ieškoti posėdžių')"
      @row-action="(_item, key) => actions.select(key)"
      @quick-filter="toggleQuickFilter"
    >
      <template #row="{ item, actions: recordActions }">
        <MeetingCollectionRow :meeting="item" :actions="recordActions" hide-institution @select="key => actions.select(key)" />
      </template>

      <template #cell="{ item, column }">
        <MeetingCollectionCell :item :column hide-institution />
      </template>

      <template #empty>
        <EmptyState
          mode="empty"
          :icon="MeetingIcon"
          :title="$t('Nėra susitikimų')"
          :description="$t('Šiai institucijai dar nėra suplanuota susitikimų.')"
          :action-label="canRecordMeeting ? $t('Suplanuoti susitikimą') : undefined"
          @action="emit('record')"
        />
      </template>
    </EmbeddedCollection>

    <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import EmbeddedCollection from '@/Components/Collection/EmbeddedCollection.vue';
import type { CollectionQuickFilter } from '@/Components/Collection/types';
import { MeetingIcon } from '@/Components/icons';
import MeetingCollectionCell from '@/Components/Meetings/MeetingCollectionCell.vue';
import MeetingCollectionRow from '@/Components/Meetings/MeetingCollectionRow.vue';
import { meetingColumns, meetingKey, meetingValueLabel, useMeetingRowActions } from '@/Components/Meetings/meetingCollection';
import { EmptyState } from '@/Components/Patterns';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import type { MeetingSearchResult } from '@/Shared/Search/types';

/**
 * The meetings index, narrowed to one institution. The scoped search key decides what the reader may
 * see, exactly as on the index; the institution filter can only narrow that, never widen it.
 */
const props = defineProps<{
  institutionId: string;
  canRecordMeeting?: boolean;
}>();

const emit = defineEmits<{
  record: [];
}>();

const source = useTypesenseCollectionSource<MeetingSearchResult>({
  collection: 'meetings',
  rememberKey: `institution-meetings:${props.institutionId}`,
  syncToUrl: false,
  baseFilterBy: () => `institution_ids:=[\`${props.institutionId}\`]`,
  // The institution and its padalinys are the page itself.
  facetFields: ['year', 'completion_status', 'vote_alignment_status'],
  valueLabel: meetingValueLabel,
});

const { actions, actionsFor } = useMeetingRowActions(source);

const columns = computed(() => meetingColumns({ titleLabel: $t('Posėdis') }));

const currentYear = new Date().getFullYear();
const asList = (value: unknown): string[] => (Array.isArray(value) ? value.map(String) : []);

const quickFilters = computed<CollectionQuickFilter[]>(() => [
  { id: 'no_votes', label: $t('Neužpildyti'), active: asList(source.filters.value.completion_status).includes('incomplete') },
  { id: 'this_year', label: $t('Šie metai'), active: asList(source.filters.value.year).includes(String(currentYear)) },
]);

function toggleQuickFilter(id: string): void {
  if (id === 'no_votes') {
    source.toggleFilter('completion_status', 'incomplete');
  }
  else if (id === 'this_year') {
    source.toggleFilter('year', String(currentYear));
  }
}
</script>
