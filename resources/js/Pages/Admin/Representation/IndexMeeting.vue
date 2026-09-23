<template>
  <CollectionPage
    :source
    collection="meetings"
    entity-type="meeting"
    :eyebrow
    :title="$t('Posėdžiai')"
    :lead="$t('Fiksuok posėdžius, jų darbotvarkę ir balsavimus vienoje vietoje.')"
    default-view="rows"
    :item-key="meetingKey"
    :quick-filters
    :columns
    :pinned-items="pinnedMeetings"
    :search-placeholder="$t('Ieškoti posėdžių')"
    :trash="{ count: deletedCount, active: isTrash }"
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <Button v-if="canCreate && !isTrash" variant="brand" size="lg" @click="actionWindow.open({ flow: 'meeting.create' })">
        <Plus aria-hidden="true" />
        {{ $t('Fiksuoti posėdį') }}
      </Button>
    </template>

    <template #row="{ item, pinned }">
      <div v-if="isTrash" class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="item.title" :sub="item.institution_name_lt || item.institution_name_en" />
          <p class="mt-2 text-xs tabular-nums text-muted-foreground">
            {{ formatDate(new Date((item.start_time ?? 0) * 1000)) }}
          </p>
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => actions.select(key)" />
      </div>
      <MeetingCollectionRow v-else :meeting="item" :pinned />
    </template>

    <template #cell="{ item, column }">
      <template v-if="column.key === 'date'">
        <span class="tabular-nums">{{ formatDate(new Date((item.start_time ?? 0) * 1000)) }}</span>
      </template>
      <CollectionPrimaryCell
        v-else-if="column.key === 'title'"
        :title="item.title"
        :href="isTrash ? undefined : route('meetings.show', item.id)"
      />
      <template v-else-if="column.key === 'institution'">
        {{ item.institution_name_lt || item.institution_name_en || '—' }}
      </template>
      <template v-else-if="column.key === 'agenda'">
        <span class="tabular-nums">{{ item.agenda_items_count ?? '—' }}</span>
      </template>
      <template v-else-if="column.key === 'status'">
        <StatusBadge
          v-if="item.completion_status && item.completion_status !== 'complete'"
          :status="meetingCompletionStatuses[item.completion_status as MeetingCompletionStatus]"
        />
      </template>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="actionsFor(item)" @select="key => actions.select(key)" />
    </template>

    <template v-if="!isTrash" #preview="{ item }">
      <MeetingDetailPreview :key="item.id" :meeting="item" />
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="MeetingIcon"
        :title="isTrash ? $t('Ištrintų posėdžių nėra') : $t('Posėdžių dar nėra')"
        :description="isTrash ? undefined : $t('Čia atsiras institucijų posėdžiai. Užfiksuok pirmąjį — užtenka institucijos ir datos, likusį gali papildyti vėliau.')"
        :action-label="canCreate && !isTrash ? $t('Fiksuoti posėdį') : undefined"
        @action="actionWindow.open({ flow: 'meeting.create' })"
      />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { MeetingIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MeetingCollectionRow from '@/Components/Meetings/MeetingCollectionRow.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { meetingCompletionStatuses, type MeetingCompletionStatus } from '@/Constants/statuses';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import {
  isTrashView,
  useTrashCollectionSource,
  useTypesenseCollectionSource,
  type CollectionSource,
} from '@/Composables/useCollectionSource';
import MeetingDetailPreview from '@/Features/Admin/AdminSearch/Components/Detail/MeetingDetailPreview.vue';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  /** Soft-deleted meetings the viewer could restore. */
  deletedCount: number;
  /** Meetings the user changed moments ago, until the search index reflects them (O1). */
  recentlyChanged: MeetingSearchResult[];
}>();

const page = usePage();
const actionWindow = useActionWindow();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.meeting));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.meeting));

const eyebrow = computed(() => `${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.posedziai')}`);

// Built directly rather than through useTrashAwareSource: the quick filters need the scoped
// key's own institutions, which only the live source knows.
const liveSource = isTrash ? null : useTypesenseCollectionSource<MeetingSearchResult>({
  collection: 'meetings',
  preserveUrlKeys: ['view', 'item'],
  collapsedChips: { institution_ids: $t('Mano institucijos') },
  // The filter popover and chips name a status exactly as the row badge does (U10).
  valueLabel: (field, value) => (field === 'completion_status' && value in meetingCompletionStatuses
    ? $t(meetingCompletionStatuses[value as MeetingCompletionStatus].label)
    : undefined),
});
const source: CollectionSource<MeetingSearchResult> = liveSource ?? useTrashCollectionSource<MeetingSearchResult>('meetings');

const actions = useCollectionRecordActions({
  routePrefix: 'meetings',
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (meeting: MeetingSearchResult) => actions.rowActions(meeting, meeting.title, true);

const meetingKey = (meeting: MeetingSearchResult) => String(meeting.id);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'date', label: $t('Data'), class: 'w-32' },
  { key: 'title', label: $t('Posėdis') },
  { key: 'institution', label: $t('Institucija') },
  { key: 'agenda', label: $t('Punktai'), class: 'w-24' },
  { key: 'status', label: $t('Būsena'), class: 'w-48' },
  ...(isTrash ? [{ key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true }] : []),
]);

// --- Quick filters (.ai/rules/js-pages-admin.md) ---------------------------------------------

const currentYear = new Date().getFullYear();

const asList = (value: unknown): string[] => (Array.isArray(value) ? value.map(String) : []);

const quickFilters = computed<CollectionQuickFilter[]>(() => {
  if (!liveSource) {
    return [];
  }

  const filters = liveSource.filters.value;
  const mine = liveSource.directInstitutionIds.value;
  const chosenInstitutions = asList(filters.institution_ids);

  return [
    ...(mine.length > 0
      ? [{
          id: 'mine',
          label: $t('Mano institucijos'),
          active: chosenInstitutions.length === mine.length && mine.every(id => chosenInstitutions.includes(id)),
        }]
      : []),
    { id: 'no_votes', label: $t('Be balsavimų'), active: asList(filters.completion_status).includes('incomplete') },
    { id: 'this_year', label: $t('Šie metai'), active: asList(filters.year).includes(String(currentYear)) },
  ];
});

function toggleQuickFilter(id: string): void {
  const active = quickFilters.value.find(filter => filter.id === id)?.active ?? false;

  if (id === 'mine') {
    source.setFilter('institution_ids', active ? undefined : liveSource?.directInstitutionIds.value);
  }
  else if (id === 'no_votes') {
    source.toggleFilter('completion_status', 'incomplete');
  }
  else if (id === 'this_year') {
    source.toggleFilter('year', String(currentYear));
  }
}

// A pinned change would contradict an active search or filter, so it only shows on the plain list.
const pinnedMeetings = computed(() =>
  !isTrash && source.query.value.trim() === '' && source.activeFilterCount.value === 0 ? props.recentlyChanged : [],
);
</script>
