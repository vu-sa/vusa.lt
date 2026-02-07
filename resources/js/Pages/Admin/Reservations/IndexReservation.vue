<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  >
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

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type ColumnDef } from "@tanstack/vue-table";
import { Link, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

import { Info } from "lucide-vue-next";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover";
import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { capitalize } from "@/Utils/String";
import { formatRelativeTime, formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import ReservationsWithUnitResources from "@/Components/Tables/ReservationsWithUnitResources.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { type IndexTablePageProps } from "@/Types/TableConfigTypes";
import { usePageBreadcrumbs, BreadcrumbHelpers } from "@/Composables/useBreadcrumbsUnified";

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

const modelName = "reservations";
const entityName = "reservation";

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Reservation) => {
  return `reservation-${row.id}`;
};

// Breadcrumbs setup
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.homeItem(),
  BreadcrumbHelpers.createBreadcrumbItem(
    $t("administration.title"),
    route("administration")
  ),
  BreadcrumbHelpers.createBreadcrumbItem(
    capitalize($tChoice("entities.reservation.model", 2)),
    undefined,
    Icons.RESERVATION
  ),
]);

const columns = computed<ColumnDef<App.Entities.Reservation, any>[]>(() => [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.title"),
    cell: ({ row }) => {
      const reservation = row.original;
      return (
        <div class="flex items-center gap-1.5">
          <Link
            href={route("reservations.show", reservation.id)}
            class="transition hover:text-vusa-red"
          >
            <div class="max-w-[250px] truncate" title={reservation.name}>
              {reservation.name}
            </div>
          </Link>
          {(reservation.description || reservation.resources?.length) ? (
            <Popover>
              <PopoverTrigger asChild>
                <Button variant="ghost" size="icon-sm" class="size-6 shrink-0">
                  <Info class="size-3.5 text-muted-foreground" />
                </Button>
              </PopoverTrigger>
              <PopoverContent class="w-80">
                <div class="flex flex-col gap-3">
                  {reservation.description && (
                    <div>
                      <p class="text-sm font-medium">
                        {$t("forms.fields.description")}
                      </p>
                      <p class="text-sm text-muted-foreground">
                        {reservation.description}
                      </p>
                    </div>
                  )}
                  {reservation.resources?.length ? (
                    <div>
                      <p class="text-sm font-medium">
                        {capitalize($t("entities.reservation.resources"))}
                      </p>
                      <ul class="list-inside list-disc text-sm">
                        {reservation.resources.map((resource) => (
                          <li key={resource.id}>
                            <div class="inline-flex items-center gap-1.5">
                              <Link href={route("resources.edit", resource.id)}>
                                {resource.name}
                              </Link>
                              {resource.tenant?.shortname && (
                                <Badge variant="secondary" class="text-xs">
                                  {$t(resource.tenant.shortname)}
                                </Badge>
                              )}
                            </div>
                          </li>
                        ))}
                      </ul>
                    </div>
                  ) : null}
                </div>
              </PopoverContent>
            </Popover>
          ) : null}
        </div>
      );
    },
    size: 300,
    enableSorting: true,
  },
  {
    accessorKey: "managers",
    header: () => capitalize($tChoice("entities.reservation.managers", 2)),
    cell: ({ row }) => {
      const users = row.original.users;
      return users && users.length > 0 ? (
        <UsersAvatarGroup class="align-middle" size={30} users={users} />
      ) : (
        <span class="text-muted-foreground">-</span>
      );
    },
    size: 150,
  },
  {
    accessorKey: "start_time",
    header: () => capitalize($tChoice("entities.reservation.start_time", 2)),
    cell: ({ row }) => {
      const startTime = row.original.start_time;
      return (
        <div class="max-w-[180px] truncate" title={startTime}>
          {formatStaticTime(
            new Date(startTime),
            RESERVATION_DATE_TIME_FORMAT,
            usePage().props.app.locale
          )}
        </div>
      );
    },
    size: 180,
    enableSorting: true,
  },
  {
    accessorKey: "end_time",
    header: () => capitalize($tChoice("entities.reservation.end_time", 2)),
    cell: ({ row }) => {
      const endTime = row.original.end_time;
      return (
        <div class="max-w-[180px] truncate" title={endTime}>
          {formatStaticTime(
            new Date(endTime),
            RESERVATION_DATE_TIME_FORMAT,
            usePage().props.app.locale
          )}
        </div>
      );
    },
    size: 180,
    enableSorting: true,
  },
  {
    accessorKey: "created_at",
    header: () => $t("forms.fields.created_at"),
    cell: ({ row }) => {
      return formatRelativeTime(
        new Date(row.original.created_at),
        { numeric: "auto" },
        usePage().props.app.locale
      );
    },
    size: 150,
  },
  createStandardActionsColumn<App.Entities.Reservation>("reservations", {
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
    initialSorting: props.sorting ?? [{ id: "start_time", desc: true }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,
    allowToggleDeleted: true,

    headerTitle: capitalize($tChoice("entities.reservation.model", 2)),
    icon: Icons.RESERVATION,
    createRoute: route("reservations.create"),
    canCreate: true,
  })
);

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
