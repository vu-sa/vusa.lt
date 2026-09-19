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
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <Button v-if="deletedCount > 0" as-child variant="ghost">
        <Link :href="route('meetings.index', { showDeleted: 'true' })">
          <Trash2 aria-hidden="true" />
          {{ $t('Ištrinti') }} ({{ deletedCount }})
        </Link>
      </Button>
      <Button v-if="canCreate" variant="brand" @click="actionWindow.open({ flow: 'meeting.create' })">
        <Plus aria-hidden="true" />
        {{ $t('Fiksuoti posėdį') }}
      </Button>
    </template>

    <template #row="{ item, pinned }">
      <MeetingCollectionRow :meeting="item" :pinned />
    </template>

    <template #cell="{ item, column }">
      <template v-if="column.key === 'date'">
        <span class="tabular-nums">{{ formatDate(new Date((item.start_time ?? 0) * 1000)) }}</span>
      </template>
      <Link
        v-else-if="column.key === 'title'"
        :href="route('meetings.show', item.id)"
        prefetch
        class="font-medium hover:text-brand"
      >
        {{ item.title }}
      </Link>
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
    </template>

    <template #preview="{ item }">
      <MeetingDetailPreview :key="item.id" :meeting="item" />
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="MeetingIcon"
        :title="$t('Posėdžių dar nėra')"
        :description="$t('Čia atsiras institucijų posėdžiai. Užfiksuok pirmąjį — užtenka institucijos ir datos, likusį gali papildyti vėliau.')"
        :action-label="canCreate ? $t('Fiksuoti posėdį') : undefined"
        @action="actionWindow.open({ flow: 'meeting.create' })"
      />
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { MeetingIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MeetingCollectionRow from '@/Components/Meetings/MeetingCollectionRow.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { meetingCompletionStatuses, type MeetingCompletionStatus } from '@/Constants/statuses';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import MeetingDetailPreview from '@/Features/Admin/AdminSearch/Components/Detail/MeetingDetailPreview.vue';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  /** Soft-deleted meetings the user could restore; the trash itself is a database table. */
  deletedCount: number;
  /** Meetings the user changed moments ago, until the search index reflects them (O1). */
  recentlyChanged: MeetingSearchResult[];
}>();

const actionWindow = useActionWindow();
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.meeting));

const eyebrow = computed(() => `${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.posedziai')}`);

const source = useTypesenseCollectionSource<MeetingSearchResult>({
  collection: 'meetings',
  preserveUrlKeys: ['view', 'item'],
  collapsedChips: { institution_ids: $t('Mano institucijos') },
  // The filter popover and chips name a status exactly as the row badge does (U10).
  valueLabel: (field, value) => (field === 'completion_status' && value in meetingCompletionStatuses
    ? $t(meetingCompletionStatuses[value as MeetingCompletionStatus].label)
    : undefined),
});

const meetingKey = (meeting: MeetingSearchResult) => String(meeting.id);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'date', label: $t('Data'), class: 'w-32' },
  { key: 'title', label: $t('Posėdis') },
  { key: 'institution', label: $t('Institucija') },
  { key: 'agenda', label: $t('Punktai'), class: 'w-24' },
  { key: 'status', label: $t('Būsena'), class: 'w-48' },
]);

// --- Quick filters (rules/pages.md → Collections, 2) ---------------------------------------------

const currentYear = new Date().getFullYear();

const asList = (value: unknown): string[] => (Array.isArray(value) ? value.map(String) : []);

const quickFilters = computed<CollectionQuickFilter[]>(() => {
  const filters = source.filters.value;
  const mine = source.directInstitutionIds.value;
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
    source.setFilter('institution_ids', active ? undefined : source.directInstitutionIds.value);
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
  source.query.value.trim() === '' && source.activeFilterCount.value === 0 ? props.recentlyChanged : [],
);
</script>
