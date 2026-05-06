<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange" @page-changed="handlePageChange" @filter-changed="handleFilterChange">
    <!-- After-table: Reservations with unit resources -->
    <Card v-if="activeReservations?.length" class="mt-4">
      <CardHeader>
        <CardTitle>{{ $t("Reservations with unit resources") }}</CardTitle>
      </CardHeader>
      <CardContent>
        <ReservationsWithUnitResources :active-reservations />
      </CardContent>
    </Card>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, ref, computed } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { Link } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/Components/ui/popover';
import { DateCell, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import { RESERVATION_DATE_TIME_FORMAT } from '@/Constants/DateTimeFormats';
import { capitalize } from '@/Utils/String';
import Icons from '@/Types/Icons/regular';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import ReservationsWithUnitResources from '@/Components/Tables/ReservationsWithUnitResources.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type { IndexTablePageProps } from '@/Types/TableConfigTypes';
import { BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  reservations: {
    data: App.Entities.Reservation[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
  activeReservations: Array<App.Entities.Reservation>;
}>();

const modelName = 'reservations';
const entityName = 'reservation';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Reservation) => {
  return `reservation-${row.id}`;
};

const columns = computed<Array<ColumnDef<App.Entities.Reservation, any>>>(() => [
  {
    accessorKey: 'name',
    header: () => $t('forms.fields.title'),
    cell: ({ row }) => {
      const reservation = row.original;
      return h('div', { class: 'flex items-center gap-1.5' }, [
        h(TruncatedLink, {
          href: route('reservations.show', reservation.id),
          text: reservation.name,
          class: 'transition hover:text-vusa-red',
        }),
        (reservation.description || reservation.resources?.length)
          ? h(Popover, {}, () => [
              h(PopoverTrigger, { asChild: true }, () => h(Button, {
                variant: 'ghost',
                size: 'icon-sm',
                class: 'size-6 shrink-0',
              }, () => h(Info, { class: 'size-3.5 text-muted-foreground' }))),
              h(PopoverContent, { class: 'w-80' }, () => h('div', { class: 'flex flex-col gap-3' }, [
                reservation.description
                  ? h('div', {}, [
                      h('p', { class: 'text-sm font-medium' }, $t('forms.fields.description')),
                      h('p', { class: 'text-sm text-muted-foreground' }, reservation.description),
                    ])
                  : null,
                reservation.resources?.length
                  ? h('div', {}, [
                      h('p', { class: 'text-sm font-medium' }, capitalize($t('entities.reservation.resources'))),
                      h('ul', { class: 'list-inside list-disc text-sm' }, reservation.resources.map(resource => h('li', { key: resource.id }, h('div', { class: 'inline-flex items-center gap-1.5' }, [
                        h(Link, { href: route('resources.edit', resource.id) }, () => resource.name),
                        resource.tenant?.shortname
                          ? h(Badge, { variant: 'secondary', class: 'text-xs' }, () => $t(resource.tenant.shortname))
                          : null,
                      ])))),
                    ])
                  : null,
              ])),
            ])
          : null,
      ]);
    },
    size: 300,
    enableSorting: true,
  },
  {
    accessorKey: 'managers',
    header: () => capitalize($tChoice('entities.reservation.managers', 2)),
    cell: ({ row }) => {
      const { users } = row.original;
      return users && users.length > 0
        ? h(UsersAvatarGroup, { class: 'align-middle', size: 30, users })
        : h(TruncatedText, { text: '-' });
    },
    size: 150,
  },
  {
    accessorKey: 'start_time',
    header: () => capitalize($tChoice('entities.reservation.start_time', 2)),
    cell: ({ row }) => h(DateCell, {
      date: row.original.start_time,
      mode: 'absolute',
      format: RESERVATION_DATE_TIME_FORMAT,
    }),
    size: 180,
    enableSorting: true,
  },
  {
    accessorKey: 'end_time',
    header: () => capitalize($tChoice('entities.reservation.end_time', 2)),
    cell: ({ row }) => h(DateCell, {
      date: row.original.end_time,
      mode: 'absolute',
      format: RESERVATION_DATE_TIME_FORMAT,
    }),
    size: 180,
    enableSorting: true,
  },
  {
    accessorKey: 'created_at',
    header: () => $t('forms.fields.created_at'),
    cell: ({ row }) => h(DateCell, {
      date: row.original.created_at,
      mode: 'relative',
    }),
    size: 150,
  },
  createStandardActionsColumn<App.Entities.Reservation>('reservations', {
    canView: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Reservation>>(
  () => ({
    modelName,
    entityName,
    data: props.reservations.data,
    columns: columns.value,
    getRowId,
    totalCount: props.reservations.meta.total,
    initialPage: props.reservations.meta.current_page,
    pageSize: props.reservations.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting ?? [{ id: 'start_time', desc: true }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,
    allowToggleDeleted: true,

    headerTitle: capitalize($tChoice('entities.reservation.model', 2)),
    icon: Icons.RESERVATION,
    breadcrumbs: [
      BreadcrumbHelpers.homeItem(),
      BreadcrumbHelpers.createBreadcrumbItem(
        $t('administration.title'),
        route('administration'),
      ),
      BreadcrumbHelpers.createBreadcrumbItem(
        capitalize($tChoice('entities.reservation.model', 2)),
        undefined,
        Icons.RESERVATION,
      ),
    ],
    createRoute: route('reservations.create'),
    canCreate: true,
  }),
);

const onDataLoaded = (data: any) => { };
const handleSortingChange = (sorting: any) => { };
const handlePageChange = (page: any) => { };
const handleFilterChange = (filterKey: any, value: any) => { };
</script>
