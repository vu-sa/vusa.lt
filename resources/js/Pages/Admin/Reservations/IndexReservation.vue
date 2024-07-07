<template>
  <IndexPageLayout :title="capitalize($tChoice('entities.reservation.model', 2))" model-name="reservations"
    :icon="Icons.RESERVATION" :can-use-routes="canUseRoutes" :columns="columns" :paginated-models="reservations">
    <NCard class="mb-4">
      <template #header>
        {{ $t("Reservations with unit resources") }}
      </template>
      <NDataTable :columns="columnsWithActions" :data="activeReservations" size="small" :row-key="(row) => row.id" />
    </NCard>
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  type DataTableSortState,
  NButton,
  NIcon,
  NTag,
} from "naive-ui";
import { Link, usePage } from "@inertiajs/vue3";
import { capitalize, computed, provide, ref } from "vue";

import ArrowForward20Filled from "~icons/fluent/arrow-forward20-filled";

import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { formatRelativeTime, formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

defineProps<{
  reservations: PaginatedModels<App.Entities.Reservation>;
  activeReservations: Array<App.Entities.Reservation>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: false,
  destroy: false,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  name: false,
  start_time: "descend",
  end_time: false,
});

provide("sorters", sorters);

// add columns
const columns = computed<DataTableColumns<App.Entities.Reservation>>(() => {
  return [
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
              <ul class="list-disc">
                {/* add quantity and padalinys.shortname */}
                {row.resources?.map((resource) => (
                  <li>
                    <div class="inline-flex items-center gap-2">
                      <Link href={route("resources.edit", resource.id)}>
                        {resource.name}
                      </Link>
                      <NTag size="tiny" round>
                        <span class="ml-1 text-xs text-gray-500">
                          {$t(resource.padalinys?.shortname)}
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
      sorter: true,
      sortOrder: sorters.value.name,
      maxWidth: 300,
      ellipsis: {
        tooltip: true,
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
      sorter: true,
      sortOrder: sorters.value.start_time,
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
      sorter: true,
      sortOrder: sorters.value.end_time,
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
            <NButton quaternary size="small">
              <NIcon>
                <ArrowForward20Filled />
              </NIcon>
            </NButton>
          </Link>
        );
      },
    },
  ];
});
</script>
