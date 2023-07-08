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
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { computed, provide, ref } from "vue";
import { formatRelativeTime, formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

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
const columns = computed<DataTableColumns<App.Entities.Reservation>>(() => [
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
]);
</script>
