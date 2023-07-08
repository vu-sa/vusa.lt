<template>
  <IndexPageLayout
    title="Rezervacijos"
    model-name="reservations"
    :icon="Icons.RESERVATION"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="reservations"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { type DataTableColumns, type DataTableSortState, NTag } from "naive-ui";

import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { computed, provide, ref } from "vue";
import { formatRelativeTime, formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

defineProps<{
  reservations: PaginatedModels<App.Entities.Reservation>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: false,
  destroy: false,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  name: false,
  start_time: false,
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
              <strong>Aprašymas</strong>
              <p>{row.description}</p>
            </div>
            <div>
              <strong>Rezervuoti ištekliai</strong>
              <ul class="list-inside list-disc">
                {/* add quantity and padalinys.shortname */}
                {row.resources?.map((resource) => (
                  <li>
                    {resource.name}{" "}
                    <NTag size="tiny" round>
                      <span class="text-xs text-gray-500">
                        {resource.padalinys?.shortname}
                      </span>
                    </NTag>
                  </li>
                ))}
              </ul>
            </div>
          </section>
        );
      },
    },
    {
      title: "Pavadinimas",
      key: "name",
      sorter: true,
      sortOrder: sorters.value.name,
      maxWidth: 300,
      ellipsis: {
        tooltip: true,
      },
    },
    {
      title: "Rezervacijos kūrėjai",
      key: "users",
      render(row) {
        return row.users && row.users?.length > 0 ? (
          <UsersAvatarGroup class="align-middle" size={30} users={row.users} />
        ) : (
          "Nėra"
        );
      },
    },
    {
      title: "Rezervacijos pradžia",
      key: "start_time",
      sorter: true,
      sortOrder: sorters.value.start_time,
      ellipsis: {
        tooltip: true,
      },
      render(row) {
        return formatStaticTime(
          new Date(row.start_time),
          RESERVATION_DATE_TIME_FORMAT
        );
      },
    },
    {
      title: "Rezervacijos pabaiga",
      key: "end_time",
      sorter: true,
      sortOrder: sorters.value.end_time,
      ellipsis: {
        tooltip: true,
      },
      render(row) {
        return formatStaticTime(
          new Date(row.end_time),
          RESERVATION_DATE_TIME_FORMAT
        );
      },
    },
    {
      title: "Sukurta",
      key: "created_at",
      render(row) {
        return formatRelativeTime(new Date(row.created_at));
      },
    },
  ];
});
</script>
