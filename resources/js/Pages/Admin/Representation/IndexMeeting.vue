<template>
  <IndexPageLayout
    title="Institucijų posėdžiai"
    model-name="meetings"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="meetings"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import { formatStaticTime } from "@/Utils/IntlTime";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineProps<{
  meetings: PaginatedModels<App.Entities.Meeting[]>;
}>();

const canUseRoutes = {
  create: false,
  show: true,
  edit: false,
  destroy: true,
};

const columns: DataTableColumns<App.Entities.Meeting> = [
  {
    title: "Institucijos",
    key: "institutions",
    minWidth: 200,
    render(row) {
      return row.institutions.length === 0
        ? "Neturi institucijos"
        : row.institutions?.map((institution) => institution.name).join(", ");
    },
  },
  {
    title: "Pradžios laikas",
    key: "start_time",
    minWidth: 200,
    render(row) {
      return formatStaticTime(row.start_time * 1000, {
        year: "numeric",
        month: "long",
        day: "2-digit",
      });
    },
  },
];
</script>
