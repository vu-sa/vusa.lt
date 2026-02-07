<template>
  <SimpleDataTable :data="activeReservations" :columns="columns" :enable-pagination="true" :page-size="10" :enable-filtering="true" />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { Link, usePage } from "@inertiajs/vue3";
import type { ColumnDef } from "@tanstack/vue-table";
import { capitalize } from "vue";

import ArrowForward20Filled from "~icons/fluent/arrow-forward20-filled";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { formatRelativeTime, formatStaticTime } from "@/Utils/IntlTime";
import SimpleDataTable from "@/Components/Tables/SimpleDataTable.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

defineProps<{
  activeReservations: App.Entities.Reservation[];
  showIfCompleted?: boolean;
}>();

const columns: ColumnDef<App.Entities.Reservation, any>[] = [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.title"),
    cell: ({ row }) => (
      <Link href={route("reservations.show", row.original.id)}>
        {row.original.name}
      </Link>
    ),
    size: 300,
  },
  {
    id: "managers",
    header: () => capitalize($tChoice("entities.reservation.managers", 2)),
    cell: ({ row }) => {
      const users = row.original.users;
      return users && users.length > 0 ? (
        <UsersAvatarGroup class="align-middle" size={30} users={users} />
      ) : (
        "Nėra"
      );
    },
  },
  {
    accessorKey: "start_time",
    header: () => capitalize($tChoice("entities.reservation.start_time", 2)),
    cell: ({ row }) =>
      formatStaticTime(
        new Date(row.original.start_time),
        RESERVATION_DATE_TIME_FORMAT,
        usePage().props.app.locale,
      ),
    enableSorting: true,
  },
  {
    accessorKey: "end_time",
    header: () => capitalize($tChoice("entities.reservation.end_time", 2)),
    cell: ({ row }) =>
      formatStaticTime(
        new Date(row.original.end_time),
        RESERVATION_DATE_TIME_FORMAT,
        usePage().props.app.locale,
      ),
    enableSorting: true,
  },
  {
    accessorKey: "created_at",
    header: () => $t("forms.fields.created_at"),
    cell: ({ row }) =>
      formatRelativeTime(
        new Date(row.original.created_at),
        { numeric: "auto" },
        usePage().props.app.locale,
      ),
  },
  {
    id: "actions",
    header: () => $t("Veiksmai"),
    size: 100,
    cell: ({ row }) => (
      <Link href={route("reservations.show", row.original.id)}>
        <Button variant="ghost" size="icon-sm">
          <ArrowForward20Filled />
        </Button>
      </Link>
    ),
  },
];
</script>
