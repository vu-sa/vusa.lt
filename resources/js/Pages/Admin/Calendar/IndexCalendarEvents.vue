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
            <CollectionPrimaryCell
              :title="titleOf(item)"
              :href="isTrash ? undefined : route('calendar.edit', item.id)"
              :sub="canEdit && !isTrash ? undefined : eventTypeOf(item)"
            />
            <CollectionStatusMenu
              :status="item.is_draft ? contentStatuses.draft : contentStatuses.published"
              :model-value="item.is_draft ? 'draft' : 'published'"
              :options="statusOptions"
              :editable="canEdit && !isTrash"
              @update:model-value="value => updateEvent(item, { is_draft: value === 'draft' })"
            />
          </div>
          <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <span class="tabular-nums">{{ dateOf(item) }}</span>
            <span v-if="item.tenant" aria-hidden="true" class="text-border">·</span>
            <span v-if="item.tenant">{{ item.tenant.shortname }}</span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Select
            v-if="canEdit && !isTrash"
            :model-value="item.event_type_id == null ? 'none' : String(item.event_type_id)"
            @update:model-value="value => updateType(item, String(value))"
          >
            <SelectTrigger :aria-label="$t('Renginio tipas')" class="h-9 w-44 border-border bg-background text-xs pointer-coarse:min-h-11">
              <SelectValue :placeholder="$t('Be tipo')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">
                {{ $t('Be tipo') }}
              </SelectItem>
              <SelectItem v-for="type in eventTypes" :key="type.id" :value="String(type.id)">
                {{ getTranslatedValue(type.name) }}
              </SelectItem>
            </SelectContent>
          </Select>
          <CollectionRowActions :actions="actionsFor(item)" @select="key => select(key, item)" />
        </div>
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
      <Select
        v-else-if="column.key === 'type' && canEdit && !isTrash"
        :model-value="item.event_type_id == null ? 'none' : String(item.event_type_id)"
        @update:model-value="value => updateType(item, String(value))"
      >
        <SelectTrigger :aria-label="$t('Renginio tipas')" class="h-9 w-full border-border bg-background text-xs pointer-coarse:min-h-11">
          <SelectValue :placeholder="$t('Be tipo')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="none">
            {{ $t('Be tipo') }}
          </SelectItem>
          <SelectItem v-for="type in eventTypes" :key="type.id" :value="String(type.id)">
            {{ getTranslatedValue(type.name) }}
          </SelectItem>
        </SelectContent>
      </Select>
      <span v-else-if="column.key === 'type'" class="text-muted-foreground">{{ eventTypeOf(item) }}</span>
      <CollectionStatusMenu
        v-else-if="column.key === 'status'"
        :status="item.is_draft ? contentStatuses.draft : contentStatuses.published"
        :model-value="item.is_draft ? 'draft' : 'published'"
        :options="statusOptions"
        :editable="canEdit && !isTrash"
        @update:model-value="value => updateEvent(item, { is_draft: value === 'draft' })"
      />
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
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import CollectionStatusMenu from '@/Components/Collection/CollectionStatusMenu.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { CalendarIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { contentStatuses } from '@/Constants/statuses';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
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
const canEdit = computed(() => canCreate.value);

const eyebrow = computed(() => `${$t('shell.workspaces.svetaine.title')} · ${$t('shell.sections.kalendorius')}`);

const statusOptions = [
  { value: 'published', status: contentStatuses.published },
  { value: 'draft', status: contentStatuses.draft },
];

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

const eventKey = (item: EventRow) => String(item.id);
const titleOf = (item: EventRow) => getTranslatedValue(item.title) || $t('Be pavadinimo');
const eventTypeOf = (item: EventRow) => (item.event_type ? getTranslatedValue(item.event_type.name) : $t('Be tipo'));
const dateOf = (item: EventRow) => (item.date ? formatDateTime(item.date) : '—');

const actionsFor = (item: EventRow) => actions.rowActions(item, titleOf(item), isTrash);

function select(key: string, item: EventRow): void {
  actions.select(key, item.force_delete_blocked_reason);
}

function updateEvent(item: EventRow, patch: Partial<EventRow>): void {
  const undo = source.patchItems([String(item.id)], patch);
  router.patch(route('calendar.updateIndex', item.id), patch, {
    preserveScroll: true,
    preserveState: true,
    onError: undo,
  });
}

function updateType(item: EventRow, value: string): void {
  const eventType = props.eventTypes.find(type => String(type.id) === value);
  updateEvent(item, { event_type_id: eventType?.id ?? null, event_type: eventType });
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Renginys') },
  { key: 'date', label: $t('Data'), class: 'w-48', sortField: 'date' },
  { key: 'type', label: $t('Renginio tipas'), class: 'w-44' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
