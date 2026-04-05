<template>
  <SimpleDataTable :data="reservations" :columns :enable-pagination="true" :page-size="10" :enable-filtering="false" />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';

import { RESERVATION_DATE_TIME_FORMAT } from '@/Constants/DateTimeFormats';
import { capitalize } from '@/Utils/String';
import { formatStaticTime } from '@/Utils/IntlTime';
import ReservationResourceStateTag from '@/Components/Tag/ReservationResourceStateTag.vue';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';

defineProps<{
  reservations: Array<App.Entities.Reservation>;
}>();

const columns: ColumnDef<App.Entities.Reservation, any>[] = [
  {
    accessorKey: 'name',
    header: () => $t('forms.fields.title'),
    cell: ({ row }) => (
      <Link href={route('reservations.show', row.original.id)}>{row.original.name}</Link>
    ),
    size: 150,
  },
  {
    accessorKey: 'pivot.quantity',
    header: () => $t('forms.fields.quantity'),
    cell: ({ row }) => (row.original as any).pivot?.quantity,
    size: 80,
  },
  {
    id: 'users',
    header: () => capitalize($tChoice('entities.reservation.managers', 2)),
    cell: ({ row }) => {
      const { users } = row.original;
      return users && users.length > 0
        ? (
            <UsersAvatarGroup class="align-middle" size={30} users={users} />
          )
        : (
            'Nėra'
          );
    },
  },
  {
    id: 'state',
    header: () => $t('forms.fields.state'),
    cell: ({ row }) => (
      <ReservationResourceStateTag
        state={(row.original as any).pivot?.state}
        state_properties={(row.original as any).pivot?.state_properties}
        class="align-middle"
      />
    ),
  },
  {
    accessorKey: 'start_time',
    header: () => capitalize($t('entities.reservation.start_time')),
    cell: ({ row }) =>
      formatStaticTime(
        new Date(row.original.start_time),
        RESERVATION_DATE_TIME_FORMAT,
        usePage().props.app.locale,
      ),
    enableSorting: true,
  },
  {
    accessorKey: 'end_time',
    header: () => capitalize($t('entities.reservation.end_time')),
    cell: ({ row }) =>
      formatStaticTime(
        new Date(row.original.end_time),
        RESERVATION_DATE_TIME_FORMAT,
        usePage().props.app.locale,
      ),
    enableSorting: true,
  },
];
</script>
