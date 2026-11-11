<template>
  <CollectionPage
    :row-actions="item => actionsFor(item)"
    :source
    collection="meetings"
    :eyebrow
    :title="$t('Posėdžiai')"
    :lead="$t('Fiksuok posėdžius, jų darbotvarkę ir balsavimus vienoje vietoje.')"
    default-view="rows"
    :item-key="meetingKey"
    :quick-filters
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[68rem]"
    :pinned-items="pinnedMeetings"
    :search-placeholder="$t('Ieškoti posėdžių')"
    :trash="{ count: deletedCount, active: isTrash }"
    @row-action="(item, key) => actions.select(key)"
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <Button v-if="canCreate && !isTrash" variant="brand" size="lg" @click="actionWindow.open({ flow: 'meeting.create' })">
        <Plus aria-hidden="true" />
        {{ $t('Fiksuoti posėdį') }}
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, pinned, view }">
      <CollectionRow v-if="isTrash" :actions="recordActions" :spotlight="item === source?.items.value[0]" class="px-3 py-2.5 sm:px-4" @select="key => actions.select(key)">
        <CollectionPrimaryCell :match="item._searchMatch" :title-matches="item._searchTitleMatches"
          :title="item.title" :title-lines="2" :sub="getSearchTranslation(item, 'institution_name')" />
        <p class="mt-0.5 text-xs tabular-nums text-muted-foreground">
          {{ formatDate(new Date((item.start_time ?? 0) * 1000)) }}
        </p>
      </CollectionRow>
      <MeetingCollectionRow v-else :meeting="item" :actions="view === 'preview' ? [] : recordActions" :compact="view === 'preview'" :pinned @select="key => actions.select(key)" />
    </template>

    <template #cell="{ item, column }">
      <MeetingCollectionCell :item :column :linkless="isTrash" />
    </template>

    <template v-if="!isTrash" #preview="{ item, actions: recordActions }">
      <MeetingPreview :key="item.id" :meeting="item">
        <template #actions>
          <CollectionRowActions :actions="recordActions" emphasis full-labels @select="key => actions.select(key)" />
        </template>
      </MeetingPreview>
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
import { Plus, RotateCcw, Trash2 } from '@lucide/vue';
import { computed } from 'vue';

import { getSearchTranslation } from '@/Composables/useTranslatedTitle';
import MeetingCollectionCell from '@/Components/Meetings/MeetingCollectionCell.vue';
import { meetingColumns, meetingKey, meetingValueLabel, useMeetingRowActions } from '@/Components/Meetings/meetingCollection';
import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { MeetingIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MeetingCollectionRow from '@/Components/Meetings/MeetingCollectionRow.vue';
import { EmptyState } from '@/Components/Patterns';
import MeetingPreview from '@/Components/Meetings/MeetingPreview.vue';
import { Button } from '@/Components/ui/button';
import { useActionWindow } from '@/Composables/useActionWindow';
import {
  isTrashView,
  useTrashCollectionSource,
  useTypesenseCollectionSource,
  type CollectionSource,
} from '@/Composables/useCollectionSource';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  /** Soft-deleted meetings the viewer could restore. */
  deletedCount: number;
  /** Meetings the user changed moments ago, until the search index reflects them (O1). */
  recentlyChanged: MeetingSearchResult[];
  /** The user's own padaliniai: a first visit starts filtered to them. */
  defaultTenantShortnames: string[];
}>();

const page = usePage();
const actionWindow = useActionWindow();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.meeting));

const eyebrow = computed(() => $t('shell.workspaces.atstovavimas.title'));

// Built directly rather than through useTrashAwareSource: the quick filters need the scoped
// key's own institutions, which only the live source knows.
const liveSource = isTrash
  ? null
  : useTypesenseCollectionSource<MeetingSearchResult>({
      collection: 'meetings',
      preserveUrlKeys: ['view', 'item'],
      defaultFilters: { tenant_shortnames: props.defaultTenantShortnames },
      collapsedChips: { institution_ids: $t('Mano institucijos') },
      valueLabel: meetingValueLabel,
    });
const source: CollectionSource<MeetingSearchResult> = liveSource ?? useTrashCollectionSource<MeetingSearchResult>('meetings');

const { actions, actionsFor: liveActionsFor } = useMeetingRowActions(
  source,
  () => isTrash ? [] : [...source.items.value, ...props.recentlyChanged].map(item => item.id),
);
type MeetingRow = MeetingSearchResult & { abilities?: { restore: boolean; forceDelete: boolean } };

const actionsFor = (meeting: MeetingRow) => isTrash
  ? [
      ...(meeting.abilities?.restore ? [{ key: `restore:${meeting.id}`, label: $t('Atkurti'), icon: RotateCcw, primary: !meeting.abilities?.forceDelete }] : []),
      ...(meeting.abilities?.forceDelete ? [{ key: `forceDelete:${meeting.id}:${meeting.title}`, label: $t('Ištrinti visam laikui'), icon: Trash2, destructive: true }] : []),
    ]
  : liveActionsFor(meeting);

const columns = computed<CollectionColumn[]>(() => meetingColumns());

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
    { id: 'no_votes', label: $t('Neužpildyti'), active: asList(filters.completion_status).includes('incomplete') },
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
