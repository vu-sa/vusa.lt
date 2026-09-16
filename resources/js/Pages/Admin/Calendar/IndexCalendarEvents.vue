<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  >
    <template #headerActions>
      <Button variant="outline" :class="{ 'border-primary text-primary': isUntyped }" class="gap-1.5" @click="toggleUntyped">
        <TagIcon class="h-4 w-4" />
        {{ $t('Be tipo') }}
      </Button>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { CalendarPlus, Tag as TagIcon } from 'lucide-vue-next';

import type { IndexTablePageInstance,
  IndexTablePageProps } from '@/Types/TableConfigTypes';
import { Button } from '@/Components/ui/button';
import { DateCell, TruncatedBadge, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { createTenantColumn } from '@/Composables/useDataTableColumns';
import { useActionWindow } from '@/Composables/useActionWindow';
import { CalendarIcon } from '@/Components/icons';

const props = defineProps<{
  calendar: {
    data: App.Entities.Calendar[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  eventTypes: App.Entities.EventType[];
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
  showDeleted?: boolean;
  deletedCount?: number;
  untyped?: boolean;
}>();

const isUntyped = computed(() => !!props.untyped);

/**
 * The backfill review queue: a dedicated query param rather than a Tanstack column
 * filter, since "no type" has no value to filter by (see IndexCalendarRequest::getUntyped()).
 */
function toggleUntyped() {
  router.get(route('calendar.index'), {
    ...(props.filters ?? {}),
    untyped: isUntyped.value ? undefined : 'true',
  }, { preserveState: true, replace: true });
}

const modelName = 'calendar';
const entityName = 'calendar';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.calendar ?? false);
const canCreateMeeting = computed(() => !!usePage().props.auth?.can?.create?.meeting);

const { open: openActionWindow } = useActionWindow();

const eventTitle = (event: App.Entities.Calendar): string => {
  const { title } = event;
  return typeof title === 'object' && title !== null
    ? ((title as any).lt || (title as any).en || '')
    : String(title ?? '');
};

/**
 * An event that stands for a meeting but has none yet — the announcement already fixed
 * when it happens, so the window only has to ask which body and what is on the agenda.
 */
const startMeetingFromEvent = (event: App.Entities.Calendar) => {
  openActionWindow({
    flow: 'meeting.create',
    calendarEvent: { id: Number(event.id), title: eventTitle(event), date: String(event.date) },
  });
};

const getRowId = (row: App.Entities.Calendar) => {
  return `calendar-${row.id}`;
};

const columns = computed<Array<ColumnDef<App.Entities.Calendar, any>>>(() => [
  {
    accessorKey: 'title',
    header: () => $t('Pavadinimas'),
    cell: ({ row }) => {
      const title = row.getValue('title');
      const displayTitle = typeof title === 'object' && title !== null
        ? ((title as any).lt || (title as any).en || '-')
        : title;
      return h(TruncatedText, { text: displayTitle as string, lines: 2 });
    },
    size: 200,
    enableSorting: true,
  },
  {
    accessorKey: 'date',
    header: () => $t('Data'),
    cell: ({ row }) => {
      const { date } = row.original;
      if (!date) return null;
      return h(DateCell, {
        date,
        mode: 'absolute',
        format: { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric' },
      });
    },
    size: 200,
    enableSorting: true,
  },
  {
    id: 'event_type',
    header: () => $t('Renginio tipas'),
    cell: ({ row }) => {
      const eventType = row.original.event_type;
      if (!eventType) {
        return h(TruncatedBadge, { text: $t('Be tipo'), variant: 'destructive' });
      }
      const name = typeof eventType.name === 'object' && eventType.name !== null
        ? ((eventType.name as any).lt || (eventType.name as any).en || '-')
        : eventType.name;
      return h(TruncatedText, { text: name });
    },
    size: 150,
  },
  {
    accessorKey: 'is_draft',
    header: () => $t('Ar rodomas?'),
    cell: ({ row }) => {
      return row.original.is_draft ? '❌ Ne' : '✅ Taip';
    },
    size: 100,
  },
  createTenantColumn<App.Entities.Calendar>(),
  createStandardActionsColumn<App.Entities.Calendar>('calendar', {
    canView: false,
    canEdit: true,
    canDelete: true,
    canDuplicate: true,
    canRestore: true,
    canForceDelete: canForceDelete.value,
    customActions: [
      {
        key: 'create-meeting',
        label: $t('meetings.announce.create_from_event'),
        icon: CalendarPlus,
        isAvailable: event => canCreateMeeting.value && !event.meeting_id && !event.deleted_at,
        onSelect: startMeetingFromEvent,
      },
    ],
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Calendar>>(() => {
  return {
    modelName,
    entityName,
    data: props.calendar.data,
    columns: columns.value,
    getRowId,
    totalCount: props.calendar.meta.total,
    initialPage: props.calendar.meta.current_page,
    pageSize: props.calendar.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'date', desc: true }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,
    allowToggleDeleted: true,
    showDeleted: props.showDeleted,
    deletedCount: props.deletedCount,

    headerTitle: 'Renginiai',
    icon: CalendarIcon,
    createRoute: route('calendar.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
