<template>
  <CollectionPage
    :source
    collection="calendar"
    entity-type="calendar"
    :eyebrow
    :title="$t('Renginiai')"
    :lead="isTrash
      ? $t('Ištrinti renginiai. Atkurk tai, ko dar reikia.')
      : $t('Visi kalendoriaus renginiai. Juodraščiai matomi tik čia, kol jų nepaskelbsi.')"
    default-view="table"
    :item-key="eventKey"
    :columns
    :quick-filters
    :trash="{ count: deletedCount, active: isTrash }"
    :search-placeholder="$t('Ieškoti renginių')"
    @quick-filter="id => source.toggleFilter(id, 'true')"
  >
    <template #actions>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('calendar.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas renginys') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <CollectionPrimaryCell :title="titleOf(item)" :href="isTrash ? undefined : route('calendar.edit', item.id)" :sub="eventTypeOf(item)" />
            <StatusBadge v-if="item.is_draft" :status="contentStatuses.draft" class="shrink-0" />
          </div>
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <span class="tabular-nums">{{ dateOf(item) }}</span>
            <span v-if="item.tenant" aria-hidden="true" class="text-border">·</span>
            <span v-if="item.tenant">{{ item.tenant.shortname }}</span>
          </p>
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => select(key, item)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell
        v-if="column.key === 'title'"
        :title="titleOf(item)"
        :href="isTrash ? undefined : route('calendar.edit', item.id)"
        :sub="item.tenant?.shortname"
      />
      <span v-else-if="column.key === 'date'" class="tabular-nums text-muted-foreground">{{ dateOf(item) }}</span>
      <template v-else-if="column.key === 'type'">
        <span v-if="item.event_type" class="text-muted-foreground">{{ eventTypeOf(item) }}</span>
        <StatusBadge v-else :status="untypedStatus" />
      </template>
      <StatusBadge v-else-if="column.key === 'status' && item.is_draft" :status="contentStatuses.draft" />
      <CollectionRowActions
        v-else-if="column.key === 'actions'"
        :actions="actionsFor(item)"
        @select="key => select(key, item)"
      />
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="CalendarIcon"
        :title="isTrash ? $t('Ištrintų renginių nėra') : $t('Renginių dar nėra')"
        :description="isTrash ? undefined : $t('Renginys atsiras viešame kalendoriuje, kai jį paskelbsi.')"
        :action-label="canCreate && !isTrash ? $t('Naujas renginys') : undefined"
        :action-href="canCreate && !isTrash ? route('calendar.create') : undefined"
      />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarPlus, Plus, Tag } from 'lucide-vue-next';
import { computed } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { CalendarIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { contentStatuses, type StatusPresentation } from '@/Constants/statuses';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useDatabaseCollectionSource, type DatabaseFacetDefinition } from '@/Composables/useCollectionSource';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { formatDateTime } from '@/Utils/dateTime';

type EventRow = App.Entities.Calendar & { force_delete_blocked_reason?: string | null };

const props = defineProps<{
  calendar: {
    data: EventRow[];
    meta: { total: number; current_page: number; per_page: number; last_page: number };
  };
  eventTypes: App.Entities.EventType[];
  deletedCount: number;
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.calendar));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.calendar));
const canCreateMeeting = computed(() => Boolean(page.props.auth?.can?.create?.meeting));

const eyebrow = computed(() => `${$t('shell.workspaces.svetaine.title')} · ${$t('shell.sections.kalendorius')}`);

const untypedStatus: StatusPresentation = { label: 'Be tipo', role: 'attention', icon: Tag };

const facets: DatabaseFacetDefinition[] = [
  {
    field: 'event_type_id',
    label: $t('Renginio tipas'),
    values: props.eventTypes.map(type => ({ value: String(type.id), label: getTranslatedValue(type.name) })),
  },
  {
    field: 'is_draft',
    label: $t('Būsena'),
    values: [
      { value: '0', label: $t('Paskelbta') },
      { value: '1', label: $t('Juodraštis') },
    ],
  },
  { field: 'untyped', label: $t('Be tipo'), single: true, values: [{ value: 'true', label: $t('Taip') }] },
];

const source = useDatabaseCollectionSource<EventRow>({
  endpoint: route('api.v1.admin.calendar.index'),
  initial: {
    items: props.calendar.data,
    total: props.calendar.meta.total,
    perPage: props.calendar.meta.per_page,
    currentPage: props.calendar.meta.current_page,
    lastPage: props.calendar.meta.last_page,
  },
  defaultSort: 'date:desc',
  sortOptions: [
    { value: 'date:desc', label: $t('Naujausi pirmi') },
    { value: 'date:asc', label: $t('Seniausi pirmi') },
    { value: 'created_at:desc', label: $t('Neseniai sukurti') },
  ],
  preserveUrlKeys: ['showDeleted'],
  facets,
});

const quickFilters = computed<CollectionQuickFilter[]>(() => (isTrash
  ? []
  : [{ id: 'untyped', label: $t('Be tipo'), active: source.filters.value.untyped === 'true' }]));

const actions = useCollectionRecordActions({
  routePrefix: 'calendar',
  canDuplicate: () => canCreate.value,
  canDelete: () => canCreate.value,
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});

const { open: openActionWindow } = useActionWindow();

const eventKey = (item: EventRow) => String(item.id);
const titleOf = (item: EventRow) => getTranslatedValue(item.title) || $t('Be pavadinimo');
const eventTypeOf = (item: EventRow) => (item.event_type ? getTranslatedValue(item.event_type.name) : $t('Be tipo'));
const dateOf = (item: EventRow) => (item.date ? formatDateTime(item.date) : '—');

function actionsFor(item: EventRow) {
  const shared = actions.rowActions(item, titleOf(item), isTrash);

  if (isTrash || !canCreateMeeting.value || item.meeting_id) {
    return shared;
  }

  // An event that stands for a meeting but has none yet: the date is already fixed.
  return [...shared, { key: `meeting:${item.id}`, label: $t('meetings.announce.create_from_event'), icon: CalendarPlus }];
}

function select(key: string, item: EventRow): void {
  if (key.startsWith('meeting:')) {
    openActionWindow({
      flow: 'meeting.create',
      calendarEvent: { id: Number(item.id), title: titleOf(item), date: String(item.date) },
    });
    return;
  }

  actions.select(key, item.force_delete_blocked_reason);
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Renginys') },
  { key: 'date', label: $t('Data'), class: 'w-48', sortField: 'date' },
  { key: 'type', label: $t('Renginio tipas'), class: 'w-44' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
