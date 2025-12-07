<template>
  <NDataTable :columns="columnsWithActions" :data="activeReservations" size="small" :row-key="(row) => row.id" />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  NTag,
} from "naive-ui";
import { Link, usePage } from "@inertiajs/vue3";
import { capitalize, computed } from "vue";

import ArrowForward20Filled from "~icons/fluent/arrow-forward20-filled";

import { Button } from "@/Components/ui/button";
import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { formatRelativeTime, formatStaticTime } from "@/Utils/IntlTime";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

const { showIfCompleted } = defineProps<{
  activeReservations: App.Entities.Reservation[];
  showIfCompleted?: boolean;
}>();

// Reuses from IndexReservation, need to use for implementation in dashboard
const columns = computed<DataTableColumns<App.Entities.Reservation>>(() => {
  let columns = [
    {
      type: "expand",
      renderExpand(row) {
        return (
          <section class="flex flex-col gap-2 p-2">
            <div>
              <strong>{$t("forms.fields.description")}</strong>
              <p>{row.description}</p>
            </div>
            <div>
              <strong>
                {capitalize($t("entities.reservation.resources"))}
              </strong>
              <ul class="list-inside list-disc">
                {/* add quantity and tenant.shortname */}
                {row.resources?.map((resource) => (
                  <li>
                    <div class="inline-flex items-center gap-2">
                      <Link href={route("resources.edit", resource.id)}>
                        {resource.name}
                      </Link>
                      <NTag size="tiny" round>
                        <span class="ml-1 text-xs text-gray-500">
                          {$t(resource.tenant?.shortname)}
                        </span>
                      </NTag>
                    </div>
                  </li>
                ))}
              </ul>
            </div>
          </section>
        );
      },
    },
    {
      title() {
        return $t("forms.fields.title");
      },
      key: "name",
      sorter: 'default',
      maxWidth: 300,
      ellipsis: {
        tooltip: true,
      },
      render(row) {
        return (
          <Link href={route("reservations.show", row.id)}>
            {row.name}
          </Link>
        );
      },
    },
    {
      title() {
        return capitalize($tChoice("entities.reservation.managers", 2));
      },
      key: "managers",
      render(row) {
        return row.users && row.users?.length > 0 ? (
          <UsersAvatarGroup class="align-middle" size={30} users={row.users} />
        ) : (
          "Nėra"
        );
      },
    },
    {
      title() {
        return capitalize($tChoice("entities.reservation.start_time", 2));
      },
      key: "start_time",
      sorter: (a, b) =>
        new Date(a.start_time).getTime() - new Date(b.start_time).getTime(),
      defaultSortOrder: "descend",
      ellipsis: {
        tooltip: true,
      },
      render(row) {
        return formatStaticTime(
          new Date(row.start_time),
          RESERVATION_DATE_TIME_FORMAT,
          usePage().props.app.locale,
        );
      },
    },
    {
      title() {
        return capitalize($tChoice("entities.reservation.end_time", 2));
      },
      key: "end_time",
      sorter: (a, b) =>
        new Date(a.end_time).getTime() - new Date(b.end_time).getTime(),
      ellipsis: {
        tooltip: true,
      },
      render(row) {
        return formatStaticTime(
          new Date(row.end_time),
          RESERVATION_DATE_TIME_FORMAT,
          usePage().props.app.locale,
        );
      },
    },
    {
      title() {
        return $t("forms.fields.created_at");
      },
      key: "created_at",
      render(row) {
        return formatRelativeTime(
          new Date(row.created_at),
          {
            numeric: "auto",
          },
          usePage().props.app.locale,
        );
      },
    },
  ];

  if (showIfCompleted) {
    columns.push({
      title: "Ar užbaigta",
      key: "isCompleted",
      filter(value, row) {
        return row.isCompleted === value;
      },
      filterOptions: [
        { label: "Taip", value: true },
        { label: "Ne", value: false },
      ],
      defaultFilterOptionValues: [false],
      render(row) {
        return row.isCompleted ? "✅ Taip" : "❌ Ne";
      },
    });
  }

  return columns;
});

const columnsWithActions = computed(() => {
  return [
    ...columns.value,
    {
      title() {
        return $t("Veiksmai");
      },
      key: "actions",
      width: 100,
      render(row) {
        return (
          <Link href={route("reservations.show", row.id)} >
            <Button variant="ghost" size="icon-sm">
              <ArrowForward20Filled />
            </Button>
          </Link>
        );
      },
    },
  ];
});
</script>
